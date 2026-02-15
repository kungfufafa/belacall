<?php

namespace App\Exports\Sheets;

use App\Models\Report;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReportsDetailSheet implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
{
    private int $index = 0;

    /**
     * @param  Collection<int, Report>  $reports
     */
    public function __construct(
        private readonly Collection $reports,
    ) {}

    public function title(): string
    {
        return 'Detail Laporan';
    }

    /**
     * @return Collection<int, Report>
     */
    public function collection(): Collection
    {
        return $this->reports;
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'No',
            'No. Tiket',
            'Judul',
            'Deskripsi',
            'Prioritas',
            'Status',
            'Lokasi',
            'Pelapor',
            'Petugas',
            'Tanggal Masuk',
            'Tanggal Selesai',
        ];
    }

    /**
     * @param  Report  $report
     * @return array<int, string|int|null>
     */
    public function map($report): array
    {
        $this->index++;

        return [
            $this->index,
            $report->ticket_number,
            $report->title,
            Str::limit($report->description, 100),
            $report->priority?->label() ?? '-',
            $report->status->label(),
            $report->location_name ?? '-',
            $report->user?->name ?? '-',
            $report->assignee?->name ?? '-',
            $report->created_at->format('d/m/Y H:i'),
            $report->resolved_at?->format('d/m/Y H:i') ?? '-',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '2563EB']],
            ],
        ];
    }
}
