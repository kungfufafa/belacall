<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SetTelegramWebhookCommand extends Command
{
    protected $signature = 'telegram:set-webhook
                            {--remove : Remove the webhook instead of setting it}';

    protected $description = 'Register or remove the Telegram Bot webhook';

    public function handle(): int
    {
        $token = config('services.telegram.bot_token');

        if (! $token) {
            $this->error('TELEGRAM_BOT_TOKEN is not set in .env');

            return self::FAILURE;
        }

        if ($this->option('remove')) {
            return $this->removeWebhook($token);
        }

        return $this->setWebhook($token);
    }

    private function setWebhook(string $token): int
    {
        $url = rtrim(config('app.url'), '/').'/webhook/telegram';
        $secret = config('services.telegram.webhook_secret');

        $payload = ['url' => $url];

        if ($secret) {
            $payload['secret_token'] = $secret;
        }

        $this->info("Setting webhook to: {$url}");

        $response = Http::post(
            "https://api.telegram.org/bot{$token}/setWebhook",
            $payload
        );

        if ($response->successful() && $response->json('ok')) {
            $this->info('Webhook set successfully.');
            $this->table(['Key', 'Value'], [
                ['URL', $url],
                ['Secret', $secret ? 'Configured' : 'Not set'],
                ['Description', $response->json('description', '-')],
            ]);

            return self::SUCCESS;
        }

        $this->error('Failed to set webhook: '.$response->json('description', $response->body()));

        return self::FAILURE;
    }

    private function removeWebhook(string $token): int
    {
        $response = Http::post(
            "https://api.telegram.org/bot{$token}/deleteWebhook"
        );

        if ($response->successful() && $response->json('ok')) {
            $this->info('Webhook removed successfully.');

            return self::SUCCESS;
        }

        $this->error('Failed to remove webhook: '.$response->json('description', $response->body()));

        return self::FAILURE;
    }
}
