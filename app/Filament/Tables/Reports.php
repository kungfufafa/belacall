<?php

namespace App\Filament\Tables;

use App\Enums\ReportCategory;
use App\Enums\ReportStatus;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class Reports
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('ticket_number')
                    ->label('Nomor Tiket')
                    ->searchable(),
                TextColumn::make('title')
                    ->label('Judul')
                    ->searchable()
                    ->limit(50),
                TextColumn::make('description')
                    ->label('Deskripsi')
                    ->limit(100)
                    ->toggleable(),
                TextColumn::make('category')
                    ->label('Kategori')
                    ->badge()
                    ->color(fn (ReportCategory $state): string => match ($state) {
                        ReportCategory::GENERAL => 'gray',
                        ReportCategory::INFRASTRUKTUR => 'primary',
                        ReportCategory::SAMPAH => 'warning',
                        ReportCategory::KEAMANAN => 'danger',
                        ReportCategory::PELAYANAN => 'info',
                        ReportCategory::LAINNYA => 'gray',
                    }),
                TextColumn::make('location_name')
                    ->label('Lokasi')
                    ->searchable(),
                TextColumn::make('user.name')
                    ->label('Pelapor')
                    ->toggleable()
                    ->searchable(),
                TextColumn::make('assignee.name')
                    ->label('Petugas')
                    ->toggleable()
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (ReportStatus $state): string => match ($state) {
                        ReportStatus::SUBMITTED => 'warning',
                        ReportStatus::VERIFIED => 'primary',
                        ReportStatus::IN_PROGRESS => 'warning',
                        ReportStatus::RESOLVED => 'success',
                        ReportStatus::CLOSED => 'gray',
                        ReportStatus::REJECTED => 'danger',
                        ReportStatus::NEEDS_REVISION => 'warning',
                    }),
                TextColumn::make('created_at')
                    ->label('Tanggal Dibuat')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(ReportStatus::class),
                SelectFilter::make('category')
                    ->label('Kategori')
                    ->options(ReportCategory::class),
            ])
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->with(['user', 'assignee']));
    }
}
