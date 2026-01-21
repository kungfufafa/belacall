<?php

namespace App\Filament\Resources\ReportResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EvidencesRelationManager extends RelationManager
{
    protected static string $relationship = 'evidences';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\FileUpload::make('file_path')
                    ->label('Bukti Foto/Video')
                    ->image()
                    ->directory('evidence')
                    ->required(),
                Forms\Components\Hidden::make('file_type')
                    ->default('image'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('file_path')
            ->columns([
                Tables\Columns\ImageColumn::make('file_path')
                    ->label('Preview')
                    ->disk('public'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Diunggah')
                    ->dateTime(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
