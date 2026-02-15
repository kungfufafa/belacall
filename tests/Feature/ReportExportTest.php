<?php

namespace Tests\Feature;

use App\Enums\ReportPriority;
use App\Enums\ReportStatus;
use App\Enums\Role;
use App\Filament\Resources\Reports\Pages\ListReports;
use App\Models\Report;
use App\Models\User;
use App\Services\ReportExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReportExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_filtered_reports_returns_all_without_filters(): void
    {
        Report::factory()->count(3)->create();

        $service = new ReportExportService;
        $reports = $service->getFilteredReports([]);

        $this->assertCount(3, $reports);
    }

    public function test_get_filtered_reports_filters_by_date_range(): void
    {
        Report::factory()->create(['created_at' => now()->subDays(10)]);
        Report::factory()->create(['created_at' => now()->subDays(5)]);
        Report::factory()->create(['created_at' => now()->subDay()]);

        $service = new ReportExportService;
        $reports = $service->getFilteredReports([
            'date_from' => now()->subDays(7)->toDateString(),
            'date_to' => now()->toDateString(),
        ]);

        $this->assertCount(2, $reports);
    }

    public function test_get_filtered_reports_filters_by_status(): void
    {
        Report::factory()->create(['status' => ReportStatus::SUBMITTED]);
        Report::factory()->create(['status' => ReportStatus::RESOLVED]);
        Report::factory()->create(['status' => ReportStatus::CLOSED]);

        $service = new ReportExportService;
        $reports = $service->getFilteredReports([
            'status' => ReportStatus::RESOLVED->value,
        ]);

        $this->assertCount(1, $reports);
        $this->assertEquals(ReportStatus::RESOLVED, $reports->first()->status);
    }

    public function test_get_filtered_reports_filters_by_priority(): void
    {
        Report::factory()->create(['priority' => ReportPriority::URGENT]);
        Report::factory()->create(['priority' => ReportPriority::LOW]);

        $service = new ReportExportService;
        $reports = $service->getFilteredReports([
            'priority' => ReportPriority::URGENT->value,
        ]);

        $this->assertCount(1, $reports);
        $this->assertEquals(ReportPriority::URGENT, $reports->first()->priority);
    }

    public function test_build_summary_data_computes_correct_totals(): void
    {
        Report::factory()->create(['status' => ReportStatus::SUBMITTED, 'priority' => ReportPriority::URGENT]);
        Report::factory()->create([
            'status' => ReportStatus::RESOLVED,
            'priority' => ReportPriority::HIGH,
            'responded_at' => now()->subHours(5),
            'response_deadline' => now()->subHours(4),
            'resolved_at' => now()->subHours(2),
            'resolution_deadline' => now()->subHour(),
        ]);
        Report::factory()->create([
            'status' => ReportStatus::CLOSED,
            'priority' => ReportPriority::LOW,
            'responded_at' => now()->subHours(2),
            'response_deadline' => now()->subHours(3),
            'resolved_at' => now()->subHour(),
            'resolution_deadline' => now()->subHours(2),
        ]);

        $service = new ReportExportService;
        $reports = $service->getFilteredReports([]);
        $summary = $service->buildSummaryData($reports);

        $this->assertSame(3, $summary['total']);
        $this->assertArrayHasKey('by_status', $summary);
        $this->assertArrayHasKey('by_priority', $summary);
        $this->assertArrayHasKey('response_sla_compliance_rate', $summary);
        $this->assertArrayHasKey('resolution_sla_compliance_rate', $summary);
        $this->assertArrayHasKey('sla_compliance_rate', $summary);
        $this->assertArrayHasKey('average_resolution_time', $summary);
        $this->assertSame(1, $summary['by_status'][ReportStatus::SUBMITTED->value]);
        $this->assertSame(1, $summary['by_priority'][ReportPriority::URGENT->value]);
        $this->assertSame(50.0, $summary['response_sla_compliance_rate']);
        $this->assertSame(50.0, $summary['resolution_sla_compliance_rate']);
        $this->assertSame($summary['resolution_sla_compliance_rate'], $summary['sla_compliance_rate']);
    }

    public function test_pdf_export_returns_success_response(): void
    {
        $admin = User::factory()->create(['role' => Role::ADMIN]);
        Report::factory()->count(3)->create();

        $this->actingAs($admin);

        Livewire::test(ListReports::class)
            ->callAction('exportPdf', [
                'date_from' => null,
                'date_to' => null,
                'status' => null,
                'priority' => null,
            ])
            ->assertFileDownloaded();
    }

    public function test_excel_export_returns_success_response(): void
    {
        $admin = User::factory()->create(['role' => Role::ADMIN]);
        Report::factory()->count(3)->create();

        $this->actingAs($admin);

        Livewire::test(ListReports::class)
            ->callAction('exportExcel', [
                'date_from' => null,
                'date_to' => null,
                'status' => null,
                'priority' => null,
            ])
            ->assertFileDownloaded();
    }

    public function test_only_authorized_users_can_export(): void
    {
        $operator = User::factory()->create(['role' => Role::OPERATOR]);

        $this->actingAs($operator);
        Livewire::test(ListReports::class)
            ->assertActionHidden('exportPdf')
            ->assertActionHidden('exportExcel');
    }

    public function test_pimpinan_can_export(): void
    {
        $pimpinan = User::factory()->create(['role' => Role::PIMPINAN]);
        Report::factory()->count(2)->create();

        $this->actingAs($pimpinan);

        Livewire::test(ListReports::class)
            ->assertActionVisible('exportPdf')
            ->assertActionVisible('exportExcel')
            ->callAction('exportPdf', [
                'date_from' => null,
                'date_to' => null,
                'status' => null,
                'priority' => null,
            ])
            ->assertFileDownloaded();
    }

    public function test_export_with_date_range_filter(): void
    {
        $admin = User::factory()->create(['role' => Role::ADMIN]);
        Report::factory()->create(['created_at' => now()->subDays(30)]);
        Report::factory()->create(['created_at' => now()->subDays(5)]);

        $this->actingAs($admin);

        Livewire::test(ListReports::class)
            ->callAction('exportPdf', [
                'date_from' => now()->subDays(7)->toDateString(),
                'date_to' => now()->toDateString(),
                'status' => null,
                'priority' => null,
            ])
            ->assertFileDownloaded();
    }

    public function test_export_with_status_filter(): void
    {
        $admin = User::factory()->create(['role' => Role::ADMIN]);
        Report::factory()->create(['status' => ReportStatus::RESOLVED]);
        Report::factory()->create(['status' => ReportStatus::SUBMITTED]);

        $this->actingAs($admin);

        Livewire::test(ListReports::class)
            ->callAction('exportExcel', [
                'date_from' => null,
                'date_to' => null,
                'status' => ReportStatus::RESOLVED->value,
                'priority' => null,
            ])
            ->assertFileDownloaded();
    }
}
