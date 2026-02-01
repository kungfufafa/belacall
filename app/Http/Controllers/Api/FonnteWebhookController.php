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

    protected $reportParser;

    public function __construct(FonnteService $fonnteService, \App\Services\ReportParser $reportParser)
    {
        $this->fonnteService = $fonnteService;
        $this->reportParser = $reportParser;
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

        Log::info('🔥 FONNTE HIT:', $request->all());

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
        if (! empty($file)) {
            $type = 'image';
        } elseif (! empty($location)) {
            $type = 'location';
        }

        if (empty($sender)) {
            return response()->json(['status' => 'invalid_sender']);
        }

        // --- NEW: FORM PARSER CHECK ---
        // If message contains form data, process immediately regardless of state
        if ($type === 'text' && ! empty($message)) {
            $formData = $this->reportParser->parse($message);
            if ($formData) {
                return $this->handleDirectFormSubmission($sender, $formData);
            }
        }
        // ------------------------------

        // 2. Concurrency Protection (Locking)
        $lock = Cache::lock('bot_session_'.$sender, 5);

        if (! $lock->get()) {
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

                case 'WAITING_OPTIONAL_LOCATION':
                    $this->handleOptionalLocationState($session, $type, $location ?? $message, $sender);
                    break;

                default:
                    $session->update(['state' => 'IDLE', 'temp_data' => null]);
                    $this->fonnteService->sendText($sender, 'Maaf, saya bingung. Silakan ketik *LAPOR* untuk memulai.');
            }

        } finally {
            $lock->release();
        }

        return response()->json(['status' => 'processed', 'detail' => 'fonnte']);
    }

    private function handleOptionalLocationState($session, $type, $locationData, $waId)
    {
        // Jika user mengirim text biasa (bukan lokasi), anggap dia selesai / skip
        // Atau jika dia memulai command baru "Lapor"
        if ($type !== 'location' && ! str_contains($locationData, ',')) {
            // Jika isinya 'lapor', biarkan flow handleIdleState menangkapnya di request berikutnya (karena kita reset ke IDLE)
            // Tapi karena ini dalam switch case, kita harus handle manual atau set ke IDLE dulu

            $session->update(['state' => 'IDLE', 'temp_data' => null]);

            // Jika dia ngetik "Lapor" lagi, kita panggil handleIdleState langsung?
            // Untuk simplenya, kita anggap interaksi selesai.
            if (strtolower(trim($locationData)) === 'lapor') {
                $this->handleIdleState($session, $locationData, $waId);
            } else {
                // Ignore text chatter, just reset
                // Optional: Say "Terima kasih"
            }

            return;
        }

        // Parsing Location
        $lat = null;
        $long = null;

        if ($type === 'location' || str_contains($locationData, ',')) {
            $parts = explode(',', $locationData);
            if (count($parts) >= 2) {
                $rawLat = trim($parts[0]);
                $rawLong = trim($parts[1]);
                
                // VALIDASI NUMERIC STRICT
                if (is_numeric($rawLat) && is_numeric($rawLong)) {
                    $lat = $rawLat;
                    $long = $rawLong;
                }
            }
        }

        if ($lat && $long) {
            // Update Report Terakhir
            $reportId = $session->temp_data['report_id'] ?? null;
            if ($reportId) {
                $report = Report::find($reportId);
                if ($report) {
                    $report->update([
                        'latitude' => $lat,
                        'longitude' => $long,
                        'location_name' => "Koordinat: $lat, $long (" . $report->location_name . ")"
                    ]);
                    
                    $this->fonnteService->sendText($waId, "📍 *Lokasi Diupdate!*\nTerima kasih, data lokasi presisi telah ditambahkan ke laporan #{$report->ticket_number}.");
                }
            }
        } else {
             // Jika format location invalid, kita beri tahu user (opsional) atau abaikan
             // Di sini kita abaikan saja agar tidak spam jika user kirim text "Oke makasih"
        }

        // Selesai, kembali ke IDLE
        $session->update(['state' => 'IDLE', 'temp_data' => null]);
    }
        }

        if ($lat && $long) {
            // Update Report Terakhir
            $reportId = $session->temp_data['report_id'] ?? null;
            if ($reportId) {
                $report = Report::find($reportId);
                if ($report) {
                    $report->update([
                        'latitude' => $lat,
                        'longitude' => $long,
                        'location_name' => "Koordinat: $lat, $long (".$report->location_name.')',
                    ]);

                    $this->fonnteService->sendText($waId, "📍 *Lokasi Diupdate!*\nTerima kasih, data lokasi presisi telah ditambahkan ke laporan #{$report->ticket_number}.");
                }
            }
        }

        // Selesai, kembali ke IDLE
        $session->update(['state' => 'IDLE', 'temp_data' => null]);
    }

    private function handleDirectFormSubmission($sender, array $data)
    {
        // Create User
        $user = User::firstOrCreate(
            ['phone' => $sender],
            ['name' => $data['name'] ?? 'Warga '.substr($sender, -4), 'role' => 'warga']
        );

        // Create Report
        $report = Report::create([
            'ticket_number' => 'T-'.now()->format('YmdHi').rand(10, 99),
            'user_id' => $user->id,
            'title' => $data['title'] ?? 'Laporan Via WA',
            'description' => $data['description'] ?? '-',
            'location_name' => $data['location'] ?? 'Tidak disebutkan',
            'status' => 'SUBMITTED',
            'category' => 'General',
        ]);

        // SET SESSION STATE to wait for Optional Location
        BotSession::updateOrCreate(
            ['phone_number' => $sender],
            [
                'state' => 'WAITING_OPTIONAL_LOCATION',
                'temp_data' => ['report_id' => $report->id],
                'last_interaction_at' => now(),
            ]
        );

        // Reply
        $reply = "✅ *Laporan Berhasil Diterima!*\n\n";
        $reply .= "Nomor Tiket: *{$report->ticket_number}*\n";
        $reply .= "Status: *Menunggu Verifikasi*\n\n";
        $reply .= "📍 *OPSIONAL*: Agar petugas lebih mudah menemukan lokasi, silakan kirim *Lokasi Saat Ini* (Share Current Location).\n\n";
        $reply .= "⚠️ *Mohon JANGAN kirim Live Location*, karena sistem tidak bisa membacanya.\n\n";
        $reply .= '_Atau abaikan pesan ini jika lokasi sudah cukup jelas._';

        $this->fonnteService->sendText($sender, $reply);

        return response()->json(['status' => 'processed', 'detail' => 'form_submission']);
    }

    private function handleIdleState($session, $text, $waId)
    {
        if (strtolower($text) === 'lapor') {
            $session->update([
                'state' => 'WAITING_REPORT_DESC',
            ]);
            $this->fonnteService->sendText($waId, 'Halo! Apa yang ingin Anda laporkan? (Contoh: Jalan berlubang di Desa Sukamaju)');

            return;
        }

        if ($session->state === 'WAITING_REPORT_DESC') {
            $session->update([
                'state' => 'WAITING_REPORT_PHOTO',
                'temp_data' => ['description' => $text],
            ]);
            $this->fonnteService->sendText($waId, 'Baik. Tolong kirimkan FOTO buktinya ya. (Jika tidak ada foto/gagal upload, balas dengan *SKIP*)');

            return;
        }

        $this->fonnteService->sendText($waId, "Selamat datang di BELACALL! \n\nSilakan kirim laporan dengan format berikut:\n\n*FORM PELAPORAN*\nNama: [Nama Anda]\nJudul: [Judul Laporan]\nLokasi: [Lokasi Kejadian]\nKeterangan: [Jelaskan detailnya]\n\nAtau ketik *LAPOR* untuk dipandu langkah demi langkah.");
    }

    private function handlePhotoState($session, $type, $fileUrl, $textMessage, $waId)
    {
        // 1. Cek jika user ingin BATAL atau SKIP
        if (strtolower((string) $textMessage) === 'batal') {
            $session->update(['state' => 'IDLE', 'temp_data' => null]);
            $this->fonnteService->sendText($waId, 'Laporan dibatalkan. Ketik *LAPOR* jika ingin mulai lagi.');

            return;
        }

        if (strtolower((string) $textMessage) === 'skip') {
            // User memilih skip foto
            $session->update([
                'state' => 'WAITING_REPORT_LOCATION',
                // Keep existing temp_data
            ]);
            $this->fonnteService->sendText($waId, 'Foto dilewati. Terakhir, kirimkan LOKASI (Share Current Location/Lokasi Saat Ini) atau ketik nama jalannya.');

            return;
        }

        // Jika fileUrl kosong, cek apakah ada di message (kadang Fonnte taruh link di message)
        if (empty($fileUrl) && filter_var($textMessage, FILTER_VALIDATE_URL)) {
            $fileUrl = $textMessage;
            $type = 'image';
        }

        if ($type !== 'image' || empty($fileUrl)) {
            Log::warning("Fonnte Photo Expected but got: Type=$type, URL=".($fileUrl ?? 'NULL').", Msg=$textMessage");
            $this->fonnteService->sendText($waId, 'Mohon kirimkan GAMBAR/FOTO, bukan teks. Atau ketik *BATAL* untuk cancel.');

            return;
        }

        // Update Session
        $tempData = $session->temp_data;
        $tempData['photo_url'] = $fileUrl;

        $session->update([
            'state' => 'WAITING_REPORT_LOCATION',
            'temp_data' => $tempData,
        ]);

        $this->fonnteService->sendText($waId, 'Foto diterima. Terakhir, kirimkan LOKASI (Share Current Location/Lokasi Saat Ini) atau ketik nama jalannya.');
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
            ['name' => 'Warga '.substr($session->phone_number, -4), 'role' => 'warga']
        );

        // Simpan ke DB
        $report = Report::create([
            'ticket_number' => 'T-'.now()->format('YmdHi').rand(10, 99),
            'user_id' => $user->id,
            'title' => substr($description, 0, 50).'...',
            'description' => $description,
            'location_name' => $locationName,
            'latitude' => $lat,
            'longitude' => $long,
            'status' => 'SUBMITTED',
            'category' => 'General',
        ]);

        if ($photoUrl) {
            $report->evidences()->create([
                'file_path' => $photoUrl,
                'file_type' => 'image',
            ]);
        }

        // Reset Session
        $session->update(['state' => 'IDLE', 'temp_data' => null]);

        // Notif Sukses
        $reply = "✅ Laporan Diterima!\n\n";
        $reply .= "Nomor Tiket: *{$report->ticket_number}*\n";
        $reply .= "Status: Menunggu Verifikasi\n\n";
        $reply .= 'Kami akan mengabari Anda jika ada update.';

        $this->fonnteService->sendText($waId, $reply);
    }
}
