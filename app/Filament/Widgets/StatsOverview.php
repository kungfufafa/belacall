<?php

namespace App\Filament\Widgets;

use App\Models\Report;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Laporan', Report::count())
                ->description('Semua laporan masuk')
                ->descriptionIcon('heroicon-m-inbox')
                ->color('primary'),
            Stat::make('Perlu Tindakan', Report::whereIn('status', ['SUBMITTED', 'VERIFIED'])->count())
                ->description('Menunggu diproses')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color('warning'),
            Stat::make('Selesai', Report::whereIn('status', ['RESOLVED', 'CLOSED'])->count())
                ->description('Laporan berhasil ditangani')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
        ];
    }
}
