<?php

namespace Database\Seeders;

use App\Enums\ReportPriority;
use App\Models\SlaConfig;
use Illuminate\Database\Seeder;

class SlaConfigSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            ReportPriority::URGENT->value => ['response_time_minutes' => 15, 'resolution_time_minutes' => 120],
            ReportPriority::HIGH->value => ['response_time_minutes' => 60, 'resolution_time_minutes' => 480],
            ReportPriority::MEDIUM->value => ['response_time_minutes' => 240, 'resolution_time_minutes' => 2880],
            ReportPriority::LOW->value => ['response_time_minutes' => 1440, 'resolution_time_minutes' => 10080],
        ];

        foreach ($defaults as $priority => $times) {
            SlaConfig::firstOrCreate(
                ['priority' => $priority],
                $times
            );
        }
    }
}
