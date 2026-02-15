<?php

namespace Tests\Feature;

use App\Services\TelegramService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramServiceFakeModeTest extends TestCase
{
    public function test_send_text_skips_outbound_request_when_fake_mode_enabled(): void
    {
        config()->set('services.telegram.fake_mode', true);
        config()->set('services.telegram.bot_token', 'fake-token');

        Http::fake();

        $service = app(TelegramService::class);
        $result = $service->sendText('12345', 'Pesan uji fake mode');

        $this->assertIsArray($result);
        $this->assertTrue((bool) ($result['ok'] ?? false));
        $this->assertTrue((bool) ($result['fake'] ?? false));
        Http::assertNothingSent();
    }

    public function test_send_text_keeps_original_behavior_when_fake_mode_disabled(): void
    {
        config()->set('services.telegram.fake_mode', false);
        config()->set('services.telegram.bot_token', 'real-token-for-test');

        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => []], 200),
        ]);

        $service = app(TelegramService::class);
        $result = $service->sendText('12345', 'Pesan uji normal mode');

        $this->assertIsArray($result);
        $this->assertTrue((bool) ($result['ok'] ?? false));
        Http::assertSentCount(1);
        Http::assertSent(function (Request $request): bool {
            return str_contains($request->url(), 'api.telegram.org/botreal-token-for-test/sendMessage');
        });
    }

    public function test_send_text_returns_false_without_token_when_fake_mode_disabled(): void
    {
        config()->set('services.telegram.fake_mode', false);
        config()->set('services.telegram.bot_token', '');

        Http::fake();

        $service = app(TelegramService::class);
        $result = $service->sendText('12345', 'Pesan tanpa token');

        $this->assertFalse($result);
        Http::assertNothingSent();
    }
}
