<?php

namespace Tests\Feature;

use App\Models\BotSession;
use App\Services\FonnteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;

class FonnteWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock FonnteService agar tidak hit API beneran saat test
        $this->mock(FonnteService::class, function ($mock) {
            $mock->shouldReceive('sendText')->andReturn(['status' => true]);
        });
    }

    /** @test */
    public function it_starts_conversation_when_user_sends_lapor()
    {
        $payload = [
            'sender' => '628123456789',
            'message' => 'LAPOR',
            'name' => 'Pak Budi'
        ];

        $response = $this->postJson(route('webhook.fonnte'), $payload);

        $response->assertStatus(200);
        
        $this->assertDatabaseHas('bot_sessions', [
            'phone_number' => '628123456789',
            'state' => 'WAITING_REPORT_DESC'
        ]);
    }

    /** @test */
    public function it_saves_description_and_asks_for_photo()
    {
        BotSession::create([
            'phone_number' => '628123456789',
            'state' => 'WAITING_REPORT_DESC',
            'last_interaction_at' => now()
        ]);

        $payload = [
            'sender' => '628123456789',
            'message' => 'Ada sampah numpuk di selokan',
        ];

        $this->postJson(route('webhook.fonnte'), $payload);

        $this->assertDatabaseHas('bot_sessions', [
            'phone_number' => '628123456789',
            'state' => 'WAITING_REPORT_PHOTO',
        ]);
    }

    /** @test */
    public function it_accepts_photo_via_file_parameter()
    {
        BotSession::create([
            'phone_number' => '628123456789',
            'state' => 'WAITING_REPORT_PHOTO',
            'temp_data' => ['description' => 'Sampah'],
            'last_interaction_at' => now()
        ]);

        // Fonnte mengirim URL file di parameter 'file'
        $payload = [
            'sender' => '628123456789',
            'message' => 'Ini fotonya',
            'file' => 'https://fonnte.com/storage/image.jpg'
        ];

        $this->postJson(route('webhook.fonnte'), $payload);

        $this->assertDatabaseHas('bot_sessions', [
            'state' => 'WAITING_REPORT_LOCATION',
        ]);

        $session = BotSession::where('phone_number', '628123456789')->first();
        $this->assertEquals('https://fonnte.com/storage/image.jpg', $session->temp_data['photo_url']);
    }

    /** @test */
    public function it_finalizes_report_with_location_coordinates()
    {
        BotSession::create([
            'phone_number' => '628123456789',
            'state' => 'WAITING_REPORT_LOCATION',
            'temp_data' => [
                'description' => 'Sampah',
                'photo_url' => 'https://img.com'
            ],
            'last_interaction_at' => now()
        ]);

        // Simulasi share location (Fonnte kirim lat,long di message atau location)
        $payload = [
            'sender' => '628123456789',
            'message' => '-6.200000, 106.816666', // Format lat,long
            'location' => '-6.200000, 106.816666'
        ];

        $this->postJson(route('webhook.fonnte'), $payload);

        // Cek Report Terbuat dengan koordinat
        $this->assertDatabaseHas('reports', [
            'description' => 'Sampah',
            'latitude' => -6.200000,
            'longitude' => 106.816666,
            'status' => 'SUBMITTED'
        ]);
        
        // Session reset
        $this->assertDatabaseHas('bot_sessions', [
            'phone_number' => '628123456789',
            'state' => 'IDLE'
        ]);
    }
}
