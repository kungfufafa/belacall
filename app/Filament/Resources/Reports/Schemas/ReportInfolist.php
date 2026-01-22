<?php

namespace App\Filament\Resources\Reports\Schemas;

use App\Enums\ReportCategory;
use App\Enums\ReportStatus;
use App\Models\ReportEvidence;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;

class ReportInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(3)
                    ->schema([
                        Group::make()
                            ->schema([
                                Section::make('Detail Laporan')
                                    ->schema([
                                        TextEntry::make('ticket_number')
                                            ->label('Nomor Tiket')
                                            ->copyable()
                                            ->placeholder('-'),
                                        TextEntry::make('title')
                                            ->label('Judul')
                                            ->placeholder('-')
                                            ->columnSpanFull(),
                                        TextEntry::make('description')
                                            ->label('Deskripsi')
                                            ->placeholder('-')
                                            ->columnSpanFull()
                                            ->prose(),
                                    ])
                                    ->columns(2),
                                Section::make('Lokasi')
                                    ->schema([
                                        TextEntry::make('location_name')
                                            ->label('Nama Lokasi')
                                            ->placeholder('-')
                                            ->columnSpanFull(),
                                        TextEntry::make('latitude')
                                            ->label('Latitude')
                                            ->placeholder('-'),
                                        TextEntry::make('longitude')
                                            ->label('Longitude')
                                            ->placeholder('-'),
                                    ])
                                    ->columns(2),
                                Section::make('Bukti Laporan')
                                    ->schema([
                                        RepeatableEntry::make('evidences')
                                            ->hiddenLabel()
                                            ->placeholder('Belum ada bukti yang diunggah.')
                                            ->schema([
                                                ImageEntry::make('file_path')
                                                    ->label('Preview')
                                                    ->imageSize(96)
                                                    ->square()
                                                    ->url(fn (?string $state): ?string => self::resolveEvidenceUrl($state), true)
                                                    ->visible(fn (ReportEvidence $record): bool => $record->file_type === 'image'),
                                                TextEntry::make('file_type')
                                                    ->label('Tipe')
                                                    ->badge()
                                                    ->formatStateUsing(fn (?string $state): string => strtoupper((string) $state)),
                                                TextEntry::make('file_path')
                                                    ->label('File')
                                                    ->formatStateUsing(fn (?string $state): string => self::evidenceName($state))
                                                    ->url(fn (?string $state): ?string => self::resolveEvidenceUrl($state), true)
                                                    ->columnSpanFull(),
                                                TextEntry::make('uploaded_by')
                                                    ->label('Diunggah Oleh')
                                                    ->placeholder('-'),
                                                TextEntry::make('created_at')
                                                    ->label('Waktu Upload')
                                                    ->dateTime(),
                                            ])
                                            ->columns(2),
                                    ]),
                                Section::make('Riwayat Aktivitas')
                                    ->schema([
                                        RepeatableEntry::make('histories')
                                            ->hiddenLabel()
                                            ->placeholder('Belum ada aktivitas.')
                                            ->table([
                                                TableColumn::make('Aksi'),
                                                TableColumn::make('Pelaksana'),
                                                TableColumn::make('Nilai Lama'),
                                                TableColumn::make('Nilai Baru'),
                                                TableColumn::make('Catatan'),
                                                TableColumn::make('Waktu'),
                                            ])
                                            ->schema([
                                                TextEntry::make('action')
                                                    ->label('Aksi')
                                                    ->badge(),
                                                TextEntry::make('user.name')
                                                    ->label('Pelaksana')
                                                    ->placeholder('-'),
                                                TextEntry::make('old_value')
                                                    ->label('Nilai Lama')
                                                    ->placeholder('-'),
                                                TextEntry::make('new_value')
                                                    ->label('Nilai Baru')
                                                    ->placeholder('-'),
                                                TextEntry::make('notes')
                                                    ->label('Catatan')
                                                    ->placeholder('-')
                                                    ->columnSpanFull(),
                                                TextEntry::make('created_at')
                                                    ->label('Waktu')
                                                    ->dateTime(),
                                            ]),
                                    ]),
                            ])
                            ->columnSpan(2),

                        Group::make()
                            ->schema([
                                Section::make('Status & Penugasan')
                                    ->schema([
                                        TextEntry::make('category')
                                            ->label('Kategori')
                                            ->badge()
                                            ->color(fn (ReportCategory $state): string => self::categoryColor($state)),
                                        TextEntry::make('status')
                                            ->label('Status')
                                            ->badge()
                                            ->color(fn (ReportStatus $state): string => $state->color())
                                            ->formatStateUsing(fn (ReportStatus $state): string => $state->label()),
                                        TextEntry::make('user.name')
                                            ->label('Pelapor')
                                            ->placeholder('-'),
                                        TextEntry::make('user.role')
                                            ->label('Peran Pelapor')
                                            ->badge()
                                            ->color(fn (\App\Enums\Role $state): string => self::roleColor($state)),
                                        TextEntry::make('assignee.name')
                                            ->label('Petugas')
                                            ->placeholder('Belum ditugaskan'),
                                        TextEntry::make('assignee.role')
                                            ->label('Peran Petugas')
                                            ->badge()
                                            ->placeholder('-')
                                            ->color(fn (\App\Enums\Role $state): string => self::roleColor($state)),
                                        TextEntry::make('created_at')
                                            ->label('Tanggal Dibuat')
                                            ->dateTime(),
                                        TextEntry::make('updated_at')
                                            ->label('Terakhir Diubah')
                                            ->dateTime(),
                                    ]),
                            ])
                            ->columnSpan(1),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    private static function roleColor(\App\Enums\Role $role): string
    {
        return match ($role) {
            \App\Enums\Role::ADMIN => 'danger',
            \App\Enums\Role::PIMPINAN => 'primary',
            \App\Enums\Role::OPERATOR => 'warning',
            \App\Enums\Role::WARGA => 'gray',
        };
    }

    private static function categoryColor(ReportCategory $category): string
    {
        return match ($category) {
            ReportCategory::GENERAL => 'gray',
            ReportCategory::INFRASTRUKTUR => 'primary',
            ReportCategory::SAMPAH => 'warning',
            ReportCategory::KEAMANAN => 'danger',
            ReportCategory::PELAYANAN => 'info',
            ReportCategory::LAINNYA => 'gray',
        };
    }

    private static function resolveEvidenceUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (filter_var($path, FILTER_VALIDATE_URL) !== false) {
            return $path;
        }

        return Storage::url($path);
    }

    private static function evidenceName(?string $path): string
    {
        if (! $path) {
            return '-';
        }

        $basename = parse_url($path, PHP_URL_PATH);
        $basename = $basename ? basename($basename) : basename($path);

        return $basename ?: $path;
    }
}
