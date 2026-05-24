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

    private function getPreviousContext(Message $message): ?array
    {
        if ($message->payload_type !== 'message_new') {
            return null;
        }

        $fromId = $message->payload['object']['from_id'] ?? null;
        if ($fromId === null) {
            return null;
        }

        $previous = Message::where('payload_type', 'message_new')
            ->where('id', '!=', $message->id)
            ->whereRaw("json_extract(payload, '$.object.from_id') = ?", [$fromId])
            ->where('received_at', '<', $message->received_at)
            ->orderBy('received_at', 'desc')
            ->first();

        if ($previous === null) {
            return null;
        }

        $answer = $previous->latestAnswer;
        if ($answer === null || $answer->response === '' || $answer->response === null) {
            return null;
        }

        return [
            'question' => $previous->text,
            'answer' => $answer->response,
        ];
    }

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

        $context = $this->getPreviousContext($message);
        if ($context !== null) {
            $prompt = "Предыдущий вопрос: {$context['question']}\nПредыдущий ответ: {$context['answer']}\n\nНовый вопрос: {$prompt}";
        }

        $driver = env('OPENCODE_DRIVER', 'docker');
        $driverName = $driver === 'po' ? 'Python (local)' : 'Docker';
        $sessionId = (string) $message->id;

        logger()->info('Processing message', [
            'message_id' => $message->id,
            'driver' => $driverName,
            'model' => $model,
            'session_id' => $sessionId,
            'has_context' => $context !== null,
        ]);

        $infoText = "Настройки:\n- Обработчик: {$driverName}\n- Модель: {$model}";

        try {
            $this->vk->sendReply($message, $infoText);
        } catch (\Throwable $e) {
            logger()->warning('Failed to send info reply to VK', [
                'message_id' => $message->id,
                'error' => $e->getMessage(),
            ]);
        }

        $answer = Answer::create([
            'message_id' => $message->id,
            'prompt' => $prompt,
            'model' => $model,
            'started_at' => now(),
        ]);

        $attempts = 2;
        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            try {
                $result = $this->opencode->run($prompt, $model);
                break;
            } catch (\Throwable $e) {
                if ($attempt < $attempts) {
                    logger()->warning("Opencode attempt {$attempt} failed, retrying...", [
                        'message_id' => $message->id,
                        'error' => $e->getMessage(),
                    ]);
                    sleep(2);
                } else {
                    throw $e;
                }
            }
        }

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
