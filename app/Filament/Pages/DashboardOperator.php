<?php

namespace App\Filament\Pages;

use App\Enums\ReportPriority;
use App\Enums\ReportStatus;
use App\Enums\Role;
use App\Filament\Resources\Reports\ReportResource;
use App\Models\Report;
use App\Models\SlaConfig;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class DashboardOperator extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected string $view = 'filament.pages.dashboard-operator';

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        if (! $user) {
            return false;
        }

        return $user->role === Role::OPERATOR;
    }

    protected function getViewData(): array
    {
        $user = Filament::auth()->user();

        return [
            'summary' => $this->buildSummary($user?->id),
            'overdueDays' => $this->overdueDays(),
            'priorityReports' => $this->getPriorityReports($user?->id),
            'actionQueueReports' => $this->getActionQueueReports($user?->id),
            'completedReports' => $this->getCompletedReports($user?->id),
        ];
    }

    private function overdueDays(): int
    {
        $config = SlaConfig::forPriority(ReportPriority::MEDIUM);

        return max(1, (int) ceil($config->resolution_time_minutes / 1440));
    }

    private function buildSummary(?int $userId): array
    {
        if (! $userId) {
            return [
                ['label' => 'Tugas Aktif', 'value' => 0],
                ['label' => 'Perlu Tindakan Hari Ini', 'value' => 0],
                ['label' => 'SLA Respon Terlewat', 'value' => 0],
                ['label' => 'Sedang Dikerjakan', 'value' => 0],
                ['label' => 'Melewati Deadline', 'value' => 0],
                ['label' => 'Selesai Minggu Ini', 'value' => 0],
            ];
        }

        $assignedQuery = Report::query()->where('assignee_id', $userId);
        $actionableStatuses = $this->operatorActionableStatuses();
        $priorityStatuses = [
            ReportStatus::VERIFIED->value,
            ReportStatus::RESOLVED->value,
        ];

        return [
            [
                'label' => 'Tugas Aktif',
                'value' => (clone $assignedQuery)->whereIn('status', $actionableStatuses)->count(),
            ],
            [
                'label' => 'Perlu Tindakan Hari Ini',
                'value' => (clone $assignedQuery)
                    ->where(function ($query) use ($priorityStatuses): void {
                        $query->whereIn('status', $priorityStatuses)
                            ->orWhere(function ($responseOverdueQuery): void {
                                $responseOverdueQuery->whereNotNull('response_deadline')
                                    ->where('response_deadline', '<', now())
                                    ->whereNull('responded_at');
                            })
                            ->orWhere(function ($overdueQuery): void {
                                $overdueQuery->where('status', ReportStatus::IN_PROGRESS->value)
                                    ->whereNotNull('resolution_deadline')
                                    ->where('resolution_deadline', '<', now());
                            });
                    })
                    ->count(),
            ],
            [
                'label' => 'SLA Respon Terlewat',
                'value' => (clone $assignedQuery)
                    ->whereNotIn('status', [ReportStatus::CLOSED->value, ReportStatus::REJECTED->value])
                    ->whereNotNull('response_deadline')
                    ->where('response_deadline', '<', now())
                    ->whereNull('responded_at')
                    ->count(),
            ],
            [
                'label' => 'Sedang Dikerjakan',
                'value' => (clone $assignedQuery)->where('status', ReportStatus::IN_PROGRESS->value)->count(),
            ],
            [
                'label' => 'Melewati Deadline',
                'value' => (clone $assignedQuery)
                    ->whereIn('status', $actionableStatuses)
                    ->whereNotNull('resolution_deadline')
                    ->where('resolution_deadline', '<', now())
                    ->count(),
            ],
            [
                'label' => 'Selesai Minggu Ini',
                'value' => (clone $assignedQuery)
                    ->whereIn('status', [ReportStatus::RESOLVED, ReportStatus::CLOSED])
                    ->where('updated_at', '>=', now()->subDays(7))
                    ->count(),
            ],
        ];
    }

    private function getPriorityReports(?int $userId): array
    {
        if (! $userId) {
            return [];
        }

        return Report::query()
            ->with(['user', 'assignee'])
            ->where('assignee_id', $userId)
            ->whereIn('status', $this->operatorActionableStatuses())
            ->whereIn('priority', [ReportPriority::URGENT->value, ReportPriority::HIGH->value])
            ->orderByRaw("CASE priority WHEN 'Urgent' THEN 0 WHEN 'High' THEN 1 ELSE 2 END")
            ->oldest('created_at')
            ->limit(6)
            ->get()
            ->map(fn (Report $report): array => $this->mapReport($report, withHint: true))
            ->all();
    }

    private function getActionQueueReports(?int $userId): array
    {
        if (! $userId) {
            return [];
        }

        return Report::query()
            ->with(['user', 'assignee'])
            ->where('assignee_id', $userId)
            ->whereIn('status', $this->operatorActionableStatuses())
            ->orderByRaw("CASE status
                WHEN 'VERIFIED' THEN 0
                WHEN 'IN_PROGRESS' THEN 1
                WHEN 'RESOLVED' THEN 2
                ELSE 3 END")
            ->orderByRaw('CASE WHEN resolution_deadline IS NOT NULL AND resolution_deadline < ? THEN 0 ELSE 1 END', [now()])
            ->oldest('created_at')
            ->limit(6)
            ->get()
            ->map(fn (Report $report): array => $this->mapReport($report, withHint: true))
            ->all();
    }

    private function getCompletedReports(?int $userId): array
    {
        if (! $userId) {
            return [];
        }

        return Report::query()
            ->with(['user', 'assignee'])
            ->where('assignee_id', $userId)
            ->whereIn('status', [ReportStatus::RESOLVED, ReportStatus::CLOSED])
            ->where('updated_at', '>=', now()->subDays(7))
            ->latest('updated_at')
            ->limit(6)
            ->get()
            ->map(fn (Report $report): array => $this->mapReport($report))
            ->all();
    }

    private function mapReport(Report $report, bool $withHint = false): array
    {
        $status = $report->status;
        $priority = $report->priority;
        $isOverdue = $report->resolution_deadline
            && $report->resolution_deadline->isPast()
            && ! $status->isFinal();
        $isResponseOverdue = $report->response_deadline
            && $report->response_deadline->isPast()
            && ! $report->responded_at
            && ! $status->isFinal();
        $mapped = [
            'ticket' => $report->ticket_number,
            'title' => $report->title ?: 'Tanpa judul',
            'location' => $report->location_name ?: 'Lokasi belum diisi',
            'status_label' => $status->label(),
            'status_color' => $status->color(),
            'priority_label' => $priority?->label() ?? 'Belum ditetapkan',
            'priority_color' => $priority?->color() ?? 'gray',
            'created_at' => $report->created_at?->format('d M Y H:i') ?? '-',
            'updated_at' => $report->updated_at?->format('d M Y H:i') ?? '-',
            'assignee' => $report->assignee?->name ?? 'Belum ditugaskan',
            'reporter' => $report->user?->name ?? 'Warga',
            'url' => ReportResource::getUrl('view', ['record' => $report]),
            'response_deadline' => $report->response_deadline?->format('d M Y H:i') ?? '-',
            'resolution_deadline' => $report->resolution_deadline?->format('d M Y H:i') ?? '-',
            'is_overdue' => $isOverdue,
            'is_response_overdue' => $isResponseOverdue,
        ];

        if (! $withHint) {
            return $mapped;
        }

        $mapped['hint'] = match ($status) {
            ReportStatus::VERIFIED => 'Laporan siap dikerjakan.',
            ReportStatus::IN_PROGRESS => 'Lanjutkan pekerjaan dan unggah bukti.',
            ReportStatus::RESOLVED => 'Sudah selesai, silakan tutup jika valid.',
            default => 'Silakan cek tindak lanjut laporan.',
        };

        return $mapped;
    }

    /**
     * @return array<int, string>
     */
    private function operatorActionableStatuses(): array
    {
        return [
            ReportStatus::VERIFIED->value,
            ReportStatus::IN_PROGRESS->value,
            ReportStatus::RESOLVED->value,
        ];
    }
}
