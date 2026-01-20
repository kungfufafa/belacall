<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BotSession;
use App\Models\Report;
use App\Models\User;
use App\Services\GowaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\Cache;

class GowaWebhookController extends Controller
{
    protected $gowaService;

    public function __construct(GowaService $gowaService)
    {
        $this->gowaService = $gowaService;
    }

    /**
     * Handle incoming webhook from GOWA
     */
    public function handle(Request $request)
    {
        // 1. Validasi Webhook Secret (Security)
        // if ($request->header('X-Gowa-Signature') !== config('services.gowa.webhook_secret')) {
        //     return response()->json(['status' => 'unauthorized'], 401);
        // }

        $payload = $request->all();
        Log::info('GOWA Webhook:', $payload);

        // Pastikan ini event pesan masuk
        if (($payload['event'] ?? '') !== 'message') {
            return response()->json(['status' => 'ignored']);
        }

        // Ambil data penting
        $data = $payload['payload'] ?? [];
        $from = $data['from'] ?? '';
        $body = trim($data['body'] ?? '');
        $type = $data['type'] ?? 'text'; // text, image, location

        // Skip pesan dari status broadcast atau grup (jika tidak didukung)
        if (str_contains($from, 'status') || str_contains($from, 'g.us')) {
            return response()->json(['status' => 'ignored']);
        }

        // Hapus @s.whatsapp.net untuk identifikasi user di DB
        $phoneNumber = explode('@', $from)[0];

        // 2. Ambil/Buat Session dengan Lock (Concurrency Protection)
        // Gunakan Cache Lock selama 5 detik agar request dari user yang sama antri
        $lock = Cache::lock('bot_session_' . $phoneNumber, 5);

        if (!$lock->get()) {
            // Jika terkunci, abaikan request kedua (atau bisa return 429)
            Log::warning("Race condition detected for $phoneNumber. Ignoring duplicate request.");
            return response()->json(['status' => 'locked']);
        }

        try {
            $session = BotSession::firstOrCreate(
                ['phone_number' => $phoneNumber],
                ['state' => 'IDLE', 'last_interaction_at' => now()]
            );
            $session->touch(); // Update updated_at

            // 3. Routing Logika Bot berdasarkan STATE
            switch ($session->state) {
                case 'IDLE':
                    $this->handleIdleState($session, $body, $from);
                    break;
                
                case 'WAITING_REPORT_DESC': 
                    $this->handleIdleState($session, $body, $from); 
                    break;

                case 'WAITING_REPORT_PHOTO':
                    $this->handlePhotoState($session, $type, $data, $from);
                    break;

                case 'WAITING_REPORT_LOCATION':
                    $this->handleLocationState($session, $body, $from);
                    break;
                    
                default:
                    $session->update(['state' => 'IDLE', 'temp_data' => null]);
                    $this->gowaService->sendText($from, "Maaf, saya bingung. Silakan ketik *LAPOR* untuk memulai.");
            }
        } finally {
            $lock->release();
        }

        return response()->json(['status' => 'processed']);
    }

    private function handleIdleState($session, $text, $waId)
    {
        if (strtolower($text) === 'lapor') {
            $session->update([
                'state' => 'WAITING_REPORT_TITLE', 
                // Kita skip title, langsung minta deskripsi/foto biar cepat
                // Atau: State = WAITING_DESC
            ]);
            
            // Shortcut: Langsung minta apa keluhannya
            $session->update(['state' => 'WAITING_REPORT_DESC']);
            $this->gowaService->sendText($waId, "Halo! Apa yang ingin Anda laporkan? (Contoh: Jalan berlubang di Desa Sukamaju)");
            return;
        }
        
        if ($session->state === 'WAITING_REPORT_DESC') {
             // Simpan deskripsi awal
             $session->update([
                 'state' => 'WAITING_REPORT_PHOTO',
                 'temp_data' => ['description' => $text]
             ]);
             $this->gowaService->sendText($waId, "Baik. Tolong kirimkan FOTO buktinya ya.");
             return;
        }

        // Default response
        $this->gowaService->sendText($waId, "Selamat datang di BELACALL! Ketik *LAPOR* untuk membuat pengaduan baru.");
    }

    private function handlePhotoState($session, $type, $data, $waId)
    {
        if ($type !== 'image') {
            $this->gowaService->sendText($waId, "Mohon kirimkan GAMBAR/FOTO, bukan teks. Atau ketik *BATAL* untuk cancel.");
            return;
        }

        // Ambil URL gambar (GOWA biasanya kirim URL publik atau base64)
        // Asumsi GOWA kirim URL file yang sudah didownload
        $imageUrl = $data['url'] ?? null; 
        
        // Update Session
        $tempData = $session->temp_data;
        $tempData['photo_url'] = $imageUrl;
        
        $session->update([
            'state' => 'WAITING_REPORT_LOCATION',
            'temp_data' => $tempData
        ]);

        $this->gowaService->sendText($waId, "Foto diterima. Terakhir, tuliskan LOKASI kejadian (Nama Jalan/Dusun/RT).");
    }

    private function handleLocationState($session, $text, $waId)
    {
        // Finalisasi Laporan
        $tempData = $session->temp_data;
        $description = $tempData['description'] ?? 'Laporan Warga';
        $photoUrl = $tempData['photo_url'] ?? '';
        $locationName = $text;

        // Cari/Buat User Warga
        $user = User::firstOrCreate(
            ['phone' => $session->phone_number],
            ['name' => 'Warga ' . substr($session->phone_number, -4), 'role' => 'warga']
        );

        // Simpan ke DB
        $report = Report::create([
            'ticket_number' => 'T-' . now()->format('YmdHi') . rand(10,99),
            'user_id' => $user->id,
            'title' => substr($description, 0, 50) . '...',
            'description' => $description,
            'location_name' => $locationName,
            'status' => 'SUBMITTED', // Langsung masuk, menunggu verifikasi operator
            'category' => 'General'
        ]);

        // Simpan Bukti
        if ($photoUrl) {
            $report->evidences()->create([
                'file_path' => $photoUrl, // Nanti perlu logic download image ke local storage
                'file_type' => 'image'
            ]);
        }

        // Reset Session
        $session->update(['state' => 'IDLE', 'temp_data' => null]);

        // Notif Sukses
        $reply = "✅ Laporan Diterima!\n\n";
        $reply .= "Nomor Tiket: *{$report->ticket_number}*\n";
        $reply .= "Status: Menunggu Verifikasi\n\n";
        $reply .= "Kami akan mengabari Anda jika ada update.";
        
        $this->gowaService->sendText($waId, $reply);
    }
}
