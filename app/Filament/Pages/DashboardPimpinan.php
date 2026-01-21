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

        return in_array($user->role, [Role::ADMIN, Role::PIMPINAN], true);
    }

    protected function getViewData(): array
    {
        return [
            'summary' => $this->buildSummary(),
            'overdueDays' => $this->overdueDays(),
            'overdueReports' => $this->getOverdueReports(),
            'recentReports' => $this->getRecentReports(),
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
                'label' => 'Total Laporan',
                'value' => Report::query()->count(),
            ],
            [
                'label' => 'Laporan Masuk',
                'value' => Report::query()->where('status', ReportStatus::SUBMITTED)->count(),
            ],
            [
                'label' => 'Diproses',
                'value' => Report::query()->where('status', ReportStatus::IN_PROGRESS)->count(),
            ],
            [
                'label' => 'Overdue',
                'value' => Report::query()
                    ->where('status', ReportStatus::IN_PROGRESS)
                    ->where('created_at', '<', $overdueCutoff)
                    ->count(),
            ],
            [
                'label' => 'Selesai',
                'value' => Report::query()
                    ->whereIn('status', [ReportStatus::RESOLVED, ReportStatus::CLOSED])
                    ->count(),
            ],
            [
                'label' => 'Ditolak',
                'value' => Report::query()->where('status', ReportStatus::REJECTED)->count(),
            ],
        ];
    }

    private function getOverdueReports(): array
    {
        $now = now();
        $overdueCutoff = $now->copy()->subDays($this->overdueDays());

        return Report::query()
            ->with(['user', 'assignee'])
            ->where('status', ReportStatus::IN_PROGRESS)
            ->where('created_at', '<', $overdueCutoff)
            ->latest()
            ->limit(6)
            ->get()
            ->map(function (Report $report) use ($now): array {
                $mapped = $this->mapReport($report);
                $mapped['age'] = $report->created_at
                    ? $report->created_at->diffInDays($now).' hari'
                    : '-';

                return $mapped;
            })
            ->all();
    }

    private function getRecentReports(): array
    {
        return Report::query()
            ->with(['user', 'assignee'])
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
