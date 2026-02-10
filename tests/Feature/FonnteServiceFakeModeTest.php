<?php

namespace Tests\Feature;

use App\Services\FonnteService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FonnteServiceFakeModeTest extends TestCase
{
    public function test_send_text_skips_outbound_request_when_fake_mode_enabled(): void
    {
        config()->set('services.fonnte.fake_mode', true);
        config()->set('services.fonnte.token', 'fake-token');

        Http::fake();

        $service = app(FonnteService::class);
        $result = $service->sendText('6281234567890', 'Pesan uji fake mode');

        $this->assertIsArray($result);
        $this->assertTrue((bool) ($result['status'] ?? false));
        $this->assertTrue((bool) ($result['fake'] ?? false));
        Http::assertNothingSent();
    }

    public function test_send_text_keeps_original_behavior_when_fake_mode_disabled(): void
    {
        config()->set('services.fonnte.fake_mode', false);
        config()->set('services.fonnte.token', 'real-token-for-test');

        Http::fake([
            'api.fonnte.com/send' => Http::response(['status' => true], 200),
        ]);

        $service = app(FonnteService::class);
        $result = $service->sendText('6281234567890', 'Pesan uji normal mode');

        $this->assertIsArray($result);
        $this->assertTrue((bool) ($result['status'] ?? false));
        Http::assertSentCount(1);
        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://api.fonnte.com/send';
        });
    }

    public function test_send_text_returns_false_without_token_when_fake_mode_disabled(): void
    {
        config()->set('services.fonnte.fake_mode', false);
        config()->set('services.fonnte.token', '');

        Http::fake();

        $service = app(FonnteService::class);
        $result = $service->sendText('6281234567890', 'Pesan tanpa token');

        $this->assertFalse($result);
        Http::assertNothingSent();
    }
}
