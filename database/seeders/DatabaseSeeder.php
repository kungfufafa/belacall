<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Admin System
        User::create([
            'name' => 'Administrator',
            'email' => 'admin@belacall.test',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'phone' => '08110000001',
        ]);

        // 2. Operator Desa (Mbak Siti)
        User::create([
            'name' => 'Mbak Siti (Operator)',
            'email' => 'siti@belacall.test',
            'password' => Hash::make('password'),
            'role' => 'operator',
            'phone' => '08120000002',
        ]);

        // 3. Pimpinan Desa (Pak Lurah Joko)
        User::create([
            'name' => 'Pak Lurah Joko',
            'email' => 'lurah@belacall.test',
            'password' => Hash::make('password'),
            'role' => 'pimpinan',
            'phone' => '08130000003',
        ]);

        // 4. Dummy Warga (Pak Budi)
        User::create([
            'name' => 'Pak Budi (Warga)',
            'phone' => '628570000004', // Format internasional untuk simulasi WA
            'email' => null,
            'password' => null, // Warga login via OTP
            'role' => 'warga',
        ]);
    }
}
