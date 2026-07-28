<?php
// Simple status page
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config.php';

http_response_code(200);
header('Content-Type: text/plain');
$env = getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'production');
echo "Telegram Aadhaar->PAN Bot\n";
echo "Environment: " . $env . "\n";
echo "Bot token set: " . (BOT_TOKEN ? 'yes' : 'no') . "\n";
echo "API key set: " . (API_KEY ? 'yes' : 'no') . "\n";
