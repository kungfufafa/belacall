<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class FonnteService
{
    private string $baseUrl;

    private string $token;

    public function __construct()
    {
        $this->baseUrl = 'https://api.fonnte.com';
        $this->token = (string) config('services.fonnte.token', '');
    }

    /**
     * Kirim pesan teks ke nomor tujuan
     */
    public function sendText(string $target, string $message): array|bool
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
    public function sendImage(string $target, string $imageUrl, string $caption = ''): array|bool
    {
        $payload = [
            'target' => $target,
            'message' => $caption, // Di Fonnte, caption masuk ke field message
            'url' => $imageUrl,
            'countryCode' => '62',
        ];

        return $this->sendRequest('send', $payload);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function sendRequest(string $endpoint, array $data): array|bool
    {
        try {
            if ($this->token === '') {
                Log::error('Fonnte token is missing.');

                return false;
            }

            $url = "{$this->baseUrl}/{$endpoint}";
            $target = isset($data['target']) ? (string) $data['target'] : '';
            $message = isset($data['message']) ? (string) $data['message'] : '';

            Log::info('Fonnte request', [
                'endpoint' => $endpoint,
                'target_suffix' => substr(preg_replace('/\D+/', '', $target) ?: '', -4),
                'message_length' => mb_strlen($message),
                'has_media_url' => isset($data['url']),
            ]);

            $response = Http::withHeaders([
                'Authorization' => $this->token,
            ])
                ->timeout(8)
                ->retry(1, 250)
                ->post($url, $data);

            if ($response->failed()) {
                Log::error('Fonnte API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }

            $decoded = $response->json();

            if (! is_array($decoded)) {
                return [
                    'status' => true,
                    'raw' => $response->body(),
                ];
            }

            return $decoded;
        } catch (Throwable $e) {
            Log::error('Fonnte exception', [
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
