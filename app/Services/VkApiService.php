<?php

namespace App\Services;

use App\Models\Message;
use App\Models\Platform;
use Illuminate\Support\Facades\Http;

class VkApiService
{
    private const API_URL = 'https://api.php-cat.com/api/vk/send';

    public function sendReply(Message $message, string $text): bool
    {
        $platform = Platform::where('platform_id', $message->channel)->first();

        if (!$platform) {
            throw new \RuntimeException("Платформа '{$message->channel}' не найдена");
        }

        $fromId = $message->payload['object']['message']['from_id']
            ?? $message->payload['object']['message']['peer_id']
            ?? null;

        if (!$fromId) {
            throw new \RuntimeException("Не удалось определить from_id для сообщения #{$message->external_id}");
        }

        $response = Http::post(self::API_URL, [

//            'group_name' => $platform->platform_id,
//            'group_name' => 'notification',
            'group_name' => 'ai-pm',
            'token' => $platform->secret,
            'secret' => 1,
            'user_id' => (int) $fromId,
            'message' => $text,

        ]);

        $body = $response->json();

        if (!$response->successful()) {
            throw new \RuntimeException("HTTP {$response->status()}: " . ($body['error'] ?? $body['message'] ?? 'неизвестная ошибка'));
        }

        if (!($body['ok'] ?? false)) {
            throw new \RuntimeException("API вернуло ошибку: " . ($body['error'] ?? $body['message'] ?? 'неизвестная ошибка'));
        }

        return true;
    }
}
