<?php

namespace App\Http\Controllers\Api;

use App\Enums\ReportStatus;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\BotSession;
use App\Models\Report;
use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class TelegramWebhookController extends Controller
{
    private int $sessionTimeoutMinutes = 30;

    protected TelegramService $telegramService;

    public function __construct(TelegramService $telegramService)
    {
        $this->telegramService = $telegramService;
        $this->sessionTimeoutMinutes = max(1, (int) config('services.telegram.session_timeout_minutes', 30));
    }

    public function handle(Request $request): JsonResponse
    {
        if (! $this->isAuthorizedWebhook($request)) {
            Log::warning('Unauthorized Telegram webhook request blocked.');

            return response()->json(['status' => 'unauthorized'], 401);
        }

        $update = $request->all();
        $messageData = $update['message'] ?? $update['callback_query']['message'] ?? null;

        if (! $messageData || ! isset($messageData['chat']['id'])) {
            return response()->json(['status' => 'ignored']);
        }

        $chatId = (string) $messageData['chat']['id'];
        $senderUserId = isset($messageData['from']['id']) ? (string) $messageData['from']['id'] : null;
        $text = trim((string) ($messageData['text'] ?? ''));
        $contact = $messageData['contact'] ?? null;
        $location = $messageData['location'] ?? null;

        $type = 'text';
        if ($contact !== null) {
            $type = 'contact';
        } elseif ($location !== null) {
            $type = 'location';
        } elseif (! empty($messageData['photo'])) {
            $type = 'image';
        }

        Log::info('Telegram webhook received.', [
            'chat_id_suffix' => substr($chatId, -4),
            'type' => $type,
        ]);

        $lockSeconds = max(10, (int) config('services.telegram.webhook_lock_seconds', 30));
        $waitSeconds = max(1, min($lockSeconds - 1, (int) config('services.telegram.webhook_wait_seconds', 20)));
        $lock = Cache::lock('bot_session_'.$chatId, $lockSeconds);

        try {
            return $lock->block($waitSeconds, function () use ($chatId, $senderUserId, $text, $type, $contact, $location): JsonResponse {
                $session = BotSession::firstOrCreate(
                    ['telegram_chat_id' => $chatId],
                    ['state' => 'IDLE', 'last_interaction_at' => now()]
                );

                if ($session->phone_number === null && $type !== 'contact') {
                    $this->telegramService->requestContact(
                        $chatId,
                        "Selamat datang di *BELACALL*.\nUntuk melanjutkan, silakan bagikan nomor HP Anda dengan menekan tombol di bawah."
                    );

                    return response()->json(['status' => 'awaiting_contact']);
                }

                if ($type === 'contact') {
                    return $this->handleContactSharing($session, $chatId, $senderUserId, $contact);
                }

                $messageLower = strtolower($text);

                if (
                    $this->isSessionExpired($session)
                    && ! in_array($messageLower, ['lapor', 'batal', '/start', '/darurat', 'darurat'], true)
                ) {
                    $this->resetToIdle($session);
                    $session->update(['last_interaction_at' => now()]);

                    $this->sendBotText(
                        $chatId,
                        "Sesi laporan sebelumnya sudah berakhir. Batas sesi *{$this->sessionTimeoutMinutes} menit*. Ketik *LAPOR* untuk memulai laporan baru."
                    );

                    return response()->json(['status' => 'session_expired']);
                }

                $session->update(['last_interaction_at' => now()]);

                if (in_array($messageLower, ['/darurat', 'darurat'], true)) {
                    $this->handleEmergencyShortcuts($chatId);

                    return response()->json(['status' => 'processed', 'detail' => 'emergency_shortcuts']);
                }

                if ($messageLower === 'lapor' || $messageLower === '/start') {
                    $this->resetToIdle($session);
                } elseif ($messageLower === 'batal') {
                    $this->resetToIdle($session);
                    $this->sendBotText($chatId, 'Laporan dibatalkan. Ketik *LAPOR* untuk memulai kembali.');

                    return response()->json(['status' => 'cancelled']);
                }

                if ($type === 'image') {
                    $this->handleUnsupportedImage($session, $chatId);

                    return response()->json(['status' => 'processed', 'detail' => 'image_not_supported']);
                }

                $locationData = null;
                if ($type === 'location' && $location !== null) {
                    $locationData = $location['latitude'].','.$location['longitude'];
                }

                switch ($session->state) {
                    case 'IDLE':
                        $this->handleIdle($session, $text, $chatId, $type, $locationData);
                        break;

                    case 'WAITING_TITLE':
                        $this->handleWaitingTitle($session, $text, $chatId);
                        break;

                    case 'WAITING_LOCATION_OPTION':
                        $locData = ($type === 'location') ? $locationData : $text;
                        $this->handleWaitingLocationOption($session, $text, $type, $locData, $chatId);
                        break;

                    case 'WAITING_LOCATION_COORDINATES':
                    case 'WAITING_LOCATION':
                        $locData = ($type === 'location') ? $locationData : $text;
                        $this->handleWaitingLocationCoordinates($session, $type, $locData, $chatId);
                        break;

                    case 'WAITING_DESCRIPTION':
                        $this->handleWaitingDescription($session, $text, $chatId);
                        break;

                    default:
                        $this->resetToIdle($session);
                        $this->sendBotText($chatId, 'Maaf, saya bingung. Silakan ketik *LAPOR* untuk memulai.');
                }

                return response()->json(['status' => 'processed', 'detail' => 'telegram']);
            });
        } catch (LockTimeoutException) {
            Log::warning('Telegram webhook busy lock timeout.', [
                'chat_id_suffix' => substr($chatId, -4),
            ]);

            return response()->json(['status' => 'busy'], 429);
        }
    }

    private function handleContactSharing(BotSession $session, string $chatId, ?string $senderUserId, ?array $contact): JsonResponse
    {
        if ($contact === null || empty($contact['phone_number'])) {
            $this->telegramService->requestContact(
                $chatId,
                'Kontak tidak valid. Silakan bagikan nomor HP menggunakan tombol di bawah.'
            );

            return response()->json(['status' => 'invalid_contact']);
        }

        $contactUserId = isset($contact['user_id']) ? (string) $contact['user_id'] : null;

        if (! $contactUserId || ! $senderUserId || ! hash_equals($senderUserId, $contactUserId)) {
            Log::warning('Rejected Telegram contact sharing due to ownership mismatch.', [
                'chat_id_suffix' => substr($chatId, -4),
                'sender_user_id' => $senderUserId,
                'contact_user_id' => $contactUserId,
            ]);

            $this->telegramService->requestContact(
                $chatId,
                'Silakan bagikan kontak Anda sendiri melalui tombol yang tersedia.'
            );

            return response()->json(['status' => 'invalid_contact_owner']);
        }

        $phone = User::normalizePhoneNumber($contact['phone_number']);
        if (! $phone || ! preg_match('/^62[0-9]{8,13}$/', $phone)) {
            $this->telegramService->requestContact(
                $chatId,
                'Nomor HP tidak valid. Silakan bagikan nomor HP Indonesia menggunakan tombol di bawah.'
            );

            return response()->json(['status' => 'invalid_phone']);
        }

        $session->update([
            'phone_number' => $phone,
            'last_interaction_at' => now(),
        ]);

        $user = User::firstOrCreate(
            ['phone' => $phone],
            ['name' => 'Warga '.substr($phone, -4), 'role' => Role::WARGA->value]
        );

        if ($user->telegram_chat_id !== $chatId) {
            $user->update(['telegram_chat_id' => $chatId]);
        }

        $this->telegramService->sendTextRemoveKeyboard(
            $chatId,
            "Terima kasih! Nomor HP Anda berhasil terdaftar.\n\nSelamat datang di *BELACALL*.\nKetik *LAPOR* untuk membuat laporan baru.\nKetik *BATAL* kapan saja untuk membatalkan."
        );

        return response()->json(['status' => 'contact_registered']);
    }

    private function resetToIdle(BotSession $session): void
    {
        $session->update([
            'state' => 'IDLE',
            'temp_data' => null,
        ]);
    }

    private function handleIdle(BotSession $session, string $text, string $chatId, string $type = 'text', mixed $location = null): void
    {
        if (in_array(strtolower($text), ['lapor', '/start'], true)) {
            if (! $this->sendBotText($chatId, "Halo, saya Asisten BELACALL.\nSilakan kirim *judul singkat* laporan Anda.\nContoh: *Jalan berlubang di depan SD*")) {
                return;
            }

            $session->update([
                'state' => 'WAITING_TITLE',
                'temp_data' => [
                    'session_started_at' => now()->toIso8601String(),
                ],
            ]);

            return;
        }

        $this->sendBotText($chatId, "Selamat datang di BELACALL.\nKetik *LAPOR* untuk membuat laporan baru.\nKetik *BATAL* kapan saja untuk membatalkan.");
    }

    private function handleWaitingTitle(BotSession $session, string $text, string $chatId): void
    {
        if (empty($text)) {
            $this->sendBotText($chatId, 'Mohon masukkan judul laporan.');

            return;
        }

        if (mb_strlen($text) > 255) {
            $this->sendBotText($chatId, 'Judul terlalu panjang. Maksimal 255 karakter.');

            return;
        }

        $existingTempData = $session->temp_data ?? [];
        $sessionStartedAt = $existingTempData['session_started_at'] ?? now()->toIso8601String();

        if (! $this->sendBotText(
            $chatId,
            "Apakah Anda ingin menambahkan titik lokasi?\n"
            ."1. *Ya* (kirim share location atau format lat,long)\n"
            ."2. *Tidak* (lanjut tanpa lokasi)\n\n"
            .'Balas *1* atau *2*.'
        )) {
            return;
        }

        $session->update([
            'state' => 'WAITING_LOCATION_OPTION',
            'temp_data' => [
                'session_started_at' => $sessionStartedAt,
                'title' => $text,
            ],
        ]);
    }

    private function handleWaitingLocationOption(
        BotSession $session,
        string $text,
        string $type,
        mixed $locationData,
        string $chatId
    ): void {
        $choice = strtolower(trim($text));
        $tempData = $session->temp_data ?? [];
        $wantsLocation = in_array($choice, ['1', 'ya', 'iya', 'y', 'yes'], true);
        $skipLocation = in_array($choice, ['2', 'tidak', 'ga', 'gak', 'enggak', 'skip', 'lewati'], true);

        if ($skipLocation) {
            unset($tempData['latitude'], $tempData['longitude']);

            if (! $this->sendBotText(
                $chatId,
                'Baik, laporan dilanjutkan tanpa lokasi. Sekarang jelaskan detail kejadian (minimal 10 karakter).'
            )) {
                return;
            }

            $session->update([
                'state' => 'WAITING_DESCRIPTION',
                'temp_data' => $tempData,
            ]);

            return;
        }

        if ($wantsLocation) {
            if (! $this->sendBotText(
                $chatId,
                "Silakan kirim *share location* atau format *lat,long*.\n"
                .'Contoh: *-6.200000, 106.816666*'
            )) {
                return;
            }

            $session->update([
                'state' => 'WAITING_LOCATION_COORDINATES',
                'temp_data' => $tempData,
            ]);

            return;
        }

        if ($type === 'location' || $this->parseCoordinates((string) $locationData) !== null) {
            $this->handleWaitingLocationCoordinates($session, $type, $locationData, $chatId);

            return;
        }

        $this->sendBotText(
            $chatId,
            "Mohon balas *1* atau *2*.\n"
            ."1. Ya, tambahkan lokasi\n"
            .'2. Tidak, lanjut tanpa lokasi'
        );
    }

    private function handleWaitingLocationCoordinates(BotSession $session, string $type, mixed $locationData, string $chatId): void
    {
        $tempData = $session->temp_data ?? [];
        $rawLocation = trim((string) $locationData);
        $coordinates = null;

        if ($type === 'location') {
            $coordinates = $this->parseCoordinates($rawLocation);
        } elseif ($rawLocation !== '') {
            $coordinates = $this->parseCoordinates($rawLocation);
        }

        if ($coordinates) {
            $tempData['latitude'] = number_format($coordinates['latitude'], 6, '.', '');
            $tempData['longitude'] = number_format($coordinates['longitude'], 6, '.', '');
            $message = "Lokasi berhasil dicatat.\nSekarang jelaskan detail kejadian (minimal 10 karakter).";
        } else {
            unset($tempData['latitude'], $tempData['longitude']);
            $message = 'Lokasi tidak terbaca, laporan tetap dilanjutkan tanpa lokasi. Sekarang kirim detail kejadian (minimal 10 karakter).';
        }

        if (! $this->sendBotText($chatId, $message)) {
            return;
        }

        $session->update([
            'state' => 'WAITING_DESCRIPTION',
            'temp_data' => $tempData,
        ]);
    }

    private function handleWaitingDescription(BotSession $session, string $text, string $chatId): void
    {
        if (empty($text)) {
            $this->sendBotText($chatId, 'Mohon masukkan deskripsi laporan.');

            return;
        }

        if (mb_strlen($text) < 10) {
            $this->sendBotText($chatId, 'Deskripsi terlalu singkat. Mohon jelaskan minimal 10 karakter.');

            return;
        }

        $tempData = $session->temp_data;
        $title = $tempData['title'] ?? 'Laporan Warga';
        $latitude = isset($tempData['latitude']) ? (string) $tempData['latitude'] : null;
        $longitude = isset($tempData['longitude']) ? (string) $tempData['longitude'] : null;
        $locationName = null;

        if ($latitude !== null && $longitude !== null) {
            $locationName = "Koordinat: {$latitude}, {$longitude}";
        }

        $phone = $session->phone_number;

        DB::transaction(function () use ($phone, $session, $title, $text, $latitude, $longitude, $locationName, $chatId): void {
            $user = User::firstOrCreate(
                ['phone' => $phone],
                ['name' => 'Warga '.substr($phone, -4), 'role' => Role::WARGA->value]
            );

            if ($user->telegram_chat_id !== $session->telegram_chat_id) {
                $user->update(['telegram_chat_id' => $session->telegram_chat_id]);
            }

            $report = Report::create([
                'ticket_number' => Report::generateTicketNumber(),
                'user_id' => $user->id,
                'title' => $title,
                'description' => $text,
                'location_name' => $locationName,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'status' => ReportStatus::SUBMITTED->value,
                // Priority will be set by pimpinan when assigning operator
            ]);

            $this->resetToIdle($session);

            $trackingUrl = route('report.tracking.view', ['ticket' => $report->ticket_number]);

            $reply = "✅ *Laporan berhasil dibuat*\n\n";
            $reply .= "Tiket: *{$report->ticket_number}*\n";
            $reply .= "Status: *Menunggu Verifikasi*\n";
            $reply .= "Cek status: {$trackingUrl}\n\n";
            $reply .= 'Simpan nomor tiket Anda. Jika perlu bantuan lagi, ketik *LAPOR*.';

            $this->sendBotText($chatId, $reply);
        });
    }

    private function handleEmergencyShortcuts(string $chatId): void
    {
        $shortcuts = \App\Models\EmergencyShortcut::query()->active()->get();

        if ($shortcuts->isEmpty()) {
            $this->sendBotText($chatId, 'Belum ada kontak darurat yang tersedia. Hubungi admin untuk informasi lebih lanjut.');

            return;
        }

        $message = "🚨 *Kontak Darurat*\n\n";

        foreach ($shortcuts as $shortcut) {
            $message .= "📞 *{$shortcut->name}* — `{$shortcut->phone_number}`\n";
            if ($shortcut->description) {
                $message .= "   _{$shortcut->description}_\n";
            }
            $message .= "\n";
        }

        $message .= "Hubungi nomor di atas untuk bantuan darurat.\nKetik *LAPOR* untuk membuat laporan.";

        $this->sendBotText($chatId, $message);
    }

    private function isSessionExpired(BotSession $session): bool
    {
        if ($session->state === 'IDLE' || ! $session->last_interaction_at) {
            return false;
        }

        $now = now();
        $isInactivityExpired = $session->last_interaction_at->lt($now->copy()->subMinutes($this->sessionTimeoutMinutes));
        $sessionStartedAt = $this->getSessionStartedAt($session);
        $isHardExpired = $sessionStartedAt
            ? $sessionStartedAt->lt($now->copy()->subMinutes($this->sessionTimeoutMinutes))
            : false;

        return $isInactivityExpired || $isHardExpired;
    }

    private function getSessionStartedAt(BotSession $session): ?Carbon
    {
        $tempData = $session->temp_data ?? [];
        $rawTimestamp = $tempData['session_started_at'] ?? null;

        if (! is_string($rawTimestamp) || $rawTimestamp === '') {
            return null;
        }

        try {
            return Carbon::parse($rawTimestamp);
        } catch (Throwable) {
            return null;
        }
    }

    private function handleUnsupportedImage(BotSession $session, string $chatId): void
    {
        $message = match ($session->state) {
            'WAITING_TITLE' => 'Saat ini foto belum didukung. Mohon kirim judul laporan dalam teks.',
            'WAITING_LOCATION_OPTION' => 'Saat ini foto belum didukung. Mohon balas 1 (dengan lokasi) atau 2 (tanpa lokasi).',
            'WAITING_LOCATION_COORDINATES', 'WAITING_LOCATION' => 'Saat ini foto belum didukung. Mohon kirim share location atau format lat,long.',
            'WAITING_DESCRIPTION' => 'Saat ini foto belum didukung. Mohon kirim deskripsi laporan dalam teks.',
            default => 'Saat ini foto belum didukung. Ketik *LAPOR* untuk membuat laporan via teks.',
        };

        $this->sendBotText($chatId, $message);
    }

    private function sendBotText(string $chatId, string $message): bool
    {
        $response = $this->telegramService->sendText($chatId, $message);
        if ($response === false) {
            Log::warning('Failed to send bot message via Telegram.', [
                'chat_id_suffix' => substr($chatId, -4),
            ]);

            return false;
        }

        if (is_array($response) && array_key_exists('ok', $response) && $response['ok'] === false) {
            Log::warning('Telegram returned unsuccessful status for bot message.', [
                'chat_id_suffix' => substr($chatId, -4),
                'response' => $response,
            ]);

            return false;
        }

        return true;
    }

    private function isAuthorizedWebhook(Request $request): bool
    {
        $configuredSecret = (string) config('services.telegram.webhook_secret');

        // If no secret is configured, only allow in testing environment
        // For production and staging, webhook secret is mandatory
        if ($configuredSecret === '') {
            if (app()->environment('testing')) {
                return true;
            }

            // Log warning for development/local without configured secret
            if (app()->environment('local', 'development')) {
                Log::warning('Telegram webhook secret not configured. Webhook access denied for security.', [
                    'environment' => app()->environment(),
                ]);
            }

            return false;
        }

        $providedToken = (string) $request->header('X-Telegram-Bot-Api-Secret-Token', '');

        return hash_equals($configuredSecret, trim($providedToken));
    }

    /**
     * @return array{latitude: float, longitude: float}|null
     */
    private function parseCoordinates(string $rawCoordinates): ?array
    {
        $parts = explode(',', $rawCoordinates);

        if (count($parts) < 2) {
            return null;
        }

        $latitude = trim($parts[0]);
        $longitude = trim($parts[1]);

        if (! is_numeric($latitude) || ! is_numeric($longitude)) {
            return null;
        }

        $latitudeFloat = (float) $latitude;
        $longitudeFloat = (float) $longitude;

        if ($latitudeFloat < -90 || $latitudeFloat > 90) {
            return null;
        }

        if ($longitudeFloat < -180 || $longitudeFloat > 180) {
            return null;
        }

        return [
            'latitude' => $latitudeFloat,
            'longitude' => $longitudeFloat,
        ];
    }
}
