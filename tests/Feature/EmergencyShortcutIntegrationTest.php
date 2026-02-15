<?php

namespace Tests\Feature;

use App\Models\BotSession;
use App\Models\EmergencyShortcut;
use App\Services\TelegramService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmergencyShortcutIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_displays_active_emergency_shortcuts(): void
    {
        $active = EmergencyShortcut::factory()->create([
            'name' => 'Ambulans',
            'phone_number' => '118',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('Kontak Darurat')
            ->assertSee('Ambulans')
            ->assertSee('118');
    }

    public function test_landing_page_hides_inactive_shortcuts(): void
    {
        EmergencyShortcut::factory()->inactive()->create([
            'name' => 'Hidden Service',
            'phone_number' => '999',
        ]);

        $response = $this->get('/');

        $response->assertOk()
            ->assertDontSee('Hidden Service')
            ->assertDontSee('Kontak Darurat');
    }

    public function test_telegram_darurat_command_lists_shortcuts(): void
    {
        $this->mock(TelegramService::class, function ($mock) {
            $mock->shouldReceive('sendText')->andReturn(['ok' => true, 'fake' => true]);
            $mock->shouldReceive('requestContact')->andReturn(['ok' => true, 'fake' => true]);
            $mock->shouldReceive('sendTextRemoveKeyboard')->andReturn(['ok' => true, 'fake' => true]);
        });

        EmergencyShortcut::factory()->create([
            'name' => 'Ambulans',
            'phone_number' => '118',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        EmergencyShortcut::factory()->create([
            'name' => 'Polisi',
            'phone_number' => '110',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $chatId = '12345';
        BotSession::create([
            'telegram_chat_id' => $chatId,
            'phone_number' => '628123456789',
            'state' => 'IDLE',
            'last_interaction_at' => now(),
        ]);

        $response = $this->postJson('/webhook/telegram', [
            'update_id' => random_int(100000, 999999),
            'message' => [
                'message_id' => random_int(1, 99999),
                'from' => ['id' => (int) $chatId, 'first_name' => 'Test'],
                'chat' => ['id' => (int) $chatId, 'type' => 'private'],
                'text' => '/darurat',
            ],
        ]);

        $response->assertOk()
            ->assertJson(['status' => 'processed', 'detail' => 'emergency_shortcuts']);
    }

    public function test_telegram_darurat_command_with_no_shortcuts(): void
    {
        $this->mock(TelegramService::class, function ($mock) {
            $mock->shouldReceive('sendText')->andReturn(['ok' => true, 'fake' => true]);
            $mock->shouldReceive('requestContact')->andReturn(['ok' => true, 'fake' => true]);
            $mock->shouldReceive('sendTextRemoveKeyboard')->andReturn(['ok' => true, 'fake' => true]);
        });

        $chatId = '12345';
        BotSession::create([
            'telegram_chat_id' => $chatId,
            'phone_number' => '628123456789',
            'state' => 'IDLE',
            'last_interaction_at' => now(),
        ]);

        $response = $this->postJson('/webhook/telegram', [
            'update_id' => random_int(100000, 999999),
            'message' => [
                'message_id' => random_int(1, 99999),
                'from' => ['id' => (int) $chatId, 'first_name' => 'Test'],
                'chat' => ['id' => (int) $chatId, 'type' => 'private'],
                'text' => 'darurat',
            ],
        ]);

        $response->assertOk()
            ->assertJson(['status' => 'processed', 'detail' => 'emergency_shortcuts']);
    }

    public function test_telegram_darurat_works_with_slash_prefix(): void
    {
        $this->mock(TelegramService::class, function ($mock) {
            $mock->shouldReceive('sendText')->andReturn(['ok' => true, 'fake' => true]);
            $mock->shouldReceive('requestContact')->andReturn(['ok' => true, 'fake' => true]);
            $mock->shouldReceive('sendTextRemoveKeyboard')->andReturn(['ok' => true, 'fake' => true]);
        });

        EmergencyShortcut::factory()->create([
            'name' => 'SAR',
            'phone_number' => '115',
            'is_active' => true,
        ]);

        $chatId = '67890';
        BotSession::create([
            'telegram_chat_id' => $chatId,
            'phone_number' => '628987654321',
            'state' => 'IDLE',
            'last_interaction_at' => now(),
        ]);

        $response = $this->postJson('/webhook/telegram', [
            'update_id' => random_int(100000, 999999),
            'message' => [
                'message_id' => random_int(1, 99999),
                'from' => ['id' => (int) $chatId, 'first_name' => 'Test'],
                'chat' => ['id' => (int) $chatId, 'type' => 'private'],
                'text' => '/darurat',
            ],
        ]);

        $response->assertOk()
            ->assertJson(['status' => 'processed', 'detail' => 'emergency_shortcuts']);
    }
}
