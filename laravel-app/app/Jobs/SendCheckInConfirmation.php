<?php

namespace App\Jobs;

use App\Models\Attendance;
use App\Services\FonnteWhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendCheckInConfirmation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 10;

    public function __construct(
        public readonly Attendance $attendance
    ) {}

    public function handle(FonnteWhatsAppService $waService): void
    {
        @set_time_limit(0);
        @ignore_user_abort(true);

        $this->attendance->loadMissing(['participant.group', 'session']);

        $waService->sendCheckInConfirmation($this->attendance);
    }
}
