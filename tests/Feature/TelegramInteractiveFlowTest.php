<?php

namespace Tests\Feature;

use App\Models\BotSession;
use App\Services\TelegramService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TelegramInteractiveFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(TelegramService::class, function ($mock) {
            $mock->shouldReceive('sendText')->andReturn(['ok' => true, 'fake' => true]);
            $mock->shouldReceive('requestContact')->andReturn(['ok' => true, 'fake' => true]);
            $mock->shouldReceive('sendTextRemoveKeyboard')->andReturn(['ok' => true, 'fake' => true]);
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function telegramPayload(string $chatId, ?string $text = null, ?array $contact = null, ?array $location = null): array
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

        return [
            'update_id' => random_int(100000, 999999),
            'message' => $message,
        ];
    }

    private function createSessionWithPhone(string $chatId, string $phone): void
    {
        BotSession::create([
            'telegram_chat_id' => $chatId,
            'phone_number' => $phone,
            'state' => 'IDLE',
            'last_interaction_at' => now(),
        ]);
    }

    public function test_full_report_flow_text_only(): void
    {
        $chatId = '12345';
        $phone = '628123456789';

        $this->createSessionWithPhone($chatId, $phone);

        // Step 1: LAPOR
        $response = $this->postJson('/webhook/telegram', $this->telegramPayload($chatId, 'LAPOR'));
        $response->assertStatus(200);
        $this->assertDatabaseHas('bot_sessions', [
            'telegram_chat_id' => $chatId,
            'state' => 'WAITING_TITLE',
        ]);

        // Step 2: Title
        $this->postJson('/webhook/telegram', $this->telegramPayload($chatId, 'Jalan Rusak Parah'));
        $this->assertDatabaseHas('bot_sessions', [
            'telegram_chat_id' => $chatId,
            'state' => 'WAITING_LOCATION_OPTION',
        ]);

        $session = BotSession::where('telegram_chat_id', $chatId)->first();
        $this->assertEquals('Jalan Rusak Parah', $session->temp_data['title']);

        // Step 3: Skip location
        $this->postJson('/webhook/telegram', $this->telegramPayload($chatId, '2'));
        $this->assertDatabaseHas('bot_sessions', [
            'telegram_chat_id' => $chatId,
            'state' => 'WAITING_DESCRIPTION',
        ]);

        $session->refresh();
        $this->assertArrayNotHasKey('latitude', $session->temp_data);
        $this->assertArrayNotHasKey('longitude', $session->temp_data);

        // Step 4: Description → Finalize
        $this->postJson('/webhook/telegram', $this->telegramPayload($chatId, 'Lubang besar di tengah jalan, sudah 2 motor jatuh.'));
        $this->assertDatabaseHas('bot_sessions', [
            'telegram_chat_id' => $chatId,
            'state' => 'IDLE',
        ]);

        $this->assertDatabaseHas('reports', [
            'title' => 'Jalan Rusak Parah',
            'location_name' => null,
            'description' => 'Lubang besar di tengah jalan, sudah 2 motor jatuh.',
            'status' => 'SUBMITTED',
        ]);
    }

    public function test_full_report_flow_with_contact_sharing_first(): void
    {
        $chatId = '67890';

        // Step 0: First message triggers contact request
        $response = $this->postJson('/webhook/telegram', $this->telegramPayload($chatId, 'LAPOR'));
        $response->assertJson(['status' => 'awaiting_contact']);

        // Step 1: Share contact
        $response = $this->postJson('/webhook/telegram', $this->telegramPayload(
            $chatId,
            null,
            ['phone_number' => '6289876543210', 'user_id' => (int) $chatId]
        ));
        $response->assertJson(['status' => 'contact_registered']);

        // Step 2: LAPOR
        $response = $this->postJson('/webhook/telegram', $this->telegramPayload($chatId, 'LAPOR'));
        $response->assertStatus(200);
        $this->assertDatabaseHas('bot_sessions', [
            'telegram_chat_id' => $chatId,
            'state' => 'WAITING_TITLE',
        ]);

        // Step 3: Title
        $this->postJson('/webhook/telegram', $this->telegramPayload($chatId, 'Lampu Jalan Mati'));
        $this->assertDatabaseHas('bot_sessions', [
            'telegram_chat_id' => $chatId,
            'state' => 'WAITING_LOCATION_OPTION',
        ]);

        // Step 4: Skip location
        $this->postJson('/webhook/telegram', $this->telegramPayload($chatId, '2'));

        // Step 5: Description
        $this->postJson('/webhook/telegram', $this->telegramPayload($chatId, 'Lampu di RT 03 sudah mati 3 hari.'));

        $this->assertDatabaseHas('reports', [
            'title' => 'Lampu Jalan Mati',
            'description' => 'Lampu di RT 03 sudah mati 3 hari.',
            'status' => 'SUBMITTED',
        ]);
    }

    public function test_full_report_flow_with_location(): void
    {
        $chatId = '12345';

        $this->createSessionWithPhone($chatId, '628123456789');

        // LAPOR
        $this->postJson('/webhook/telegram', $this->telegramPayload($chatId, 'LAPOR'));

        // Title
        $this->postJson('/webhook/telegram', $this->telegramPayload($chatId, 'Saluran Air Tersumbat'));

        // Choose location: yes
        $this->postJson('/webhook/telegram', $this->telegramPayload($chatId, '1'));
        $this->assertDatabaseHas('bot_sessions', [
            'telegram_chat_id' => $chatId,
            'state' => 'WAITING_LOCATION_COORDINATES',
        ]);

        // Send location object
        $this->postJson('/webhook/telegram', $this->telegramPayload(
            $chatId,
            null,
            null,
            ['latitude' => -6.175, 'longitude' => 106.827]
        ));
        $this->assertDatabaseHas('bot_sessions', [
            'telegram_chat_id' => $chatId,
            'state' => 'WAITING_DESCRIPTION',
        ]);

        // Description
        $this->postJson('/webhook/telegram', $this->telegramPayload($chatId, 'Air meluap ke jalan saat hujan deras.'));
        $this->assertDatabaseHas('reports', [
            'title' => 'Saluran Air Tersumbat',
            'description' => 'Air meluap ke jalan saat hujan deras.',
            'status' => 'SUBMITTED',
        ]);
    }

    public function test_reset_flow_with_lapor(): void
    {
        $chatId = '12345';

        $this->createSessionWithPhone($chatId, '628123456789');

        $this->postJson('/webhook/telegram', $this->telegramPayload($chatId, 'LAPOR'));
        $this->assertDatabaseHas('bot_sessions', ['state' => 'WAITING_TITLE']);

        $this->postJson('/webhook/telegram', $this->telegramPayload($chatId, 'LAPOR'));
        $this->assertDatabaseHas('bot_sessions', ['state' => 'WAITING_TITLE']);

        $session = BotSession::where('telegram_chat_id', $chatId)->first();
        $this->assertIsArray($session->temp_data);
        $this->assertArrayHasKey('session_started_at', $session->temp_data);
    }

    public function test_cancel_flow(): void
    {
        $chatId = '12345';

        $this->createSessionWithPhone($chatId, '628123456789');

        $this->postJson('/webhook/telegram', $this->telegramPayload($chatId, 'LAPOR'));

        $response = $this->postJson('/webhook/telegram', $this->telegramPayload($chatId, 'BATAL'));

        $response->assertJson(['status' => 'cancelled']);
        $this->assertDatabaseHas('bot_sessions', ['state' => 'IDLE']);
    }
}
