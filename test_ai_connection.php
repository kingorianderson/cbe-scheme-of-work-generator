<?php
// test_ai_connection.php — AJAX endpoint; tests Claude, Groq, or DeepSeek API connectivity
// Called via fetch() from settings.php
header('Content-Type: application/json');
require_once 'config.php';

$pdo = getDB();

$provider = $_REQUEST['provider'] ?? 'claude';

// ── Test Groq ──────────────────────────────────────────────────────────────
if ($provider === 'groq') {
    $apiKey = trim($_REQUEST['groq_api_key'] ?? '');
    if ($apiKey === '') {
        $row = $pdo->prepare("SELECT setting_value FROM app_settings WHERE setting_key = 'groq_api_key'");
        $row->execute();
        $apiKey = $row->fetchColumn() ?? '';
    }
    if ($apiKey === '') {
        echo json_encode(['ok' => false, 'error' => 'No Groq API key provided']);
        exit;
    }

    $groqModels = ['llama-3.3-70b-versatile','llama3-70b-8192','mixtral-8x7b-32768','gemma2-9b-it'];
    $model = in_array($_POST['groq_model'] ?? '', $groqModels, true)
        ? $_POST['groq_model']
        : 'llama-3.3-70b-versatile';

    $payload = json_encode([
        'model'      => $model,
        'max_tokens' => 10,
        'messages'   => [['role' => 'user', 'content' => 'Say "ok"']],
    ]);

    $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
        ],
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        echo json_encode(['ok' => false, 'error' => 'cURL error: ' . $curlErr]);
        exit;
    }

    $body = json_decode($response, true);
    if ($httpCode === 200 && isset($body['choices'])) {
        echo json_encode(['ok' => true]);
    } else {
        $errMsg = $body['error']['message'] ?? ('HTTP ' . $httpCode);
        echo json_encode(['ok' => false, 'error' => $errMsg]);
    }
    exit;
}

// ── Test DeepSeek ─────────────────────────────────────────────────────────
if ($provider === 'deepseek') {
    $apiKey = trim($_REQUEST['deepseek_api_key'] ?? '');
    if ($apiKey === '') {
        $row = $pdo->prepare("SELECT setting_value FROM app_settings WHERE setting_key = 'deepseek_api_key'");
        $row->execute();
        $apiKey = (string)($row->fetchColumn() ?: '');
    }
    if ($apiKey === '') {
        echo json_encode(['ok' => false, 'error' => 'No DeepSeek API key saved']);
        exit;
    }
    $payload = json_encode([
        'model'      => 'deepseek-chat',
        'max_tokens' => 10,
        'messages'   => [['role' => 'user', 'content' => 'Say "ok"']],
    ]);
    $ch = curl_init('https://api.deepseek.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
        ],
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);
    if ($curlErr) { echo json_encode(['ok' => false, 'error' => 'cURL: ' . $curlErr]); exit; }
    $body = json_decode($response, true);
    if ($httpCode === 200 && isset($body['choices'][0]['message']['content'])) {
        echo json_encode(['ok' => true]);
    } else {
        echo json_encode(['ok' => false, 'error' => $body['error']['message'] ?? ('HTTP ' . $httpCode)]);
    }
    exit;
}

// ── Test Claude (default) ──────────────────────────────────────────────────
$apiKey = trim($_POST['ai_api_key'] ?? '');
if ($apiKey === '') {
    $row = $pdo->prepare("SELECT setting_value FROM app_settings WHERE setting_key = 'ai_api_key'");
    $row->execute();
    $apiKey = $row->fetchColumn() ?? '';
}

if ($apiKey === '') {
    echo json_encode(['ok' => false, 'error' => 'No API key provided']);
    exit;
}

// Whitelist model
$allowedModels = [
    'claude-opus-4-5',
    'claude-sonnet-4-5',
    'claude-haiku-4-5',
    'claude-3-5-sonnet-20241022',
    'claude-3-haiku-20240307',
];
$model = in_array($_POST['ai_model'] ?? '', $allowedModels, true)
    ? $_POST['ai_model']
    : 'claude-sonnet-4-5';

// Minimal test request to Claude API
$payload = json_encode([
    'model'      => $model,
    'max_tokens' => 10,
    'messages'   => [['role' => 'user', 'content' => 'Say "ok"']],
]);

$ch = curl_init('https://api.anthropic.com/v1/messages');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'x-api-key: ' . $apiKey,
        'anthropic-version: 2023-06-01',
    ],
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr  = curl_error($ch);
curl_close($ch);

if ($curlErr) {
    echo json_encode(['ok' => false, 'error' => 'cURL error: ' . $curlErr]);
    exit;
}

$body = json_decode($response, true);

if ($httpCode === 200 && isset($body['content'])) {
    echo json_encode(['ok' => true]);
} else {
    $errMsg = $body['error']['message'] ?? ('HTTP ' . $httpCode);
    echo json_encode(['ok' => false, 'error' => $errMsg]);
}


// Use POSTed key if provided, otherwise use saved key
$apiKey = trim($_POST['ai_api_key'] ?? '');
if ($apiKey === '') {
    $row = $pdo->prepare("SELECT setting_value FROM app_settings WHERE setting_key = 'ai_api_key'");
    $row->execute();
    $apiKey = $row->fetchColumn() ?? '';
}

if ($apiKey === '') {
    echo json_encode(['ok' => false, 'error' => 'No API key provided']);
    exit;
}

// Whitelist model
$allowedModels = [
    'claude-opus-4-5',
    'claude-sonnet-4-5',
    'claude-haiku-4-5',
    'claude-3-5-sonnet-20241022',
    'claude-3-haiku-20240307',
];
$model = in_array($_POST['ai_model'] ?? '', $allowedModels, true)
    ? $_POST['ai_model']
    : 'claude-sonnet-4-5';

// Minimal test request to Claude API
$payload = json_encode([
    'model'      => $model,
    'max_tokens' => 10,
    'messages'   => [['role' => 'user', 'content' => 'Say "ok"']],
]);

$ch = curl_init('https://api.anthropic.com/v1/messages');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'x-api-key: ' . $apiKey,
        'anthropic-version: 2023-06-01',
    ],
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr  = curl_error($ch);
curl_close($ch);

if ($curlErr) {
    echo json_encode(['ok' => false, 'error' => 'cURL error: ' . $curlErr]);
    exit;
}

$body = json_decode($response, true);

if ($httpCode === 200 && isset($body['content'])) {
    echo json_encode(['ok' => true]);
} else {
    $errMsg = $body['error']['message'] ?? ('HTTP ' . $httpCode);
    echo json_encode(['ok' => false, 'error' => $errMsg]);
}
