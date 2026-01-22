<?php

namespace Tests\Feature;

use App\Enums\ReportCategory;
use App\Enums\ReportStatus;
use App\Enums\Role;
use App\Filament\Pages\DashboardAdmin;
use App\Filament\Pages\DashboardOperator;
use App\Filament\Pages\DashboardPimpinan;
use App\Filament\Resources\Reports\Pages\ListReports;
use App\Filament\Resources\Reports\Pages\ViewReport;
use App\Filament\Resources\Reports\ReportResource;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\UserResource;
use App\Models\Report;
use App\Models\User;
use App\Notifications\ReportAssigned;
use Filament\Actions\Action;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class AdminPanelRoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_operator_can_access_operator_dashboard_and_reports(): void
    {
        $operator = User::factory()->create(['role' => Role::OPERATOR]);
        $assignedReport = Report::factory()->create([
            'assignee_id' => $operator->id,
            'status' => ReportStatus::SUBMITTED,
        ]);
        $otherReport = Report::factory()->create();

        $this->actingAs($operator);

        $this->get(DashboardOperator::getUrl())
            ->assertOk()
            ->assertSee('Ringkasan Laporan')
            ->assertSee('Tiket Ditugaskan ke Saya')
            ->assertSee('Laporan Terbaru');
        $this->get(DashboardPimpinan::getUrl())->assertForbidden();
        $this->get(ReportResource::getUrl('index'))
            ->assertOk()
            ->assertSee('Nomor Tiket')
            ->assertSee($assignedReport->ticket_number)
            ->assertDontSee($otherReport->ticket_number);
        $this->get(ReportResource::getUrl('create'))->assertForbidden();
        $this->get(ReportResource::getUrl('edit', ['record' => $assignedReport]))->assertForbidden();
        $this->get(UserResource::getUrl('index'))->assertForbidden();
    }

    public function test_pimpinan_can_view_reports_without_edit_access(): void
    {
        $pimpinan = User::factory()->create(['role' => Role::PIMPINAN]);
        $report = Report::factory()->create();

        $this->actingAs($pimpinan);

        $this->get(DashboardPimpinan::getUrl())
            ->assertOk()
            ->assertSee('Overview Kasus')
            ->assertSee('Kasus Overdue')
            ->assertSee('Aktivitas Terbaru');
        $this->get(DashboardOperator::getUrl())->assertForbidden();
        $this->get(ReportResource::getUrl('index'))->assertOk();
        $this->get(ReportResource::getUrl('view', ['record' => $report]))
            ->assertOk()
            ->assertSee('Detail Laporan')
            ->assertSee('Bukti Laporan')
            ->assertSee('Riwayat Aktivitas');
        $this->get(ReportResource::getUrl('edit', ['record' => $report]))->assertForbidden();
        $this->get(ReportResource::getUrl('create'))->assertForbidden();
        $this->get(UserResource::getUrl('index'))->assertForbidden();
    }

    public function test_admin_can_manage_users_and_reports(): void
    {
        $admin = User::factory()->create(['role' => Role::ADMIN]);
        $report = Report::factory()->create();

        $this->actingAs($admin);

        $this->get(DashboardAdmin::getUrl())->assertOk();
        $this->get(UserResource::getUrl('index'))->assertOk();
        $this->get(ReportResource::getUrl('edit', ['record' => $report]))->assertOk();

        Livewire::test(ViewReport::class, ['record' => $report->id])
            ->assertActionHasColor('edit', 'warning');
    }

    public function test_user_resource_actions_use_slideover_modals(): void
    {
        $admin = User::factory()->create(['role' => Role::ADMIN]);
        $user = User::factory()->create();

        $this->actingAs($admin);

        $this->assertFalse(UserResource::hasPage('create'));
        $this->assertFalse(UserResource::hasPage('edit'));

        Livewire::test(ListUsers::class)
            ->assertActionExists('create', fn (Action $action): bool => $action->isModalSlideOver())
            ->assertTableActionExists('edit', fn (Action $action): bool => $action->isModalSlideOver(), $user)
            ->assertTableActionHasColor('edit', 'warning', $user);
    }

    public function test_pimpinan_can_assign_operator_from_report_view(): void
    {
        Notification::fake();

        $pimpinan = User::factory()->create(['role' => Role::PIMPINAN]);
        $operator = User::factory()->create(['role' => Role::OPERATOR]);
        $report = Report::factory()->create([
            'assignee_id' => null,
        ]);

        $this->actingAs($pimpinan);

        Livewire::test(ViewReport::class, ['record' => $report->id])
            ->assertActionHasColor('assignOperator', 'info')
            ->callAction('assignOperator', [
                'assignee_id' => $operator->id,
                'notes' => 'Mohon ditindaklanjuti.',
            ]);

        $this->assertDatabaseHas('reports', [
            'id' => $report->id,
            'assignee_id' => $operator->id,
        ]);
        $this->assertDatabaseHas('report_histories', [
            'report_id' => $report->id,
            'action' => 'ASSIGNMENT',
            'new_value' => $operator->name,
        ]);
        Notification::assertSentTo($operator, ReportAssigned::class);
    }

    public function test_operator_can_follow_up_assigned_report_from_report_view(): void
    {
        $operator = User::factory()->create(['role' => Role::OPERATOR]);
        $report = Report::factory()->create([
            'assignee_id' => $operator->id,
            'status' => ReportStatus::SUBMITTED,
        ]);

        $this->actingAs($operator);

        Livewire::test(ViewReport::class, ['record' => $report->id])
            ->assertActionHasColor('followUp', 'secondary')
            ->callAction('followUp', [
                'status' => ReportStatus::VERIFIED->value,
                'category' => ReportCategory::INFRASTRUKTUR->value,
                'notes' => 'Sedang ditangani.',
            ]);

        $this->assertDatabaseHas('reports', [
            'id' => $report->id,
            'status' => ReportStatus::VERIFIED->value,
            'category' => ReportCategory::INFRASTRUKTUR->value,
        ]);
        $this->assertDatabaseHas('report_histories', [
            'report_id' => $report->id,
            'action' => 'STATUS_CHANGE',
            'old_value' => ReportStatus::SUBMITTED->value,
            'new_value' => ReportStatus::VERIFIED->value,
        ]);
    }

    public function test_operator_must_set_category_when_verifying_report(): void
    {
        $operator = User::factory()->create(['role' => Role::OPERATOR]);
        $report = Report::factory()->create([
            'assignee_id' => $operator->id,
            'status' => ReportStatus::SUBMITTED,
        ]);

        $this->actingAs($operator);

        Livewire::test(ViewReport::class, ['record' => $report->id])
            ->callAction('followUp', [
                'status' => ReportStatus::VERIFIED->value,
                'notes' => 'Kategori harus diisi.',
            ])
            ->assertHasActionErrors(['category' => 'required']);

        $this->assertDatabaseHas('reports', [
            'id' => $report->id,
            'status' => ReportStatus::SUBMITTED->value,
        ]);
    }

    public function test_pimpinan_cannot_assign_final_reports(): void
    {
        $pimpinan = User::factory()->create(['role' => Role::PIMPINAN]);
        $finalStatuses = [
            ReportStatus::RESOLVED,
            ReportStatus::CLOSED,
            ReportStatus::REJECTED,
        ];

        foreach ($finalStatuses as $status) {
            $report = Report::factory()->create([
                'status' => $status,
            ]);

            $this->assertFalse(Gate::forUser($pimpinan)->allows('assign', $report));
        }
    }

    public function test_pimpinan_can_bulk_assign_reports(): void
    {
        Notification::fake();

        $pimpinan = User::factory()->create(['role' => Role::PIMPINAN]);
        $operator = User::factory()->create(['role' => Role::OPERATOR]);
        $reports = Report::factory()
            ->count(2)
            ->create([
                'assignee_id' => null,
                'status' => ReportStatus::SUBMITTED,
            ]);

        $this->actingAs($pimpinan);

        Livewire::test(ListReports::class)
            ->callTableBulkAction('assignOperator', $reports, [
                'assignee_id' => $operator->id,
                'notes' => 'Penugasan massal.',
            ]);

        foreach ($reports as $report) {
            $this->assertDatabaseHas('reports', [
                'id' => $report->id,
                'assignee_id' => $operator->id,
            ]);
            $this->assertDatabaseHas('report_histories', [
                'report_id' => $report->id,
                'action' => 'ASSIGNMENT',
                'new_value' => $operator->name,
            ]);
        }

        Notification::assertSentToTimes($operator, ReportAssigned::class, 2);
    }

    public function test_operator_options_show_in_progress_load(): void
    {
        $operator = User::factory()->create([
            'role' => Role::OPERATOR,
            'name' => 'Budi Operator',
        ]);

        Report::factory()->count(2)->create([
            'assignee_id' => $operator->id,
            'status' => ReportStatus::IN_PROGRESS,
        ]);
        Report::factory()->create([
            'assignee_id' => $operator->id,
            'status' => ReportStatus::SUBMITTED,
        ]);

        $options = ReportResource::operatorOptionsWithLoad();

        $this->assertArrayHasKey($operator->id, $options);
        $this->assertSame('Budi Operator (2 tugas)', $options[$operator->id]);
    }
}
