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
        // Load relationships if not loaded
        $this->attendance->loadMissing(['participant', 'session']);

        $waService->sendCheckInConfirmation($this->attendance);
    }
}
