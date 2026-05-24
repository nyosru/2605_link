<?php

/**
 * VK Callback API → local Flask bot proxy
 *
 * Put this file on your domain, e.g. https://example.com/callback.php
 * Set VK Callback API to that URL.
 * Configure BOT_URL below to point to your running Flask bot.
 */

// Your local Flask bot (via Docker on port 5002)
define('BOT_URL', 'http://localhost:5002/callback');

define('LOG_FILE', __DIR__ . '/callback.log');

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    die('Invalid JSON');
}

log_event("Received: " . json_encode($data, JSON_UNESCAPED_UNICODE));

// Forward to local Flask bot using cURL
$ch = curl_init(BOT_URL);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS => $input,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_CONNECTTIMEOUT => 5,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    log_event("cURL error: $error");
    http_response_code(502);
    die('Bad Gateway');
}

log_event("Flask replied ($httpCode): $response");

http_response_code($httpCode);
echo $response;

function log_event(string $message): void {
    file_put_contents(
        LOG_FILE,
        '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL,
        FILE_APPEND
    );
}
