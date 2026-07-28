<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$logger = new Logger('telegram-bot');
$logger->pushHandler(new StreamHandler(__DIR__ . '/logs/bot.log', LOG_LEVEL));

$bot = new class($logger) {
    private $logger;
    private $botToken;

    public function __construct($logger)
    {
        $this->logger = $logger;
        $this->botToken = BOT_TOKEN;
    }

    public function sendMessage($chatId, $text)
    {
        $url = "https://api.telegram.org/bot" . $this->botToken . "/sendMessage";
        $payload = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML'
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        $resp = curl_exec($ch);
        $err = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($err) {
            $this->logger->error('Telegram sendMessage curl error: ' . $err);
            return false;
        }

        $this->logger->info('Telegram sendMessage response', ['http_code' => $httpCode, 'response' => $resp]);
        return json_decode($resp, true);
    }
};

// Expose the $bot object for other scripts
return $bot;
