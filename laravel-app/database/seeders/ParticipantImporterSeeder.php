<?php

namespace Database\Seeders;

use App\Models\Group;
use App\Models\Participant;
use Illuminate\Database\Seeder;

class ParticipantImporterSeeder extends Seeder
{
    public function run(): void
    {
        // Data peserta 79 orang dari spreadsheet
        $participantsData = [
            // Ampenan (1-5)
            ['no' => 1, 'group' => 'Ampenan', 'name' => 'Abdul Azis Sulton Aulia', 'gender' => 'Laki-laki', 'phone' => '089510675420'],
            ['no' => 2, 'group' => 'Ampenan', 'name' => 'Muhammad Daud', 'gender' => 'Laki-laki', 'phone' => '08592586166'],
            ['no' => 3, 'group' => 'Ampenan', 'name' => 'Muhammad Satria Setiawan', 'gender' => 'Laki-laki', 'phone' => '087856729754'],
            ['no' => 4, 'group' => 'Ampenan', 'name' => 'Nadiani Wibawanindah', 'gender' => 'Perempuan', 'phone' => '081238931957'],
            ['no' => 5, 'group' => 'Ampenan', 'name' => 'Sulthon Annaji', 'gender' => 'Laki-laki', 'phone' => '087856729754'],

            // Cakra (6-13)
            ['no' => 6, 'group' => 'Cakra', 'name' => 'Ahmad Fadila Abdul', 'gender' => 'Laki-laki', 'phone' => '089604227295'],
            ['no' => 7, 'group' => 'Cakra', 'name' => 'Dimas Arif Abdulloh', 'gender' => 'Laki-laki', 'phone' => '085803511533'],
            ['no' => 8, 'group' => 'Cakra', 'name' => 'Indah Soraya', 'gender' => 'Perempuan', 'phone' => '087882587370'],
            ['no' => 9, 'group' => 'Cakra', 'name' => 'Luluk Indah Purnama Sari', 'gender' => 'Perempuan', 'phone' => '082331583343'],
            ['no' => 10, 'group' => 'Cakra', 'name' => 'Muhamad Alim Rusdiyanto', 'gender' => 'Laki-laki', 'phone' => '085238403946'],
            ['no' => 11, 'group' => 'Cakra', 'name' => 'Muhammad Rizal Bachtiar', 'gender' => 'Laki-laki', 'phone' => '082335683227'],
            ['no' => 12, 'group' => 'Cakra', 'name' => 'Nur Laily Tri Azaria', 'gender' => 'Perempuan', 'phone' => '085333937727'],
            ['no' => 13, 'group' => 'Cakra', 'name' => 'Sekar Paramita', 'gender' => 'Perempuan', 'phone' => '087882587370'],

            // Mataram (14-23)
            ['no' => 14, 'group' => 'Mataram', 'name' => 'Aji Zukarnaen', 'gender' => 'Laki-laki', 'phone' => '087861498454'],
            ['no' => 15, 'group' => 'Mataram', 'name' => 'Bagus Wicaksono', 'gender' => 'Laki-laki', 'phone' => '085137349618'],
            ['no' => 16, 'group' => 'Mataram', 'name' => 'Davina Zaara Kaeina Aulia', 'gender' => 'Perempuan', 'phone' => '089684349627'],
            ['no' => 17, 'group' => 'Mataram', 'name' => 'Filza Aneta Agustien', 'gender' => 'Perempuan', 'phone' => '085804610973'],
            ['no' => 18, 'group' => 'Mataram', 'name' => 'Hegar Marga Birawa', 'gender' => 'Laki-laki', 'phone' => '085706473965'],
            ['no' => 19, 'group' => 'Mataram', 'name' => 'Muhammad Husen Ali Firdaus', 'gender' => 'Laki-laki', 'phone' => '085770438173'],
            ['no' => 20, 'group' => 'Mataram', 'name' => 'Muhammad Sulton Aulia', 'gender' => 'Laki-laki', 'phone' => '085338249528'],
            ['no' => 21, 'group' => 'Mataram', 'name' => 'Putranda Khomsa Ramadhan', 'gender' => 'Laki-laki', 'phone' => '087856967126'],
            ['no' => 22, 'group' => 'Mataram', 'name' => 'Raditya Fayadz Fadzillah', 'gender' => 'Laki-laki', 'phone' => '089510675046'],
            ['no' => 23, 'group' => 'Mataram', 'name' => 'Ramadhanty Dira Salsabila', 'gender' => 'Perempuan', 'phone' => '081907899745'],

            // Narmada (24-29)
            ['no' => 24, 'group' => 'Narmada', 'name' => 'Duha Al-Hasani Wirmala', 'gender' => 'Laki-laki', 'phone' => '085353818295'],
            ['no' => 25, 'group' => 'Narmada', 'name' => 'Inda Zahrotin Khubina', 'gender' => 'Perempuan', 'phone' => '085937029325'],
            ['no' => 26, 'group' => 'Narmada', 'name' => 'Lusiana', 'gender' => 'Perempuan', 'phone' => '081929292300'],
            ['no' => 27, 'group' => 'Narmada', 'name' => 'M.Anton Ardino', 'gender' => 'Laki-laki', 'phone' => '083877657277'],
            ['no' => 28, 'group' => 'Narmada', 'name' => 'Muhamad Satria Sya\'Bani', 'gender' => 'Laki-laki', 'phone' => '087860344787'],
            ['no' => 29, 'group' => 'Narmada', 'name' => 'Syafira Triani Wirmala', 'gender' => 'Perempuan', 'phone' => '087784606866'],

            // Perumnas (30-39)
            ['no' => 30, 'group' => 'Perumnas', 'name' => 'Ersya Bufonanda', 'gender' => 'Laki-laki', 'phone' => '085237066686'],
            ['no' => 31, 'group' => 'Perumnas', 'name' => 'Keysa Osama Jasak', 'gender' => 'Laki-laki', 'phone' => '083877974425'],
            ['no' => 32, 'group' => 'Perumnas', 'name' => 'Ahmad Rayhan Trusty', 'gender' => 'Laki-laki', 'phone' => '081916354160'],
            ['no' => 33, 'group' => 'Perumnas', 'name' => 'Neo Aflakha Sany', 'gender' => 'Laki-laki', 'phone' => '085879021762'],
            ['no' => 34, 'group' => 'Perumnas', 'name' => 'Asep', 'gender' => 'Laki-laki', 'phone' => '085645821105'],
            ['no' => 35, 'group' => 'Perumnas', 'name' => 'Halim Seno Aji', 'gender' => 'Laki-laki', 'phone' => '089533969448'],
            ['no' => 36, 'group' => 'Perumnas', 'name' => 'Dwi Amisya Azzahrani Putri', 'gender' => 'Perempuan', 'phone' => '085904360290'],
            ['no' => 37, 'group' => 'Perumnas', 'name' => 'Alaina Rizqia Ghani', 'gender' => 'Perempuan', 'phone' => '081252889050'],
            ['no' => 38, 'group' => 'Perumnas', 'name' => 'Kurnia Tamawulan', 'gender' => 'Perempuan', 'phone' => '081239838025'],
            ['no' => 39, 'group' => 'Perumnas', 'name' => 'Dea Sulistiani', 'gender' => 'Perempuan', 'phone' => '087717279101'],

            // Praya (40-52)
            ['no' => 40, 'group' => 'Praya', 'name' => 'Amar Abdillah Antofani', 'gender' => 'Laki-laki', 'phone' => '081947900185'],
            ['no' => 41, 'group' => 'Praya', 'name' => 'Badrija Adit Haqqani', 'gender' => 'Laki-laki', 'phone' => '085964267483'],
            ['no' => 42, 'group' => 'Praya', 'name' => 'Baiq Dinda Rahma Devina', 'gender' => 'Perempuan', 'phone' => '087865246487'],
            ['no' => 43, 'group' => 'Praya', 'name' => 'Baiq Vania Talitha', 'gender' => 'Perempuan', 'phone' => '087730097455'],
            ['no' => 44, 'group' => 'Praya', 'name' => 'Elmy Zidan Faidlillah', 'gender' => 'Laki-laki', 'phone' => '089646696597'],
            ['no' => 45, 'group' => 'Praya', 'name' => 'Elya Salsa Putri', 'gender' => 'Perempuan', 'phone' => '087741204329'],
            ['no' => 46, 'group' => 'Praya', 'name' => 'Meysya Nuraini Antofani', 'gender' => 'Perempuan', 'phone' => '087857677872'],
            ['no' => 47, 'group' => 'Praya', 'name' => 'Muhamad Akbar Dio Sagita', 'gender' => 'Laki-laki', 'phone' => '0881037308020'],
            ['no' => 48, 'group' => 'Praya', 'name' => 'Muhammad Ridho Wicaksono', 'gender' => 'Laki-laki', 'phone' => '087817868589'],
            ['no' => 49, 'group' => 'Praya', 'name' => 'Muhammad Syahroni', 'gender' => 'Laki-laki', 'phone' => '087802325476'],
            ['no' => 50, 'group' => 'Praya', 'name' => 'Oktavia Nurul Andini', 'gender' => 'Perempuan', 'phone' => '087824739449'],
            ['no' => 51, 'group' => 'Praya', 'name' => 'Resty Sevita Fidini', 'gender' => 'Perempuan', 'phone' => '082245416985'],
            ['no' => 52, 'group' => 'Praya', 'name' => 'Shofie Ananda Sabina', 'gender' => 'Perempuan', 'phone' => '081999563222'],

            // Sandik (53-60)
            ['no' => 53, 'group' => 'Sandik', 'name' => 'Ahmad Dwi Fauzan', 'gender' => 'Laki-laki', 'phone' => '087865888334'],
            ['no' => 54, 'group' => 'Sandik', 'name' => 'Cyntia Cakra Wardani', 'gender' => 'Perempuan', 'phone' => '0853-3788-3639'],
            ['no' => 55, 'group' => 'Sandik', 'name' => 'Khairil Fajri', 'gender' => 'Laki-laki', 'phone' => '087892113274'],
            ['no' => 56, 'group' => 'Sandik', 'name' => 'M. Fadil Nasrullah Assiddiqi', 'gender' => 'Laki-laki', 'phone' => '083848819845'],
            ['no' => 57, 'group' => 'Sandik', 'name' => 'Mutia Nirmala Sani', 'gender' => 'Perempuan', 'phone' => '08968761850'],
            ['no' => 58, 'group' => 'Sandik', 'name' => 'Nova Achmad Fauzi', 'gender' => 'Laki-laki', 'phone' => '087794693291'],
            ['no' => 59, 'group' => 'Sandik', 'name' => 'Novi Rizqi Fatania', 'gender' => 'Laki-laki', 'phone' => '08814877994'],
            ['no' => 60, 'group' => 'Sandik', 'name' => 'Sulthon Shokhibul Firdaus', 'gender' => 'Laki-laki', 'phone' => '085333112645'],

            // Selong (61-67)
            ['no' => 61, 'group' => 'Selong', 'name' => 'Catria Dina Auliaunnisa', 'gender' => 'Perempuan', 'phone' => '081239885130'],
            ['no' => 62, 'group' => 'Selong', 'name' => 'Erik Sasongko', 'gender' => 'Laki-laki', 'phone' => '089528070754'],
            ['no' => 63, 'group' => 'Selong', 'name' => 'Naufal Habibi', 'gender' => 'Laki-laki', 'phone' => '083895679400'],
            ['no' => 64, 'group' => 'Selong', 'name' => 'Ratu Jannatti N.F.', 'gender' => 'Perempuan', 'phone' => '085606850858'],
            ['no' => 65, 'group' => 'Selong', 'name' => 'Robeth Almansyurin', 'gender' => 'Laki-laki', 'phone' => '087896609591'],
            ['no' => 66, 'group' => 'Selong', 'name' => 'Sahlan Faris Nabalan', 'gender' => 'Laki-laki', 'phone' => '08123558898'],
            ['no' => 67, 'group' => 'Selong', 'name' => 'Elya Novtiari Aulia', 'gender' => 'Perempuan', 'phone' => '087858749553'],

            // Pagutan (68-79)
            ['no' => 68, 'group' => 'Pagutan', 'name' => 'Muhammd Mulkan Fadlan', 'gender' => 'Laki-laki', 'phone' => '0895800768900'],
            ['no' => 69, 'group' => 'Pagutan', 'name' => 'Vernando', 'gender' => 'Laki-laki', 'phone' => '083180801559'],
            ['no' => 70, 'group' => 'Pagutan', 'name' => 'Rizal', 'gender' => 'Laki-laki', 'phone' => '083195894927'],
            ['no' => 71, 'group' => 'Pagutan', 'name' => 'M. Ubay Alfiyansah', 'gender' => 'Laki-laki', 'phone' => '083138036147'],
            ['no' => 72, 'group' => 'Pagutan', 'name' => 'Satria', 'gender' => 'Laki-laki', 'phone' => '082313026096'],
            ['no' => 73, 'group' => 'Pagutan', 'name' => 'Nafis', 'gender' => 'Laki-laki', 'phone' => '082342559443'],
            ['no' => 74, 'group' => 'Pagutan', 'name' => 'Diena Reksa Buana', 'gender' => 'Laki-laki', 'phone' => '081529611437'],
            ['no' => 75, 'group' => 'Pagutan', 'name' => 'Husnul Hotima', 'gender' => 'Perempuan', 'phone' => '085804199040'],
            ['no' => 76, 'group' => 'Pagutan', 'name' => 'Luluk Fauziah', 'gender' => 'Perempuan', 'phone' => '087879800471'],
            ['no' => 77, 'group' => 'Pagutan', 'name' => 'Elmira Henin Zulfana', 'gender' => 'Perempuan', 'phone' => '08135634568'],
            ['no' => 78, 'group' => 'Pagutan', 'name' => 'Wartika', 'gender' => 'Perempuan', 'phone' => '085367273354'],
            ['no' => 79, 'group' => 'Pagutan', 'name' => 'Giandra Fauzi Fadhillah', 'gender' => 'Laki-laki', 'phone' => '082340570994'],
        ];

        // Cache map Group name ke ID
        $groupCache = [];

        foreach ($participantsData as $data) {
            $groupName = $data['group'];

            if (!isset($groupCache[$groupName])) {
                // Cari atau buat group jika belum terdaftar
                $group = Group::firstOrCreate(
                    ['name' => $groupName],
                    [
                        'region_code' => strtoupper(substr($groupName, 0, 3)),
                        'color' => '#' . substr(md5($groupName), 0, 6),
                        'pembina_name' => '-',
                        'pembina_phone' => '-'
                    ]
                );
                $groupCache[$groupName] = $group->id;
            }

            $groupId = $groupCache[$groupName];

            // ID QR Code: prefix "354" + 2-digit format nomor urut
            $qrCode = '354' . sprintf('%02d', $data['no']);

            // Insert / update data peserta
            Participant::updateOrCreate(
                ['qr_code' => $qrCode],
                [
                    'group_id' => $groupId,
                    'name'     => $data['name'],
                    'gender'   => $data['gender'],
                    'phone'    => $data['phone'],
                ]
            );
        }

        $this->command->info('✅ Berhasil mengimpor 79 peserta dengan ID QR Code 35401 - 35479.');
    }
}
