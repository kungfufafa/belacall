<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReportResource\Pages;
use App\Filament\Resources\ReportResource\RelationManagers;
use App\Models\Report;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class ReportResource extends Resource
{
    protected static ?string $model = Report::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    
    protected static ?string $navigationLabel = 'Laporan Masuk';
    
    protected static ?string $pluralModelLabel = 'Laporan';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (Auth::user()->role === 'petugas') {
            return $query->where('assignee_id', Auth::id());
        }

        return $query;
    }

    public static function getNavigationBadge(): ?string
    {
        if (Auth::user()->role === 'petugas') {
            return static::getModel()::where('assignee_id', Auth::id())
                ->whereIn('status', ['VERIFIED', 'IN_PROGRESS'])
                ->count();
        }

        return static::getModel()::where('status', 'SUBMITTED')->count();
    }
    
    public static function getNavigationBadgeColor(): ?string
    {
        return static::getNavigationBadge() > 0 ? 'danger' : 'success';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Laporan')
                    ->schema([
                        Forms\Components\TextInput::make('ticket_number')
                            ->label('Nomor Tiket')
                            ->required()
                            ->readOnly(),
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->label('Pelapor')
                            ->required()
                            ->searchable(),
                        Forms\Components\TextInput::make('title')
                            ->label('Judul Laporan')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi')
                            ->columnSpanFull(),
                        Forms\Components\Select::make('category')
                            ->label('Kategori')
                            ->options([
                                'Infrastruktur' => 'Infrastruktur',
                                'Sampah' => 'Sampah',
                                'Keamanan' => 'Keamanan',
                                'Lainnya' => 'Lainnya',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('location_name')
                            ->label('Lokasi'),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('latitude')
                                    ->numeric(),
                                Forms\Components\TextInput::make('longitude')
                                    ->numeric(),
                            ]),
                    ])->columns(2),

                Forms\Components\Section::make('Status & Penugasan')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options([
                                'DRAFT' => 'Draft',
                                'SUBMITTED' => 'Submitted',
                                'VERIFIED' => 'Verified',
                                'IN_PROGRESS' => 'In Progress',
                                'RESOLVED' => 'Resolved',
                                'CLOSED' => 'Closed',
                                'REJECTED' => 'Rejected',
                            ])
                            ->required(),
                        Forms\Components\Select::make('assignee_id')
                            ->relationship('assignee', 'name')
                            ->label('Petugas Lapangan')
                            ->searchable()
                            ->preload(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('ticket_number')
                    ->label('Tiket')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->label('Judul')
                    ->searchable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Pelapor')
                    ->searchable(),
                Tables\Columns\TextColumn::make('category')
                    ->badge(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'DRAFT' => 'gray',
                        'SUBMITTED' => 'warning',
                        'VERIFIED' => 'info',
                        'IN_PROGRESS' => 'primary',
                        'RESOLVED' => 'success',
                        'CLOSED' => 'success',
                        'REJECTED' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('assignee.name')
                    ->label('Petugas'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'SUBMITTED' => 'Submitted',
                        'VERIFIED' => 'Verified',
                        'IN_PROGRESS' => 'In Progress',
                        'RESOLVED' => 'Resolved',
                    ]),
                Tables\Filters\SelectFilter::make('category'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('verifikasi')
                    ->label('Verifikasi')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Report $record) => $record->status === 'SUBMITTED' && Auth::user()->role === 'operator')
                    ->action(fn (Report $record) => $record->update(['status' => 'VERIFIED'])),
                
                Tables\Actions\Action::make('assign')
                    ->label('Tugaskan')
                    ->icon('heroicon-o-user-plus')
                    ->form([
                        Forms\Components\Select::make('assignee_id')
                            ->label('Pilih Petugas')
                            ->options(fn () => \App\Models\User::where('role', 'petugas')->pluck('name', 'id'))
                            ->required(),
                    ])
                    ->visible(fn (Report $record) => $record->status === 'VERIFIED' && Auth::user()->role === 'operator')
                    ->action(function (Report $record, array $data) {
                        $record->update([
                            'assignee_id' => $data['assignee_id'],
                            'status' => 'IN_PROGRESS'
                        ]);
                    }),

                Tables\Actions\Action::make('resolve')
                    ->label('Selesai')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Report $record) => $record->status === 'IN_PROGRESS' && (Auth::user()->role === 'petugas' || Auth::user()->role === 'operator'))
                    ->action(fn (Report $record) => $record->update(['status' => 'RESOLVED'])),

                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\EvidencesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReports::route('/'),
            'create' => Pages\CreateReport::route('/create'),
            'edit' => Pages\EditReport::route('/{record}/edit'),
        ];
    }
}
