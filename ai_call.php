<?php
// ai_call.php — Shared helper: call Groq (primary 1) → DeepSeek (primary 2) → Claude (last resort)
// Include this file; call aiCallWithFallback($pdo, $prompt, $maxTokens) → string|false
// Returns the raw text content from the AI, or false on total failure.
// $lastAiError (global string) is set to the error message on failure.

if (!function_exists('aiCallWithFallback')) {

/**
 * Call Groq first; if it fails, try Claude as fallback.
 * Returns raw AI text string, or false if both fail.
 * Sets global $lastAiError on failure.
 */
/**
 * @param array $skipProviders  List of provider names to skip, e.g. ['groq']
 */
function aiCallWithFallback(PDO $pdo, string $prompt, int $maxTokens = 600, array $skipProviders = []): string|false {
    global $lastAiError;
    $lastAiError = '';
    $skip = array_map('strtolower', $skipProviders);

    // ── Load settings ──────────────────────────────────────────────────────
    $settings = [];
    try {
        $rows = $pdo->query("SELECT setting_key, setting_value FROM app_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
        $settings = $rows ?: [];
    } catch (PDOException $e) {}

    $claudeKey    = trim($settings['ai_api_key']      ?? '');
    $claudeModel  = trim($settings['ai_model']         ?? 'claude-sonnet-4-5');
    $groqKey      = trim($settings['groq_api_key']     ?? '');
    $groqModel    = trim($settings['groq_model']       ?? 'llama-3.3-70b-versatile');
    $deepseekKey  = trim($settings['deepseek_api_key'] ?? '');
    $deepseekModel= trim($settings['deepseek_model']   ?? 'deepseek-chat');

    if ($groqKey === '' && $deepseekKey === '' && $claudeKey === '') {
        $lastAiError = 'No API key configured. Visit Settings to add a Groq, DeepSeek, or Claude key.';
        return false;
    }

    $errors = [];

    // ── 1. Try DeepSeek (primary) ──────────────────────────────────────────
    if ($deepseekKey !== '' && !in_array('deepseek', $skip, true)) {
        $result = _callDeepSeek($deepseekKey, $deepseekModel, $prompt, $maxTokens);
        if ($result !== false) return $result;
        $errors['DeepSeek'] = _getLastCurlError();
    }

    // ── 2. Try Groq (fast fallback) — skip if caller flagged it as exhausted
    if ($groqKey !== '' && !in_array('groq', $skip, true)) {
        $result = _callGroq($groqKey, $groqModel, $prompt, $maxTokens);
        if ($result !== false) return $result;
        $errors['Groq'] = _getLastCurlError();
    }

    // ── 3. Last resort: Claude ─────────────────────────────────────────────
    if ($claudeKey !== '' && !in_array('claude', $skip, true)) {
        $result = _callClaude($claudeKey, $claudeModel, $prompt, $maxTokens);
        if ($result !== false) return $result;
        $errors['Claude'] = _getLastCurlError();
    }

    $lastAiError = implode(' | ', array_map(
        fn($k, $v) => "$k: $v", array_keys($errors), $errors
    ));
    return false;
}

// Internal: last curl/api error string
$_aiLastErr = '';
function _getLastCurlError(): string { global $_aiLastErr; return $_aiLastErr; }

/**
 * Call Anthropic Claude API.
 */
function _callClaude(string $apiKey, string $model, string $prompt, int $maxTokens): string|false {
    global $_aiLastErr;

    $payload = json_encode([
        'model'      => $model,
        'max_tokens' => $maxTokens,
        'messages'   => [['role' => 'user', 'content' => $prompt]],
    ]);

    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_TIMEOUT        => 45,
        CURLOPT_CONNECTTIMEOUT => 8,
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

    if ($curlErr) { $_aiLastErr = 'Network: ' . $curlErr; return false; }

    $data = json_decode($response, true);
    // Any non-200 (401 auth, 402 payment/quota, 429 rate-limit, 529 overload, etc.) → fallback
    if ($httpCode !== 200 || !isset($data['content'][0]['text'])) {
        $_aiLastErr = 'HTTP ' . $httpCode . ' – ' . ($data['error']['message'] ?? 'no detail');
        return false;
    }

    return trim($data['content'][0]['text']);
}

/**
 * Call Groq API (OpenAI-compatible chat/completions endpoint).
 */
function _callGroq(string $apiKey, string $model, string $prompt, int $maxTokens): string|false {
    global $_aiLastErr;

    $payload = json_encode([
        'model'      => $model,
        'max_tokens' => $maxTokens,
        'messages'   => [['role' => 'user', 'content' => $prompt]],
    ]);

    $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_TIMEOUT        => 45,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
        ],
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr) { $_aiLastErr = 'Network: ' . $curlErr; return false; }

    $data = json_decode($response, true);
    // Any non-200 (401 auth, 429 rate-limit/quota, etc.) counts as failure
    if ($httpCode !== 200 || !isset($data['choices'][0]['message']['content'])) {
        $_aiLastErr = 'HTTP ' . $httpCode . ' – ' . ($data['error']['message'] ?? 'no detail');
        return false;
    }

    return trim($data['choices'][0]['message']['content']);
}

/**
 * Call DeepSeek API (OpenAI-compatible endpoint).
 */
function _callDeepSeek(string $apiKey, string $model, string $prompt, int $maxTokens): string|false {
    global $_aiLastErr;

    $payload = json_encode([
        'model'      => $model,
        'max_tokens' => $maxTokens,
        'messages'   => [['role' => 'user', 'content' => $prompt]],
    ]);

    $ch = curl_init('https://api.deepseek.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_TIMEOUT        => 90,  // DeepSeek can be slow on large responses
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
        ],
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr) { $_aiLastErr = 'Network: ' . $curlErr; return false; }

    $data = json_decode($response, true);
    if ($httpCode !== 200 || !isset($data['choices'][0]['message']['content'])) {
        $_aiLastErr = 'HTTP ' . $httpCode . ' – ' . ($data['error']['message'] ?? 'no detail');
        return false;
    }

    return trim($data['choices'][0]['message']['content']);
}

} // end if !function_exists
