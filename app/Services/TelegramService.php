<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class TelegramService
{
    private string $baseUrl;

    private string $token;

    public function __construct()
    {
        $this->token = (string) config('services.telegram.bot_token', '');
        $this->baseUrl = "https://api.telegram.org/bot{$this->token}";
    }

    /**
     * Kirim pesan teks ke chat ID
     */
    public function sendText(string $chatId, string $message, ?array $replyMarkup = null): array|bool
    {
        $payload = [
            'chat_id' => $chatId,
            'text' => $this->escapeMarkdown($message),
            'parse_mode' => 'MarkdownV2',
        ];

        if ($replyMarkup !== null) {
            $payload['reply_markup'] = json_encode($replyMarkup);
        }

        return $this->sendRequest('sendMessage', $payload);
    }

    /**
     * Kirim pesan gambar (URL atau file_id)
     */
    public function sendPhoto(string $chatId, string $photo, string $caption = ''): array|bool
    {
        $payload = [
            'chat_id' => $chatId,
            'photo' => $photo,
            'parse_mode' => 'MarkdownV2',
        ];

        if ($caption !== '') {
            $payload['caption'] = $this->escapeMarkdown($caption);
        }

        return $this->sendRequest('sendPhoto', $payload);
    }

    /**
     * Kirim lokasi
     */
    public function sendLocation(string $chatId, float $latitude, float $longitude): array|bool
    {
        $payload = [
            'chat_id' => $chatId,
            'latitude' => $latitude,
            'longitude' => $longitude,
        ];

        return $this->sendRequest('sendLocation', $payload);
    }

    /**
     * Minta user share kontak via custom keyboard
     */
    public function requestContact(string $chatId, string $message): array|bool
    {
        $replyMarkup = [
            'keyboard' => [
                [
                    [
                        'text' => 'Bagikan Nomor HP',
                        'request_contact' => true,
                    ],
                ],
            ],
            'resize_keyboard' => true,
            'one_time_keyboard' => true,
        ];

        return $this->sendText($chatId, $message, $replyMarkup);
    }

    /**
     * Hapus custom keyboard dan kirim pesan
     */
    public function sendTextRemoveKeyboard(string $chatId, string $message): array|bool
    {
        $replyMarkup = [
            'remove_keyboard' => true,
        ];

        return $this->sendText($chatId, $message, $replyMarkup);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function sendRequest(string $method, array $data): array|bool
    {
        try {
            if ((bool) config('services.telegram.fake_mode', false)) {
                Log::info('Telegram fake mode enabled, skipping outbound request.', [
                    'method' => $method,
                ]);

                return [
                    'ok' => true,
                    'fake' => true,
                ];
            }

            if ($this->token === '') {
                Log::error('Telegram bot token is missing.');

                return false;
            }

            $url = "{$this->baseUrl}/{$method}";
            $chatId = isset($data['chat_id']) ? (string) $data['chat_id'] : '';
            $text = isset($data['text']) ? (string) $data['text'] : '';

            Log::info('Telegram request', [
                'method' => $method,
                'chat_id_suffix' => substr($chatId, -4),
                'text_length' => mb_strlen($text),
            ]);

            $response = Http::timeout(8)
                ->retry(1, 250)
                ->post($url, $data);

            if ($response->failed()) {
                Log::error('Telegram API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }

            $decoded = $response->json();

            if (! is_array($decoded)) {
                return [
                    'ok' => true,
                    'raw' => $response->body(),
                ];
            }

            return $decoded;
        } catch (Throwable $e) {
            Log::error('Telegram exception', [
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Escape special characters for Telegram MarkdownV2 format.
     * According to Telegram API docs, these characters must be escaped:
     * _ * [ ] ( ) ~ ` > # + - = | { } . !
     *
     * @see https://core.telegram.org/bots/api#markdownv2-style
     */
    private function escapeMarkdown(string $text): string
    {
        $specialChars = ['_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!'];

        return str_replace(
            $specialChars,
            array_map(fn ($char) => '\\'.$char, $specialChars),
            $text
        );
    }
}
