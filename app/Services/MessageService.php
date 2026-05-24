<?php

namespace App\Services;

use App\Models\Message;
use App\Models\MessageStatus;
use App\Repositories\MessageRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class MessageService
{
    public function __construct(
        private readonly MessageRepository $repository,
    ) {}

    public function syncFromApi(string $channel = 'testPM'): int
    {
        return $this->repository->syncFromApi($channel);
    }

    public function search(?string $query = null, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->search($query, $perPage);
    }

    public function markSent(Message $message): void
    {
        $status = $message->status ?? MessageStatus::create(['message_id' => $message->id]);
        $status->update([
            'sent_for_processing' => true,
            'sent_at' => now(),
        ]);
    }

    public function markNotSent(Message $message): void
    {
        $status = $message->status ?? MessageStatus::create(['message_id' => $message->id]);
        $status->update([
            'sent_for_processing' => false,
            'sent_at' => null,
        ]);
    }

    public function markResponded(Message $message): void
    {
        $status = $message->status ?? MessageStatus::create(['message_id' => $message->id]);
        $status->update([
            'response_received' => true,
            'responded_at' => now(),
        ]);
    }

    public function markNotResponded(Message $message): void
    {
        $status = $message->status ?? MessageStatus::create(['message_id' => $message->id]);
        $status->update([
            'response_received' => false,
            'responded_at' => null,
        ]);
    }
}
