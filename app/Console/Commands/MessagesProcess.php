<?php

namespace App\Console\Commands;

use App\Models\Message;
use App\Services\ProcessingService;
use Illuminate\Console\Command;

class MessagesProcess extends Command
{
    protected $signature = 'messages:process {--model=opencode/deepseek-v4-flash-free}';
    protected $description = 'Process pending messages through opencode';

    public function handle(ProcessingService $service): int
    {
        $pending = Message::where('payload_type', 'message_new')
            ->whereDoesntHave('answers')
            ->orderBy('received_at')
            ->get();

        if ($pending->isEmpty()) {
            $this->info('No pending messages.');
            return self::SUCCESS;
        }

        foreach ($pending as $message) {
            $this->line("Processing message #{$message->id}: {$message->text}");

            try {
                $answer = $service->process($message, $this->option('model'));
                $this->line("  Done in {$answer->processing_time_ms}ms");
            } catch (\Throwable $e) {
                $this->error("  Error: {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }
}
