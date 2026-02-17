<?php

namespace App\Filament\Widgets;

use App\Models\Report;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RecentReports extends TableWidget
{
    protected static ?int $sort = -2;

    protected static ?string $heading = 'Laporan Terbaru';

    public function table(Table $table): Table
    {
        $query = Report::query()
            ->with(['user', 'assignee'])
            ->latest()
            ->limit(5);

        return $table
            ->query($query)
            ->columns([
                TextColumn::make('ticket_number')
                    ->label('Nomor Tiket')
                    ->searchable(),
                TextColumn::make('title')
                    ->label('Judul')
                    ->limit(50)
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge(),
                TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultPaginationPageOption(5);
    }
}
