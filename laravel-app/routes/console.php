<?php

use App\Jobs\SendWhatsAppReport;
use App\Models\Group;
use App\Models\Session;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| CAI LOMBOK 2026 - Scheduled Tasks
|--------------------------------------------------------------------------
*/

/**
 * Every minute: check if any active session ends within 60 minutes.
 * If so, dispatch WhatsApp report jobs for all groups.
 */
Schedule::call(function () {
    $session = Session::getActive();

    if (!$session) return;

    if ($session->endsWithinMinutes(60)) {
        $groups = Group::all();
        foreach ($groups as $group) {
            $exists = \App\Models\NotificationLog::where('group_id', $group->id)
                ->where('session_id', $session->id)
                ->exists();
            if (!$exists) {
                SendWhatsAppReport::dispatch($group, $session);
            }
        }
    }
})->everyMinute()->name('cai-wa-t-minus-60')->withoutOverlapping();
