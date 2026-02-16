<?php

namespace Tests\Feature;

use App\Enums\ReportStatus;
use App\Enums\Role;
use App\Models\Report;
use App\Models\User;
use App\Notifications\ReportSubmittedForTriage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WebReportSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_submission_requires_evidence_and_allows_gps(): void
    {
        Storage::fake('public');
        Http::fake();

        $payload = [
            'title' => 'Pencurian motor',
            'description' => 'Motor saya hilang di area parkir.',
            'phone' => '081234567890',
            'location_name' => 'Jl. Melati',
            'latitude' => -6.201234,
            'longitude' => 106.812345,
            'evidence' => UploadedFile::fake()->image('bukti.jpg'),
        ];

        $response = $this->post('/lapor', $payload);

        $report = Report::query()->first();

        $this->assertNotNull($report);
        $this->assertSame(ReportStatus::SUBMITTED, $report->status);
        $this->assertNull($report->priority);
        $this->assertDatabaseHas('reports', [
            'ticket_number' => $report->ticket_number,
            'location_name' => 'Jl. Melati',
            'latitude' => -6.201234,
            'longitude' => 106.812345,
            'priority' => null,
        ]);
        $this->assertDatabaseHas('users', [
            'phone' => '6281234567890',
        ]);
        $this->assertDatabaseCount('report_evidences', 1);
        $response->assertRedirect(route('report.tracking.view', ['ticket' => $report->ticket_number]));
    }

    public function test_report_submission_stores_evidence_when_present(): void
    {
        Storage::fake('public');
        Http::fake();

        $payload = [
            'title' => 'Lampu jalan mati',
            'description' => 'Lampu jalan depan rumah mati.',
            'phone' => '081234000111',
            'location_name' => 'Dusun Melati',
            'evidence' => UploadedFile::fake()->image('bukti.jpg'),
        ];

        $response = $this->post('/lapor', $payload);

        $report = Report::query()->first();

        $this->assertNotNull($report);
        $this->assertDatabaseHas('report_evidences', [
            'report_id' => $report->id,
            'file_type' => 'image',
        ]);
        $this->assertCount(1, Storage::disk('public')->allFiles('evidences'));
        $response->assertRedirect(route('report.tracking.view', ['ticket' => $report->ticket_number]));
    }

    public function test_report_submission_rejects_invalid_payload(): void
    {
        Storage::fake('public');
        Http::fake();

        $response = $this->from('/lapor')->post('/lapor', [
            'title' => 'abc',
            'description' => 'pendek',
            'phone' => 'not-a-phone',
            'location_name' => '',
        ]);

        $response->assertRedirect('/lapor');
        $response->assertSessionHasErrors([
            'title',
            'description',
            'phone',
            'location_name',
            'evidence',
        ]);
        $this->assertDatabaseCount('reports', 0);
    }

    public function test_report_submission_requires_evidence(): void
    {
        Storage::fake('public');
        Http::fake();

        $response = $this->from('/lapor')->post('/lapor', [
            'title' => 'Kabel listrik jatuh',
            'description' => 'Ada kabel listrik tergeletak di jalan utama.',
            'phone' => '081234111222',
            'location_name' => 'Jl. Kenanga',
        ]);

        $response->assertRedirect('/lapor');
        $response->assertSessionHasErrors([
            'evidence',
        ]);
        $this->assertDatabaseCount('reports', 0);
    }

    public function test_report_submission_notifies_pimpinan_for_triage_not_admin(): void
    {
        Storage::fake('public');
        Http::fake();
        Notification::fake();

        $pimpinan = User::factory()->create(['role' => Role::PIMPINAN]);
        $admin = User::factory()->create(['role' => Role::ADMIN]);

        $response = $this->post('/lapor', [
            'title' => 'Drainase tersumbat',
            'description' => 'Saluran air tersumbat dan meluap ke jalan kampung.',
            'phone' => '081299900011',
            'location_name' => 'RT 03 / RW 01',
            'evidence' => UploadedFile::fake()->image('drainase.jpg'),
        ]);

        $response->assertRedirect();
        Notification::assertSentTo($pimpinan, ReportSubmittedForTriage::class);
        Notification::assertNotSentTo($admin, ReportSubmittedForTriage::class);
    }
}
