<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Filament\Pages\Auth\Login;
use App\Models\User;
use App\Notifications\LoginMagicLinkNotification;
use App\Services\MagicLinkLoginService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class MagicLinkLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_login_page_displays_email_and_password_by_default(): void
    {
        Livewire::test(Login::class)
            ->assertSee('Email')
            ->assertSee('Password')
            ->assertSee('Masuk');
    }

    public function test_can_login_with_email_and_password(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => Role::ADMIN,
        ]);

        Livewire::test(Login::class)
            ->set('data.email', 'admin@test.com')
            ->set('data.password', 'password')
            ->call('authenticate')
            ->assertRedirect();

        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_email_password_shows_error(): void
    {
        User::factory()->create([
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => Role::ADMIN,
        ]);

        Livewire::test(Login::class)
            ->set('data.email', 'admin@test.com')
            ->set('data.password', 'wrong-password')
            ->call('authenticate')
            ->assertHasErrors(['data.email']);

        $this->assertGuest();
    }

    public function test_can_switch_to_magic_link_login(): void
    {
        Livewire::test(Login::class)
            ->call('switchLoginMethod', 'magic_link')
            ->assertSet('loginMethod', 'magic_link')
            ->assertSee('Email')
            ->assertSee('Kirim Magic Link');
    }

    public function test_can_send_magic_link_to_valid_email(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'operator@test.com',
            'role' => Role::OPERATOR,
        ]);

        Livewire::test(Login::class)
            ->call('switchLoginMethod', 'magic_link')
            ->set('data.magic_link_email', 'operator@test.com')
            ->call('sendMagicLink')
            ->assertNotified('Cek Email Anda');

        Notification::assertSentTo($user, LoginMagicLinkNotification::class);
    }

    public function test_magic_link_can_login_user_successfully(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'admin@test.com',
            'role' => Role::ADMIN,
        ]);

        Livewire::test(Login::class)
            ->call('switchLoginMethod', 'magic_link')
            ->set('data.magic_link_email', 'admin@test.com')
            ->call('sendMagicLink')
            ->assertNotified('Cek Email Anda');

        $magicLinkUrl = null;

        Notification::assertSentTo($user, LoginMagicLinkNotification::class, function (LoginMagicLinkNotification $notification) use (&$magicLinkUrl): bool {
            $magicLinkUrl = $notification->loginUrl;

            return str_contains($notification->loginUrl, 'token=');
        });

        $this->assertNotNull($magicLinkUrl);

        $response = $this->get($magicLinkUrl);

        $response->assertRedirect(Filament::getPanel('admin')->getUrl());
        $this->assertAuthenticatedAs($user);
    }

    public function test_magic_link_is_one_time_use_only(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@test.com',
            'role' => Role::ADMIN,
        ]);

        $magicLinkLoginService = app(MagicLinkLoginService::class);
        $magicLinkUrl = $magicLinkLoginService->issue($user);

        $this->get($magicLinkUrl)
            ->assertRedirect(Filament::getPanel('admin')->getUrl());

        $this->assertAuthenticatedAs($user);

        Auth::logout();

        $this->get($magicLinkUrl)->assertForbidden();

        $this->assertGuest();
    }

    public function test_tampered_magic_link_is_rejected(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@test.com',
            'role' => Role::ADMIN,
        ]);

        $magicLinkLoginService = app(MagicLinkLoginService::class);
        $magicLinkUrl = $magicLinkLoginService->issue($user);

        $tamperedUrl = preg_replace('/token=[^&]+/', 'token=invalid-token', $magicLinkUrl);

        $this->assertIsString($tamperedUrl);

        $this->get($tamperedUrl)->assertForbidden();

        $this->assertGuest();
    }

    public function test_warga_does_not_receive_magic_link_email(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'warga@test.com',
            'role' => Role::WARGA,
        ]);

        Livewire::test(Login::class)
            ->call('switchLoginMethod', 'magic_link')
            ->set('data.magic_link_email', 'warga@test.com')
            ->call('sendMagicLink')
            ->assertNotified('Cek Email Anda');

        Notification::assertNothingSent();

        $magicLinkLoginService = app(MagicLinkLoginService::class);
        $magicLinkUrl = $magicLinkLoginService->issue($user);

        $this->get($magicLinkUrl)->assertForbidden();

        $this->assertGuest();
    }

    public function test_can_switch_between_login_methods(): void
    {
        Livewire::test(Login::class)
            ->assertSet('loginMethod', 'email')
            ->call('switchLoginMethod', 'magic_link')
            ->assertSet('loginMethod', 'magic_link')
            ->call('switchLoginMethod', 'email')
            ->assertSet('loginMethod', 'email');
    }
}
