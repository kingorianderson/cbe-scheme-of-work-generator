<?php
// save_settings.php — POST handler for settings.php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: settings.php');
    exit;
}

$pdo = getDB();

function setSetting(PDO $pdo, string $key, string $value): void {
    $stmt = $pdo->prepare(
        "INSERT INTO app_settings (setting_key, setting_value)
         VALUES (:k, :v)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
    );
    $stmt->execute([':k' => $key, ':v' => $value]);
}

// ai_enabled
$aiEnabled = isset($_POST['ai_enabled']) && $_POST['ai_enabled'] === '1' ? '1' : '0';
setSetting($pdo, 'ai_enabled', $aiEnabled);

// ai_model — whitelist
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
setSetting($pdo, 'ai_model', $model);

// ai_api_key — only update if a new key was provided
$newKey = trim($_POST['ai_api_key'] ?? '');
if ($newKey !== '') {
    // Basic format check: Anthropic keys start with sk-ant-
    if (!str_starts_with($newKey, 'sk-ant-')) {
        header('Location: settings.php?msg=invalid_key');
        exit;
    }
    setSetting($pdo, 'ai_api_key', $newKey);
}

// groq_api_key — only update if a new key was provided
$groqKey = trim($_POST['groq_api_key'] ?? '');
if ($groqKey !== '') {
    if (!str_starts_with($groqKey, 'gsk_')) {
        header('Location: settings.php?msg=invalid_groq_key');
        exit;
    }
    setSetting($pdo, 'groq_api_key', $groqKey);
}

// deepseek_api_key — only update if a new key was provided
$deepseekKey = trim($_POST['deepseek_api_key'] ?? '');
if ($deepseekKey !== '') {
    // DeepSeek keys start with sk- (but NOT sk-ant- which is Claude)
    if (!str_starts_with($deepseekKey, 'sk-') || str_starts_with($deepseekKey, 'sk-ant-')) {
        header('Location: settings.php?msg=invalid_deepseek_key');
        exit;
    }
    setSetting($pdo, 'deepseek_api_key', $deepseekKey);
}

// deepseek_model — whitelist
$deepseekModels = ['deepseek-chat', 'deepseek-reasoner'];
$deepseekModel  = in_array($_POST['deepseek_model'] ?? '', $deepseekModels, true)
    ? $_POST['deepseek_model']
    : 'deepseek-chat';
if (!empty($_POST['deepseek_model'])) {
    setSetting($pdo, 'deepseek_model', $deepseekModel);
}

// groq_model — whitelist
$groqModels = [
    'llama-3.3-70b-versatile',
    'llama3-70b-8192',
    'mixtral-8x7b-32768',
    'gemma2-9b-it',
];
$groqModel = in_array($_POST['groq_model'] ?? '', $groqModels, true)
    ? $_POST['groq_model']
    : 'llama-3.3-70b-versatile';
setSetting($pdo, 'groq_model', $groqModel);

header('Location: settings.php?msg=saved');
exit;
