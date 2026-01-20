<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GowaService
{
    protected $baseUrl;
    protected $apiKey; // Jika GOWA Anda diproteksi
    protected $deviceId;

    public function __construct()
    {
        $this->baseUrl = config('services.gowa.base_url', 'http://localhost:3000');
        $this->apiKey = config('services.gowa.api_key'); 
        $this->deviceId = config('services.gowa.device_id');
    }

    /**
     * Kirim pesan teks ke nomor tujuan
     */
    public function sendText($to, $message)
    {
        $payload = [
            'to' => $this->formatPhone($to),
            'message' => $message,
        ];

        return $this->sendRequest('message/text', $payload);
    }

    /**
     * Kirim pesan gambar (URL/Local)
     */
    public function sendImage($to, $imageUrl, $caption = '')
    {
        $payload = [
            'to' => $this->formatPhone($to),
            'url' => $imageUrl, // GOWA support URL
            'caption' => $caption
        ];

        return $this->sendRequest('message/image', $payload);
    }

    /**
     * Format nomor HP ke format internasional (62xxx) tanpa '+'
     */
    private function formatPhone($phone)
    {
        // Hapus karakter non-digit
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Ubah 08xx jadi 628xx
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }

        return $phone . '@s.whatsapp.net';
    }

    private function sendRequest($endpoint, $data)
    {
        try {
            // Sesuaikan endpoint ini dengan dokumentasi GOWA v8
            $url = "{$this->baseUrl}/client/$endpoint"; 
            
            // Tambahkan Device ID jika diperlukan
            if ($this->deviceId) {
                 $url .= "?device_id={$this->deviceId}";
            }

            Log::info("GOWA Request to $url", $data);

            $response = Http::timeout(10)->post($url, $data);

            if ($response->failed()) {
                Log::error("GOWA API Error: " . $response->body());
                return false;
            }

            return $response->json();

        } catch (\Exception $e) {
            Log::error("GOWA Exception: " . $e->getMessage());
            return false;
        }
    }
}
