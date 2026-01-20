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

    /**
     * Handle incoming webhook from Fonnte
     */
    public function handle(Request $request)
    {
        // Handle Verification (GET Request)
        if ($request->isMethod('get')) {
            return response('OK', 200);
        }

        Log::info('🔥 FONNTE HIT:', $request->all()); // Tambah log dengan emoji biar kelihatan

        // 1. Parsing Data Fonnte (Flat Structure)
        // Format Fonnte: sender, message, file, location, name
        $sender = $request->input('sender'); // Nomor HP Pengirim
        
        // Bersihkan sender number (hanya angka)
        $sender = preg_replace('/[^0-9]/', '', $sender);

        $message = trim($request->input('message', ''));
        $file = $request->input('file'); // URL File jika ada
        $location = $request->input('location'); // Lat,Long jika ada
        
        // Deteksi Tipe Pesan
        $type = 'text';
        if (!empty($file)) {
            $type = 'image';
        } elseif (!empty($location)) {
            $type = 'location';
        }

        if (empty($sender)) {
            return response()->json(['status' => 'invalid_sender']);
        }

        // 2. Concurrency Protection (Locking)
        $lock = Cache::lock('bot_session_' . $sender, 5);

        if (!$lock->get()) {
            Log::warning("Race condition detected for $sender. Ignoring duplicate request.");
            return response()->json(['status' => 'locked']);
        }

        try {
            // 3. Ambil/Buat Session
            $session = BotSession::firstOrCreate(
                ['phone_number' => $sender],
                ['state' => 'IDLE', 'last_interaction_at' => now()]
            );
            $session->touch();

            // 4. Routing Logic (Sama dengan Gowa, tapi adapter datanya beda)
            switch ($session->state) {
                case 'IDLE':
                case 'WAITING_REPORT_DESC':
                    $this->handleIdleState($session, $message, $sender);
                    break;

                case 'WAITING_REPORT_PHOTO':
                    $this->handlePhotoState($session, $type, $file, $message, $sender);
                    break;

                case 'WAITING_REPORT_LOCATION':
                    // Fonnte mengirim location dalam format "Lat, Long" string
                    $this->handleLocationState($session, $type, $location ?? $message, $sender);
                    break;
                    
                default:
                    $session->update(['state' => 'IDLE', 'temp_data' => null]);
                    $this->fonnteService->sendText($sender, "Maaf, saya bingung. Silakan ketik *LAPOR* untuk memulai.");
            }

        } finally {
            $lock->release();
        }

        return response()->json(['status' => 'processed', 'detail' => 'fonnte']);
    }

    private function handleIdleState($session, $text, $waId)
    {
        if (strtolower($text) === 'lapor') {
            $session->update([
                'state' => 'WAITING_REPORT_DESC'
            ]);
            $this->fonnteService->sendText($waId, "Halo! Apa yang ingin Anda laporkan? (Contoh: Jalan berlubang di Desa Sukamaju)");
            return;
        }
        
        if ($session->state === 'WAITING_REPORT_DESC') {
             $session->update([
                 'state' => 'WAITING_REPORT_PHOTO',
                 'temp_data' => ['description' => $text]
             ]);
             $this->fonnteService->sendText($waId, "Baik. Tolong kirimkan FOTO buktinya ya.");
             return;
        }

        $this->fonnteService->sendText($waId, "Selamat datang di BELACALL! Ketik *LAPOR* untuk membuat pengaduan baru.");
    }

    private function handlePhotoState($session, $type, $fileUrl, $textMessage, $waId)
    {
        // 1. Cek jika user ingin BATAL
        if (strtolower((string)$textMessage) === 'batal') {
            $session->update(['state' => 'IDLE', 'temp_data' => null]);
            $this->fonnteService->sendText($waId, "Laporan dibatalkan. Ketik *LAPOR* jika ingin mulai lagi.");
            return;
        }

        // Jika fileUrl kosong, cek apakah ada di message (kadang Fonnte taruh link di message)
        if (empty($fileUrl) && filter_var($textMessage, FILTER_VALIDATE_URL)) {
            $fileUrl = $textMessage;
            $type = 'image';
        }

        if ($type !== 'image' || empty($fileUrl)) {
            Log::warning("Fonnte Photo Expected but got: Type=$type, URL=" . ($fileUrl ?? 'NULL') . ", Msg=$textMessage");
            $this->fonnteService->sendText($waId, "Mohon kirimkan GAMBAR/FOTO, bukan teks. Atau ketik *BATAL* untuk cancel.");
            return;
        }


        // Update Session
        $tempData = $session->temp_data;
        $tempData['photo_url'] = $fileUrl;
        
        $session->update([
            'state' => 'WAITING_REPORT_LOCATION',
            'temp_data' => $tempData
        ]);

        $this->fonnteService->sendText($waId, "Foto diterima. Terakhir, kirimkan LOKASI (Share Location) atau ketik nama jalannya.");
    }


    private function handleLocationState($session, $type, $locationData, $waId)
    {
        // Finalisasi Laporan
        $tempData = $session->temp_data;
        $description = $tempData['description'] ?? 'Laporan Warga';
        $photoUrl = $tempData['photo_url'] ?? '';
        
        // Parsing Location (Fonnte kirim lat,long string jika share loc)
        $locationName = $locationData;
        $lat = null;
        $long = null;

        if ($type === 'location' || str_contains($locationData, ',')) {
            $parts = explode(',', $locationData);
            if (count($parts) >= 2) {
                $lat = trim($parts[0]);
                $long = trim($parts[1]);
                $locationName = "Koordinat: $lat, $long";
            }
        }

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
            'latitude' => $lat,
            'longitude' => $long,
            'status' => 'SUBMITTED',
            'category' => 'General'
        ]);

        if ($photoUrl) {
            $report->evidences()->create([
                'file_path' => $photoUrl,
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
        
        $this->fonnteService->sendText($waId, $reply);
    }
}
