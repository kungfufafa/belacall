<?php

namespace App\Filament\Resources\SlaConfigs;

use App\Enums\Role;
use App\Filament\Resources\SlaConfigs\Pages\ListSlaConfigs;
use App\Filament\Resources\SlaConfigs\Schemas\SlaConfigForm;
use App\Filament\Resources\SlaConfigs\Tables\SlaConfigsTable;
use App\Models\SlaConfig;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SlaConfigResource extends Resource
{
    protected static ?string $model = SlaConfig::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static UnitEnum|string|null $navigationGroup = 'Pengaturan';

    protected static ?string $navigationLabel = 'Konfigurasi SLA';

    protected static ?string $modelLabel = 'Konfigurasi SLA';

    protected static ?string $pluralModelLabel = 'Konfigurasi SLA';

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return $user?->role === Role::ADMIN;
    }

    public static function form(Schema $schema): Schema
    {
        return SlaConfigForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SlaConfigsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSlaConfigs::route('/'),
        ];
    }
}
