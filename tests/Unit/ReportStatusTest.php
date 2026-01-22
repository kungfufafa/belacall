<?php

namespace Tests\Unit;

use App\Enums\ReportStatus;
use Tests\TestCase;

class ReportStatusTest extends TestCase
{
    public function test_report_status_colors_are_distinct(): void
    {
        $expected = [
            ReportStatus::SUBMITTED->value => 'info',
            ReportStatus::VERIFIED->value => 'primary',
            ReportStatus::IN_PROGRESS->value => 'secondary',
            ReportStatus::RESOLVED->value => 'success',
            ReportStatus::CLOSED->value => 'gray',
            ReportStatus::REJECTED->value => 'danger',
            ReportStatus::NEEDS_REVISION->value => 'warning',
        ];

        $actual = [];

        foreach (ReportStatus::cases() as $status) {
            $actual[$status->value] = $status->color();
        }

        $this->assertSame($expected, $actual);
        $this->assertCount(count($expected), array_unique($actual));
    }

    public function test_report_status_labels_are_expected(): void
    {
        $expected = [
            ReportStatus::SUBMITTED->value => 'Masuk',
            ReportStatus::VERIFIED->value => 'Terverifikasi',
            ReportStatus::IN_PROGRESS->value => 'Diproses',
            ReportStatus::RESOLVED->value => 'Selesai',
            ReportStatus::CLOSED->value => 'Ditutup',
            ReportStatus::REJECTED->value => 'Ditolak',
            ReportStatus::NEEDS_REVISION->value => 'Perlu Revisi',
        ];

        $actual = [];

        foreach (ReportStatus::cases() as $status) {
            $actual[$status->value] = $status->label();
        }

        $this->assertSame($expected, $actual);
    }
}
