<?php

namespace Database\Seeders;

use App\Enums\ReportPriority;
use App\Enums\ReportStatus;
use App\Models\Report;
use App\Models\ReportEvidence;
use Illuminate\Database\Seeder;

class ReportEvidenceSeeder extends Seeder
{
    public function run(): void
    {
        $reportsWithEvidence = Report::whereIn('status', [
            ReportStatus::VERIFIED->value,
            ReportStatus::IN_PROGRESS->value,
            ReportStatus::RESOLVED->value,
            ReportStatus::CLOSED->value,
            ReportStatus::NEEDS_REVISION->value,
        ])->get();

        if ($reportsWithEvidence->isEmpty()) {
            $this->command->error('Please run ReportSeeder first!');

            return;
        }

        foreach ($reportsWithEvidence as $report) {
            $this->createEvidenceForReport($report);
        }
    }

    private function createEvidenceForReport(Report $report): void
    {
        $ticketNumber = $report->ticket_number;

        ReportEvidence::firstOrCreate(
            ['report_id' => $report->id, 'file_path' => "reports/{$ticketNumber}/bukti_1.jpg"],
            [
                'file_type' => 'image',
                'uploaded_by' => 'bot',
                'created_at' => $report->created_at->addMinutes(5),
            ]
        );

        if (in_array($report->status, [ReportStatus::RESOLVED, ReportStatus::CLOSED], true)) {
            ReportEvidence::firstOrCreate(
                ['report_id' => $report->id, 'file_path' => "reports/{$ticketNumber}/bukti_tindaklanjut_1.jpg"],
                [
                    'file_type' => 'image',
                    'uploaded_by' => (string) $report->assignee_id,
                    'created_at' => $report->updated_at->subHours(2),
                ]
            );
        }

        if ($report->status === ReportStatus::NEEDS_REVISION) {
            ReportEvidence::firstOrCreate(
                ['report_id' => $report->id, 'file_path' => "reports/{$ticketNumber}/bukti_blur.jpg"],
                [
                    'file_type' => 'image',
                    'uploaded_by' => 'bot',
                    'created_at' => $report->created_at->addMinutes(3),
                ]
            );
        }

        if (in_array($report->priority, [ReportPriority::HIGH, ReportPriority::URGENT], true)) {
            ReportEvidence::firstOrCreate(
                ['report_id' => $report->id, 'file_path' => "reports/{$ticketNumber}/dokumen_pendukung.pdf"],
                [
                    'file_type' => 'document',
                    'uploaded_by' => (string) $report->user_id,
                    'created_at' => $report->created_at->addMinutes(10),
                ]
            );
        }
    }
}
