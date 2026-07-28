<?php

/**
 * Validates a 12-digit Aadhaar number using the Verhoeff algorithm.
 * Returns true if valid, false otherwise.
 */
function isValidAadhaar(string $aadhaar): bool
{
    if (!preg_match('/^\d{12}$/', $aadhaar)) {
        return false;
    }
    return verhoeffValidate($aadhaar);
}

// Verhoeff algorithm implementation
function verhoeffValidate(string $num): bool
{
    $d = [
        [0,1,2,3,4,5,6,7,8,9],
        [1,2,3,4,0,6,7,8,9,5],
        [2,3,4,0,1,7,8,9,5,6],
        [3,4,0,1,2,8,9,5,6,7],
        [4,0,1,2,3,9,5,6,7,8],
        [5,9,8,7,6,0,4,3,2,1],
        [6,5,9,8,7,1,0,4,3,2],
        [7,6,5,9,8,2,1,0,4,3],
        [8,7,6,5,9,3,2,1,0,4],
        [9,8,7,6,5,4,3,2,1,0]
    ];

    $p = [
        [0,1,2,3,4,5,6,7,8,9],
        [1,5,7,6,2,8,3,0,9,4],
        [5,8,0,3,7,9,6,1,4,2],
        [8,9,1,6,0,4,3,5,2,7],
        [9,4,5,3,1,2,6,8,7,0],
        [4,2,8,6,5,7,3,9,0,1],
        [2,7,9,3,8,0,6,4,1,5],
        [7,0,4,6,9,1,3,2,5,8]
    ];

    $inv = [0,4,3,2,1,5,6,7,8,9];

    $c = 0;
    $num = strrev($num);
    for ($i = 0, $len = strlen($num); $i < $len; $i++) {
        $c = $d[$c][$p[$i % 8][intval($num[$i])]];
    }

    return $c === 0;
}

/**
 * Call external API to map Aadhaar to PAN using cURL.
 * Returns ['ok' => true, 'pan' => 'XXXX'] or ['ok'=>false, 'error' => 'msg']
 */
function callAadhaarToPanAPI(string $aadhaar, string $apiKey, $logger = null): array
{
    $endpoint = 'https://www.apicentre.in/api/aadhaar_to_pan';

    $payload = json_encode(['aadhaar' => $aadhaar]);

    $ch = curl_init($endpoint);
    $headers = [
        'Content-Type: application/json',
    ];
    if (!empty($apiKey)) {
        // Prefer Authorization header but the API may accept api_key in body. We add both.
        $headers[] = 'Authorization: Bearer ' . $apiKey;
    }

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);

    $resp = curl_exec($ch);
    $err = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($logger) {
        $logger->info('Aadhaar->PAN API response', ['http_code' => $httpCode, 'response' => $resp, 'error' => $err]);
    }

    if ($err) {
        return ['ok' => false, 'error' => 'cURL error: ' . $err];
    }

    if ($httpCode < 200 || $httpCode >= 300) {
        return ['ok' => false, 'error' => "HTTP $httpCode", 'raw' => $resp];
    }

    $data = json_decode($resp, true);
    if ($data === null) {
        return ['ok' => false, 'error' => 'Invalid JSON from API', 'raw' => $resp];
    }

    // Try to locate PAN in common fields
    if (isset($data['pan'])) {
        return ['ok' => true, 'pan' => $data['pan']];
    }
    if (isset($data['data']['pan'])) {
        return ['ok' => true, 'pan' => $data['data']['pan']];
    }

    // Fallback: try to find a PAN-like string
    if (preg_match('/[A-Z]{5}[0-9]{4}[A-Z]{1}/i', json_encode($data), $m)) {
        return ['ok' => true, 'pan' => strtoupper($m[0])];
    }

    return ['ok' => false, 'error' => 'PAN not found in response', 'raw' => $data];
}

/**
 * Helper to send telegram message (used by webhook.php)
 */
function sendTelegramMessage(string $botToken, $chatId, string $text, $logger = null)
{
    $url = "https://api.telegram.org/bot" . $botToken . "/sendMessage";
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

    if ($logger) {
        $logger->info('sendTelegramMessage result', ['http_code' => $httpCode, 'response' => $resp, 'error' => $err]);
    }

    if ($err) {
        if ($logger) $logger->error('sendTelegramMessage curl error: ' . $err);
        return false;
    }
    return json_decode($resp, true);
}
