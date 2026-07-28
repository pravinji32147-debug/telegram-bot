<?php
require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

if (empty($_ENV['BOT_TOKEN']) || empty($_ENV['API_KEY'])) {
    // We'll allow runtime failure but define constants for convenience.
}

define('BOT_TOKEN', $_ENV['BOT_TOKEN'] ?? getenv('BOT_TOKEN') ?: '');
define('API_KEY', $_ENV['API_KEY'] ?? getenv('API_KEY') ?: '');

// Logging level mapping for Monolog
$level = strtoupper($_ENV['LOG_LEVEL'] ?? getenv('LOG_LEVEL') ?: 'INFO');
switch ($level) {
    case 'DEBUG':
        define('LOG_LEVEL', Monolog\Logger::DEBUG);
        break;
    case 'NOTICE':
        define('LOG_LEVEL', Monolog\Logger::NOTICE);
        break;
    case 'WARNING':
        define('LOG_LEVEL', Monolog\Logger::WARNING);
        break;
    case 'ERROR':
        define('LOG_LEVEL', Monolog\Logger::ERROR);
        break;
    case 'CRITICAL':
        define('LOG_LEVEL', Monolog\Logger::CRITICAL);
        break;
    case 'ALERT':
        define('LOG_LEVEL', Monolog\Logger::ALERT);
        break;
    case 'EMERGENCY':
        define('LOG_LEVEL', Monolog\Logger::EMERGENCY);
        break;
    case 'INFO':
    default:
        define('LOG_LEVEL', Monolog\Logger::INFO);
}
