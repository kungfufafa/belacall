<?php

namespace App\Filament\Resources\Reports\Schemas;

use App\Enums\ReportCategory;
use App\Enums\ReportStatus;
use App\Models\Report;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ReportForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(3)
                    ->schema([
                        Group::make()
                            ->schema([
                                Section::make('Informasi Laporan')
                                    ->schema([
                                        TextInput::make('title')
                                            ->label('Judul')
                                            ->required()
                                            ->maxLength(255),
                                        Textarea::make('description')
                                            ->label('Deskripsi')
                                            ->required()
                                            ->rows(5),
                                        TextInput::make('location_name')
                                            ->label('Nama Lokasi')
                                            ->required()
                                            ->maxLength(255),
                                        Fieldset::make('Koordinat Lokasi')
                                            ->schema([
                                                TextInput::make('latitude')
                                                    ->label('Latitude')
                                                    ->numeric()
                                                    ->rules(['required_without:longitude']),
                                                TextInput::make('longitude')
                                                    ->label('Longitude')
                                                    ->numeric()
                                                    ->rules(['required_without:latitude']),
                                            ])
                                            ->columns(2),
                                    ]),
                            ])
                            ->columnSpan(2),

                        Group::make()
                            ->schema([
                                Section::make('Status & Penugasan')
                                    ->schema([
                                        Select::make('category')
                                            ->label('Kategori')
                                            ->options(ReportCategory::class)
                                            ->required()
                                            ->disabled(fn (?Report $record): bool => $record && self::isCategoryLocked($record->status)),
                                        Select::make('status')
                                            ->label('Status')
                                            ->options(ReportStatus::class)
                                            ->required()
                                            ->default(ReportStatus::SUBMITTED),
                                        Select::make('assignee_id')
                                            ->label('Petugas')
                                            ->relationship(
                                                name: 'assignee',
                                                titleAttribute: 'name'
                                            )
                                            ->searchable()
                                            ->preload(),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    private static function isCategoryLocked(mixed $status): bool
    {
        $value = $status instanceof ReportStatus ? $status->value : (string) $status;

        return in_array($value, [
            ReportStatus::VERIFIED->value,
            ReportStatus::IN_PROGRESS->value,
            ReportStatus::RESOLVED->value,
            ReportStatus::CLOSED->value,
        ], true);
    }
}
