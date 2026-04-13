<?php
// migrate3.php — creates the app_settings table for AI configuration
// Safe to run multiple times. Visit: http://localhost/SCHEME/migrate3.php
require_once 'config.php';
$pdo = getDB();
$log = [];

$steps = [
    "CREATE TABLE IF NOT EXISTS app_settings (
        id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        setting_key   VARCHAR(100) NOT NULL UNIQUE,
        setting_value TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "INSERT IGNORE INTO app_settings (setting_key, setting_value) VALUES
        ('ai_enabled',    '0'),
        ('ai_model',      'claude-sonnet-4-5'),
        ('ai_api_key',    '')",
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
<title>Migration 3 — App Settings</title>
<style>body{font-family:Segoe UI,sans-serif;padding:30px}.ok{color:#065f46}.warn{color:#92400e}</style>
</head><body>
<h2>Migration 3: app_settings table</h2>
<ul>
<?php foreach ($log as [$type, $msg]): ?>
  <li class="<?= $type ?>"><?= htmlspecialchars($msg) ?></li>
<?php endforeach; ?>
</ul>
<br>
<a href="settings.php" style="background:#1a56db;color:#fff;padding:10px 20px;border-radius:5px;text-decoration:none;font-weight:bold">Go to Settings &rarr;</a>
</body></html>
