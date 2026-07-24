<?php

use App\Models\Group;
use App\Models\Participant;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $group = Group::firstOrCreate(
            ['name' => 'PANITIA'],
            [
                'region_code'   => 'PNT',
                'pembina_name'  => 'Koordinator Panitia',
                'pembina_phone' => '',
                'color'         => '#6c757d'
            ]
        );

        $panitiaNames = [
            'JEERU',
            'MABRURI SAPTA WIRMALA',
            'Martanu Abdillah',
            'MUH IKHWAN RIDWAN',
            'MUHAMMAD DANDY SOFYAN',
            'MUHAMMAD YUDHA MAHARDIKA',
            'MYA AZZAHRA',
            'NUR HASAN',
            'NURBUANA ANGGUN PUTRI',
            'PRATAMA MUGI BAGASKARA',
            'RAHMAT DONA FAUZI',
            'RIFQI AULIYA FIRDAUS',
            'Rohim',
            'RUSMAN JAYADI',
            'TIARA RIFNA PUTRI',
            'TIARA SEKAR NATA KENCANA',
            'UBED BURHAN HASANUDIN',
            'WAROS',
            'YOSA MORENO HADIYANA FIRDAUS',
            'YUSUF HERMANA',
            'ZOGA PRATAMA',
        ];

        foreach ($panitiaNames as $name) {
            Participant::firstOrCreate(
                [
                    'group_id' => $group->id,
                    'name'     => $name,
                ],
                [
                    'gender'          => 'Laki-laki',
                    'phone'           => null,
                    'face_registered' => false,
                ]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $group = Group::where('name', 'PANITIA')->first();
        if ($group) {
            Participant::where('group_id', $group->id)->delete();
        }
    }
};
