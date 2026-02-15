<?php

namespace Tests\Feature;

use App\Enums\ReportStatus;
use App\Models\Report;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_sets_responded_at_when_status_moves_out_of_submitted(): void
    {
        $report = Report::factory()->create([
            'status' => ReportStatus::SUBMITTED,
            'responded_at' => null,
        ]);

        $report->update([
            'status' => ReportStatus::VERIFIED,
        ]);

        $report->refresh();

        $this->assertNotNull($report->responded_at);
    }

    public function test_it_clears_resolved_at_when_report_is_reopened(): void
    {
        $report = Report::factory()->create([
            'status' => ReportStatus::SUBMITTED,
        ]);

        $report->update([
            'status' => ReportStatus::RESOLVED,
        ]);

        $report->refresh();
        $this->assertNotNull($report->resolved_at);

        $report->update([
            'status' => ReportStatus::IN_PROGRESS,
        ]);

        $report->refresh();

        $this->assertNull($report->resolved_at);
    }
}
