<?php

namespace App\Http\Controllers;

use App\Enums\ReportStatus;
use App\Http\Requests\StoreWebReportRequest;
use App\Http\Requests\SubmitReportRevisionRequest;
use App\Models\Report;
use App\Models\ReportHistory;
use App\Models\User;
use App\Services\FonnteService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class WebReportController extends Controller
{
    public function index(): View
    {
        return view('landing');
    }

    public function create(): View
    {
        return view('reports.create');
    }

    public function store(StoreWebReportRequest $request, FonnteService $fonnteService): RedirectResponse
    {
        $validated = $request->validated();

        $phone = preg_replace('/[^0-9]/', '', $validated['phone']);
        if (str_starts_with($phone, '0')) {
            $phone = '62'.substr($phone, 1);
        }

        $user = User::firstOrCreate(
            ['phone' => $phone],
            ['name' => 'Warga '.substr($phone, -4), 'role' => 'warga']
        );

        $report = Report::create([
            'ticket_number' => 'T-'.now()->format('YmdHi').rand(10, 99),
            'user_id' => $user->id,
            'title' => $validated['title'],
            'description' => $validated['description'],
            'location_name' => $validated['location_name'],
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'status' => ReportStatus::SUBMITTED->value,
            'category' => 'General',
        ]);

        if ($request->hasFile('evidence')) {
            $path = $request->file('evidence')->store('evidences', 'public');
            $fullPath = asset('storage/'.$path);

            $report->evidences()->create([
                'file_path' => $fullPath,
                'file_type' => 'image',
            ]);
        }

        $msg = "✅ Laporan Web Diterima!\nTiket: *{$report->ticket_number}*\nSimpan nomor ini untuk tracking.";
        $fonnteService->sendText($phone, $msg);

        return redirect()->route('report.tracking.view', ['ticket' => $report->ticket_number])
            ->with('success', 'Laporan berhasil dikirim! Silakan simpan nomor tiket Anda.');
    }

    public function trackingView(Request $request): View
    {
        $ticket = $request->query('ticket');
        $report = null;
        $statusDisplay = null;
        $timeline = [];
        $evidenceItems = [];
        $canRevise = false;
        $revisionNote = null;

        if ($ticket) {
            $report = Report::with(['evidences', 'histories.user', 'user'])
                ->where('ticket_number', $ticket)
                ->first();
        }

        if ($report) {
            $currentStatus = $this->resolveCurrentStatus($report);
            $statusDisplay = [
                'value' => $currentStatus,
                'label' => $this->statusLabel($currentStatus),
                'classes' => $this->statusBadgeClasses($currentStatus),
            ];
            $timeline = $this->buildTimeline($report);
            $evidenceItems = $this->buildEvidenceItems($report);
            $canRevise = $currentStatus === ReportStatus::NEEDS_REVISION->value;
            $revisionNote = $this->latestRevisionNote($report);
        }

        return view('reports.tracking', compact('report', 'ticket', 'statusDisplay', 'timeline', 'evidenceItems', 'canRevise', 'revisionNote'));
    }

    public function submitRevision(SubmitReportRevisionRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $ticket = $validated['ticket'];

        $report = Report::query()
            ->with('user')
            ->where('ticket_number', $ticket)
            ->first();

        if (! $report) {
            return redirect()->route('report.tracking.view', ['ticket' => $ticket])
                ->with('error', 'Nomor tiket tidak ditemukan.');
        }

        if ($report->status !== ReportStatus::NEEDS_REVISION) {
            return redirect()->route('report.tracking.view', ['ticket' => $ticket])
                ->with('error', 'Laporan ini belum membutuhkan perbaikan.');
        }

        $updates = ['status' => ReportStatus::SUBMITTED->value];

        if ($request->filled('description')) {
            $updates['description'] = $validated['description'];
        }

        if ($request->filled('location_name')) {
            $updates['location_name'] = $validated['location_name'];
        }

        if ($request->filled('latitude') || $request->filled('longitude')) {
            $updates['latitude'] = $validated['latitude'];
            $updates['longitude'] = $validated['longitude'];
        }

        $report->update($updates);

        ReportHistory::create([
            'report_id' => $report->id,
            'user_id' => $report->user_id,
            'action' => 'STATUS_CHANGE',
            'old_value' => ReportStatus::NEEDS_REVISION->value,
            'new_value' => ReportStatus::SUBMITTED->value,
            'notes' => $validated['notes'] ?? 'Perbaikan dikirim oleh warga.',
        ]);

        if ($request->hasFile('evidence')) {
            $path = $request->file('evidence')->store('evidences', 'public');
            $fullPath = asset('storage/'.$path);

            $report->evidences()->create([
                'file_path' => $fullPath,
                'file_type' => 'image',
                'uploaded_by' => $report->user?->name,
            ]);

            ReportHistory::create([
                'report_id' => $report->id,
                'user_id' => $report->user_id,
                'action' => 'EVIDENCE_UPLOAD',
                'new_value' => basename($path),
            ]);
        }

        return redirect()->route('report.tracking.view', ['ticket' => $ticket])
            ->with('success', 'Perbaikan laporan berhasil dikirim. Menunggu verifikasi ulang.');
    }

    private function resolveCurrentStatus(Report $report): ?string
    {
        $historyStatus = $report->histories
            ->where('action', 'STATUS_CHANGE')
            ->sortByDesc('created_at')
            ->first()?->new_value;

        if ($historyStatus) {
            return $historyStatus;
        }

        return $this->normalizeStatus($report->status);
    }

    private function normalizeStatus(mixed $status): ?string
    {
        if ($status instanceof ReportStatus) {
            return $status->value;
        }

        if (is_string($status) && $status !== '') {
            return $status;
        }

        return null;
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function buildTimeline(Report $report): array
    {
        return $report->histories
            ->sortBy('created_at')
            ->map(function (ReportHistory $history): array {
                $status = $history->action === 'STATUS_CHANGE' ? $history->new_value : null;

                return [
                    'title' => $this->historyTitle($history, $status),
                    'description' => $this->historyDescription($history, $status),
                    'actor' => $history->user?->name ?? 'Sistem',
                    'created_at' => $history->created_at?->format('d M Y, H:i') ?? '-',
                    'badge' => $status ? $this->statusLabel($status) : Str::title(str_replace('_', ' ', $history->action)),
                    'badge_classes' => $status ? $this->statusBadgeClasses($status) : 'bg-gray-50 text-gray-600 border-gray-200',
                ];
            })
            ->values()
            ->all();
    }

    private function historyTitle(ReportHistory $history, ?string $status): string
    {
        return match ($history->action) {
            'STATUS_CHANGE' => 'Status: '.$this->statusLabel($status),
            'ASSIGNMENT' => 'Penugasan',
            'COMMENT' => 'Catatan',
            'EVIDENCE_UPLOAD' => 'Upload Bukti',
            'CREATED' => 'Laporan Dibuat',
            default => Str::title(str_replace('_', ' ', $history->action)),
        };
    }

    private function historyDescription(ReportHistory $history, ?string $status): string
    {
        if ($history->notes) {
            return $history->notes;
        }

        return match ($history->action) {
            'STATUS_CHANGE' => sprintf(
                'Status berubah dari %s ke %s.',
                $this->statusLabel($history->old_value),
                $this->statusLabel($status)
            ),
            'ASSIGNMENT' => $history->new_value
                ? 'Ditugaskan ke '.$history->new_value.'.'
                : 'Penugasan diperbarui.',
            'EVIDENCE_UPLOAD' => $history->new_value
                ? 'Bukti diunggah: '.$history->new_value.'.'
                : 'Bukti baru diunggah.',
            default => 'Aktivitas diperbarui.',
        };
    }

    private function latestRevisionNote(Report $report): ?string
    {
        return $report->histories
            ->where('action', 'STATUS_CHANGE')
            ->where('new_value', ReportStatus::NEEDS_REVISION->value)
            ->sortByDesc('created_at')
            ->first()?->notes;
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function buildEvidenceItems(Report $report): array
    {
        return $report->evidences
            ->map(function ($evidence): array {
                $url = $this->resolveEvidenceUrl($evidence->file_path);

                return [
                    'name' => $this->evidenceName($evidence->file_path),
                    'type' => strtoupper((string) $evidence->file_type),
                    'url' => $url ?? '#',
                    'is_image' => $evidence->file_type === 'image' && $url,
                    'created_at' => $evidence->created_at?->format('d M Y, H:i') ?? '-',
                ];
            })
            ->values()
            ->all();
    }

    private function resolveEvidenceUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (filter_var($path, FILTER_VALIDATE_URL) !== false) {
            return $path;
        }

        return Storage::url($path);
    }

    private function evidenceName(?string $path): string
    {
        if (! $path) {
            return '-';
        }

        $basename = parse_url($path, PHP_URL_PATH);
        $basename = $basename ? basename($basename) : basename($path);

        return $basename ?: $path;
    }

    private function statusLabel(?string $status): string
    {
        if (! $status) {
            return 'Belum Diproses';
        }

        return match ($status) {
            ReportStatus::SUBMITTED->value => 'Masuk',
            ReportStatus::VERIFIED->value => 'Terverifikasi',
            ReportStatus::IN_PROGRESS->value => 'Diproses',
            ReportStatus::RESOLVED->value => 'Selesai',
            ReportStatus::CLOSED->value => 'Ditutup',
            ReportStatus::REJECTED->value => 'Ditolak',
            ReportStatus::NEEDS_REVISION->value => 'Perlu Revisi',
            default => $status,
        };
    }

    private function statusBadgeClasses(?string $status): string
    {
        return match ($status) {
            ReportStatus::SUBMITTED->value => 'bg-yellow-50 text-yellow-700 border-yellow-200',
            ReportStatus::VERIFIED->value => 'bg-blue-50 text-blue-700 border-blue-200',
            ReportStatus::IN_PROGRESS->value => 'bg-blue-50 text-blue-700 border-blue-200',
            ReportStatus::RESOLVED->value => 'bg-green-50 text-green-700 border-green-200',
            ReportStatus::CLOSED->value => 'bg-gray-50 text-gray-600 border-gray-200',
            ReportStatus::REJECTED->value => 'bg-red-50 text-red-700 border-red-200',
            ReportStatus::NEEDS_REVISION->value => 'bg-amber-50 text-amber-700 border-amber-200',
            default => 'bg-gray-50 text-gray-600 border-gray-200',
        };
    }
}
