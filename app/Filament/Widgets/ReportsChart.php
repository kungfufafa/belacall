<?php

namespace App\Filament\Widgets;

use App\Models\Report;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class ReportsChart extends ChartWidget
{
    protected static ?string $heading = 'Statistik Laporan Bulanan';
    
    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $data = Trend::model(Report::class)
            ->between(
                start: now()->startOfYear(),
                end: now()->endOfYear(),
            )
            ->perMonth()
            ->count();
 
        return [
            'datasets' => [
                [
                    'label' => 'Laporan Masuk',
                    'data' => $data->map(fn (TrendValue $value) => $value->aggregate),
                    'borderColor' => '#3b82f6',
                    'fill' => 'start',
                ],
            ],
            'labels' => $data->map(fn (TrendValue $value) => $value->date),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
