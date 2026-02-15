<?php

namespace App\Exports\Sheets;

use App\Enums\ReportPriority;
use App\Enums\ReportStatus;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReportsSummarySheet implements FromArray, WithStyles, WithTitle
{
    /**
     * @param  array{total: int, by_status: array<string, int>, by_priority: array<string, int>, sla_compliance_rate: float, average_resolution_time: string}  $summary
     */
    public function __construct(
        private readonly array $summary,
        private readonly string $period,
    ) {}

    public function title(): string
    {
        return 'Ringkasan';
    }

    /**
     * @return array<int, array<int, string|int|float>>
     */
    public function array(): array
    {
        $rows = [];

        $rows[] = ['LAPORAN PENGADUAN DESA'];
        $rows[] = ['Periode: '.$this->period];
        $rows[] = [];

        $rows[] = ['Statistik Umum'];
        $rows[] = ['Total Laporan', $this->summary['total']];
        $rows[] = ['Kepatuhan SLA', $this->summary['sla_compliance_rate'].'%'];
        $rows[] = ['Rata-rata Penyelesaian', $this->summary['average_resolution_time']];
        $rows[] = [];

        $rows[] = ['Berdasarkan Status'];
        $rows[] = ['Status', 'Jumlah'];
        foreach ($this->summary['by_status'] as $status => $count) {
            $label = ReportStatus::tryFrom($status)?->label() ?? $status;
            $rows[] = [$label, $count];
        }
        $rows[] = [];

        $rows[] = ['Berdasarkan Prioritas'];
        $rows[] = ['Prioritas', 'Jumlah'];
        foreach ($this->summary['by_priority'] as $priority => $count) {
            $label = ReportPriority::tryFrom($priority)?->label() ?? $priority;
            $rows[] = [$label, $count];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->getColumnDimension('A')->setWidth(30);
        $sheet->getColumnDimension('B')->setWidth(20);

        return [
            1 => ['font' => ['bold' => true, 'size' => 16]],
            2 => ['font' => ['italic' => true, 'color' => ['rgb' => '6B7280']]],
            4 => ['font' => ['bold' => true, 'size' => 12], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'EFF6FF']]],
        ];
    }
}
