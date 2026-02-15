<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use App\Notifications\LoginMagicLinkNotification;
use App\Services\MagicLinkLoginService;
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
use Illuminate\Validation\ValidationException;
use SensitiveParameter;

class Login extends SimplePage
{
    use WithRateLimiting;

    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    public string $loginMethod = 'email'; // 'email' or 'magic_link'

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
        if ($this->loginMethod === 'magic_link') {
            return 'Masuk dengan Email Magic Link';
        }

        return 'Masuk ke Akun';
    }

    public function getSubheading(): string|Htmlable|null
    {
        if ($this->loginMethod === 'magic_link') {
            return 'Kami akan mengirim tautan login ke email Anda.';
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
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getRememberFormComponent(),
                $this->getMagicLinkEmailFormComponent(),
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

    protected function getMagicLinkEmailFormComponent(): Component
    {
        return TextInput::make('magic_link_email')
            ->label('Email')
            ->email()
            ->required()
            ->autocomplete()
            ->autofocus()
            ->visible(fn (): bool => $this->loginMethod === 'magic_link');
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getLoginMethodToggle(),
                $this->getEmailFormContentComponent(),
                $this->getMagicLinkFormContentComponent(),
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
                Action::make('switchToMagicLink')
                    ->label('Email Magic Link')
                    ->color(fn (): string => $this->loginMethod === 'magic_link' ? 'primary' : 'gray')
                    ->action(fn () => $this->switchLoginMethod('magic_link')),
            ])->fullWidth(),
        ]);
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

    public function getMagicLinkFormContentComponent(): Component
    {
        return Form::make([EmbeddedSchema::make('form')])
            ->id('magicLinkForm')
            ->livewireSubmitHandler('sendMagicLink')
            ->footer([
                Actions::make($this->getMagicLinkFormActions())
                    ->fullWidth(),
            ])
            ->visible(fn (): bool => $this->loginMethod === 'magic_link');
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
    protected function getMagicLinkFormActions(): array
    {
        return [
            $this->getSendMagicLinkAction(),
        ];
    }

    protected function getAuthenticateFormAction(): Action
    {
        return Action::make('authenticate')
            ->label('Masuk')
            ->submit('authenticate');
    }

    protected function getSendMagicLinkAction(): Action
    {
        return Action::make('sendMagicLink')
            ->label('Kirim Magic Link')
            ->submit('sendMagicLink');
    }

    public function switchLoginMethod(string $method): void
    {
        $this->loginMethod = $method;
        $this->form->fill();
    }

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

    public function sendMagicLink(): void
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
        $email = mb_strtolower(trim((string) ($data['magic_link_email'] ?? '')));

        $user = User::query()->where('email', $email)->first();
        $panel = Filament::getCurrentOrDefaultPanel();

        if (($user instanceof User) && $user->canAccessPanel($panel)) {
            $magicLinkLoginService = app(MagicLinkLoginService::class);
            $loginUrl = $magicLinkLoginService->issue($user);

            $user->notify(new LoginMagicLinkNotification($loginUrl));
        }

        Notification::make()
            ->title('Cek Email Anda')
            ->body('Jika email terdaftar, tautan login sudah dikirim.')
            ->success()
            ->send();
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
}
