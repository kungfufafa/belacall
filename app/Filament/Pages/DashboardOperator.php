<?php

namespace App\Filament\Pages;

use App\Enums\ReportPriority;
use App\Enums\ReportStatus;
use App\Enums\Role;
use App\Filament\Resources\Reports\ReportResource;
use App\Models\Report;
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
        return 2;
    }

    private function buildSummary(?int $userId): array
    {
        if (! $userId) {
            return [
                ['label' => 'Tugas Aktif', 'value' => 0],
                ['label' => 'Perlu Tindakan Hari Ini', 'value' => 0],
                ['label' => 'Sedang Dikerjakan', 'value' => 0],
                ['label' => 'Belum Update > 2 Hari', 'value' => 0],
                ['label' => 'Selesai Minggu Ini', 'value' => 0],
            ];
        }

        $assignedQuery = Report::query()->where('assignee_id', $userId);
        $overdueCutoff = now()->subDays($this->overdueDays());
        $activeStatuses = [
            ReportStatus::SUBMITTED,
            ReportStatus::VERIFIED,
            ReportStatus::IN_PROGRESS,
            ReportStatus::NEEDS_REVISION,
            ReportStatus::RESOLVED,
        ];
        $actionTodayStatuses = [
            ReportStatus::SUBMITTED,
            ReportStatus::NEEDS_REVISION,
            ReportStatus::RESOLVED,
        ];

        return [
            [
                'label' => 'Tugas Aktif',
                'value' => (clone $assignedQuery)->whereIn('status', $activeStatuses)->count(),
            ],
            [
                'label' => 'Perlu Tindakan Hari Ini',
                'value' => (clone $assignedQuery)->whereIn('status', $actionTodayStatuses)->count(),
            ],
            [
                'label' => 'Sedang Dikerjakan',
                'value' => (clone $assignedQuery)->where('status', ReportStatus::IN_PROGRESS)->count(),
            ],
            [
                'label' => 'Belum Update > 2 Hari',
                'value' => (clone $assignedQuery)
                    ->where('status', ReportStatus::IN_PROGRESS)
                    ->where('updated_at', '<', $overdueCutoff)
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
            ->whereIn('status', [
                ReportStatus::SUBMITTED,
                ReportStatus::VERIFIED,
                ReportStatus::IN_PROGRESS,
                ReportStatus::NEEDS_REVISION,
                ReportStatus::RESOLVED,
            ])
            ->whereIn('priority', [ReportPriority::URGENT, ReportPriority::HIGH])
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

        $overdueCutoff = now()->subDays($this->overdueDays());

        return Report::query()
            ->with(['user', 'assignee'])
            ->where('assignee_id', $userId)
            ->whereIn('status', [
                ReportStatus::SUBMITTED,
                ReportStatus::VERIFIED,
                ReportStatus::IN_PROGRESS,
                ReportStatus::NEEDS_REVISION,
                ReportStatus::RESOLVED,
            ])
            ->orderByRaw("CASE status
                WHEN 'SUBMITTED' THEN 0
                WHEN 'NEEDS_REVISION' THEN 1
                WHEN 'VERIFIED' THEN 2
                WHEN 'IN_PROGRESS' THEN 3
                WHEN 'RESOLVED' THEN 4
                ELSE 5 END")
            ->orderByRaw("CASE WHEN status = 'IN_PROGRESS' AND updated_at < ? THEN 0 ELSE 1 END", [$overdueCutoff])
            ->oldest('updated_at')
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
        $overdueCutoff = now()->subDays($this->overdueDays());
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
            'is_overdue' => $status === ReportStatus::IN_PROGRESS
                && (bool) $report->updated_at
                && $report->updated_at->lt($overdueCutoff),
        ];

        if (! $withHint) {
            return $mapped;
        }

        $mapped['hint'] = match ($status) {
            ReportStatus::SUBMITTED => 'Mohon cek dan verifikasi laporan ini.',
            ReportStatus::VERIFIED => 'Laporan siap dikerjakan.',
            ReportStatus::IN_PROGRESS => 'Lanjutkan pekerjaan dan unggah bukti.',
            ReportStatus::NEEDS_REVISION => 'Menunggu perbaikan dari warga.',
            ReportStatus::RESOLVED => 'Sudah selesai, silakan tutup jika valid.',
            default => 'Silakan cek tindak lanjut laporan.',
        };

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
