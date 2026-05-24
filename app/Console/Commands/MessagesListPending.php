<?php

namespace App\Console\Commands;

use App\Services\ProcessingService;
use Illuminate\Console\Command;

class MessagesListPending extends Command
{
    protected $signature = 'messages:pending';
    protected $description = 'List pending messages as JSON for external processing';

    public function handle(ProcessingService $service): int
    {
        $pending = $service->getPending();

        $this->line(json_encode($pending, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        return self::SUCCESS;
    }
}
