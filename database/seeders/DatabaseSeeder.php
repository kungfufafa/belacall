<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            SlaConfigSeeder::class,
            EmergencyShortcutSeeder::class,
            ReportSeeder::class,
            ReportEvidenceSeeder::class,
            ReportHistorySeeder::class,
            BotSessionSeeder::class,
        ]);
    }
}
