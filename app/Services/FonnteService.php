<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FonnteService
{
    protected $baseUrl;
    protected $token;

    public function __construct()
    {
        $this->baseUrl = 'https://api.fonnte.com';
        $this->token = config('services.fonnte.token');
    }

    /**
     * Kirim pesan teks ke nomor tujuan
     */
    public function sendText($target, $message)
    {
        $payload = [
            'target' => $target,
            'message' => $message,
            'countryCode' => '62', // Default Indonesia
        ];

        return $this->sendRequest('send', $payload);
    }

    /**
     * Kirim pesan gambar (URL)
     */
    public function sendImage($target, $imageUrl, $caption = '')
    {
        $payload = [
            'target' => $target,
            'message' => $caption, // Di Fonnte, caption masuk ke field message
            'url' => $imageUrl,
            'countryCode' => '62',
        ];

        return $this->sendRequest('send', $payload);
    }

    private function sendRequest($endpoint, $data)
    {
        try {
            $url = "{$this->baseUrl}/{$endpoint}";

            Log::info("Fonnte Request to $url", $data);

            $response = Http::withHeaders([
                'Authorization' => $this->token,
            ])->post($url, $data);

            if ($response->failed()) {
                Log::error("Fonnte API Error: " . $response->body());
                return false;
            }

            return $response->json();

        } catch (\Exception $e) {
            Log::error("Fonnte Exception: " . $e->getMessage());
            return false;
        }
    }
}
