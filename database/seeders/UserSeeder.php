<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Seed the users table with realistic Indonesian village personas.
     * Based on PRD.md user personas and realistic village context.
     */
    public function run(): void
    {
        // =============================================
        // ADMIN & STAFF (Existing from original seeder)
        // =============================================

        // 1. Admin System
        User::firstOrCreate(
            ['email' => 'admin@belacall.test'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'phone' => '08110000001',
            ]
        );

        // 2. Operator Desa (Mbak Siti - from PRD persona)
        User::firstOrCreate(
            ['email' => 'siti@belacall.test'],
            [
                'name' => 'Mbak Siti (Operator)',
                'password' => Hash::make('password'),
                'role' => 'operator',
                'phone' => '628120000002',
            ]
        );

        // 3. Additional Operator - Pak Darto
        User::firstOrCreate(
            ['email' => 'darto@belacall.test'],
            [
                'name' => 'Pak Darto (Operator)',
                'password' => Hash::make('password'),
                'role' => 'operator',
                'phone' => '628120000003',
            ]
        );

        // 4. Pimpinan Desa (Pak Lurah Joko - from PRD persona)
        User::firstOrCreate(
            ['email' => 'lurah@belacall.test'],
            [
                'name' => 'Pak Lurah Joko',
                'password' => Hash::make('password'),
                'role' => 'pimpinan',
                'phone' => '628130000003',
            ]
        );

        // =============================================
        // WARGA DESA (Realistic Indonesian villagers)
        // =============================================

        $wargaData = [
            // Pak Budi - Main persona from PRD (Petani, 45 tahun)
            [
                'name' => 'Pak Budi',
                'phone' => '6285700000001',
                'email' => null,
                'password' => null,
                'role' => 'warga',
            ],
            // Bu Tini - Pedagang warung
            [
                'name' => 'Bu Tini',
                'phone' => '6285700000002',
                'email' => null,
                'password' => null,
                'role' => 'warga',
            ],
            // Pak Slamet - Petani padi
            [
                'name' => 'Pak Slamet',
                'phone' => '6285700000003',
                'email' => null,
                'password' => null,
                'role' => 'warga',
            ],
            // Bu Rani - Ibu rumah tangga
            [
                'name' => 'Bu Rani',
                'phone' => '6285700000004',
                'email' => null,
                'password' => null,
                'role' => 'warga',
            ],
            // Mas Agus - Pemuda desa, teknisi
            [
                'name' => 'Mas Agus',
                'phone' => '6285700000005',
                'email' => null,
                'password' => null,
                'role' => 'warga',
            ],
            // Mbak Dewi - Guru PAUD
            [
                'name' => 'Mbak Dewi',
                'phone' => '6285700000006',
                'email' => null,
                'password' => null,
                'role' => 'warga',
            ],
            // Pak Hadi - Tukang kayu
            [
                'name' => 'Pak Hadi',
                'phone' => '6285700000007',
                'email' => null,
                'password' => null,
                'role' => 'warga',
            ],
            // Bu Yanti - Penjual jamu
            [
                'name' => 'Bu Yanti',
                'phone' => '6285700000008',
                'email' => null,
                'password' => null,
                'role' => 'warga',
            ],
            // Pak Bambang - Peternak ayam
            [
                'name' => 'Pak Bambang',
                'phone' => '6285700000009',
                'email' => null,
                'password' => null,
                'role' => 'warga',
            ],
            // Mas Rudi - Ojek online
            [
                'name' => 'Mas Rudi',
                'phone' => '6285700000010',
                'email' => null,
                'password' => null,
                'role' => 'warga',
            ],
        ];

        foreach ($wargaData as $warga) {
            User::firstOrCreate(
                ['phone' => $warga['phone']],
                $warga
            );
        }
    }
}
