<?php

namespace App\Filament\Resources\EmergencyShortcuts\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EmergencyShortcutForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Kontak Darurat')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('phone_number')
                            ->label('Nomor Telepon')
                            ->tel()
                            ->required()
                            ->maxLength(20),
                        Textarea::make('description')
                            ->label('Deskripsi')
                            ->rows(3),
                        FileUpload::make('icon_path')
                            ->label('Ikon')
                            ->image()
                            ->disk('public')
                            ->directory('emergency-shortcuts')
                            ->maxSize(1024)
                            ->nullable(),
                        TextInput::make('sort_order')
                            ->label('Urutan')
                            ->numeric()
                            ->required()
                            ->default(0)
                            ->minValue(0),
                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),
                    ]),
            ])->columns(1);
    }
}
