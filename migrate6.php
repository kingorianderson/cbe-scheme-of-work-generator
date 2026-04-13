<?php
// migrate6.php — Adds groq_api_key and groq_model to app_settings
require_once 'config.php';
$pdo = getDB();
$log = [];

$steps = [
    "INSERT IGNORE INTO app_settings (setting_key, setting_value) VALUES
        ('groq_api_key', ''),
        ('groq_model', 'llama-3.3-70b-versatile')",
];

foreach ($steps as $i => $sql) {
    try {
        $pdo->exec($sql);
        $log[] = ['ok', "Step " . ($i + 1) . ": OK"];
    } catch (PDOException $e) {
        $log[] = ['warn', "Step " . ($i + 1) . ": " . $e->getMessage()];
    }
}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">
<title>Migration 6</title>
<style>body{font-family:Segoe UI,sans-serif;padding:30px}.ok{color:#065f46}.warn{color:#92400e}
a{color:#1a56db}</style></head><body>
<h2>Migration 6: Groq AI Fallback</h2>
<?php foreach ($log as [$cls, $msg]): ?>
  <p class="<?= $cls ?>">&#10003; <?= htmlspecialchars($msg) ?></p>
<?php endforeach; ?>
<p><a href="settings.php">&#8594; Go to Settings to add your Groq API key</a></p>
</body></html>
