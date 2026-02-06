<?php

namespace App\Http\Controllers\Api;

use App\Enums\ReportPriority;
use App\Enums\ReportStatus;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\BotSession;
use App\Models\Report;
use App\Models\User;
use App\Services\FonnteService;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class FonnteWebhookController extends Controller
{
    private int $sessionTimeoutMinutes = 30;

    protected FonnteService $fonnteService;

    public function __construct(FonnteService $fonnteService)
    {
        $this->fonnteService = $fonnteService;
        $this->sessionTimeoutMinutes = max(1, (int) config('services.fonnte.session_timeout_minutes', 30));
    }

    public function handle(Request $request): JsonResponse
    {
        if (! $this->isAuthorizedWebhook($request)) {
            Log::warning('Unauthorized Fonnte webhook request blocked.');

            return response()->json(['status' => 'unauthorized'], 401);
        }

        $sender = $this->normalizeSenderPhone($request->input('sender'));
        if ($sender === null) {
            return response()->json(['status' => 'invalid_sender'], 422);
        }

        $message = trim((string) $request->input('message', ''));
        $file = $request->input('file');
        $location = $request->input('location');

        $type = 'text';
        if (! empty($file)) {
            $type = 'image';
        } elseif (! empty($location)) {
            $type = 'location';
        }

        Log::info('Fonnte webhook received.', [
            'sender_suffix' => substr($sender, -4),
            'type' => $type,
        ]);

        $lockSeconds = max(10, (int) config('services.fonnte.webhook_lock_seconds', 30));
        $waitSeconds = max(1, min($lockSeconds - 1, (int) config('services.fonnte.webhook_wait_seconds', 20)));
        $lock = Cache::lock('bot_session_'.$sender, $lockSeconds);

        try {
            return $lock->block($waitSeconds, function () use ($sender, $message, $type, $location): JsonResponse {
                $session = BotSession::firstOrCreate(
                    ['phone_number' => $sender],
                    ['state' => 'IDLE', 'last_interaction_at' => now()]
                );
                $messageLower = strtolower($message);

                if (
                    $this->isSessionExpired($session)
                    && ! in_array($messageLower, ['lapor', 'batal'], true)
                ) {
                    $this->resetToIdle($session);
                    $session->update(['last_interaction_at' => now()]);

                    $this->sendBotText(
                        $sender,
                        "Sesi laporan sebelumnya sudah berakhir. Batas sesi *{$this->sessionTimeoutMinutes} menit*. Ketik *LAPOR* untuk memulai laporan baru."
                    );

                    return response()->json(['status' => 'session_expired']);
                }

                $session->update(['last_interaction_at' => now()]);

                if ($messageLower === 'lapor') {
                    $this->resetToIdle($session);
                } elseif ($messageLower === 'batal') {
                    $this->resetToIdle($session);
                    $this->sendBotText($sender, 'Laporan dibatalkan. Ketik *LAPOR* untuk memulai kembali.');

                    return response()->json(['status' => 'cancelled']);
                }

                if ($type === 'image') {
                    $this->handleUnsupportedImage($session, $sender);

                    return response()->json(['status' => 'processed', 'detail' => 'image_not_supported']);
                }

                switch ($session->state) {
                    case 'IDLE':
                        $this->handleIdle($session, $message, $sender, $type, $location);
                        break;

                    case 'WAITING_TITLE':
                        $this->handleWaitingTitle($session, $message, $sender);
                        break;

                    case 'WAITING_LOCATION_OPTION':
                        $locationData = ($type === 'location') ? $location : $message;
                        $this->handleWaitingLocationOption($session, $message, $type, $locationData, $sender);
                        break;

                    case 'WAITING_LOCATION_COORDINATES':
                    case 'WAITING_LOCATION':
                        $locationData = ($type === 'location') ? $location : $message;
                        $this->handleWaitingLocationCoordinates($session, $type, $locationData, $sender);
                        break;

                    case 'WAITING_DESCRIPTION':
                        $this->handleWaitingDescription($session, $message, $sender);
                        break;

                    default:
                        $this->resetToIdle($session);
                        $this->sendBotText($sender, 'Maaf, saya bingung. Silakan ketik *LAPOR* untuk memulai.');
                }

                return response()->json(['status' => 'processed', 'detail' => 'fonnte']);
            });
        } catch (LockTimeoutException) {
            Log::warning('Fonnte webhook busy lock timeout.', [
                'sender_suffix' => substr($sender, -4),
            ]);

            return response()->json(['status' => 'busy'], 429);
        }
    }

    private function resetToIdle(BotSession $session): void
    {
        $session->update([
            'state' => 'IDLE',
            'temp_data' => null,
        ]);
    }

    private function handleIdle(BotSession $session, string $text, string $sender, string $type = 'text', mixed $location = null): void
    {
        if (strtolower($text) === 'lapor') {
            if (! $this->sendBotText($sender, "Halo, saya Asisten BELACALL.\nSilakan kirim *judul singkat* laporan Anda.\nContoh: *Jalan berlubang di depan SD*")) {
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

        $this->sendBotText($sender, "Selamat datang di BELACALL.\nKetik *LAPOR* untuk membuat laporan baru.\nKetik *BATAL* kapan saja untuk membatalkan.");
    }

    private function handleWaitingTitle(BotSession $session, string $text, string $sender): void
    {
        if (empty($text)) {
            $this->sendBotText($sender, 'Mohon masukkan judul laporan.');

            return;
        }

        if (mb_strlen($text) > 255) {
            $this->sendBotText($sender, 'Judul terlalu panjang. Maksimal 255 karakter.');

            return;
        }

        $existingTempData = $session->temp_data ?? [];
        $sessionStartedAt = $existingTempData['session_started_at'] ?? now()->toIso8601String();

        if (! $this->sendBotText(
            $sender,
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
        string $sender
    ): void {
        $choice = strtolower(trim($text));
        $tempData = $session->temp_data ?? [];
        $wantsLocation = in_array($choice, ['1', 'ya', 'iya', 'y', 'yes'], true);
        $skipLocation = in_array($choice, ['2', 'tidak', 'ga', 'gak', 'enggak', 'skip', 'lewati'], true);

        if ($skipLocation) {
            unset($tempData['latitude'], $tempData['longitude']);

            if (! $this->sendBotText(
                $sender,
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
                $sender,
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
            $this->handleWaitingLocationCoordinates($session, $type, $locationData, $sender);

            return;
        }

        $this->sendBotText(
            $sender,
            "Mohon balas *1* atau *2*.\n"
            ."1. Ya, tambahkan lokasi\n"
            .'2. Tidak, lanjut tanpa lokasi'
        );
    }

    private function handleWaitingLocationCoordinates(BotSession $session, string $type, mixed $locationData, string $sender): void
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

        if (! $this->sendBotText($sender, $message)) {
            return;
        }

        $session->update([
            'state' => 'WAITING_DESCRIPTION',
            'temp_data' => $tempData,
        ]);
    }

    private function handleWaitingDescription(BotSession $session, string $text, string $sender): void
    {
        if (empty($text)) {
            $this->sendBotText($sender, 'Mohon masukkan deskripsi laporan.');

            return;
        }

        if (mb_strlen($text) < 10) {
            $this->sendBotText($sender, 'Deskripsi terlalu singkat. Mohon jelaskan minimal 10 karakter.');

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

        $user = User::firstOrCreate(
            ['phone' => $sender],
            ['name' => 'Warga '.substr($sender, -4), 'role' => Role::WARGA->value]
        );

        $report = Report::create([
            'ticket_number' => Report::generateTicketNumber(),
            'user_id' => $user->id,
            'title' => $title,
            'description' => $text,
            'location_name' => $locationName,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'status' => ReportStatus::SUBMITTED->value,
            'priority' => ReportPriority::MEDIUM->value,
        ]);

        $this->resetToIdle($session);

        $trackingUrl = route('report.tracking.view', ['ticket' => $report->ticket_number]);

        $reply = "✅ *Laporan berhasil dibuat*\n\n";
        $reply .= "Tiket: *{$report->ticket_number}*\n";
        $reply .= "Status: *Menunggu Verifikasi*\n";
        $reply .= "Cek status: {$trackingUrl}\n\n";
        $reply .= 'Simpan nomor tiket Anda. Jika perlu bantuan lagi, ketik *LAPOR*.';

        $this->sendBotText($sender, $reply);
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

    private function handleUnsupportedImage(BotSession $session, string $sender): void
    {
        $message = match ($session->state) {
            'WAITING_TITLE' => 'Saat ini foto belum didukung di paket Fonnte gratis. Mohon kirim judul laporan dalam teks.',
            'WAITING_LOCATION_OPTION' => 'Saat ini foto belum didukung di paket Fonnte gratis. Mohon balas 1 (dengan lokasi) atau 2 (tanpa lokasi).',
            'WAITING_LOCATION_COORDINATES', 'WAITING_LOCATION' => 'Saat ini foto belum didukung di paket Fonnte gratis. Mohon kirim share location atau format lat,long.',
            'WAITING_DESCRIPTION' => 'Saat ini foto belum didukung di paket Fonnte gratis. Mohon kirim deskripsi laporan dalam teks.',
            default => 'Saat ini foto belum didukung di paket Fonnte gratis. Ketik *LAPOR* untuk membuat laporan via teks.',
        };

        $this->sendBotText($sender, $message);
    }

    private function sendBotText(string $sender, string $message): bool
    {
        $response = $this->fonnteService->sendText($sender, $message);
        if ($response === false) {
            Log::warning('Failed to send bot message via Fonnte.', [
                'sender_suffix' => substr($sender, -4),
            ]);

            return false;
        }

        if (is_array($response) && array_key_exists('status', $response) && $response['status'] === false) {
            Log::warning('Fonnte returned unsuccessful status for bot message.', [
                'sender_suffix' => substr($sender, -4),
                'response' => $response,
            ]);

            return false;
        }

        return true;
    }

    private function isAuthorizedWebhook(Request $request): bool
    {
        $configuredToken = (string) config('services.fonnte.webhook_token');

        if ($configuredToken === '') {
            return app()->environment('testing')
                || (bool) config('services.fonnte.allow_insecure_webhook', false);
        }

        $providedToken = (string) ($request->header('X-Fonnte-Token') ?? $request->header('Authorization', ''));

        if (str_starts_with(strtolower($providedToken), 'bearer ')) {
            $providedToken = substr($providedToken, 7);
        }

        return hash_equals($configuredToken, trim($providedToken));
    }

    private function normalizeSenderPhone(mixed $sender): ?string
    {
        if (! is_string($sender) && ! is_numeric($sender)) {
            return null;
        }

        $normalized = User::normalizePhoneNumber((string) $sender);
        if (! $normalized) {
            return null;
        }

        if (! preg_match('/^62[0-9]{8,13}$/', $normalized)) {
            return null;
        }

        return $normalized;
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
