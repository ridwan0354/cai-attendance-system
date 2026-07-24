<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Participant;
use App\Models\Session;
use App\Models\Attendance;

return new class extends Migration
{
    public function up(): void
    {
        // Find all participants with registered_at who don't have an attendance record yet
        $registered = Participant::whereNotNull('registered_at')
            ->whereDoesntHave('attendances')
            ->get();

        if ($registered->isNotEmpty()) {
            $defaultSession = Session::getActive() ?? Session::orderBy('day_number')->orderBy('start_time')->first();
            if ($defaultSession) {
                foreach ($registered as $p) {
                    Attendance::firstOrCreate(
                        [
                            'participant_id' => $p->id,
                            'session_id'     => $defaultSession->id,
                        ],
                        [
                            'check_in_time'    => $p->registered_at,
                            'method'           => $p->face_registered ? 'face' : 'manual',
                            'confidence_score' => null,
                            'notes'            => $p->registration_notes ?: 'Registrasi awal peserta',
                        ]
                    );
                }
            }
        }
    }

    public function down(): void
    {
        // No down migration needed
    }
};
