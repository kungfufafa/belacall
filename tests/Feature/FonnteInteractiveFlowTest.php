<?php

namespace Tests\Feature;

use App\Models\BotSession;
use App\Services\FonnteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FonnteInteractiveFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(FonnteService::class, function ($mock) {
            $mock->shouldReceive('sendText')->andReturn(true);
        });
    }

    public function test_full_report_flow_text_only()
    {
        $sender = '628123456789';

        $response = $this->postJson('/webhook/fonnte', [
            'sender' => $sender,
            'message' => 'LAPOR',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('bot_sessions', [
            'phone_number' => $sender,
            'state' => 'WAITING_TITLE',
        ]);

        $response = $this->postJson('/webhook/fonnte', [
            'sender' => $sender,
            'message' => 'Jalan Rusak Parah',
        ]);

        $this->assertDatabaseHas('bot_sessions', [
            'phone_number' => $sender,
            'state' => 'WAITING_LOCATION',
        ]);

        $session = BotSession::where('phone_number', $sender)->first();
        $this->assertEquals('Jalan Rusak Parah', $session->temp_data['title']);

        $response = $this->postJson('/webhook/fonnte', [
            'sender' => $sender,
            'message' => 'Desa Sukamaju RT 01',
        ]);

        $this->assertDatabaseHas('bot_sessions', [
            'phone_number' => $sender,
            'state' => 'WAITING_DESCRIPTION',
        ]);

        $session->refresh();
        $this->assertEquals('Desa Sukamaju RT 01', $session->temp_data['location']);

        $response = $this->postJson('/webhook/fonnte', [
            'sender' => $sender,
            'message' => 'Lubang besar di tengah jalan, sudah 2 motor jatuh.',
        ]);

        $this->assertDatabaseHas('bot_sessions', [
            'phone_number' => $sender,
            'state' => 'IDLE',
        ]);

        $this->assertDatabaseHas('reports', [
            'title' => 'Jalan Rusak Parah',
            'location_name' => 'Desa Sukamaju RT 01',
            'description' => 'Lubang besar di tengah jalan, sudah 2 motor jatuh.',
            'status' => 'SUBMITTED',
        ]);
    }

    public function test_reset_flow_with_lapor()
    {
        $sender = '628123456789';

        $this->postJson('/webhook/fonnte', ['sender' => $sender, 'message' => 'LAPOR']);
        $this->assertDatabaseHas('bot_sessions', ['state' => 'WAITING_TITLE']);

        $this->postJson('/webhook/fonnte', ['sender' => $sender, 'message' => 'LAPOR']);

        $this->assertDatabaseHas('bot_sessions', ['state' => 'WAITING_TITLE']);

        $session = BotSession::where('phone_number', $sender)->first();
        $this->assertNull($session->temp_data);
    }

    public function test_cancel_flow()
    {
        $sender = '628123456789';

        $this->postJson('/webhook/fonnte', ['sender' => $sender, 'message' => 'LAPOR']);

        $response = $this->postJson('/webhook/fonnte', ['sender' => $sender, 'message' => 'BATAL']);

        $response->assertJson(['status' => 'cancelled']);
        $this->assertDatabaseHas('bot_sessions', ['state' => 'IDLE']);
    }
}
