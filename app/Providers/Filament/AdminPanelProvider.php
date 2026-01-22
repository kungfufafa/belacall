<?php

namespace App\Providers\Filament;

use App\Enums\Role;
use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\DashboardAdmin;
use App\Filament\Pages\DashboardOperator;
use App\Filament\Pages\DashboardPimpinan;
use Filament\Facades\Filament;
use Filament\FontProviders\GoogleFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->login(Login::class)
            ->homeUrl(function (): ?string {
                $user = Filament::auth()->user();

                if (! $user) {
                    return null;
                }

                return match ($user->role) {
                    Role::ADMIN => DashboardAdmin::getUrl(),
                    Role::PIMPINAN => DashboardPimpinan::getUrl(),
                    Role::OPERATOR => DashboardOperator::getUrl(),
                    default => null,
                };
            })
            ->colors([
                'primary' => Color::Green,
                'secondary' => Color::Gray,
                'success' => Color::Blue,
                'warning' => Color::Yellow,
                'danger' => Color::Red,
                'info' => Color::Purple,
            ])
            ->font('Instrument Sans', provider: GoogleFontProvider::class)
            ->favicon(asset('favicon.ico'))
            ->sidebarCollapsibleOnDesktop()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->pages([
                DashboardAdmin::class,
                DashboardOperator::class,
                DashboardPimpinan::class,
            ])
            ->widgets([
                AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->databaseNotifications()
            ->viteTheme('resources/css/filament/admin/theme.css');
    }
}
