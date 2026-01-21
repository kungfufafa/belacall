<?php

namespace Tests\Feature;

use App\Enums\ReportStatus;
use App\Models\BotSession;
use App\Models\Report;
use App\Services\GowaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GowaWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock GowaService agar tidak hit API beneran saat test
        $this->mock(GowaService::class, function ($mock) {
            $mock->shouldReceive('sendText')->andReturn(true);
        });
    }

    /** @test */
    public function it_starts_conversation_when_user_sends_lapor()
    {
        $payload = [
            'event' => 'message',
            'payload' => [
                'from' => '628123456789@s.whatsapp.net',
                'body' => 'LAPOR',
                'type' => 'text',
            ],
        ];

        $response = $this->postJson(route('webhook.gowa'), $payload);

        $response->assertStatus(200);

        // Cek session terbuat
        $this->assertDatabaseHas('bot_sessions', [
            'phone_number' => '628123456789',
            'state' => 'WAITING_REPORT_DESC', // Sesuai logic shortcut di controller
        ]);
    }

    /** @test */
    public function it_saves_description_and_asks_for_photo()
    {
        // 1. Setup Session awal
        BotSession::create([
            'phone_number' => '628123456789',
            'state' => 'WAITING_REPORT_DESC',
            'last_interaction_at' => now(),
        ]);

        $payload = [
            'event' => 'message',
            'payload' => [
                'from' => '628123456789@s.whatsapp.net',
                'body' => 'Jalan berlubang di depan pasar',
                'type' => 'text',
            ],
        ];

        $this->postJson(route('webhook.gowa'), $payload);

        $this->assertDatabaseHas('bot_sessions', [
            'phone_number' => '628123456789',
            'state' => 'WAITING_REPORT_PHOTO',
        ]);

        // Cek data sementara tersimpan
        $session = BotSession::where('phone_number', '628123456789')->first();
        $this->assertEquals('Jalan berlubang di depan pasar', $session->temp_data['description']);
    }

    /** @test */
    public function it_accepts_photo_and_asks_for_location()
    {
        BotSession::create([
            'phone_number' => '628123456789',
            'state' => 'WAITING_REPORT_PHOTO',
            'temp_data' => ['description' => 'Jalan rusak'],
            'last_interaction_at' => now(),
        ]);

        $payload = [
            'event' => 'message',
            'payload' => [
                'from' => '628123456789@s.whatsapp.net',
                'body' => '',
                'type' => 'image',
                'url' => 'https://example.com/image.jpg',
            ],
        ];

        $this->postJson(route('webhook.gowa'), $payload);

        $this->assertDatabaseHas('bot_sessions', [
            'state' => 'WAITING_REPORT_LOCATION',
        ]);

        $session = BotSession::where('phone_number', '628123456789')->first();
        $this->assertEquals('https://example.com/image.jpg', $session->temp_data['photo_url']);
    }

    /** @test */
    public function it_finalizes_report_when_location_is_sent()
    {
        BotSession::create([
            'phone_number' => '628123456789',
            'state' => 'WAITING_REPORT_LOCATION',
            'temp_data' => [
                'description' => 'Jalan rusak',
                'photo_url' => 'https://example.com/image.jpg',
            ],
            'last_interaction_at' => now(),
        ]);

        $payload = [
            'event' => 'message',
            'payload' => [
                'from' => '628123456789@s.whatsapp.net',
                'body' => 'Desa Sukamaju RT 01',
                'type' => 'text',
            ],
        ];

        $this->postJson(route('webhook.gowa'), $payload);

        // 1. Cek Report Terbuat
        $this->assertDatabaseHas('reports', [
            'description' => 'Jalan rusak',
            'location_name' => 'Desa Sukamaju RT 01',
            'status' => ReportStatus::SUBMITTED->value,
        ]);

        // 2. Cek Bukti Terbuat
        $this->assertDatabaseHas('report_evidences', [
            'file_path' => 'https://example.com/image.jpg',
        ]);

        // 3. Cek User Warga Terbuat Otomatis
        $this->assertDatabaseHas('users', [
            'phone' => '628123456789',
            'role' => 'warga',
        ]);

        // 4. Cek Session Reset
        $this->assertDatabaseHas('bot_sessions', [
            'phone_number' => '628123456789',
            'state' => 'IDLE',
            'temp_data' => null, // json null disimpan sebagai string "null" atau null di sqlite
        ]);
    }
}
