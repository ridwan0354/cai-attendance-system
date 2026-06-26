<?php

namespace App\Jobs;

use App\Models\Group;
use App\Models\NotificationLog;
use App\Models\Session;
use App\Services\FonnteWhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendWhatsAppReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(
        public readonly Group   $group,
        public readonly Session $session
    ) {}

    public function handle(FonnteWhatsAppService $waService): void
    {
        // Prevent duplicate sends for the same group+session
        $alreadySent = NotificationLog::where('group_id', $this->group->id)
            ->where('session_id', $this->session->id)
            ->where('status', 'sent')
            ->exists();

        if ($alreadySent) {
            Log::info("WA report already sent for group {$this->group->id}, session {$this->session->id}. Skipping.");
            return;
        }

        $waService->sendAttendanceReport($this->group, $this->session);
    }
}
