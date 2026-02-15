<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Filament\Pages\Auth\Login;
use App\Models\OtpCode;
use App\Models\User;
use App\Services\FonnteService;
use App\Services\OtpService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery\MockInterface;
use Tests\TestCase;

class OtpLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock FonnteService to avoid actual API calls
        $this->mock(FonnteService::class, function (MockInterface $mock) {
            $mock->shouldReceive('sendText')->andReturn(['status' => true]);
        });

        // Set the default panel for tests
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    // ==================== EMAIL/PASSWORD LOGIN TESTS ====================

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

    // ==================== OTP LOGIN TESTS ====================

    public function test_can_switch_to_otp_login(): void
    {
        Livewire::test(Login::class)
            ->call('switchLoginMethod', 'otp')
            ->assertSet('loginMethod', 'otp')
            ->assertSee('Nomor HP')
            ->assertSee('Kirim Kode OTP');
    }

    public function test_can_send_otp_to_valid_phone(): void
    {
        Livewire::test(Login::class)
            ->call('switchLoginMethod', 'otp')
            ->set('data.phone', '081234567890')
            ->call('sendOtp')
            ->assertSet('otpStep', 'verify')
            ->assertNotified('OTP Terkirim');

        $this->assertDatabaseHas('otp_codes', [
            'phone' => '6281234567890',
        ]);
    }

    public function test_phone_is_normalized_correctly(): void
    {
        Livewire::test(Login::class)
            ->call('switchLoginMethod', 'otp')
            ->set('data.phone', '08123456789')
            ->call('sendOtp');

        $this->assertDatabaseHas('otp_codes', [
            'phone' => '628123456789',
        ]);
    }

    public function test_user_phone_is_normalized_on_save(): void
    {
        $user = User::factory()->create([
            'phone' => '0812-345-678',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'phone' => '62812345678',
        ]);
    }

    public function test_can_verify_valid_otp(): void
    {
        $phone = '6281234567890';

        // Create user with proper role
        $user = User::factory()->create([
            'phone' => $phone,
            'role' => Role::OPERATOR,
        ]);

        // Start flow: switch to OTP and send OTP first
        $component = Livewire::test(Login::class)
            ->call('switchLoginMethod', 'otp')
            ->set('data.phone', '081234567890')
            ->call('sendOtp')
            ->assertSet('otpStep', 'verify');

        // Get the created OTP from database
        $otp = OtpCode::where('phone', $phone)->latest()->first();

        // Now verify the OTP
        $component
            ->set('data.otp', $otp->code)
            ->call('verifyOtp')
            ->assertNotified('Login Berhasil')
            ->assertRedirect();

        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_otp_shows_error(): void
    {
        $phone = '6281234567890';

        // Start flow: switch to OTP and send OTP first
        $component = Livewire::test(Login::class)
            ->call('switchLoginMethod', 'otp')
            ->set('data.phone', '081234567890')
            ->call('sendOtp')
            ->assertSet('otpStep', 'verify');

        // Now try with wrong OTP
        $component
            ->set('data.otp', '999999')
            ->call('verifyOtp')
            ->assertNotified('Verifikasi Gagal');

        $this->assertGuest();
    }

    public function test_expired_otp_shows_error(): void
    {
        $phone = '6281234567890';

        // Start flow: switch to OTP and send OTP first
        $component = Livewire::test(Login::class)
            ->call('switchLoginMethod', 'otp')
            ->set('data.phone', '081234567890')
            ->call('sendOtp')
            ->assertSet('otpStep', 'verify');

        // Get the created OTP and make it expired
        $otp = OtpCode::where('phone', $phone)->latest()->first();
        $otp->update(['expires_at' => now()->subMinutes(1)]);

        // Now try to verify
        $component
            ->set('data.otp', $otp->code)
            ->call('verifyOtp')
            ->assertNotified('Verifikasi Gagal');

        $this->assertGuest();
    }

    public function test_otp_attempts_are_limited(): void
    {
        $phone = '6281234567890';

        // Start flow: switch to OTP and send OTP first
        $component = Livewire::test(Login::class)
            ->call('switchLoginMethod', 'otp')
            ->set('data.phone', '081234567890')
            ->call('sendOtp')
            ->assertSet('otpStep', 'verify');

        // Get the created OTP and set max attempts
        $otp = OtpCode::where('phone', $phone)->latest()->first();
        $otp->update(['attempts' => 5]);

        // Now try to verify
        $component
            ->set('data.otp', $otp->code)
            ->call('verifyOtp')
            ->assertNotified('Verifikasi Gagal');

        $this->assertGuest();
    }

    public function test_new_user_is_created_on_first_login(): void
    {
        $phone = '6281234567890';

        // User does not exist
        $this->assertDatabaseMissing('users', ['phone' => $phone]);

        // Start flow: switch to OTP and send OTP first
        $component = Livewire::test(Login::class)
            ->call('switchLoginMethod', 'otp')
            ->set('data.phone', '081234567890')
            ->call('sendOtp')
            ->assertSet('otpStep', 'verify');

        // Get the created OTP
        $otp = OtpCode::where('phone', $phone)->latest()->first();

        // Verify OTP - this should create a user but fail panel access
        $component
            ->set('data.otp', $otp->code)
            ->call('verifyOtp');

        // User should be created with WARGA role
        $this->assertDatabaseHas('users', [
            'phone' => $phone,
            'role' => Role::WARGA->value,
        ]);
    }

    public function test_warga_cannot_access_admin_panel(): void
    {
        $phone = '6281234567890';

        // Create user with WARGA role
        User::factory()->create([
            'phone' => $phone,
            'role' => Role::WARGA,
        ]);

        // Start flow: switch to OTP and send OTP first
        $component = Livewire::test(Login::class)
            ->call('switchLoginMethod', 'otp')
            ->set('data.phone', '081234567890')
            ->call('sendOtp')
            ->assertSet('otpStep', 'verify');

        // Get the created OTP
        $otp = OtpCode::where('phone', $phone)->latest()->first();

        // Verify OTP
        $component
            ->set('data.otp', $otp->code)
            ->call('verifyOtp')
            ->assertNotified('Akses Ditolak');

        $this->assertGuest();
    }

    public function test_can_go_back_to_change_phone(): void
    {
        // Start flow: switch to OTP and send OTP first to get to verify step
        Livewire::test(Login::class)
            ->call('switchLoginMethod', 'otp')
            ->set('data.phone', '081234567890')
            ->call('sendOtp')
            ->assertSet('otpStep', 'verify')
            ->call('goBack')
            ->assertSet('otpStep', 'phone');
    }

    public function test_cooldown_prevents_immediate_resend(): void
    {
        $phone = '6281234567890';

        // Create recent OTP
        OtpCode::create([
            'phone' => $phone,
            'code' => '123456',
            'expires_at' => now()->addMinutes(5),
            'created_at' => now(),
        ]);

        $otpService = app(OtpService::class);
        $this->assertFalse($otpService->canRequestOtp($phone));
    }

    public function test_can_switch_between_login_methods(): void
    {
        Livewire::test(Login::class)
            ->assertSet('loginMethod', 'email')
            ->call('switchLoginMethod', 'otp')
            ->assertSet('loginMethod', 'otp')
            ->call('switchLoginMethod', 'email')
            ->assertSet('loginMethod', 'email');
    }
}
