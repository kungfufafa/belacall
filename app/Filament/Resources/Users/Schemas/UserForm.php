<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\Role;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Pengguna')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(),
                        TextInput::make('phone')
                            ->tel()
                            ->required()
                            ->maxLength(20)
                            ->unique(),
                        Select::make('role')
                            ->options(Role::class)
                            ->required()
                            ->default(Role::WARGA),
                    ])
                    ->columns(2),
                Section::make('Keamanan')
                    ->schema([
                        TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->required(fn (string $operation): bool => $operation === 'create'),
                    ])
                    ->columns(1),
            ]);
    }
}
