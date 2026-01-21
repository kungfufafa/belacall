<?php

namespace App\Filament\Pages;

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

        return in_array($user->role, [Role::ADMIN, Role::OPERATOR], true);
    }

    protected function getViewData(): array
    {
        $user = Filament::auth()->user();

        return [
            'summary' => $this->buildSummary($user?->id),
            'assignedReports' => $this->getAssignedReports($user?->id),
            'recentReports' => $this->getRecentReports($user?->id),
        ];
    }

    private function buildSummary(?int $userId): array
    {
        if (! $userId) {
            return [
                ['label' => 'Laporan Masuk', 'value' => 0],
                ['label' => 'Terverifikasi', 'value' => 0],
                ['label' => 'Perlu Revisi', 'value' => 0],
                ['label' => 'Diproses', 'value' => 0],
                ['label' => 'Selesai', 'value' => 0],
                ['label' => 'Ditugaskan ke Saya', 'value' => 0],
            ];
        }

        $assignedQuery = Report::query()->where('assignee_id', $userId);

        return [
            [
                'label' => 'Laporan Masuk',
                'value' => (clone $assignedQuery)->where('status', ReportStatus::SUBMITTED)->count(),
            ],
            [
                'label' => 'Terverifikasi',
                'value' => (clone $assignedQuery)->where('status', ReportStatus::VERIFIED)->count(),
            ],
            [
                'label' => 'Perlu Revisi',
                'value' => (clone $assignedQuery)->where('status', ReportStatus::NEEDS_REVISION)->count(),
            ],
            [
                'label' => 'Diproses',
                'value' => (clone $assignedQuery)->where('status', ReportStatus::IN_PROGRESS)->count(),
            ],
            [
                'label' => 'Selesai',
                'value' => (clone $assignedQuery)
                    ->whereIn('status', [ReportStatus::RESOLVED, ReportStatus::CLOSED])
                    ->count(),
            ],
            [
                'label' => 'Ditugaskan ke Saya',
                'value' => (clone $assignedQuery)->count(),
            ],
        ];
    }

    private function getAssignedReports(?int $userId): array
    {
        if (! $userId) {
            return [];
        }

        return Report::query()
            ->with(['user', 'assignee'])
            ->where('assignee_id', $userId)
            ->whereNotIn('status', [ReportStatus::RESOLVED, ReportStatus::CLOSED, ReportStatus::REJECTED])
            ->latest()
            ->limit(6)
            ->get()
            ->map(fn (Report $report): array => $this->mapReport($report))
            ->all();
    }

    private function getRecentReports(?int $userId): array
    {
        if (! $userId) {
            return [];
        }

        return Report::query()
            ->with(['user', 'assignee'])
            ->where('assignee_id', $userId)
            ->latest()
            ->limit(6)
            ->get()
            ->map(fn (Report $report): array => $this->mapReport($report))
            ->all();
    }

    private function mapReport(Report $report): array
    {
        $status = $report->status;

        return [
            'ticket' => $report->ticket_number,
            'title' => $report->title ?: 'Tanpa judul',
            'location' => $report->location_name ?: 'Lokasi belum diisi',
            'status_label' => $this->statusLabel($status),
            'status_color' => $this->statusColor($status),
            'created_at' => $report->created_at?->format('d M Y H:i') ?? '-',
            'assignee' => $report->assignee?->name ?? 'Belum ditugaskan',
            'reporter' => $report->user?->name ?? 'Warga',
            'url' => ReportResource::getUrl('view', ['record' => $report]),
        ];
    }

    private function statusLabel(ReportStatus $status): string
    {
        return match ($status) {
            ReportStatus::SUBMITTED => 'Masuk',
            ReportStatus::VERIFIED => 'Terverifikasi',
            ReportStatus::IN_PROGRESS => 'Diproses',
            ReportStatus::RESOLVED => 'Selesai',
            ReportStatus::CLOSED => 'Ditutup',
            ReportStatus::REJECTED => 'Ditolak',
            ReportStatus::NEEDS_REVISION => 'Perlu Revisi',
        };
    }

    private function statusColor(ReportStatus $status): string
    {
        return match ($status) {
            ReportStatus::SUBMITTED => 'warning',
            ReportStatus::VERIFIED => 'primary',
            ReportStatus::IN_PROGRESS => 'warning',
            ReportStatus::RESOLVED => 'success',
            ReportStatus::CLOSED => 'gray',
            ReportStatus::REJECTED => 'danger',
            ReportStatus::NEEDS_REVISION => 'warning',
        };
    }
}
