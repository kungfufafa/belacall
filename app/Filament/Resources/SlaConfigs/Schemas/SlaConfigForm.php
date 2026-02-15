<?php

namespace App\Filament\Resources\SlaConfigs\Schemas;

use App\Enums\ReportPriority;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SlaConfigForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Konfigurasi SLA')
                    ->schema([
                        Select::make('priority')
                            ->label('Prioritas')
                            ->options(ReportPriority::class)
                            ->required()
                            ->disabled(),
                        TextInput::make('response_time_minutes')
                            ->label('Waktu Respon (menit)')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->suffix('menit'),
                        TextInput::make('resolution_time_minutes')
                            ->label('Waktu Penyelesaian (menit)')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->suffix('menit'),
                    ]),
            ])->columns(1);
    }
}
