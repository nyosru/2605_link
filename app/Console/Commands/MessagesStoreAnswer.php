<?php

namespace App\Console\Commands;

use App\Models\Message;
use App\Services\ProcessingService;
use Illuminate\Console\Command;

class MessagesStoreAnswer extends Command
{
    protected $signature = 'messages:store-answer {message_id} {response} {elapsed_ms}';
    protected $description = 'Store an opencode response for a message';

    public function handle(ProcessingService $service): int
    {
        $message = Message::findOrFail((int) $this->argument('message_id'));

        $service->processFromHost(
            $message,
            $this->argument('response'),
            (int) $this->argument('elapsed_ms'),
        );

        $this->info("Answer stored for message #{$message->id}");

        return self::SUCCESS;
    }
}
