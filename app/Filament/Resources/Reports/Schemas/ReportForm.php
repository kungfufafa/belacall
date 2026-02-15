<?php

namespace App\Filament\Resources\Reports\Schemas;

use App\Enums\ReportStatus;
use App\Filament\Resources\Reports\ReportResource;
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
                                        Select::make('priority')
                                            ->label('Prioritas')
                                            ->options(fn (): array => ReportResource::priorityOptionsWithSla())
                                            ->placeholder('Ditentukan saat assign')
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->helperText('Prioritas ditetapkan saat assign operator oleh Lurah/Pimpinan/Admin.'),
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
                                            ->preload()
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->helperText('Penugasan operator dilakukan melalui aksi Assign Operator.'),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
