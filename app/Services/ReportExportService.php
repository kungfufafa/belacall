<?php

namespace App\Services;

use App\Enums\ReportPriority;
use App\Enums\ReportStatus;
use App\Models\Report;
use Illuminate\Support\Collection;

class ReportExportService
{
    /**
     * @param  array{date_from?: string, date_to?: string, status?: string, priority?: string}  $filters
     * @return Collection<int, Report>
     */
    public function getFilteredReports(array $filters): Collection
    {
        $query = Report::query()
            ->with(['user', 'assignee'])
            ->orderBy('created_at', 'desc');

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        return $query->get();
    }

    /**
     * @param  Collection<int, Report>  $reports
     * @return array{
     *     total: int,
     *     by_status: array<string, int>,
     *     by_priority: array<string, int>,
     *     response_sla_compliance_rate: float,
     *     resolution_sla_compliance_rate: float,
     *     sla_compliance_rate: float,
     *     average_resolution_time: string
     * }
     */
    public function buildSummaryData(Collection $reports): array
    {
        $byStatus = [];
        foreach (ReportStatus::cases() as $status) {
            $count = $reports->where('status', $status)->count();
            if ($count > 0) {
                $byStatus[$status->value] = $count;
            }
        }

        $byPriority = [];
        foreach (ReportPriority::cases() as $priority) {
            $count = $reports->where('priority', $priority)->count();
            if ($count > 0) {
                $byPriority[$priority->value] = $count;
            }
        }

        $withoutPriority = $reports->filter(fn (Report $report): bool => $report->priority === null)->count();
        if ($withoutPriority > 0) {
            $byPriority['Belum ditetapkan'] = $withoutPriority;
        }

        $responseEligible = $reports->filter(
            fn (Report $r): bool => $r->responded_at !== null && $r->response_deadline !== null
        );
        $responseCompliant = $responseEligible->filter(
            fn (Report $r): bool => $r->responded_at->lte($r->response_deadline)
        );
        $responseSlaRate = $responseEligible->count() > 0
            ? round(($responseCompliant->count() / $responseEligible->count()) * 100, 1)
            : 0.0;

        $resolvedOrClosed = $reports->filter(
            fn (Report $r): bool => in_array($r->status, [ReportStatus::RESOLVED, ReportStatus::CLOSED], true)
        );

        $total = $reports->count();

        $slaEligible = $resolvedOrClosed->filter(
            fn (Report $r): bool => $r->resolved_at !== null && $r->resolution_deadline !== null
        );
        $slaCompliant = $slaEligible->filter(
            fn (Report $r): bool => $r->resolved_at->lte($r->resolution_deadline)
        );
        $resolutionSlaRate = $slaEligible->count() > 0
            ? round(($slaCompliant->count() / $slaEligible->count()) * 100, 1)
            : 0.0;

        $resolvedWithTime = $reports->filter(fn (Report $r): bool => $r->resolved_at !== null);
        $avgHours = $resolvedWithTime->count() > 0
            ? $resolvedWithTime->avg(fn (Report $r): float => $r->created_at->diffInHours($r->resolved_at))
            : 0;
        $avgDays = floor($avgHours / 24);
        $avgRemainingHours = round($avgHours - ($avgDays * 24));
        $avgResolutionTime = $avgDays > 0
            ? "{$avgDays} hari {$avgRemainingHours} jam"
            : "{$avgRemainingHours} jam";

        return [
            'total' => $total,
            'by_status' => $byStatus,
            'by_priority' => $byPriority,
            'response_sla_compliance_rate' => $responseSlaRate,
            'resolution_sla_compliance_rate' => $resolutionSlaRate,
            'sla_compliance_rate' => $resolutionSlaRate,
            'average_resolution_time' => $avgResolutionTime,
        ];
    }
}
