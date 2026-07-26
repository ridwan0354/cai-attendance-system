<?php

namespace App\Jobs;

use App\Models\Group;
use App\Services\FonnteWhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendWhatsAppAllSessionsReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(
        public readonly Group $group
    ) {}

    public function handle(FonnteWhatsAppService $waService): void
    {
        $waService->sendAllSessionsRecapReport($this->group);
    }
}
