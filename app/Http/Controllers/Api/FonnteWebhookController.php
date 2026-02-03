<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BotSession;
use App\Models\Report;
use App\Models\User;
use App\Services\FonnteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class FonnteWebhookController extends Controller
{
    protected $fonnteService;

    public function __construct(FonnteService $fonnteService)
    {
        $this->fonnteService = $fonnteService;
    }

    public function handle(Request $request)
    {
        if ($request->isMethod('get')) {
            return response('OK', 200);
        }

        Log::info('🔥 FONNTE HIT:', $request->all());

        $sender = $request->input('sender');
        $sender = preg_replace('/[^0-9]/', '', $sender);

        $message = trim($request->input('message', ''));
        $file = $request->input('file');
        $location = $request->input('location');

        $type = 'text';
        if (! empty($file)) {
            $type = 'image';
        } elseif (! empty($location)) {
            $type = 'location';
        }

        if (empty($sender)) {
            return response()->json(['status' => 'invalid_sender']);
        }

        $lock = Cache::lock('bot_session_'.$sender, 5);

        if (! $lock->get()) {
            Log::warning("Race condition detected for $sender. Ignoring duplicate request.");

            return response()->json(['status' => 'locked']);
        }

        try {
            $session = BotSession::firstOrCreate(
                ['phone_number' => $sender],
                ['state' => 'IDLE', 'last_interaction_at' => now()]
            );
            $session->touch();

            if (strtolower($message) === 'lapor') {
                $this->resetToIdle($session);
            } elseif (strtolower($message) === 'batal') {
                $this->resetToIdle($session);
                $this->fonnteService->sendText($sender, 'Laporan dibatalkan. Ketik *LAPOR* untuk memulai kembali.');

                return response()->json(['status' => 'cancelled']);
            }

            switch ($session->state) {
                case 'IDLE':
                    $this->handleIdle($session, $message, $sender, $type, $location);
                    break;

                case 'WAITING_TITLE':
                    $this->handleWaitingTitle($session, $message, $sender);
                    break;

                case 'WAITING_LOCATION':
                    $locationData = ($type === 'location') ? $location : $message;
                    $this->handleWaitingLocation($session, $type, $locationData, $sender);
                    break;

                case 'WAITING_DESCRIPTION':
                    $this->handleWaitingDescription($session, $message, $sender);
                    break;

                default:
                    $this->resetToIdle($session);
                    $this->fonnteService->sendText($sender, 'Maaf, saya bingung. Silakan ketik *LAPOR* untuk memulai.');
            }

        } finally {
            $lock->release();
        }

        return response()->json(['status' => 'processed', 'detail' => 'fonnte']);
    }

    private function resetToIdle($session)
    {
        $session->update([
            'state' => 'IDLE',
            'temp_data' => null,
        ]);
    }

    private function handleIdle($session, $text, $sender, $type = 'text', $location = null)
    {
        if (strtolower($text) === 'lapor') {
            $session->update(['state' => 'WAITING_TITLE']);
            $this->fonnteService->sendText($sender, 'Siap bantu. Masalah apa yang mau dilaporkan secara singkat? (Contoh: Jalan berlubang)');

            return;
        }

        if ($type === 'location') {
            $latestReport = Report::whereHas('user', function ($q) use ($sender) {
                $q->where('phone', $sender);
            })->latest()->first();

            if ($latestReport && $latestReport->created_at->diffInMinutes(now()) < 10) {
                $parts = explode(',', $location);
                if (count($parts) >= 2) {
                    $lat = trim($parts[0]);
                    $long = trim($parts[1]);
                    $latestReport->update([
                        'latitude' => $lat,
                        'longitude' => $long,
                        'location_name' => $latestReport->location_name." (GPS: $lat, $long)",
                    ]);
                    $this->fonnteService->sendText($sender, "📍 Lokasi GPS berhasil ditambahkan ke laporan #{$latestReport->ticket_number}.");

                    return;
                }
            }
        }

        $this->fonnteService->sendText($sender, 'Selamat datang di BELACALL! Ketik *LAPOR* untuk membuat laporan pengaduan.');
    }

    private function handleWaitingTitle($session, $text, $sender)
    {
        if (empty($text)) {
            $this->fonnteService->sendText($sender, 'Mohon masukkan judul laporan.');

            return;
        }

        $session->update([
            'state' => 'WAITING_LOCATION',
            'temp_data' => ['title' => $text],
        ]);

        $this->fonnteService->sendText($sender, 'Dimana posisinya? Sebutkan Desa/RT atau patokan.');
    }

    private function handleWaitingLocation($session, $type, $locationData, $sender)
    {
        $locationName = $locationData;

        $tempData = $session->temp_data ?? [];
        $tempData['location'] = $locationName;

        if ($type === 'location') {
            $tempData['is_gps'] = true;
        }

        $session->update([
            'state' => 'WAITING_DESCRIPTION',
            'temp_data' => $tempData,
        ]);

        $this->fonnteService->sendText($sender, 'Baik. Ceritakan lebih detail (kapan, seberapa parah, dll).');
    }

    private function handleWaitingDescription($session, $text, $sender)
    {
        if (empty($text)) {
            $this->fonnteService->sendText($sender, 'Mohon masukkan deskripsi laporan.');

            return;
        }

        $tempData = $session->temp_data;
        $title = $tempData['title'] ?? 'Laporan Warga';
        $location = $tempData['location'] ?? 'Tidak disebutkan';

        $user = User::firstOrCreate(
            ['phone' => $sender],
            ['name' => 'Warga '.substr($sender, -4), 'role' => 'warga']
        );

        $lat = null;
        $long = null;
        if (isset($tempData['is_gps']) && $tempData['is_gps']) {
            $parts = explode(',', $location);
            if (count($parts) >= 2) {
                $lat = trim($parts[0]);
                $long = trim($parts[1]);
                $location = "Koordinat: $lat, $long";
            }
        }

        $report = Report::create([
            'ticket_number' => 'T-'.now()->format('YmdHi').rand(10, 99),
            'user_id' => $user->id,
            'title' => $title,
            'description' => $text,
            'location_name' => $location,
            'latitude' => $lat,
            'longitude' => $long,
            'status' => 'SUBMITTED',
            'category' => 'General',
        ]);

        $this->resetToIdle($session);

        $reply = "✅ *Laporan Diterima!*\n\n";
        $reply .= "Tiket: *{$report->ticket_number}*\n";
        $reply .= "Status: *Menunggu Verifikasi*\n\n";
        $reply .= '_Jika ingin lokasi lebih akurat, Anda boleh Share Location sekarang. Jika tidak, abaikan saja._';

        $this->fonnteService->sendText($sender, $reply);
    }
}
