<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GowaConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mock(GowaService::class, function ($mock) {
            $mock->shouldReceive('sendText')->andReturn(true);
        });
    }

    /** @test */
    public function it_prevents_race_condition_using_atomic_lock()
    {
        $phoneNumber = '628123456789';
        
        // Simulasikan Lock sudah diambil oleh proses lain
        Cache::lock('bot_session_' . $phoneNumber, 5)->get();

        $payload = [
            'event' => 'message',
            'payload' => [
                'from' => $phoneNumber . '@s.whatsapp.net',
                'body' => 'LAPOR',
                'type' => 'text'
            ]
        ];

        // Request kedua harus ditolak karena lock aktif
        $response = $this->postJson(route('webhook.gowa'), $payload);

        $response->assertJson(['status' => 'locked']);
        
        // Pastikan session tidak terbuat karena di-lock
        $this->assertDatabaseCount('bot_sessions', 0);
    }
}
