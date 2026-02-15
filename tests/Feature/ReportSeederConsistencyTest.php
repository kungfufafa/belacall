<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Report;
use App\Models\User;
use Database\Seeders\ReportSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportSeederConsistencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_seed_data_is_consistent_with_priority_assignment_flow(): void
    {
        User::factory()->create([
            'name' => 'Siti Operator',
            'email' => 'siti@belacall.test',
            'role' => Role::OPERATOR,
        ]);

        User::factory()->create([
            'name' => 'Darto Operator',
            'email' => 'darto@belacall.test',
            'role' => Role::OPERATOR,
        ]);

        foreach ([
            'Pak Budi',
            'Bu Tini',
            'Pak Slamet',
            'Bu Rani',
            'Mas Agus',
            'Mbak Dewi',
            'Pak Hadi',
            'Bu Yanti',
            'Pak Bambang',
            'Mas Rudi',
        ] as $index => $name) {
            User::factory()->create([
                'name' => $name,
                'email' => "warga{$index}@belacall.test",
                'phone' => '62812345'.str_pad((string) $index, 4, '0', STR_PAD_LEFT),
                'role' => Role::WARGA,
            ]);
        }

        $this->seed(ReportSeeder::class);

        $this->assertGreaterThan(0, Report::query()->count());
        $this->assertSame(
            0,
            Report::query()->whereNull('assignee_id')->whereNotNull('priority')->count(),
            'Laporan tanpa assignee tidak boleh memiliki priority.'
        );
        $this->assertSame(
            0,
            Report::query()->whereNotNull('assignee_id')->whereNull('priority')->count(),
            'Laporan yang sudah di-assign harus memiliki priority.'
        );
    }
}
