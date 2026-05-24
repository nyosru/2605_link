<?php

namespace App\Console\Commands;

use App\Models\Message;
use App\Services\MessageService;
use App\Services\ProcessingService;
use Illuminate\Console\Command;

class MessagesListen extends Command
{
    protected $signature = 'messages:listen
        {--model=opencode/deepseek-v4-flash-free}
        {--channel=testPM}
        {--interval=10}';

    protected $description = 'Listen for new messages every N seconds and process via AI';

    private bool $running = true;

    public function handle(MessageService $messages, ProcessingService $processing): int
    {
        $this->info('Starting message listener...');
        $this->info('Press Ctrl+C to stop.');

        $this->trapSignals();

        while ($this->running) {
            $this->syncAndProcess($messages, $processing);
            $this->wait();
        }

        $this->info('Listener stopped.');

        return self::SUCCESS;
    }

    private function syncAndProcess(MessageService $messages, ProcessingService $processing): void
    {
        $synced = $messages->syncFromApi($this->option('channel'));

        if ($synced > 0) {
            $this->line("Synced {$synced} new message(s)");
        }

        $pending = Message::where('payload_type', 'message_new')
            ->whereDoesntHave('answers')
            ->orderBy('received_at')
            ->get();

        if ($pending->isEmpty()) {
            return;
        }

        foreach ($pending as $message) {
            if (!$this->running) {
                break;
            }

            $this->line("Processing message #{$message->id}: {$message->text}");

            try {
                $answer = $processing->process($message, $this->option('model'));
                $this->line("  Done in {$answer->processing_time_ms}ms");
            } catch (\Throwable $e) {
                $this->error("  Error: {$e->getMessage()}");
            }
        }
    }

    private function wait(): void
    {
        $interval = (int) $this->option('interval');

        for ($i = 0; $i < $interval; $i++) {
            if (!$this->running) {
                break;
            }
            sleep(1);
        }
    }

    private function trapSignals(): void
    {
        if (!function_exists('pcntl_signal')) {
            $this->warn('pcntl extension not available, graceful shutdown disabled');
            return;
        }

        pcntl_async_signals(true);

        $handler = function (int $signal): void {
            $this->running = false;
            $this->info("Received signal {$signal}, shutting down...");
        };

        pcntl_signal(SIGINT, $handler);
        pcntl_signal(SIGTERM, $handler);
    }
}
