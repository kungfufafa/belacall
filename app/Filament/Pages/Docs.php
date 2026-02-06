<?php

namespace App\Filament\Pages;

use App\Enums\Role;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class Docs extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'Docs';

    protected static ?int $navigationSort = 99;

    protected static ?string $title = 'Panduan Peran';

    protected string $view = 'filament.pages.docs';

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        if (! $user) {
            return false;
        }

        return in_array($user->role, [Role::ADMIN, Role::PIMPINAN, Role::OPERATOR], true);
    }
}
