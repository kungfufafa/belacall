<?php

namespace Tests\Feature;

use App\Enums\ReportStatus;
use App\Models\Report;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WebReportSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_submission_allows_optional_evidence_and_gps(): void
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
        ];

        $response = $this->post('/lapor', $payload);

        $report = Report::query()->first();

        $this->assertNotNull($report);
        $this->assertSame(ReportStatus::SUBMITTED, $report->status);
        $this->assertDatabaseHas('reports', [
            'ticket_number' => $report->ticket_number,
            'location_name' => 'Jl. Melati',
            'latitude' => -6.201234,
            'longitude' => 106.812345,
        ]);
        $this->assertDatabaseHas('users', [
            'phone' => '6281234567890',
        ]);
        $this->assertDatabaseCount('report_evidences', 0);
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
}
