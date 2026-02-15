<?php

namespace App\Filament\Resources\SlaConfigs\Tables;

use App\Enums\ReportPriority;
use Filament\Actions\EditAction;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SlaConfigsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('priority')
                    ->label('Prioritas')
                    ->badge()
                    ->color(fn (ReportPriority $state): string => $state->color())
                    ->formatStateUsing(fn (ReportPriority $state): string => $state->label()),
                TextColumn::make('response_time_minutes')
                    ->label('Waktu Respon')
                    ->suffix(' menit')
                    ->sortable(),
                TextColumn::make('resolution_time_minutes')
                    ->label('Waktu Penyelesaian')
                    ->suffix(' menit')
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Terakhir Diubah')
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make()
                    ->modal()
                    ->slideOver()
                    ->color('warning')
                    ->modalWidth(Width::Medium),
            ]);
    }
}
