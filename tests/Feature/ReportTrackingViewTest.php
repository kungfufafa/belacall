<?php

namespace Tests\Feature;

use App\Enums\ReportStatus;
use App\Models\Report;
use App\Models\ReportEvidence;
use App\Models\ReportHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTrackingViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_tracking_page_shows_submitted_status_label(): void
    {
        $reporter = User::factory()->create([
            'phone' => '628123450000',
        ]);

        $report = Report::factory()->create([
            'ticket_number' => 'T-20260108-022',
            'user_id' => $reporter->id,
            'status' => ReportStatus::SUBMITTED,
        ]);

        $this->get('/tracking?ticket='.$report->ticket_number)
            ->assertOk()
            ->assertSee('Verifikasi Nomor HP')
            ->assertDontSee('Masuk');
    }

    public function test_tracking_page_uses_latest_history_status_and_shows_evidence(): void
    {
        $reporter = User::factory()->create([
            'name' => 'Pak Budi',
            'phone' => '628123456789',
        ]);
        $report = Report::factory()->create([
            'ticket_number' => 'T-20260108-021',
            'user_id' => $reporter->id,
            'status' => ReportStatus::IN_PROGRESS,
        ]);
        ReportHistory::create([
            'report_id' => $report->id,
            'action' => 'STATUS_CHANGE',
            'old_value' => ReportStatus::IN_PROGRESS->value,
            'new_value' => ReportStatus::RESOLVED->value,
            'notes' => 'Penanganan selesai dilakukan',
        ]);
        ReportEvidence::create([
            'report_id' => $report->id,
            'file_path' => 'https://example.com/bukti.jpg',
            'file_type' => 'image',
            'uploaded_by' => 'system',
        ]);

        $this->withSession($this->trackingAccessSession($report->ticket_number, (string) $reporter->phone))
            ->get('/tracking?ticket='.$report->ticket_number)
            ->assertOk()
            ->assertSee('Selesai')
            ->assertSee('Bukti Laporan')
            ->assertSee('bukti.jpg')
            ->assertSee('Riwayat Status');
    }

    public function test_revision_submission_updates_status_and_history(): void
    {
        $reporter = User::factory()->create([
            'phone' => '628123456000',
        ]);

        $report = Report::factory()->create([
            'ticket_number' => 'T-20260108-023',
            'user_id' => $reporter->id,
            'status' => ReportStatus::NEEDS_REVISION,
        ]);

        $response = $this->withSession($this->trackingAccessSession($report->ticket_number, (string) $reporter->phone))
            ->post(route('report.tracking.revision'), [
                'ticket' => $report->ticket_number,
                'notes' => 'Detail lokasi sudah diperjelas.',
                'description' => 'Ada lampu jalan mati di RT 02, dekat mushola.',
            ]);

        $response->assertRedirect(route('report.tracking.view', ['ticket' => $report->ticket_number]));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('reports', [
            'id' => $report->id,
            'status' => ReportStatus::SUBMITTED->value,
        ]);
        $this->assertDatabaseHas('report_histories', [
            'report_id' => $report->id,
            'action' => 'STATUS_CHANGE',
            'old_value' => ReportStatus::NEEDS_REVISION->value,
            'new_value' => ReportStatus::SUBMITTED->value,
        ]);
    }

    public function test_revision_submission_rejected_when_status_not_needs_revision(): void
    {
        $reporter = User::factory()->create([
            'phone' => '628123456001',
        ]);

        $report = Report::factory()->create([
            'ticket_number' => 'T-20260108-024',
            'user_id' => $reporter->id,
            'status' => ReportStatus::SUBMITTED,
        ]);

        $response = $this->withSession($this->trackingAccessSession($report->ticket_number, (string) $reporter->phone))
            ->post(route('report.tracking.revision'), [
                'ticket' => $report->ticket_number,
                'notes' => 'Perbaikan tambahan.',
            ]);

        $response->assertRedirect(route('report.tracking.view', ['ticket' => $report->ticket_number]));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('reports', [
            'id' => $report->id,
            'status' => ReportStatus::SUBMITTED->value,
        ]);
        $this->assertDatabaseMissing('report_histories', [
            'report_id' => $report->id,
            'action' => 'STATUS_CHANGE',
            'old_value' => ReportStatus::NEEDS_REVISION->value,
        ]);
    }

    public function test_revision_form_shows_gps_button_when_needed(): void
    {
        $reporter = User::factory()->create([
            'phone' => '628123456002',
        ]);

        $report = Report::factory()->create([
            'ticket_number' => 'T-20260108-025',
            'user_id' => $reporter->id,
            'status' => ReportStatus::NEEDS_REVISION,
        ]);

        $this->withSession($this->trackingAccessSession($report->ticket_number, (string) $reporter->phone))
            ->get('/tracking?ticket='.$report->ticket_number)
            ->assertOk()
            ->assertSee('Perbaiki Laporan')
            ->assertSee('Ambil Lokasi GPS');
    }

    public function test_verify_phone_grants_tracking_access(): void
    {
        $reporter = User::factory()->create([
            'phone' => '628123456003',
        ]);

        $report = Report::factory()->create([
            'ticket_number' => 'T-20260108-026',
            'user_id' => $reporter->id,
            'status' => ReportStatus::SUBMITTED,
        ]);

        $verifyResponse = $this->post(route('report.tracking.verify_phone'), [
            'ticket' => $report->ticket_number,
            'phone' => '08123456003',
        ]);

        $verifyResponse->assertRedirect(route('report.tracking.view', ['ticket' => $report->ticket_number]));
        $verifyResponse->assertSessionHas('success');

        $this->get('/tracking?ticket='.$report->ticket_number)
            ->assertOk()
            ->assertSee('Masuk')
            ->assertDontSee('Verifikasi Nomor HP');
    }

    public function test_verify_phone_rejected_for_mismatched_phone(): void
    {
        $reporter = User::factory()->create([
            'phone' => '628123456004',
        ]);

        $report = Report::factory()->create([
            'ticket_number' => 'T-20260108-027',
            'user_id' => $reporter->id,
            'status' => ReportStatus::SUBMITTED,
        ]);

        $response = $this->post(route('report.tracking.verify_phone'), [
            'ticket' => $report->ticket_number,
            'phone' => '081200000000',
        ]);

        $response->assertRedirect(route('report.tracking.view', ['ticket' => $report->ticket_number]));
        $response->assertSessionHas('error');
    }

    /**
     * @return array<string, array{phone: string, verified_at: int}>
     */
    private function trackingAccessSession(string $ticket, string $phone): array
    {
        return [
            "tracking_access_{$ticket}" => [
                'phone' => $phone,
                'verified_at' => now()->getTimestamp(),
            ],
        ];
    }
}
