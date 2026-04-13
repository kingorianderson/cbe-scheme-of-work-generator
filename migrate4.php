<?php
// migrate4.php — adds assessment column to sub_strand_meta
// Safe to run multiple times. Visit: http://localhost/SCHEME/migrate4.php
require_once 'config.php';
$pdo = getDB();
$log = [];

$steps = [
    "ALTER TABLE sub_strand_meta ADD COLUMN assessment TEXT NULL AFTER resources",
];

foreach ($steps as $i => $sql) {
    try {
        $pdo->exec($sql);
        $log[] = ['ok', "Step " . ($i + 1) . ": OK — assessment column added"];
    } catch (PDOException $e) {
        // 1060 = Duplicate column name — already run, that's fine
        if (strpos($e->getMessage(), 'Duplicate column') !== false || $e->getCode() === '42S21') {
            $log[] = ['ok', "Step " . ($i + 1) . ": Already done (column exists)"];
        } else {
            $log[] = ['warn', "Step " . ($i + 1) . ": " . $e->getMessage()];
        }
    }
}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">
<title>Migration 4 — Add assessment to sub_strand_meta</title>
<style>body{font-family:Segoe UI,sans-serif;padding:30px} .ok{color:#065f46} .warn{color:#92400e}</style>
</head><body>
<h2>Migration 4: Add assessment column to sub_strand_meta</h2>
<ul>
<?php foreach ($log as [$type, $msg]): ?>
  <li class="<?= $type ?>"><?= htmlspecialchars($msg) ?></li>
<?php endforeach; ?>
</ul>
<br>
<a href="curriculum.php" style="background:#1a56db;color:#fff;padding:10px 20px;border-radius:5px;text-decoration:none;font-weight:bold">Go to Curriculum &rarr;</a>
</body></html>
