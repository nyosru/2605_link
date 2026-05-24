<?php

namespace App\Services;

use App\Models\Answer;
use App\Models\Message;
use App\Repositories\MessageRepository;

class ProcessingService
{
    public function __construct(
        private readonly MessageRepository $repository,
        private readonly OpencodeClient $opencode,
        private readonly VkApiService $vk,
    ) {}

    public function getPending(): array
    {
        return Message::where('payload_type', 'message_new')
            ->whereDoesntHave('answers')
            ->orderBy('received_at')
            ->get()
            ->toArray();
    }

    public function process(Message $message, string $model = 'opencode/deepseek-v4-flash-free'): Answer
    {
        $prompt = $message->text ?? '';

        $answer = Answer::create([
            'message_id' => $message->id,
            'prompt' => $prompt,
            'model' => $model,
            'started_at' => now(),
        ]);

        $result = $this->opencode->run($prompt, $model);

        $responseText = $result['response'] ?? '';

        $answer->update([
            'response' => $responseText,
            'finished_at' => now(),
            'processing_time_ms' => $result['elapsed_ms'] ?? 0,
        ]);

        $status = $message->status ?? $message->status()->create();
        $status->update([
            'sent_for_processing' => true,
            'sent_at' => $answer->started_at,
            'response_received' => true,
            'responded_at' => $answer->finished_at,
        ]);

        if ($responseText !== '') {
            try {
                $this->vk->sendReply($message, $responseText);
                $answer->update(['reply_sent_at' => now()]);
            } catch (\Throwable $e) {
                logger()->warning('Failed to send reply to VK', [
                    'message_id' => $message->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $answer;
    }

    public function processFromHost(Message $message, string $response, int $elapsedMs): Answer
    {
        $prompt = $message->text ?? '';

        $answer = Answer::create([
            'message_id' => $message->id,
            'prompt' => $prompt,
            'model' => 'opencode/deepseek-v4-flash-free',
            'started_at' => now()->subMilliseconds($elapsedMs),
            'finished_at' => now(),
            'response' => $response,
            'processing_time_ms' => $elapsedMs,
        ]);

        $status = $message->status ?? $message->status()->create();
        $status->update([
            'sent_for_processing' => true,
            'sent_at' => $answer->started_at,
            'response_received' => true,
            'responded_at' => $answer->finished_at,
        ]);

        return $answer;
    }
}
