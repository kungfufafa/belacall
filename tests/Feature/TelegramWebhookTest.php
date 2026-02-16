<?php

namespace Tests\Feature;

use App\Enums\ReportStatus;
use App\Enums\Role;
use App\Models\BotSession;
use App\Models\User;
use App\Notifications\ReportSubmittedForTriage;
use App\Services\TelegramService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Mockery\MockInterface;
use Tests\TestCase;

class TelegramWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected MockInterface $telegramMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->telegramMock = $this->mock(TelegramService::class, function (MockInterface $mock) {
            $mock->shouldReceive('sendText')->andReturn(['ok' => true, 'fake' => true]);
            $mock->shouldReceive('requestContact')->andReturn(['ok' => true, 'fake' => true]);
            $mock->shouldReceive('sendTextRemoveKeyboard')->andReturn(['ok' => true, 'fake' => true]);
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function telegramPayload(string $chatId, ?string $text = null, ?array $contact = null, ?array $location = null, ?array $photo = null): array
    {
        $message = [
            'message_id' => random_int(1, 99999),
            'from' => ['id' => (int) $chatId, 'first_name' => 'Test'],
            'chat' => ['id' => (int) $chatId, 'type' => 'private'],
        ];

        if ($text !== null) {
            $message['text'] = $text;
        }

        if ($contact !== null) {
            $message['contact'] = $contact;
        }

        if ($location !== null) {
            $message['location'] = $location;
        }

        if ($photo !== null) {
            $message['photo'] = $photo;
        }

        return [
            'update_id' => random_int(100000, 999999),
            'message' => $message,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $tempData
     */
    private function createSessionWithPhone(string $chatId, string $phone, string $state = 'IDLE', ?array $tempData = null): BotSession
    {
        return BotSession::create([
            'telegram_chat_id' => $chatId,
            'phone_number' => $phone,
            'state' => $state,
            'temp_data' => $tempData,
            'last_interaction_at' => now(),
        ]);
    }

    public function test_it_requests_contact_when_session_has_no_phone(): void
    {
        $response = $this->postJson(route('webhook.telegram'), $this->telegramPayload('12345', 'LAPOR'));

        $response->assertStatus(200)
            ->assertJson(['status' => 'awaiting_contact']);

        $this->telegramMock
            ->shouldHaveReceived('requestContact')
            ->atLeast()
            ->once();
    }

    public function test_it_registers_contact_when_user_shares_phone(): void
    {
        $response = $this->postJson(route('webhook.telegram'), $this->telegramPayload(
            '12345',
            null,
            ['phone_number' => '6281234567890', 'user_id' => 12345]
        ));

        $response->assertStatus(200)
            ->assertJson(['status' => 'contact_registered']);

        $this->assertDatabaseHas('bot_sessions', [
            'telegram_chat_id' => '12345',
            'phone_number' => '6281234567890',
        ]);

        $this->assertDatabaseHas('users', [
            'phone' => '6281234567890',
            'telegram_chat_id' => '12345',
        ]);
    }

    public function test_it_rejects_contact_when_shared_contact_does_not_belong_to_sender(): void
    {
        $response = $this->postJson(route('webhook.telegram'), $this->telegramPayload(
            '12345',
            null,
            ['phone_number' => '6281234567890', 'user_id' => 99999]
        ));

        $response->assertStatus(200)
            ->assertJson(['status' => 'invalid_contact_owner']);

        $this->assertDatabaseHas('bot_sessions', [
            'telegram_chat_id' => '12345',
            'phone_number' => null,
        ]);

        $this->assertDatabaseMissing('users', [
            'phone' => '6281234567890',
        ]);
    }

    public function test_it_starts_conversation_when_user_sends_lapor(): void
    {
        $this->createSessionWithPhone('12345', '628123456789');

        $response = $this->postJson(route('webhook.telegram'), $this->telegramPayload('12345', 'LAPOR'));

        $response->assertStatus(200);

        $this->assertDatabaseHas('bot_sessions', [
            'telegram_chat_id' => '12345',
            'state' => 'WAITING_TITLE',
        ]);

        $session = BotSession::query()->where('telegram_chat_id', '12345')->first();
        $this->assertIsString($session?->temp_data['session_started_at'] ?? null);
    }

    public function test_it_starts_conversation_when_user_sends_start_command(): void
    {
        $this->createSessionWithPhone('12345', '628123456789');

        $response = $this->postJson(route('webhook.telegram'), $this->telegramPayload('12345', '/start'));

        $response->assertStatus(200);

        $this->assertDatabaseHas('bot_sessions', [
            'telegram_chat_id' => '12345',
            'state' => 'WAITING_TITLE',
        ]);
    }

    public function test_it_saves_title_and_moves_to_location_option(): void
    {
        $this->createSessionWithPhone('12345', '628123456789', 'WAITING_TITLE');

        $this->postJson(route('webhook.telegram'), $this->telegramPayload('12345', 'Ada sampah numpuk di selokan'));

        $this->assertDatabaseHas('bot_sessions', [
            'telegram_chat_id' => '12345',
            'state' => 'WAITING_LOCATION_OPTION',
        ]);

        $session = BotSession::where('telegram_chat_id', '12345')->first();
        $this->assertSame('Ada sampah numpuk di selokan', $session?->temp_data['title']);
    }

    public function test_it_moves_to_location_coordinates_step_when_user_chooses_yes(): void
    {
        $this->createSessionWithPhone('12345', '628123456789', 'WAITING_LOCATION_OPTION', [
            'session_started_at' => now()->toIso8601String(),
            'title' => 'Sampah menumpuk',
        ]);

        $this->postJson(route('webhook.telegram'), $this->telegramPayload('12345', '1'));

        $this->assertDatabaseHas('bot_sessions', [
            'telegram_chat_id' => '12345',
            'state' => 'WAITING_LOCATION_COORDINATES',
        ]);
    }

    public function test_it_accepts_lat_long_directly_in_location_option_step(): void
    {
        $this->createSessionWithPhone('12345', '628123456789', 'WAITING_LOCATION_OPTION', [
            'session_started_at' => now()->toIso8601String(),
            'title' => 'Pohon tumbang',
        ]);

        $this->postJson(route('webhook.telegram'), $this->telegramPayload('12345', '-6.123456, 106.987654'))
            ->assertStatus(200);

        $this->assertDatabaseHas('bot_sessions', [
            'telegram_chat_id' => '12345',
            'state' => 'WAITING_DESCRIPTION',
        ]);

        $session = BotSession::where('telegram_chat_id', '12345')->first();
        $this->assertSame('-6.123456', $session?->temp_data['latitude']);
        $this->assertSame('106.987654', $session?->temp_data['longitude']);
    }

    public function test_it_accepts_location_object_and_moves_to_waiting_description(): void
    {
        $this->createSessionWithPhone('12345', '628123456789', 'WAITING_LOCATION_COORDINATES', [
            'session_started_at' => now()->toIso8601String(),
            'title' => 'Sampah menumpuk',
        ]);

        $this->postJson(route('webhook.telegram'), $this->telegramPayload(
            '12345',
            null,
            null,
            ['latitude' => -6.200000, 'longitude' => 106.816666]
        ));

        $this->assertDatabaseHas('bot_sessions', [
            'telegram_chat_id' => '12345',
            'state' => 'WAITING_DESCRIPTION',
        ]);

        $session = BotSession::where('telegram_chat_id', '12345')->first();
        $this->assertSame('-6.200000', $session?->temp_data['latitude']);
        $this->assertSame('106.816666', $session?->temp_data['longitude']);
    }

    public function test_it_skips_location_when_user_chooses_no(): void
    {
        $this->createSessionWithPhone('12345', '628123456789', 'WAITING_LOCATION_OPTION', [
            'session_started_at' => now()->toIso8601String(),
            'title' => 'Lampu jalan mati',
        ]);

        $this->postJson(route('webhook.telegram'), $this->telegramPayload('12345', '2'));

        $this->assertDatabaseHas('bot_sessions', [
            'telegram_chat_id' => '12345',
            'state' => 'WAITING_DESCRIPTION',
        ]);

        $session = BotSession::where('telegram_chat_id', '12345')->first();
        $this->assertArrayNotHasKey('latitude', $session?->temp_data ?? []);
        $this->assertArrayNotHasKey('longitude', $session?->temp_data ?? []);
    }

    public function test_it_finalizes_report_with_location_coordinates(): void
    {
        $this->createSessionWithPhone('12345', '628123456789', 'WAITING_DESCRIPTION', [
            'session_started_at' => now()->toIso8601String(),
            'title' => 'Sampah menumpuk',
            'latitude' => '-6.200000',
            'longitude' => '106.816666',
        ]);

        $this->postJson(route('webhook.telegram'), $this->telegramPayload('12345', 'Sampah menumpuk dan belum diangkut tiga hari.'));

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
            'telegram_chat_id' => '12345',
            'state' => 'IDLE',
        ]);

        $this->telegramMock
            ->shouldHaveReceived('sendText')
            ->withArgs(function (mixed $target, mixed $message): bool {
                return (string) $target === '12345'
                    && is_string($message)
                    && str_contains($message, '/tracking?ticket=')
                    && str_contains($message, 'Tiket: *T-');
            })
            ->atLeast()
            ->once();
    }

    public function test_it_notifies_pimpinan_when_report_is_created_from_telegram(): void
    {
        Notification::fake();

        $pimpinan = User::factory()->create(['role' => Role::PIMPINAN]);
        $admin = User::factory()->create(['role' => Role::ADMIN]);

        $this->createSessionWithPhone('12345', '628123456789', 'WAITING_DESCRIPTION', [
            'session_started_at' => now()->toIso8601String(),
            'title' => 'Jembatan retak',
        ]);

        $this->postJson(route('webhook.telegram'), $this->telegramPayload('12345', 'Struktur jembatan retak dan membahayakan pengguna.'))
            ->assertStatus(200);

        Notification::assertSentTo($pimpinan, ReportSubmittedForTriage::class);
        Notification::assertNotSentTo($admin, ReportSubmittedForTriage::class);
    }

    public function test_it_rejects_webhook_when_secret_is_invalid(): void
    {
        config()->set('services.telegram.webhook_secret', 'secure-secret');

        $response = $this->postJson(route('webhook.telegram'), $this->telegramPayload('12345', 'LAPOR'));

        $response->assertStatus(401)
            ->assertJson(['status' => 'unauthorized']);

        $this->assertDatabaseCount('bot_sessions', 0);
    }

    public function test_it_accepts_webhook_when_secret_is_valid(): void
    {
        config()->set('services.telegram.webhook_secret', 'secure-secret');

        $this->createSessionWithPhone('12345', '628123456789');

        $response = $this->withHeaders([
            'X-Telegram-Bot-Api-Secret-Token' => 'secure-secret',
        ])->postJson(route('webhook.telegram'), $this->telegramPayload('12345', 'LAPOR'));

        $response->assertStatus(200);

        $this->assertDatabaseHas('bot_sessions', [
            'telegram_chat_id' => '12345',
            'state' => 'WAITING_TITLE',
        ]);
    }

    public function test_it_rejects_webhook_when_secret_is_only_sent_in_body(): void
    {
        config()->set('services.telegram.webhook_secret', 'secure-secret');

        $payload = $this->telegramPayload('12345', 'LAPOR');
        $payload['secret_token'] = 'secure-secret';

        $response = $this->postJson(route('webhook.telegram'), $payload);

        $response->assertStatus(401)
            ->assertJson(['status' => 'unauthorized']);

        $this->assertDatabaseCount('bot_sessions', 0);
    }

    public function test_it_creates_user_and_links_report_when_finalizing(): void
    {
        $user = User::factory()->create([
            'phone' => '628123456789',
            'role' => Role::WARGA,
        ]);

        $this->createSessionWithPhone('12345', '628123456789', 'WAITING_DESCRIPTION', [
            'session_started_at' => now()->toIso8601String(),
            'title' => 'Jalan rusak',
            'latitude' => '-6.210000',
            'longitude' => '106.820000',
        ]);

        $this->postJson(route('webhook.telegram'), $this->telegramPayload('12345', 'Ada lubang besar di jalan desa.'))
            ->assertStatus(200);

        $this->assertDatabaseHas('reports', [
            'user_id' => $user->id,
            'title' => 'Jalan rusak',
            'status' => ReportStatus::SUBMITTED->value,
            'priority' => 'Medium',
            'location_name' => 'Koordinat: -6.210000, 106.820000',
        ]);
    }

    public function test_it_ignores_update_without_message(): void
    {
        $response = $this->postJson(route('webhook.telegram'), [
            'update_id' => 12345,
        ]);

        $response->assertStatus(200)
            ->assertJson(['status' => 'ignored']);

        $this->assertDatabaseCount('bot_sessions', 0);
    }

    public function test_it_replies_when_image_is_sent_but_photo_feature_is_not_supported(): void
    {
        $this->createSessionWithPhone('12345', '628123456789', 'WAITING_LOCATION_COORDINATES', [
            'title' => 'Jalan rusak',
        ]);

        $response = $this->postJson(route('webhook.telegram'), $this->telegramPayload(
            '12345',
            null,
            null,
            null,
            [['file_id' => 'abc123', 'width' => 100, 'height' => 100]]
        ));

        $response->assertStatus(200)
            ->assertJson(['detail' => 'image_not_supported']);

        $this->assertDatabaseHas('bot_sessions', [
            'telegram_chat_id' => '12345',
            'state' => 'WAITING_LOCATION_COORDINATES',
        ]);
    }

    public function test_it_expires_stale_session_when_user_returns_after_long_pause(): void
    {
        BotSession::create([
            'telegram_chat_id' => '12345',
            'phone_number' => '628123456789',
            'state' => 'WAITING_LOCATION_OPTION',
            'temp_data' => ['title' => 'Lampu jalan mati'],
            'last_interaction_at' => now()->subMinutes(45),
        ]);

        $response = $this->postJson(route('webhook.telegram'), $this->telegramPayload('12345', 'RT 03 RW 02'));

        $response->assertStatus(200)
            ->assertJson(['status' => 'session_expired']);

        $this->assertDatabaseHas('bot_sessions', [
            'telegram_chat_id' => '12345',
            'state' => 'IDLE',
        ]);

        $session = BotSession::query()->where('telegram_chat_id', '12345')->first();
        $this->assertNull($session?->temp_data);
    }

    public function test_it_expires_session_based_on_duration_since_lapor_even_if_recently_active(): void
    {
        BotSession::create([
            'telegram_chat_id' => '12345',
            'phone_number' => '628123456789',
            'state' => 'WAITING_DESCRIPTION',
            'temp_data' => [
                'session_started_at' => now()->subMinutes(31)->toIso8601String(),
                'title' => 'Drainase mampet',
            ],
            'last_interaction_at' => now(),
        ]);

        $response = $this->postJson(route('webhook.telegram'), $this->telegramPayload('12345', 'Air meluap saat hujan deras.'));

        $response->assertStatus(200)
            ->assertJson(['status' => 'session_expired']);

        $this->assertDatabaseHas('bot_sessions', [
            'telegram_chat_id' => '12345',
            'state' => 'IDLE',
        ]);
        $this->assertDatabaseCount('reports', 0);
    }
}
