<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class OpencodeClient
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = env('OPENCODE_URL', 'http://opencode:8080');
    }

    public function run(string $prompt, string $model = 'opencode/deepseek-v4-flash-free'): array
    {
        $instructions = (string) file_get_contents(base_path('AGENTS.md'));

        $response = Http::timeout(180)
            ->post("{$this->baseUrl}/run", [
                'prompt' => $prompt,
                'model' => $model,
                'instructions' => $instructions,
            ]);

        if ($response->failed()) {
            throw new \RuntimeException('opencode request failed: ' . $response->body());
        }

        return $response->json();
    }
}
