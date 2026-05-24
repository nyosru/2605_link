<?php

namespace App\Repositories;

use App\Models\Message;
use App\Models\MessageStatus;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Http;

class MessageRepository
{
    public function fetchFromApi(string $channel = 'testPM'): array
    {
        $response = Http::get('https://api.php-cat.com/api/vk/incoming', [
            'channel' => $channel,
            'preview' => 'true',
        ]);

        return $response->json() ?? [];
    }

    public function syncFromApi(string $channel = 'testPM'): int
    {
        $items = $this->fetchFromApi($channel);
        $count = 0;

        foreach ($items as $item) {
            $payload = $item['payload'] ?? [];

            $payloadType = $payload['type'] ?? null;
            $text = null;
            if ($payloadType === 'message_new' && isset($payload['object']['message']['text'])) {
                $text = $payload['object']['message']['text'];
            }

            $message = Message::firstOrCreate(
                ['external_id' => $item['id']],
                [
                    'channel' => $item['channel'],
                    'payload_type' => $payloadType,
                    'payload' => $payload,
                    'validation' => $item['validation'] ?? null,
                    'received_at' => $item['received_at'],
                    'text' => $text,
                ]
            );

            if ($message->wasRecentlyCreated) {
                MessageStatus::create(['message_id' => $message->id]);
                $count++;
            }
        }

        return $count;
    }

    public function search(?string $query = null, int $perPage = 15): LengthAwarePaginator
    {
        $q = Message::with('status');

        if ($query) {
            $q->where(function ($q) use ($query) {
                $q->where('text', 'like', "%{$query}%")
                    ->orWhere('channel', 'like', "%{$query}%")
                    ->orWhere('payload_type', 'like', "%{$query}%");
            });
        }

        return $q->orderBy('received_at', 'desc')->paginate($perPage);
    }
}
