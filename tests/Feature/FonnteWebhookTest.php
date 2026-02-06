<?php

namespace Tests\Feature;

use App\Enums\ReportStatus;
use App\Enums\Role;
use App\Models\BotSession;
use App\Models\User;
use App\Services\FonnteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class FonnteWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected MockInterface $fonnteMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock FonnteService agar tidak hit API beneran saat test
        $this->fonnteMock = $this->mock(FonnteService::class, function (MockInterface $mock) {
            $mock->shouldReceive('sendText')->andReturn(['status' => true]);
        });
    }

    public function test_it_starts_conversation_when_user_sends_lapor(): void
    {
        $payload = [
            'sender' => '628123456789',
            'message' => 'LAPOR',
            'name' => 'Pak Budi',
        ];

        $response = $this->postJson(route('webhook.fonnte'), $payload);

        $response->assertStatus(200);

        $this->assertDatabaseHas('bot_sessions', [
            'phone_number' => '628123456789',
            'state' => 'WAITING_TITLE',
        ]);

        $session = BotSession::query()->where('phone_number', '628123456789')->first();
        $this->assertIsString($session?->temp_data['session_started_at'] ?? null);
    }

    public function test_it_saves_title_and_moves_to_location_option(): void
    {
        BotSession::create([
            'phone_number' => '628123456789',
            'state' => 'WAITING_TITLE',
            'last_interaction_at' => now(),
        ]);

        $payload = [
            'sender' => '628123456789',
            'message' => 'Ada sampah numpuk di selokan',
        ];

        $this->postJson(route('webhook.fonnte'), $payload);

        $this->assertDatabaseHas('bot_sessions', [
            'phone_number' => '628123456789',
            'state' => 'WAITING_LOCATION_OPTION',
        ]);

        $session = BotSession::where('phone_number', '628123456789')->first();
        $this->assertSame('Ada sampah numpuk di selokan', $session?->temp_data['title']);
    }

    public function test_it_moves_to_location_coordinates_step_when_user_chooses_yes(): void
    {
        BotSession::create([
            'phone_number' => '628123456789',
            'state' => 'WAITING_LOCATION_OPTION',
            'temp_data' => [
                'session_started_at' => now()->toIso8601String(),
                'title' => 'Sampah menumpuk',
            ],
            'last_interaction_at' => now(),
        ]);

        $payload = [
            'sender' => '628123456789',
            'message' => '1',
        ];

        $this->postJson(route('webhook.fonnte'), $payload);

        $this->assertDatabaseHas('bot_sessions', [
            'phone_number' => '628123456789',
            'state' => 'WAITING_LOCATION_COORDINATES',
        ]);
    }

    public function test_it_accepts_lat_long_directly_in_location_option_step(): void
    {
        BotSession::create([
            'phone_number' => '628123456789',
            'state' => 'WAITING_LOCATION_OPTION',
            'temp_data' => [
                'session_started_at' => now()->toIso8601String(),
                'title' => 'Pohon tumbang',
            ],
            'last_interaction_at' => now(),
        ]);

        $this->postJson(route('webhook.fonnte'), [
            'sender' => '628123456789',
            'message' => '-6.123456, 106.987654',
        ])->assertStatus(200);

        $this->assertDatabaseHas('bot_sessions', [
            'phone_number' => '628123456789',
            'state' => 'WAITING_DESCRIPTION',
        ]);

        $session = BotSession::where('phone_number', '628123456789')->first();
        $this->assertSame('-6.123456', $session?->temp_data['latitude']);
        $this->assertSame('106.987654', $session?->temp_data['longitude']);
    }

    public function test_it_accepts_location_coordinates_and_moves_to_waiting_description(): void
    {
        BotSession::create([
            'phone_number' => '628123456789',
            'state' => 'WAITING_LOCATION_COORDINATES',
            'temp_data' => [
                'session_started_at' => now()->toIso8601String(),
                'title' => 'Sampah menumpuk',
            ],
            'last_interaction_at' => now(),
        ]);

        $payload = [
            'sender' => '628123456789',
            'message' => '-6.200000, 106.816666',
            'location' => '-6.200000, 106.816666',
        ];

        $this->postJson(route('webhook.fonnte'), $payload);

        $this->assertDatabaseHas('bot_sessions', [
            'phone_number' => '628123456789',
            'state' => 'WAITING_DESCRIPTION',
        ]);
        $session = BotSession::where('phone_number', '628123456789')->first();
        $this->assertSame('-6.200000', $session?->temp_data['latitude']);
        $this->assertSame('106.816666', $session?->temp_data['longitude']);
    }

    public function test_it_skips_location_when_user_chooses_no(): void
    {
        BotSession::create([
            'phone_number' => '628123456789',
            'state' => 'WAITING_LOCATION_OPTION',
            'temp_data' => [
                'session_started_at' => now()->toIso8601String(),
                'title' => 'Lampu jalan mati',
            ],
            'last_interaction_at' => now(),
        ]);

        $this->postJson(route('webhook.fonnte'), [
            'sender' => '628123456789',
            'message' => '2',
        ]);

        $this->assertDatabaseHas('bot_sessions', [
            'phone_number' => '628123456789',
            'state' => 'WAITING_DESCRIPTION',
        ]);

        $session = BotSession::where('phone_number', '628123456789')->first();
        $this->assertArrayNotHasKey('latitude', $session?->temp_data ?? []);
        $this->assertArrayNotHasKey('longitude', $session?->temp_data ?? []);
    }

    public function test_it_finalizes_report_with_location_coordinates(): void
    {
        BotSession::create([
            'phone_number' => '628123456789',
            'state' => 'WAITING_DESCRIPTION',
            'temp_data' => [
                'session_started_at' => now()->toIso8601String(),
                'title' => 'Sampah menumpuk',
                'latitude' => '-6.200000',
                'longitude' => '106.816666',
            ],
            'last_interaction_at' => now(),
        ]);

        $payload = [
            'sender' => '628123456789',
            'message' => 'Sampah menumpuk dan belum diangkut tiga hari.',
        ];

        $this->postJson(route('webhook.fonnte'), $payload);

        $this->assertDatabaseHas('reports', [
            'title' => 'Sampah menumpuk',
            'description' => 'Sampah menumpuk dan belum diangkut tiga hari.',
            'location_name' => 'Koordinat: -6.200000, 106.816666',
            'latitude' => -6.200000,
            'longitude' => 106.816666,
            'status' => ReportStatus::SUBMITTED->value,
            'priority' => 'Medium',
        ]);

        $this->assertDatabaseHas('bot_sessions', [
            'phone_number' => '628123456789',
            'state' => 'IDLE',
        ]);

        $this->fonnteMock
            ->shouldHaveReceived('sendText')
            ->withArgs(function (mixed $target, mixed $message): bool {
                return (string) $target === '628123456789'
                    && is_string($message)
                    && str_contains($message, '/tracking?ticket=')
                    && str_contains($message, 'Tiket: *T-');
            })
            ->atLeast()
            ->once();
    }

    public function test_it_rejects_webhook_when_token_is_invalid(): void
    {
        config()->set('services.fonnte.webhook_token', 'secure-token');

        $response = $this->postJson(route('webhook.fonnte'), [
            'sender' => '628123456789',
            'message' => 'LAPOR',
        ]);

        $response->assertStatus(401)
            ->assertJson(['status' => 'unauthorized']);

        $this->assertDatabaseCount('bot_sessions', 0);
    }

    public function test_it_accepts_webhook_when_token_is_valid(): void
    {
        config()->set('services.fonnte.webhook_token', 'secure-token');

        $response = $this->withHeaders([
            'X-Fonnte-Token' => 'secure-token',
        ])->postJson(route('webhook.fonnte'), [
            'sender' => '628123456789',
            'message' => 'LAPOR',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('bot_sessions', [
            'phone_number' => '628123456789',
            'state' => 'WAITING_TITLE',
        ]);
    }

    public function test_it_rejects_webhook_when_token_is_only_sent_in_body(): void
    {
        config()->set('services.fonnte.webhook_token', 'secure-token');

        $response = $this->postJson(route('webhook.fonnte'), [
            'sender' => '628123456789',
            'message' => 'LAPOR',
            'token' => 'secure-token',
        ]);

        $response->assertStatus(401)
            ->assertJson(['status' => 'unauthorized']);

        $this->assertDatabaseCount('bot_sessions', 0);
    }

    public function test_it_normalizes_indonesia_sender_number_format(): void
    {
        $user = User::factory()->create([
            'phone' => '628123456789',
            'role' => Role::WARGA,
        ]);

        BotSession::create([
            'phone_number' => '628123456789',
            'state' => 'WAITING_DESCRIPTION',
            'temp_data' => [
                'session_started_at' => now()->toIso8601String(),
                'title' => 'Jalan rusak',
                'latitude' => '-6.210000',
                'longitude' => '106.820000',
            ],
            'last_interaction_at' => now(),
        ]);

        $this->postJson(route('webhook.fonnte'), [
            'sender' => '08123456789',
            'message' => 'Ada lubang besar di jalan desa.',
        ])->assertStatus(200);

        $this->assertDatabaseHas('reports', [
            'user_id' => $user->id,
            'title' => 'Jalan rusak',
            'status' => ReportStatus::SUBMITTED->value,
            'priority' => 'Medium',
            'location_name' => 'Koordinat: -6.210000, 106.820000',
        ]);
    }

    public function test_it_returns_unprocessable_for_invalid_sender(): void
    {
        $response = $this->postJson(route('webhook.fonnte'), [
            'sender' => 'abc',
            'message' => 'LAPOR',
        ]);

        $response->assertStatus(422)
            ->assertJson(['status' => 'invalid_sender']);

        $this->assertDatabaseCount('bot_sessions', 0);
    }

    public function test_it_replies_when_image_is_sent_but_photo_feature_is_not_supported(): void
    {
        BotSession::create([
            'phone_number' => '628123456789',
            'state' => 'WAITING_LOCATION_COORDINATES',
            'temp_data' => ['title' => 'Jalan rusak'],
            'last_interaction_at' => now(),
        ]);

        $response = $this->postJson(route('webhook.fonnte'), [
            'sender' => '628123456789',
            'message' => '',
            'file' => 'https://example.test/foto.jpg',
        ]);

        $response->assertStatus(200)
            ->assertJson(['detail' => 'image_not_supported']);

        $this->assertDatabaseHas('bot_sessions', [
            'phone_number' => '628123456789',
            'state' => 'WAITING_LOCATION_COORDINATES',
        ]);
    }

    public function test_it_expires_stale_session_when_user_returns_after_long_pause(): void
    {
        BotSession::create([
            'phone_number' => '628123456789',
            'state' => 'WAITING_LOCATION_OPTION',
            'temp_data' => ['title' => 'Lampu jalan mati'],
            'last_interaction_at' => now()->subMinutes(45),
        ]);

        $response = $this->postJson(route('webhook.fonnte'), [
            'sender' => '628123456789',
            'message' => 'RT 03 RW 02',
        ]);

        $response->assertStatus(200)
            ->assertJson(['status' => 'session_expired']);

        $this->assertDatabaseHas('bot_sessions', [
            'phone_number' => '628123456789',
            'state' => 'IDLE',
        ]);

        $session = BotSession::query()->where('phone_number', '628123456789')->first();
        $this->assertNull($session?->temp_data);
    }

    public function test_it_expires_session_based_on_duration_since_lapor_even_if_recently_active(): void
    {
        BotSession::create([
            'phone_number' => '628123456789',
            'state' => 'WAITING_DESCRIPTION',
            'temp_data' => [
                'session_started_at' => now()->subMinutes(31)->toIso8601String(),
                'title' => 'Drainase mampet',
            ],
            'last_interaction_at' => now(),
        ]);

        $response = $this->postJson(route('webhook.fonnte'), [
            'sender' => '628123456789',
            'message' => 'Air meluap saat hujan deras.',
        ]);

        $response->assertStatus(200)
            ->assertJson(['status' => 'session_expired']);

        $this->assertDatabaseHas('bot_sessions', [
            'phone_number' => '628123456789',
            'state' => 'IDLE',
        ]);
        $this->assertDatabaseCount('reports', 0);
    }
}
