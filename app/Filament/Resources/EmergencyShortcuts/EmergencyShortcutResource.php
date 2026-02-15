<?php

namespace App\Filament\Resources\EmergencyShortcuts;

use App\Enums\Role;
use App\Filament\Resources\EmergencyShortcuts\Pages\ListEmergencyShortcuts;
use App\Filament\Resources\EmergencyShortcuts\Schemas\EmergencyShortcutForm;
use App\Filament\Resources\EmergencyShortcuts\Tables\EmergencyShortcutsTable;
use App\Models\EmergencyShortcut;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class EmergencyShortcutResource extends Resource
{
    protected static ?string $model = EmergencyShortcut::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhone;

    protected static UnitEnum|string|null $navigationGroup = 'Pengaturan';

    protected static ?string $navigationLabel = 'Kontak Darurat';

    protected static ?string $modelLabel = 'Kontak Darurat';

    protected static ?string $pluralModelLabel = 'Kontak Darurat';

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return $user?->role === Role::ADMIN;
    }

    public static function form(Schema $schema): Schema
    {
        return EmergencyShortcutForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EmergencyShortcutsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmergencyShortcuts::route('/'),
        ];
    }
}
