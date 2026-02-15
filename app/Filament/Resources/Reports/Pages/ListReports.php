<?php

namespace App\Filament\Resources\Reports\Pages;

use App\Enums\ReportPriority;
use App\Enums\ReportStatus;
use App\Enums\Role;
use App\Exports\ReportsExport;
use App\Filament\Resources\Reports\ReportResource;
use App\Services\ReportExportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Maatwebsite\Excel\Facades\Excel;

class ListReports extends ListRecords
{
    protected static string $resource = ReportResource::class;

    protected function authorizeAccess(): void
    {
        static::getResource()::authorizeViewAny();
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('exportPdf')
                ->label('Export PDF')
                ->icon(Heroicon::OutlinedDocumentArrowDown)
                ->color('danger')
                ->visible(fn (): bool => $this->canExport())
                ->form($this->exportFormSchema())
                ->action(function (array $data) {
                    $service = new ReportExportService;
                    $reports = $service->getFilteredReports($data);
                    $summary = $service->buildSummaryData($reports);
                    $period = $this->buildPeriodLabel($data);

                    $pdf = Pdf::loadView('exports.reports-pdf', [
                        'reports' => $reports,
                        'summary' => $summary,
                        'period' => $period,
                    ])->setPaper('a4', 'landscape');

                    $filename = 'laporan-pengaduan-'.now()->format('Y-m-d-His').'.pdf';

                    return response()->streamDownload(
                        fn () => print ($pdf->output()),
                        $filename,
                        ['Content-Type' => 'application/pdf']
                    );
                }),
            Action::make('exportExcel')
                ->label('Export Excel')
                ->icon(Heroicon::OutlinedTableCells)
                ->color('success')
                ->visible(fn (): bool => $this->canExport())
                ->form($this->exportFormSchema())
                ->action(function (array $data) {
                    $service = new ReportExportService;
                    $reports = $service->getFilteredReports($data);
                    $summary = $service->buildSummaryData($reports);
                    $period = $this->buildPeriodLabel($data);

                    $filename = 'laporan-pengaduan-'.now()->format('Y-m-d-His').'.xlsx';

                    return Excel::download(
                        new ReportsExport($reports, $summary, $period),
                        $filename
                    );
                }),
        ];
    }

    private function canExport(): bool
    {
        $user = auth()->user();

        return $user && in_array($user->role, [Role::ADMIN, Role::PIMPINAN], true);
    }

    /**
     * @return array<int, DatePicker|Select>
     */
    private function exportFormSchema(): array
    {
        return [
            DatePicker::make('date_from')
                ->label('Dari Tanggal')
                ->native(false),
            DatePicker::make('date_to')
                ->label('Sampai Tanggal')
                ->native(false),
            Select::make('status')
                ->label('Status')
                ->options(
                    collect(ReportStatus::cases())
                        ->mapWithKeys(fn (ReportStatus $s): array => [$s->value => $s->label()])
                        ->all()
                )
                ->placeholder('Semua Status'),
            Select::make('priority')
                ->label('Prioritas')
                ->options(
                    collect(ReportPriority::cases())
                        ->mapWithKeys(fn (ReportPriority $p): array => [$p->value => $p->label()])
                        ->all()
                )
                ->placeholder('Semua Prioritas'),
        ];
    }

    /**
     * @param  array{date_from?: string|null, date_to?: string|null, status?: string|null, priority?: string|null}  $data
     */
    private function buildPeriodLabel(array $data): string
    {
        $from = $data['date_from'] ?? null;
        $to = $data['date_to'] ?? null;

        if ($from && $to) {
            return "{$from} s/d {$to}";
        }

        if ($from) {
            return "Sejak {$from}";
        }

        if ($to) {
            return "Sampai {$to}";
        }

        return 'Semua Periode';
    }
}
