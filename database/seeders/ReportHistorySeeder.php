<?php

namespace Database\Seeders;

use App\Enums\ReportStatus;
use App\Models\Report;
use App\Models\ReportHistory;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReportHistorySeeder extends Seeder
{
    public function run(): void
    {
        $reports = Report::all();
        $operatorSiti = User::where('email', 'siti@belacall.test')->first();
        $operatorDarto = User::where('email', 'darto@belacall.test')->first();

        if ($reports->isEmpty()) {
            $this->command->error('Please run ReportSeeder first!');

            return;
        }

        foreach ($reports as $report) {
            $this->createHistoryForReport($report, $operatorSiti, $operatorDarto);
        }
    }

    private function createHistoryForReport(Report $report, $operatorSiti, $operatorDarto): void
    {
        $baseTime = $report->created_at;

        ReportHistory::firstOrCreate([
            'report_id' => $report->id,
            'action' => 'CREATED',
        ], [
            'user_id' => $report->user_id,
            'old_value' => null,
            'new_value' => ReportStatus::SUBMITTED->value,
            'notes' => 'Laporan diterima melalui WhatsApp Bot',
            'created_at' => $baseTime,
        ]);

        if ($report->status === ReportStatus::SUBMITTED) {
            return;
        }

        if ($report->status === ReportStatus::REJECTED) {
            $operator = $report->assignee_id ? User::find($report->assignee_id) : $operatorSiti;
            ReportHistory::firstOrCreate([
                'report_id' => $report->id,
                'action' => 'STATUS_CHANGE',
                'new_value' => ReportStatus::REJECTED->value,
            ], [
                'user_id' => $operator?->id,
                'old_value' => ReportStatus::SUBMITTED->value,
                'notes' => 'Laporan ditolak: Tidak valid/spam',
                'created_at' => $baseTime->copy()->addHours(2),
            ]);

            return;
        }

        if ($report->status === ReportStatus::NEEDS_REVISION) {
            $operator = $report->assignee_id ? User::find($report->assignee_id) : $operatorSiti;
            ReportHistory::firstOrCreate([
                'report_id' => $report->id,
                'action' => 'STATUS_CHANGE',
                'new_value' => ReportStatus::NEEDS_REVISION->value,
            ], [
                'user_id' => $operator?->id,
                'old_value' => ReportStatus::SUBMITTED->value,
                'notes' => 'Foto bukti kurang jelas, mohon kirim ulang',
                'created_at' => $baseTime->copy()->addHours(3),
            ]);

            return;
        }

        $operator = $report->assignee_id ? User::find($report->assignee_id) : $operatorSiti;

        ReportHistory::firstOrCreate([
            'report_id' => $report->id,
            'action' => 'STATUS_CHANGE',
            'new_value' => ReportStatus::VERIFIED->value,
        ], [
            'user_id' => $operator?->id,
            'old_value' => ReportStatus::SUBMITTED->value,
            'notes' => 'Laporan terverifikasi',
            'created_at' => $baseTime->copy()->addHours(4),
        ]);

        if ($report->assignee_id) {
            ReportHistory::firstOrCreate([
                'report_id' => $report->id,
                'action' => 'ASSIGNMENT',
            ], [
                'user_id' => $operatorSiti?->id,
                'old_value' => null,
                'new_value' => $operator?->name ?? 'Operator',
                'notes' => 'Ditugaskan untuk penanganan',
                'created_at' => $baseTime->copy()->addHours(5),
            ]);
        }

        if ($report->status === ReportStatus::VERIFIED) {
            return;
        }

        ReportHistory::firstOrCreate([
            'report_id' => $report->id,
            'action' => 'STATUS_CHANGE',
            'new_value' => ReportStatus::IN_PROGRESS->value,
        ], [
            'user_id' => $operator?->id,
            'old_value' => ReportStatus::VERIFIED->value,
            'notes' => 'Proses penanganan dimulai',
            'created_at' => $baseTime->copy()->addDays(1),
        ]);

        if ($report->status === ReportStatus::IN_PROGRESS) {
            return;
        }

        ReportHistory::firstOrCreate([
            'report_id' => $report->id,
            'action' => 'STATUS_CHANGE',
            'new_value' => ReportStatus::RESOLVED->value,
        ], [
            'user_id' => $operator?->id,
            'old_value' => ReportStatus::IN_PROGRESS->value,
            'notes' => 'Penanganan selesai dilakukan',
            'created_at' => $baseTime->copy()->addDays(3),
        ]);

        ReportHistory::firstOrCreate([
            'report_id' => $report->id,
            'action' => 'EVIDENCE_UPLOAD',
        ], [
            'user_id' => $operator?->id,
            'old_value' => null,
            'new_value' => 'bukti_tindaklanjut_1.jpg',
            'notes' => 'Bukti penyelesaian diunggah',
            'created_at' => $baseTime->copy()->addDays(3)->addMinutes(30),
        ]);

        if ($report->status === ReportStatus::RESOLVED) {
            return;
        }

        ReportHistory::firstOrCreate([
            'report_id' => $report->id,
            'action' => 'STATUS_CHANGE',
            'new_value' => ReportStatus::CLOSED->value,
        ], [
            'user_id' => $operator?->id,
            'old_value' => ReportStatus::RESOLVED->value,
            'notes' => 'Kasus ditutup setelah konfirmasi warga',
            'created_at' => $baseTime->copy()->addDays(5),
        ]);
    }
}
