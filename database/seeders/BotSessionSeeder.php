<?php

namespace Database\Seeders;

use App\Models\BotSession;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class BotSessionSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $sessions = [
            [
                'phone_number' => '6285700000001',
                'state' => 'IDLE',
                'temp_data' => null,
                'last_interaction_at' => $now->copy()->subDays(2),
            ],
            [
                'phone_number' => '6285700000002',
                'state' => 'WAITING_REPORT_TITLE',
                'temp_data' => json_encode(['intent' => 'report']),
                'last_interaction_at' => $now->copy()->subMinutes(30),
            ],
            [
                'phone_number' => '6285700000003',
                'state' => 'WAITING_REPORT_PHOTO',
                'temp_data' => json_encode([
                    'intent' => 'report',
                    'title' => 'Jalan rusak di depan rumah',
                ]),
                'last_interaction_at' => $now->copy()->subMinutes(15),
            ],
            [
                'phone_number' => '6285700000004',
                'state' => 'WAITING_REPORT_LOCATION',
                'temp_data' => json_encode([
                    'intent' => 'report',
                    'title' => 'Lampu jalan mati',
                    'photo_path' => 'temp/6285700000004/photo_123.jpg',
                ]),
                'last_interaction_at' => $now->copy()->subMinutes(5),
            ],
            [
                'phone_number' => '6285700000005',
                'state' => 'IDLE',
                'temp_data' => null,
                'last_interaction_at' => $now->copy()->subDays(7),
            ],
            [
                'phone_number' => '6281234567890',
                'state' => 'WAITING_REPORT_TITLE',
                'temp_data' => json_encode(['intent' => 'report']),
                'last_interaction_at' => $now->copy()->subHours(3),
            ],
            [
                'phone_number' => '6289876543210',
                'state' => 'IDLE',
                'temp_data' => json_encode(['last_ticket' => 'T-20260120-006']),
                'last_interaction_at' => $now->copy()->subHours(1),
            ],
            [
                'phone_number' => '6285555555555',
                'state' => 'WAITING_REPORT_PHOTO',
                'temp_data' => json_encode([
                    'intent' => 'report',
                    'title' => 'Sampah menumpuk',
                    'retry_count' => 2,
                ]),
                'last_interaction_at' => $now->copy()->subMinutes(45),
            ],
        ];

        foreach ($sessions as $session) {
            BotSession::firstOrCreate(
                ['phone_number' => $session['phone_number']],
                $session
            );
        }
    }
}
