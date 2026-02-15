<?php

namespace App\Exports;

use App\Exports\Sheets\ReportsDetailSheet;
use App\Exports\Sheets\ReportsSummarySheet;
use App\Models\Report;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ReportsExport implements WithMultipleSheets
{
    /**
     * @param  Collection<int, Report>  $reports
     * @param  array{total: int, by_status: array<string, int>, by_priority: array<string, int>, sla_compliance_rate: float, average_resolution_time: string}  $summary
     */
    public function __construct(
        private readonly Collection $reports,
        private readonly array $summary,
        private readonly string $period,
    ) {}

    /**
     * @return array<int, ReportsSummarySheet|ReportsDetailSheet>
     */
    public function sheets(): array
    {
        return [
            new ReportsSummarySheet($this->summary, $this->period),
            new ReportsDetailSheet($this->reports),
        ];
    }
}
