<?php

namespace Database\Seeders;

use App\Models\Report;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ReportSeeder extends Seeder
{
    public function run(): void
    {
        $warga = User::where('role', 'warga')->get();
        $operators = User::whereIn('role', ['operator', 'admin'])->get();

        if ($warga->isEmpty() || $operators->isEmpty()) {
            $this->command->error('Please run UserSeeder first!');

            return;
        }

        $reports = $this->getReportData($warga, $operators);

        foreach ($reports as $reportData) {
            $normalizedReportData = $this->normalizeWorkflowData($reportData);

            Report::firstOrCreate(
                ['ticket_number' => $normalizedReportData['ticket_number']],
                $normalizedReportData
            );
        }
    }

    /**
     * @param  array<string, mixed>  $reportData
     * @return array<string, mixed>
     */
    private function normalizeWorkflowData(array $reportData): array
    {
        $assigneeId = $reportData['assignee_id'] ?? null;
        $priority = $reportData['priority'] ?? null;

        if ($assigneeId === null) {
            $reportData['priority'] = null;

            return $reportData;
        }

        if ($priority === null) {
            $reportData['priority'] = 'Medium';
        }

        return $reportData;
    }

    private function getReportData($warga, $operators): array
    {
        $operatorSiti = $operators->firstWhere('email', 'siti@belacall.test');
        $operatorDarto = $operators->firstWhere('email', 'darto@belacall.test');

        $pakBudi = $warga->firstWhere('name', 'Pak Budi');
        $buTini = $warga->firstWhere('name', 'Bu Tini');
        $pakSlamet = $warga->firstWhere('name', 'Pak Slamet');
        $buRani = $warga->firstWhere('name', 'Bu Rani');
        $masAgus = $warga->firstWhere('name', 'Mas Agus');
        $mbakDewi = $warga->firstWhere('name', 'Mbak Dewi');
        $pakHadi = $warga->firstWhere('name', 'Pak Hadi');
        $buYanti = $warga->firstWhere('name', 'Bu Yanti');
        $pakBambang = $warga->firstWhere('name', 'Pak Bambang');
        $masRudi = $warga->firstWhere('name', 'Mas Rudi');

        $now = Carbon::now();

        return [
            // ==============================
            // INFRASTRUKTUR (7 reports)
            // ==============================
            [
                'ticket_number' => 'T-20260115-001',
                'user_id' => $pakBudi->id,
                'assignee_id' => $operatorSiti->id,
                'title' => 'Jalan Rusak di Dusun Suka Maju',
                'description' => 'Jalan berlubang besar di depan warung Bu Tejo, Dusun Suka Maju. Sudah beberapa kali ada motor terperosok. Mohon segera diperbaiki.',
                'priority' => 'Medium',
                'location_name' => 'Dusun Suka Maju, depan warung Bu Tejo',
                'latitude' => -7.250445,
                'longitude' => 112.768845,
                'status' => 'CLOSED',
                'created_at' => $now->copy()->subDays(14),
                'updated_at' => $now->copy()->subDays(2),
            ],
            [
                'ticket_number' => 'T-20260116-002',
                'user_id' => $masRudi->id,
                'assignee_id' => $operatorSiti->id,
                'title' => 'Jembatan Kayu Hampir Roboh',
                'description' => 'Jembatan kayu penghubung Dusun Makmur dan Dusun Sejahtera sudah lapuk dan goyang. Berbahaya untuk dilewati.',
                'priority' => 'Medium',
                'location_name' => 'Jembatan Dusun Makmur - Sejahtera',
                'latitude' => -7.251234,
                'longitude' => 112.769123,
                'status' => 'IN_PROGRESS',
                'created_at' => $now->copy()->subDays(10),
                'updated_at' => $now->copy()->subDays(3),
            ],
            [
                'ticket_number' => 'T-20260117-003',
                'user_id' => $pakSlamet->id,
                'assignee_id' => $operatorDarto->id,
                'title' => 'Saluran Irigasi Tersumbat',
                'description' => 'Saluran irigasi di sawah blok timur tersumbat sampah dan lumpur. Mengakibatkan sawah tidak dapat diairi.',
                'priority' => 'Medium',
                'location_name' => 'Sawah Blok Timur, Desa Harapan',
                'latitude' => -7.252345,
                'longitude' => 112.770234,
                'status' => 'VERIFIED',
                'created_at' => $now->copy()->subDays(7),
                'updated_at' => $now->copy()->subDays(5),
            ],
            [
                'ticket_number' => 'T-20260118-004',
                'user_id' => $mbakDewi->id,
                'assignee_id' => null,
                'title' => 'Lampu Jalan Mati di Gang 5',
                'description' => 'Lampu jalan di Gang 5 RT 03 sudah mati lebih dari 2 minggu. Sangat gelap dan berbahaya untuk warga yang pulang malam.',
                'priority' => 'Medium',
                'location_name' => 'Gang 5 RT 03 RW 02',
                'latitude' => -7.253456,
                'longitude' => 112.771345,
                'status' => 'SUBMITTED',
                'created_at' => $now->copy()->subDays(5),
                'updated_at' => $now->copy()->subDays(5),
            ],
            [
                'ticket_number' => 'T-20260119-005',
                'user_id' => $pakHadi->id,
                'assignee_id' => $operatorSiti->id,
                'title' => 'Gorong-gorong Pecah',
                'description' => 'Gorong-gorong di perempatan Dusun Sentosa pecah dan air meluber ke jalan saat hujan.',
                'priority' => 'Medium',
                'location_name' => 'Perempatan Dusun Sentosa',
                'latitude' => -7.254567,
                'longitude' => 112.772456,
                'status' => 'RESOLVED',
                'created_at' => $now->copy()->subDays(12),
                'updated_at' => $now->copy()->subDays(1),
            ],
            [
                'ticket_number' => 'T-20260120-006',
                'user_id' => $masAgus->id,
                'assignee_id' => null,
                'title' => 'Trotoar Rusak di Depan Balai Desa',
                'description' => 'Trotoar di depan balai desa banyak yang pecah dan berlubang. Lansia sering tersandung.',
                'priority' => 'Medium',
                'location_name' => 'Depan Balai Desa',
                'latitude' => -7.255678,
                'longitude' => 112.773567,
                'status' => 'SUBMITTED',
                'created_at' => $now->copy()->subDays(1),
                'updated_at' => $now->copy()->subDays(1),
            ],
            [
                'ticket_number' => 'T-20260121-007',
                'user_id' => $buRani->id,
                'assignee_id' => $operatorSiti->id,
                'title' => 'Tanggul Sungai Longsor',
                'description' => 'Tanggul sungai di belakang rumah warga RT 05 longsor akibat hujan deras kemarin. Perlu penanganan darurat.',
                'priority' => 'Medium',
                'location_name' => 'Belakang RT 05 RW 01',
                'latitude' => -7.256789,
                'longitude' => 112.774678,
                'status' => 'NEEDS_REVISION',
                'created_at' => $now->copy()->subDays(3),
                'updated_at' => $now->copy()->subDays(2),
            ],

            // ==============================
            // SAMPAH (5 reports) - PRD scenario
            // ==============================
            [
                'ticket_number' => 'T-20260110-008',
                'user_id' => $buTini->id,
                'assignee_id' => $operatorSiti->id,
                'title' => 'Sampah Menumpuk di TPS Dusun Makmur',
                'description' => 'Sampah di TPS sudah menumpuk lebih dari seminggu dan mengeluarkan bau tidak sedap. Truk sampah tidak pernah datang.',
                'priority' => 'High',
                'location_name' => 'TPS Dusun Makmur',
                'latitude' => -7.257890,
                'longitude' => 112.775789,
                'status' => 'IN_PROGRESS',
                'created_at' => $now->copy()->subDays(8),
                'updated_at' => $now->copy()->subDays(1),
            ],
            [
                'ticket_number' => 'T-20260112-009',
                'user_id' => $pakBambang->id,
                'assignee_id' => $operatorDarto->id,
                'title' => 'Pembuangan Sampah Liar di Tepi Kali',
                'description' => 'Ada oknum yang membuang sampah sembarangan di tepi kali belakang kandang ayam saya. Sudah berulang kali terjadi.',
                'priority' => 'High',
                'location_name' => 'Tepi Kali, belakang peternakan',
                'latitude' => -7.258901,
                'longitude' => 112.776890,
                'status' => 'VERIFIED',
                'created_at' => $now->copy()->subDays(6),
                'updated_at' => $now->copy()->subDays(4),
            ],
            [
                'ticket_number' => 'T-20260113-010',
                'user_id' => $buYanti->id,
                'assignee_id' => $operatorSiti->id,
                'title' => 'Tempat Sampah Rusak di Pasar',
                'description' => 'Tempat sampah besar di dekat los jamu pasar sudah rusak dan bolong-bolong. Sampah berceceran kemana-mana.',
                'priority' => 'High',
                'location_name' => 'Pasar Desa, dekat los jamu',
                'latitude' => -7.259012,
                'longitude' => 112.777901,
                'status' => 'CLOSED',
                'created_at' => $now->copy()->subDays(20),
                'updated_at' => $now->copy()->subDays(15),
            ],
            [
                'ticket_number' => 'T-20260114-011',
                'user_id' => $masRudi->id,
                'assignee_id' => null,
                'title' => 'Bangkai Hewan di Pinggir Jalan',
                'description' => 'Ada bangkai kucing yang sudah membusuk di pinggir jalan utama desa. Baunya sangat mengganggu.',
                'priority' => 'High',
                'location_name' => 'Jalan Utama Desa km 2',
                'latitude' => -7.260123,
                'longitude' => 112.778012,
                'status' => 'SUBMITTED',
                'created_at' => $now->copy()->subDays(2),
                'updated_at' => $now->copy()->subDays(2),
            ],
            [
                'ticket_number' => 'T-20260115-012',
                'user_id' => $mbakDewi->id,
                'assignee_id' => $operatorSiti->id,
                'title' => 'Sampah Plastik di Sungai',
                'description' => 'Banyak sampah plastik mengapung di sungai dekat PAUD. Anak-anak bermain di sana dan berbahaya.',
                'priority' => 'High',
                'location_name' => 'Sungai dekat PAUD Tunas Harapan',
                'latitude' => -7.261234,
                'longitude' => 112.779123,
                'status' => 'RESOLVED',
                'created_at' => $now->copy()->subDays(9),
                'updated_at' => $now->copy()->subDays(3),
            ],

            // ==============================
            // PELAYANAN (5 reports)
            // ==============================
            [
                'ticket_number' => 'T-20260108-013',
                'user_id' => $pakBudi->id,
                'assignee_id' => $operatorDarto->id,
                'title' => 'Permintaan Surat Keterangan Domisili',
                'description' => 'Mohon dibuatkan surat keterangan domisili untuk keperluan administrasi pendaftaran sekolah anak.',
                'priority' => 'Medium',
                'location_name' => 'Balai Desa',
                'latitude' => -7.255678,
                'longitude' => 112.773567,
                'status' => 'CLOSED',
                'created_at' => $now->copy()->subDays(25),
                'updated_at' => $now->copy()->subDays(23),
            ],
            [
                'ticket_number' => 'T-20260109-014',
                'user_id' => $buRani->id,
                'assignee_id' => $operatorSiti->id,
                'title' => 'Permohonan Surat Keterangan Tidak Mampu',
                'description' => 'Permohonan SKTM untuk biaya pengobatan anak di rumah sakit. Dokumen pendukung sudah disiapkan.',
                'priority' => 'Medium',
                'location_name' => 'Balai Desa',
                'latitude' => -7.255678,
                'longitude' => 112.773567,
                'status' => 'RESOLVED',
                'created_at' => $now->copy()->subDays(18),
                'updated_at' => $now->copy()->subDays(16),
            ],
            [
                'ticket_number' => 'T-20260110-015',
                'user_id' => $pakHadi->id,
                'assignee_id' => null,
                'title' => 'Keluhan Pelayanan Lambat di Kantor Desa',
                'description' => 'Sudah 3 kali datang ke kantor desa untuk mengurus surat tanah tapi selalu disuruh datang lagi. Mohon proses dipercepat.',
                'priority' => 'Medium',
                'location_name' => 'Kantor Desa',
                'latitude' => -7.255678,
                'longitude' => 112.773567,
                'status' => 'SUBMITTED',
                'created_at' => $now->copy()->subDays(4),
                'updated_at' => $now->copy()->subDays(4),
            ],
            [
                'ticket_number' => 'T-20260111-016',
                'user_id' => $masAgus->id,
                'assignee_id' => $operatorSiti->id,
                'title' => 'Update Data KK',
                'description' => 'Permohonan update data Kartu Keluarga karena ada penambahan anggota keluarga (kelahiran anak).',
                'priority' => 'Medium',
                'location_name' => 'Balai Desa',
                'latitude' => -7.255678,
                'longitude' => 112.773567,
                'status' => 'IN_PROGRESS',
                'created_at' => $now->copy()->subDays(6),
                'updated_at' => $now->copy()->subDays(2),
            ],
            [
                'ticket_number' => 'T-20260112-017',
                'user_id' => $buTini->id,
                'assignee_id' => $operatorDarto->id,
                'title' => 'Permintaan Surat Izin Usaha',
                'description' => 'Mohon dibuatkan surat keterangan usaha untuk warung makan yang akan saya buka.',
                'priority' => 'Medium',
                'location_name' => 'Balai Desa',
                'latitude' => -7.255678,
                'longitude' => 112.773567,
                'status' => 'VERIFIED',
                'created_at' => $now->copy()->subDays(5),
                'updated_at' => $now->copy()->subDays(3),
            ],

            // ==============================
            // KEAMANAN (4 reports)
            // ==============================
            [
                'ticket_number' => 'T-20260105-018',
                'user_id' => $pakSlamet->id,
                'assignee_id' => $operatorSiti->id,
                'title' => 'Motor Hilang di Sawah',
                'description' => 'Motor saya hilang saat ditinggal di pematang sawah. Kejadian sekitar jam 10 pagi kemarin.',
                'priority' => 'Urgent',
                'location_name' => 'Sawah Blok Barat',
                'latitude' => -7.262345,
                'longitude' => 112.780234,
                'status' => 'CLOSED',
                'created_at' => $now->copy()->subDays(30),
                'updated_at' => $now->copy()->subDays(25),
            ],
            [
                'ticket_number' => 'T-20260106-019',
                'user_id' => $buYanti->id,
                'assignee_id' => $operatorSiti->id,
                'title' => 'Ada Orang Mencurigakan di Malam Hari',
                'description' => 'Beberapa malam terakhir ada orang mencurigakan berkeliaran di sekitar rumah warga RT 04. Mohon patroli ditingkatkan.',
                'priority' => 'Urgent',
                'location_name' => 'RT 04 RW 03',
                'latitude' => -7.263456,
                'longitude' => 112.781345,
                'status' => 'RESOLVED',
                'created_at' => $now->copy()->subDays(15),
                'updated_at' => $now->copy()->subDays(10),
            ],
            [
                'ticket_number' => 'T-20260107-020',
                'user_id' => $masRudi->id,
                'assignee_id' => null,
                'title' => 'Anjing Liar Berkeliaran',
                'description' => 'Banyak anjing liar yang berkeliaran di sekitar pemukiman. Warga takut karena ada yang menggonggong agresif.',
                'priority' => 'Urgent',
                'location_name' => 'Dusun Sentosa',
                'latitude' => -7.264567,
                'longitude' => 112.782456,
                'status' => 'SUBMITTED',
                'created_at' => $now->copy()->subDays(3),
                'updated_at' => $now->copy()->subDays(3),
            ],
            [
                'ticket_number' => 'T-20260108-021',
                'user_id' => $pakBambang->id,
                'assignee_id' => $operatorDarto->id,
                'title' => 'Pencurian Ayam',
                'description' => 'Ayam saya dicuri sebanyak 10 ekor tadi malam. Kandang dibobol dari belakang.',
                'priority' => 'Urgent',
                'location_name' => 'Peternakan Pak Bambang, Dusun Makmur',
                'latitude' => -7.265678,
                'longitude' => 112.783567,
                'status' => 'IN_PROGRESS',
                'created_at' => $now->copy()->subDays(2),
                'updated_at' => $now->copy()->subDays(1),
            ],

            // ==============================
            // LAINNYA (5 reports)
            // ==============================
            [
                'ticket_number' => 'T-20260101-022',
                'user_id' => $mbakDewi->id,
                'assignee_id' => $operatorSiti->id,
                'title' => 'Usulan Kegiatan Posyandu',
                'description' => 'Mengusulkan agar kegiatan Posyandu ditambah menjadi 2x sebulan karena banyak balita yang perlu dipantau.',
                'priority' => 'Low',
                'location_name' => 'Posyandu Desa',
                'latitude' => -7.255678,
                'longitude' => 112.773567,
                'status' => 'CLOSED',
                'created_at' => $now->copy()->subDays(45),
                'updated_at' => $now->copy()->subDays(30),
            ],
            [
                'ticket_number' => 'T-20260102-023',
                'user_id' => $pakBudi->id,
                'assignee_id' => $operatorDarto->id,
                'title' => 'Permohonan Bantuan Bibit',
                'description' => 'Mohon informasi dan bantuan pengadaan bibit padi unggul untuk musim tanam berikutnya.',
                'priority' => 'Low',
                'location_name' => 'Kelompok Tani Makmur Jaya',
                'latitude' => -7.266789,
                'longitude' => 112.784678,
                'status' => 'RESOLVED',
                'created_at' => $now->copy()->subDays(35),
                'updated_at' => $now->copy()->subDays(28),
            ],
            [
                'ticket_number' => 'T-20260103-024',
                'user_id' => $masAgus->id,
                'assignee_id' => null,
                'title' => 'Usulan Pembangunan Lapangan Futsal',
                'description' => 'Pemuda desa mengusulkan pembangunan lapangan futsal di tanah kosong dekat balai desa untuk kegiatan positif.',
                'priority' => 'Low',
                'location_name' => 'Tanah Kosong Dekat Balai Desa',
                'latitude' => -7.255678,
                'longitude' => 112.773567,
                'status' => 'SUBMITTED',
                'created_at' => $now->copy()->subDays(7),
                'updated_at' => $now->copy()->subDays(7),
            ],
            [
                'ticket_number' => 'T-20260104-025',
                'user_id' => $buRani->id,
                'assignee_id' => $operatorSiti->id,
                'title' => 'Keluhan Sinyal Internet Lemah',
                'description' => 'Sinyal internet di Dusun Terpencil sangat lemah. Anak-anak kesulitan belajar online.',
                'priority' => 'Low',
                'location_name' => 'Dusun Terpencil',
                'latitude' => -7.267890,
                'longitude' => 112.785789,
                'status' => 'VERIFIED',
                'created_at' => $now->copy()->subDays(10),
                'updated_at' => $now->copy()->subDays(8),
            ],
            [
                'ticket_number' => 'T-20260105-026',
                'user_id' => $pakHadi->id,
                'assignee_id' => $operatorSiti->id,
                'title' => 'Pohon Tumbang Menghalangi Jalan',
                'description' => 'Pohon besar tumbang akibat angin kencang semalam. Jalan menuju Dusun Damai tertutup total.',
                'priority' => 'Low',
                'location_name' => 'Jalan ke Dusun Damai',
                'latitude' => -7.268901,
                'longitude' => 112.786890,
                'status' => 'CLOSED',
                'created_at' => $now->copy()->subDays(20),
                'updated_at' => $now->copy()->subDays(19),
            ],

            // ==============================
            // REJECTED reports (2)
            // ==============================
            [
                'ticket_number' => 'T-20260106-027',
                'user_id' => $masRudi->id,
                'assignee_id' => $operatorSiti->id,
                'title' => 'Test Laporan',
                'description' => 'Ini hanya test apakah sistem berjalan.',
                'priority' => 'Low',
                'location_name' => 'Test',
                'latitude' => null,
                'longitude' => null,
                'status' => 'REJECTED',
                'created_at' => $now->copy()->subDays(22),
                'updated_at' => $now->copy()->subDays(21),
            ],
            [
                'ticket_number' => 'T-20260107-028',
                'user_id' => $masAgus->id,
                'assignee_id' => $operatorDarto->id,
                'title' => 'hahaha',
                'description' => 'wkwkwk iseng aja',
                'priority' => 'Low',
                'location_name' => null,
                'latitude' => null,
                'longitude' => null,
                'status' => 'REJECTED',
                'created_at' => $now->copy()->subDays(18),
                'updated_at' => $now->copy()->subDays(17),
            ],
        ];
    }
}
