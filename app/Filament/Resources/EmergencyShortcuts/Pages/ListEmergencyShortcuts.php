<?php

namespace App\Filament\Resources\EmergencyShortcuts\Pages;

use App\Filament\Resources\EmergencyShortcuts\EmergencyShortcutResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;

class ListEmergencyShortcuts extends ListRecords
{
    protected static string $resource = EmergencyShortcutResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->modal()
                ->slideOver()
                ->modalWidth(Width::Medium),
        ];
    }
}
