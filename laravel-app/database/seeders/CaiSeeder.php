<?php

namespace Database\Seeders;

use App\Models\Group;
use App\Models\Participant;
use App\Models\Session;
use Illuminate\Database\Seeder;

class CaiSeeder extends Seeder
{
    public function run(): void
    {
        // ── 10 Regional Groups ────────────────────────────────────────────────
        $groups = [
            ['name' => 'Lombok Barat',  'region_code' => 'LBR', 'pembina_name' => 'H. Ahmad Fauzi',    'pembina_phone' => '081234560001', 'color' => '#0052cc'],
            ['name' => 'Lombok Tengah', 'region_code' => 'LTG', 'pembina_name' => 'Ustadz Hamdi',       'pembina_phone' => '081234560002', 'color' => '#00875a'],
            ['name' => 'Lombok Timur',  'region_code' => 'LTM', 'pembina_name' => 'H. Zulkifli',        'pembina_phone' => '081234560003', 'color' => '#6554c0'],
            ['name' => 'Lombok Utara',  'region_code' => 'LUT', 'pembina_name' => 'Ust. Syafruddin',    'pembina_phone' => '081234560004', 'color' => '#ff5630'],
            ['name' => 'Mataram',       'region_code' => 'MTR', 'pembina_name' => 'H. Nurul Huda',      'pembina_phone' => '081234560005', 'color' => '#ff8b00'],
            ['name' => 'Sumbawa',       'region_code' => 'SMB', 'pembina_name' => 'Ust. Ikbal',         'pembina_phone' => '081234560006', 'color' => '#00b8d9'],
            ['name' => 'Bima',          'region_code' => 'BIM', 'pembina_name' => 'H. Mansyur',         'pembina_phone' => '081234560007', 'color' => '#36b37e'],
            ['name' => 'Dompu',         'region_code' => 'DPU', 'pembina_name' => 'Ust. Zainal Arifin', 'pembina_phone' => '081234560008', 'color' => '#ff7452'],
            ['name' => 'Sumbawa Barat', 'region_code' => 'SBR', 'pembina_name' => 'H. Firdaus',         'pembina_phone' => '081234560009', 'color' => '#b3bac5'],
            ['name' => 'NTB Timur',     'region_code' => 'NTE', 'pembina_name' => 'Ust. Saeful',        'pembina_phone' => '081234560010', 'color' => '#344563'],
        ];

        foreach ($groups as $groupData) {
            $group = Group::create($groupData);

            // 5 participants per group
            $names = $this->getNamesByRegion($groupData['region_code']);
            foreach ($names as $name) {
                Participant::create([
                    'group_id' => $group->id,
                    'name'     => $name,
                    'gender'   => rand(0, 1) ? 'Laki-laki' : 'Perempuan',
                    'phone'    => '08' . rand(11111111, 99999999),
                ]);
            }
        }

        // ── 3-Day Sessions ────────────────────────────────────────────────────
        $sessions = [
            // Day 1 — 2026-08-10
            ['name' => 'Sesi Pagi Hari 1',   'day_number' => 1, 'date' => '2026-08-10', 'start_time' => '08:00', 'end_time' => '12:00', 'is_active' => false],
            ['name' => 'Sesi Siang Hari 1',  'day_number' => 1, 'date' => '2026-08-10', 'start_time' => '13:00', 'end_time' => '17:00', 'is_active' => false],
            // Day 2 — 2026-08-11
            ['name' => 'Sesi Pagi Hari 2',   'day_number' => 2, 'date' => '2026-08-11', 'start_time' => '08:00', 'end_time' => '12:00', 'is_active' => false],
            ['name' => 'Sesi Siang Hari 2',  'day_number' => 2, 'date' => '2026-08-11', 'start_time' => '13:00', 'end_time' => '17:00', 'is_active' => false],
            // Day 3 — 2026-08-12
            ['name' => 'Sesi Pagi Hari 3',   'day_number' => 3, 'date' => '2026-08-12', 'start_time' => '08:00', 'end_time' => '12:00', 'is_active' => false],
            ['name' => 'Sesi Penutupan',      'day_number' => 3, 'date' => '2026-08-12', 'start_time' => '13:00', 'end_time' => '17:00', 'is_active' => false],
        ];

        foreach ($sessions as $sessionData) {
            Session::create($sessionData);
        }

        $this->command->info('✅ CAI Seeder: 10 groups, 50 participants, 6 sessions created.');
    }

    private function getNamesByRegion(string $code): array
    {
        $names = [
            'LBR' => ['Ahmad Fauzi', 'Budi Rahman', 'Citra Dewi', 'Dian Pratama', 'Eko Santoso'],
            'LTG' => ['Fajar Hidayat', 'Gita Rahayu', 'Hendra Kurnia', 'Indah Sari', 'Joko Widodo'],
            'LTM' => ['Kartini Wulan', 'Lukman Hakim', 'Maya Putri', 'Nanda Rizki', 'Omar Faruk'],
            'LUT' => ['Putri Ayu', 'Qori Ananda', 'Rizky Maulana', 'Siti Fatimah', 'Taufik Hidayat'],
            'MTR' => ['Umar Bakri', 'Vina Amelia', 'Wahyu Prasetyo', 'Xander Hadi', 'Yuni Kartika'],
            'SMB' => ['Zainal Abidin', 'Adi Nugroho', 'Bella Safira', 'Cahyo Purnomo', 'Desi Ratnasari'],
            'BIM' => ['Eko Prasetya', 'Fitriani', 'Galih Pratama', 'Hani Rosita', 'Ilham Akbar'],
            'DPU' => ['Jihan Aulia', 'Kevin Ananda', 'Laila Sari', 'Muhammad Iqbal', 'Nina Dewi'],
            'SBR' => ['Oscar Febrian', 'Prita Maharani', 'Qodir Sholeh', 'Rina Amalia', 'Surya Darma'],
            'NTE' => ['Tri Wahyuni', 'Udin Sedunia', 'Vera Kusuma', 'Wahid Faturohman', 'Yuda Pratama'],
        ];

        return $names[$code] ?? ['Peserta 1', 'Peserta 2', 'Peserta 3', 'Peserta 4', 'Peserta 5'];
    }
}
