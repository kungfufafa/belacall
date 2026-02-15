<?php

namespace Tests\Feature;

use App\Enums\ReportPriority;
use App\Enums\ReportStatus;
use App\Models\Report;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_sets_responded_at_when_status_moves_to_in_progress(): void
    {
        $report = Report::factory()->create([
            'status' => ReportStatus::VERIFIED,
            'responded_at' => null,
        ]);

        $report->update([
            'status' => ReportStatus::IN_PROGRESS,
        ]);

        $report->refresh();

        $this->assertNotNull($report->responded_at);
    }

    public function test_it_does_not_set_responded_at_on_assignment_verification(): void
    {
        $report = Report::factory()->create([
            'status' => ReportStatus::SUBMITTED,
            'responded_at' => null,
        ]);

        $report->update([
            'status' => ReportStatus::VERIFIED,
        ]);

        $report->refresh();

        $this->assertNull($report->responded_at);
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

    public function test_it_does_not_set_deadlines_when_creating_report_without_priority(): void
    {
        $report = Report::factory()->create([
            'status' => ReportStatus::SUBMITTED,
            'priority' => null,
        ]);

        $report->refresh();

        $this->assertNull($report->response_deadline);
        $this->assertNull($report->resolution_deadline);
    }

    public function test_it_sets_deadlines_when_priority_assigned_for_first_time(): void
    {
        // Create report without priority
        $report = Report::factory()->create([
            'status' => ReportStatus::SUBMITTED,
            'priority' => null,
            'created_at' => now()->subHour(), // Created 1 hour ago
        ]);

        $this->assertNull($report->response_deadline);
        $this->assertNull($report->resolution_deadline);

        // Assign priority now (simulating pimpinan assigning operator)
        $assignmentTime = now();
        $report->update([
            'priority' => ReportPriority::URGENT,
            'status' => ReportStatus::VERIFIED,
        ]);

        $report->refresh();

        // Deadlines should be calculated from assignment time, not created_at
        $this->assertNotNull($report->response_deadline);
        $this->assertNotNull($report->resolution_deadline);

        // Response deadline should be ~15 minutes from NOW, not from created_at (1 hour ago)
        $expectedResponseDeadline = $assignmentTime->copy()->addMinutes(15);
        $this->assertEquals(
            $expectedResponseDeadline->timestamp,
            $report->response_deadline->timestamp,
            'Response deadline should be calculated from assignment time',
            5 // Allow 5 second tolerance
        );

        // Resolution deadline should be ~120 minutes from NOW
        $expectedResolutionDeadline = $assignmentTime->copy()->addMinutes(120);
        $this->assertEquals(
            $expectedResolutionDeadline->timestamp,
            $report->resolution_deadline->timestamp,
            'Resolution deadline should be calculated from assignment time',
            5
        );
    }

    public function test_it_does_not_recalculate_deadlines_when_priority_changes_after_first_assignment(): void
    {
        // Create report and assign priority first time
        $report = Report::factory()->create([
            'status' => ReportStatus::SUBMITTED,
            'priority' => null,
        ]);

        $report->update([
            'priority' => ReportPriority::MEDIUM,
            'status' => ReportStatus::VERIFIED,
        ]);

        $report->refresh();
        $originalResponseDeadline = $report->response_deadline;
        $originalResolutionDeadline = $report->resolution_deadline;

        $this->assertNotNull($originalResponseDeadline);
        $this->assertNotNull($originalResolutionDeadline);

        // Try to change priority (should be prevented by business logic)
        // But even if it happens, deadlines should not change
        sleep(1); // Ensure time has passed

        $report->update([
            'priority' => ReportPriority::URGENT,
        ]);

        $report->refresh();

        // Deadlines should remain the same
        $this->assertEquals(
            $originalResponseDeadline->timestamp,
            $report->response_deadline->timestamp,
            'Response deadline should not change after first assignment'
        );
        $this->assertEquals(
            $originalResolutionDeadline->timestamp,
            $report->resolution_deadline->timestamp,
            'Resolution deadline should not change after first assignment'
        );
    }
}
