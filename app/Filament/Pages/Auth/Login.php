<?php

namespace App\Filament\Pages\Auth;

use App\Services\OtpService;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Filament\Actions\Action;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Facades\Filament;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Models\Contracts\FilamentUser;
use Filament\Notifications\Notification;
use Filament\Pages\SimplePage;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Schema;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use SensitiveParameter;

class Login extends SimplePage
{
    use WithRateLimiting;

    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    public string $loginMethod = 'email'; // 'email' or 'otp'

    #[Locked]
    public string $otpStep = 'phone'; // 'phone' or 'verify'

    #[Locked]
    public ?string $phone = null;

    #[Locked]
    public int $cooldown = 0;

    public function mount(): void
    {
        if (Filament::auth()->check()) {
            redirect()->intended(Filament::getUrl());
        }

        $this->form->fill();
    }

    public function getTitle(): string|Htmlable
    {
        return 'Masuk';
    }

    public function getHeading(): string|Htmlable|null
    {
        if ($this->loginMethod === 'otp' && $this->otpStep === 'verify') {
            return 'Verifikasi Kode OTP';
        }

        return 'Masuk ke Akun';
    }

    public function getSubheading(): string|Htmlable|null
    {
        if ($this->loginMethod === 'otp' && $this->otpStep === 'verify') {
            return "Kode OTP telah dikirim ke {$this->phone}";
        }

        return null;
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema
            ->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Email/Password form components
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getRememberFormComponent(),
                // OTP form components
                $this->getPhoneFormComponent(),
                $this->getOtpFormComponent(),
            ]);
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label('Email')
            ->email()
            ->required()
            ->autocomplete()
            ->autofocus()
            ->visible(fn (): bool => $this->loginMethod === 'email');
    }

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label('Password')
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->autocomplete('current-password')
            ->required()
            ->visible(fn (): bool => $this->loginMethod === 'email');
    }

    protected function getRememberFormComponent(): Component
    {
        return Checkbox::make('remember')
            ->label('Ingat saya')
            ->visible(fn (): bool => $this->loginMethod === 'email');
    }

    protected function getPhoneFormComponent(): Component
    {
        return TextInput::make('phone')
            ->label('Nomor HP')
            ->placeholder('08xxxxxxxxxx')
            ->tel()
            ->required()
            ->maxLength(15)
            ->autofocus()
            ->extraInputAttributes(['inputmode' => 'numeric'])
            ->visible(fn (): bool => $this->loginMethod === 'otp' && $this->otpStep === 'phone');
    }

    protected function getOtpFormComponent(): Component
    {
        return Group::make([
            TextInput::make('otp')
                ->label('Kode OTP')
                ->placeholder('Masukkan 6 digit kode')
                ->required()
                ->maxLength(6)
                ->autofocus()
                ->extraInputAttributes(['inputmode' => 'numeric']),
        ])
            ->visible(fn (): bool => $this->loginMethod === 'otp' && $this->otpStep === 'verify');
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getLoginMethodToggle(),
                $this->getEmailFormContentComponent(),
                $this->getOtpPhoneFormContentComponent(),
                $this->getOtpVerifyFormContentComponent(),
            ]);
    }

    protected function getLoginMethodToggle(): Component
    {
        return Group::make([
            \Filament\Schemas\Components\Actions::make([
                Action::make('switchToEmail')
                    ->label('Email & Password')
                    ->color(fn (): string => $this->loginMethod === 'email' ? 'primary' : 'gray')
                    ->action(fn () => $this->switchLoginMethod('email')),
                Action::make('switchToOtp')
                    ->label('OTP WhatsApp')
                    ->color(fn (): string => $this->loginMethod === 'otp' ? 'primary' : 'gray')
                    ->action(fn () => $this->switchLoginMethod('otp')),
            ])->fullWidth(),
        ])->visible(fn (): bool => $this->otpStep === 'phone');
    }

    public function getEmailFormContentComponent(): Component
    {
        return Form::make([EmbeddedSchema::make('form')])
            ->id('emailForm')
            ->livewireSubmitHandler('authenticate')
            ->footer([
                Actions::make($this->getEmailFormActions())
                    ->fullWidth(),
            ])
            ->visible(fn (): bool => $this->loginMethod === 'email');
    }

    public function getOtpPhoneFormContentComponent(): Component
    {
        return Form::make([EmbeddedSchema::make('form')])
            ->id('phoneForm')
            ->livewireSubmitHandler('sendOtp')
            ->footer([
                Actions::make($this->getPhoneFormActions())
                    ->fullWidth(),
            ])
            ->visible(fn (): bool => $this->loginMethod === 'otp' && $this->otpStep === 'phone');
    }

    public function getOtpVerifyFormContentComponent(): Component
    {
        return Form::make([EmbeddedSchema::make('form')])
            ->id('otpForm')
            ->livewireSubmitHandler('verifyOtp')
            ->footer([
                Actions::make($this->getOtpFormActions())
                    ->fullWidth(),
            ])
            ->visible(fn (): bool => $this->loginMethod === 'otp' && $this->otpStep === 'verify');
    }

    /**
     * @return array<Action>
     */
    protected function getEmailFormActions(): array
    {
        return [
            $this->getAuthenticateFormAction(),
        ];
    }

    /**
     * @return array<Action>
     */
    protected function getPhoneFormActions(): array
    {
        return [
            $this->getSendOtpAction(),
        ];
    }

    /**
     * @return array<Action>
     */
    protected function getOtpFormActions(): array
    {
        return [
            $this->getVerifyOtpAction(),
            $this->getBackAction(),
            $this->getResendOtpAction(),
        ];
    }

    protected function getAuthenticateFormAction(): Action
    {
        return Action::make('authenticate')
            ->label('Masuk')
            ->submit('authenticate');
    }

    protected function getSendOtpAction(): Action
    {
        return Action::make('sendOtp')
            ->label('Kirim Kode OTP')
            ->submit('sendOtp');
    }

    protected function getVerifyOtpAction(): Action
    {
        return Action::make('verifyOtp')
            ->label('Verifikasi')
            ->submit('verifyOtp');
    }

    protected function getBackAction(): Action
    {
        return Action::make('back')
            ->label('Ganti Nomor')
            ->color('gray')
            ->action('goBack');
    }

    protected function getResendOtpAction(): Action
    {
        return Action::make('resendOtp')
            ->label(fn (): string => $this->cooldown > 0 ? "Kirim Ulang ({$this->cooldown}s)" : 'Kirim Ulang OTP')
            ->color('gray')
            ->link()
            ->disabled(fn (): bool => $this->cooldown > 0)
            ->action('resendOtp');
    }

    public function switchLoginMethod(string $method): void
    {
        $this->loginMethod = $method;
        $this->otpStep = 'phone';
        $this->form->fill();
    }

    // ==================== EMAIL/PASSWORD AUTHENTICATION ====================

    public function authenticate(): ?LoginResponse
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        $data = $this->form->getState();

        /** @var SessionGuard $authGuard */
        $authGuard = Filament::auth();

        $authProvider = $authGuard->getProvider();
        $credentials = $this->getCredentialsFromFormData($data);

        $user = $authProvider->retrieveByCredentials($credentials);

        if ((! $user) || (! $authProvider->validateCredentials($user, $credentials))) {
            $this->fireFailedEvent($authGuard, $user, $credentials);
            $this->throwFailureValidationException();
        }

        if (! $authGuard->attemptWhen($credentials, function (Authenticatable $user): bool {
            if (! ($user instanceof FilamentUser)) {
                return true;
            }

            return $user->canAccessPanel(Filament::getCurrentOrDefaultPanel());
        }, $data['remember'] ?? false)) {
            $this->fireFailedEvent($authGuard, $user, $credentials);
            $this->throwFailureValidationException();
        }

        session()->regenerate();

        return app(LoginResponse::class);
    }

    protected function getRateLimitedNotification(TooManyRequestsException $exception): ?Notification
    {
        return Notification::make()
            ->title('Terlalu Banyak Percobaan')
            ->body("Silakan coba lagi dalam {$exception->secondsUntilAvailable} detik.")
            ->danger();
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    protected function fireFailedEvent(Guard $guard, ?Authenticatable $user, #[SensitiveParameter] array $credentials): void
    {
        event(app(Failed::class, ['guard' => property_exists($guard, 'name') ? $guard->name : '', 'user' => $user, 'credentials' => $credentials]));
    }

    protected function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages([
            'data.email' => __('filament-panels::auth/pages/login.messages.failed'),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function getCredentialsFromFormData(#[SensitiveParameter] array $data): array
    {
        return [
            'email' => $data['email'],
            'password' => $data['password'],
        ];
    }

    // ==================== OTP AUTHENTICATION ====================

    public function sendOtp(): void
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            Notification::make()
                ->title('Terlalu Banyak Percobaan')
                ->body("Silakan coba lagi dalam {$exception->secondsUntilAvailable} detik.")
                ->danger()
                ->send();

            return;
        }

        $data = $this->form->getState();
        $phone = $this->normalizePhone($data['phone']);

        $otpService = app(OtpService::class);

        if (! $otpService->canRequestOtp($phone)) {
            $remaining = $otpService->getRemainingCooldown($phone);
            Notification::make()
                ->title('Mohon Tunggu')
                ->body("Anda dapat meminta OTP baru dalam {$remaining} detik.")
                ->warning()
                ->send();

            return;
        }

        $result = $otpService->send($phone);

        if (! $result) {
            Notification::make()
                ->title('Gagal Mengirim OTP')
                ->body('Terjadi kesalahan saat mengirim OTP. Silakan coba lagi.')
                ->danger()
                ->send();

            return;
        }

        $this->phone = $phone;
        $this->otpStep = 'verify';
        $this->cooldown = 60;

        Notification::make()
            ->title('OTP Terkirim')
            ->body('Kode OTP telah dikirim ke nomor WhatsApp Anda.')
            ->success()
            ->send();
    }

    public function verifyOtp(): void
    {
        try {
            $this->rateLimit(10);
        } catch (TooManyRequestsException $exception) {
            Notification::make()
                ->title('Terlalu Banyak Percobaan')
                ->body("Silakan coba lagi dalam {$exception->secondsUntilAvailable} detik.")
                ->danger()
                ->send();

            return;
        }

        $data = $this->form->getState();

        $otpService = app(OtpService::class);
        $result = $otpService->verify($this->phone, $data['otp']);

        if (! $result['valid']) {
            Notification::make()
                ->title('Verifikasi Gagal')
                ->body($result['message'])
                ->danger()
                ->send();

            return;
        }

        $user = $result['user'];

        // Check if user can access panel
        $panel = Filament::getCurrentPanel();
        if ($panel && ! $user->canAccessPanel($panel)) {
            Notification::make()
                ->title('Akses Ditolak')
                ->body('Anda tidak memiliki akses ke panel ini.')
                ->danger()
                ->send();

            return;
        }

        Auth::login($user, remember: true);

        session()->regenerate();

        Notification::make()
            ->title('Login Berhasil')
            ->body('Selamat datang!')
            ->success()
            ->send();

        redirect()->intended(Filament::getUrl());
    }

    public function resendOtp(): void
    {
        $otpService = app(OtpService::class);

        if (! $otpService->canRequestOtp($this->phone)) {
            $remaining = $otpService->getRemainingCooldown($this->phone);
            Notification::make()
                ->title('Mohon Tunggu')
                ->body("Anda dapat meminta OTP baru dalam {$remaining} detik.")
                ->warning()
                ->send();

            return;
        }

        $result = $otpService->send($this->phone);

        if ($result) {
            $this->cooldown = 60;
            Notification::make()
                ->title('OTP Terkirim')
                ->body('Kode OTP baru telah dikirim.')
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Gagal')
                ->body('Gagal mengirim OTP. Silakan coba lagi.')
                ->danger()
                ->send();
        }
    }

    public function goBack(): void
    {
        $this->otpStep = 'phone';
        $this->data['otp'] = null;
    }

    protected function normalizePhone(string $phone): string
    {
        // Remove non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Convert 08xxx to 628xxx
        if (str_starts_with($phone, '0')) {
            $phone = '62'.substr($phone, 1);
        }

        // Add 62 if not present
        if (! str_starts_with($phone, '62')) {
            $phone = '62'.$phone;
        }

        return $phone;
    }

    public function decrementCooldown(): void
    {
        if ($this->cooldown > 0) {
            $this->cooldown--;
        }
    }
}
