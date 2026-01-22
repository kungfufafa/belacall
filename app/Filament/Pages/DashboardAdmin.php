<?php

namespace App\Filament\Pages;

use App\Enums\Role;
use Filament\Facades\Filament;
use Filament\Pages\Dashboard;

class DashboardAdmin extends Dashboard
{
    protected static string $routePath = '/dashboard';

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return $user?->role === Role::ADMIN;
    }
}
