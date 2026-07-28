<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$logger = new Logger('webhook');
$logger->pushHandler(new StreamHandler(__DIR__ . '/logs/webhook.log', LOG_LEVEL));

// Read the raw body
$raw = file_get_contents('php://input');
$logger->info('Incoming webhook', ['raw' => $raw]);

$update = json_decode($raw, true);
if (!$update) {
    http_response_code(400);
    echo "ok";
    $logger->warning('Invalid JSON body');
    exit;
}

try {
    // message payload could be in several places
    $message = $update['message'] ?? $update['edited_message'] ?? null;
    if (!$message) {
        // Not a message we process
        http_response_code(200);
        echo "ok";
        $logger->info('Update has no message');
        exit;
    }

    $chatId = $message['chat']['id'];
    $text = trim($message['text'] ?? '');

    // Validate and process
    if (strcasecmp($text, '/start') === 0) {
        $reply = "Welcome! Send me a 12-digit Aadhaar number and I'll return the corresponding PAN number (via API).";
        sendTelegramMessage(BOT_TOKEN, $chatId, $reply, $logger);
        http_response_code(200);
        echo "ok";
        exit;
    }

    // Extract digits
    $digits = preg_replace('/\D/', '', $text);
    if (strlen($digits) !== 12) {
        $reply = "Please send a 12-digit Aadhaar number (only digits).";
        sendTelegramMessage(BOT_TOKEN, $chatId, $reply, $logger);
        http_response_code(200);
        echo "ok";
        exit;
    }

    if (!isValidAadhaar($digits)) {
        $reply = "The Aadhaar number you provided is invalid. Please check and send a valid 12-digit Aadhaar number.";
        sendTelegramMessage(BOT_TOKEN, $chatId, $reply, $logger);
        http_response_code(200);
        echo "ok";
        exit;
    }

    // Call external API to map Aadhaar to PAN
    $logger->info('Calling Aadhaar->PAN API', ['aadhaar' => $digits]);
    $result = callAadhaarToPanAPI($digits, API_KEY, $logger);

    if ($result['ok']) {
        $pan = $result['pan'] ?? null;
        if ($pan) {
            $reply = "PAN for Aadhaar ending with " . substr($digits, -4) . " is: <b>" . htmlspecialchars($pan) . "</b>";
            sendTelegramMessage(BOT_TOKEN, $chatId, $reply, $logger);
        } else {
            $reply = "PAN not found for the provided Aadhaar number.";
            sendTelegramMessage(BOT_TOKEN, $chatId, $reply, $logger);
        }
    } else {
        $reply = "An error occurred while fetching PAN: " . ($result['error'] ?? 'Unknown error');
        sendTelegramMessage(BOT_TOKEN, $chatId, $reply, $logger);
    }

    http_response_code(200);
    echo "ok";
} catch (Exception $e) {
    $logger->error('Webhook processing exception', ['exception' => $e->getMessage()]);
    http_response_code(500);
    echo "ok";
}
