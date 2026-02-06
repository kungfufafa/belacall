<?php

namespace App\Filament\Pages;

use App\Enums\ReportPriority;
use App\Enums\ReportStatus;
use App\Enums\Role;
use App\Filament\Resources\Reports\ReportResource;
use App\Models\Report;
use App\Models\User;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class DashboardPimpinan extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartLine;

    protected string $view = 'filament.pages.dashboard-pimpinan';

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        if (! $user) {
            return false;
        }

        return $user->role === Role::PIMPINAN;
    }

    protected function getViewData(): array
    {
        return [
            'summary' => $this->buildSummary(),
            'overdueDays' => $this->overdueDays(),
            'assignmentQueue' => $this->getAssignmentQueue(),
            'overdueReports' => $this->getOverdueReports(),
            'operatorLoads' => $this->getOperatorLoads(),
        ];
    }

    private function overdueDays(): int
    {
        return 3;
    }

    private function buildSummary(): array
    {
        $overdueCutoff = now()->subDays($this->overdueDays());

        return [
            [
                'label' => 'Belum Dibagi ke Petugas',
                'value' => Report::query()
                    ->whereNull('assignee_id')
                    ->whereIn('status', [ReportStatus::SUBMITTED, ReportStatus::VERIFIED])
                    ->count(),
            ],
            [
                'label' => 'Mendesak Belum Dibagi',
                'value' => Report::query()
                    ->whereNull('assignee_id')
                    ->whereIn('status', [ReportStatus::SUBMITTED, ReportStatus::VERIFIED])
                    ->whereIn('priority', [ReportPriority::URGENT, ReportPriority::HIGH])
                    ->count(),
            ],
            [
                'label' => 'Sedang Dikerjakan',
                'value' => Report::query()->where('status', ReportStatus::IN_PROGRESS)->count(),
            ],
            [
                'label' => 'Belum Update > 3 Hari',
                'value' => Report::query()
                    ->where('status', ReportStatus::IN_PROGRESS)
                    ->where('updated_at', '<', $overdueCutoff)
                    ->count(),
            ],
            [
                'label' => 'Selesai Minggu Ini',
                'value' => Report::query()
                    ->whereIn('status', [ReportStatus::RESOLVED, ReportStatus::CLOSED])
                    ->where('updated_at', '>=', now()->subDays(7))
                    ->count(),
            ],
            [
                'label' => 'Ditolak',
                'value' => Report::query()->where('status', ReportStatus::REJECTED)->count(),
            ],
        ];
    }

    private function getAssignmentQueue(): array
    {
        return Report::query()
            ->with(['user', 'assignee'])
            ->whereNull('assignee_id')
            ->whereIn('status', [ReportStatus::SUBMITTED, ReportStatus::VERIFIED])
            ->orderByRaw("CASE priority WHEN 'Urgent' THEN 0 WHEN 'High' THEN 1 WHEN 'Medium' THEN 2 ELSE 3 END")
            ->oldest('created_at')
            ->limit(8)
            ->get()
            ->map(fn (Report $report): array => $this->mapReport($report, includeAge: true))
            ->all();
    }

    private function getOverdueReports(): array
    {
        $now = now();
        $overdueCutoff = $now->copy()->subDays($this->overdueDays());

        return Report::query()
            ->with(['user', 'assignee'])
            ->where('status', ReportStatus::IN_PROGRESS)
            ->where('updated_at', '<', $overdueCutoff)
            ->latest()
            ->limit(6)
            ->get()
            ->map(function (Report $report) use ($now): array {
                $mapped = $this->mapReport($report, includeAge: true);
                $mapped['age'] = $report->updated_at
                    ? $report->updated_at->diffInDays($now).' hari'
                    : '-';

                return $mapped;
            })
            ->all();
    }

    private function getOperatorLoads(): array
    {
        $overdueCutoff = now()->subDays($this->overdueDays());

        return User::query()
            ->where('role', Role::OPERATOR)
            ->withCount([
                'reportsAssigned as active_count' => fn ($query) => $query->whereIn('status', [
                    ReportStatus::SUBMITTED->value,
                    ReportStatus::VERIFIED->value,
                    ReportStatus::IN_PROGRESS->value,
                    ReportStatus::NEEDS_REVISION->value,
                    ReportStatus::RESOLVED->value,
                ]),
                'reportsAssigned as overdue_count' => fn ($query) => $query
                    ->where('status', ReportStatus::IN_PROGRESS->value)
                    ->where('updated_at', '<', $overdueCutoff),
                'reportsAssigned as completed_week_count' => fn ($query) => $query
                    ->whereIn('status', [ReportStatus::RESOLVED->value, ReportStatus::CLOSED->value])
                    ->where('updated_at', '>=', now()->subDays(7)),
            ])
            ->orderByDesc('overdue_count')
            ->orderByDesc('active_count')
            ->orderBy('name')
            ->limit(8)
            ->get()
            ->map(fn (User $operator): array => [
                'name' => $operator->name,
                'active_count' => (int) ($operator->active_count ?? 0),
                'overdue_count' => (int) ($operator->overdue_count ?? 0),
                'completed_week_count' => (int) ($operator->completed_week_count ?? 0),
            ])
            ->all();
    }

    private function mapReport(Report $report, bool $includeAge = false): array
    {
        $status = $report->status;
        $priority = $report->priority;
        $mapped = [
            'ticket' => $report->ticket_number,
            'title' => $report->title ?: 'Tanpa judul',
            'location' => $report->location_name ?: 'Lokasi belum diisi',
            'status_label' => $status->label(),
            'status_color' => $status->color(),
            'priority_label' => $this->priorityLabel($priority),
            'priority_color' => $this->priorityColor($priority),
            'created_at' => $report->created_at?->format('d M Y H:i') ?? '-',
            'updated_at' => $report->updated_at?->format('d M Y H:i') ?? '-',
            'assignee' => $report->assignee?->name ?? 'Belum ditugaskan',
            'reporter' => $report->user?->name ?? 'Warga',
            'url' => ReportResource::getUrl('view', ['record' => $report]),
        ];

        if (! $includeAge) {
            return $mapped;
        }

        $mapped['age'] = $report->created_at
            ? $report->created_at->diffInDays(now()).' hari'
            : '-';

        return $mapped;
    }

    private function priorityColor(ReportPriority $priority): string
    {
        return match ($priority) {
            ReportPriority::URGENT => 'danger',
            ReportPriority::HIGH => 'warning',
            ReportPriority::MEDIUM => 'info',
            ReportPriority::LOW => 'gray',
        };
    }

    private function priorityLabel(ReportPriority $priority): string
    {
        return match ($priority) {
            ReportPriority::URGENT => 'Mendesak',
            ReportPriority::HIGH => 'Tinggi',
            ReportPriority::MEDIUM => 'Sedang',
            ReportPriority::LOW => 'Rendah',
        };
    }
}
