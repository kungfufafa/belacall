<?php

namespace Database\Seeders;

use App\Models\EmergencyShortcut;
use Illuminate\Database\Seeder;

class EmergencyShortcutSeeder extends Seeder
{
    public function run(): void
    {
        $shortcuts = [
            ['name' => 'Ambulans', 'phone_number' => '118', 'sort_order' => 1],
            ['name' => 'Pemadam Kebakaran', 'phone_number' => '113', 'sort_order' => 2],
            ['name' => 'Polisi', 'phone_number' => '110', 'sort_order' => 3],
            ['name' => 'PLN', 'phone_number' => '123', 'sort_order' => 4],
            ['name' => 'SAR', 'phone_number' => '115', 'sort_order' => 5],
        ];

        foreach ($shortcuts as $shortcut) {
            EmergencyShortcut::firstOrCreate(
                ['phone_number' => $shortcut['phone_number']],
                $shortcut
            );
        }
    }
}
