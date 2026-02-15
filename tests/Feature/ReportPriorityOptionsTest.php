<?php

namespace Tests\Feature;

use App\Enums\ReportPriority;
use App\Filament\Resources\Reports\ReportResource;
use App\Models\SlaConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ReportPriorityOptionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_priority_options_include_default_sla_durations(): void
    {
        $options = ReportResource::priorityOptionsWithSla();

        $this->assertSame(
            'Mendesak (Respon 15 menit | Selesai 2 jam)',
            $options[ReportPriority::URGENT->value]
        );
        $this->assertSame(
            'Tinggi (Respon 1 jam | Selesai 8 jam)',
            $options[ReportPriority::HIGH->value]
        );
        $this->assertSame(
            'Sedang (Respon 4 jam | Selesai 2 hari)',
            $options[ReportPriority::MEDIUM->value]
        );
        $this->assertSame(
            'Rendah (Respon 1 hari | Selesai 7 hari)',
            $options[ReportPriority::LOW->value]
        );
    }

    public function test_priority_options_use_custom_sla_config(): void
    {
        SlaConfig::factory()->create([
            'priority' => ReportPriority::URGENT,
            'response_time_minutes' => 75,
            'resolution_time_minutes' => 1505,
        ]);

        Cache::flush();
        $options = ReportResource::priorityOptionsWithSla();

        $this->assertSame(
            'Mendesak (Respon 1 jam 15 menit | Selesai 1 hari 1 jam 5 menit)',
            $options[ReportPriority::URGENT->value]
        );
    }
}
