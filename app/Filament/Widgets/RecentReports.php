<?php

namespace App\Filament\Widgets;

use App\Enums\ReportStatus;
use App\Enums\Role;
use App\Models\Report;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

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

        $user = Filament::auth()->user();

        if ($user?->role === Role::ADMIN) {
            $query->where(function (Builder $builder): void {
                $builder
                    ->where('status', '!=', ReportStatus::SUBMITTED->value)
                    ->orWhereNotNull('assignee_id');
            });
        }

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
