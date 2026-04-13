<?php
// migrate2.php — creates the sub_strand_meta table
// Safe to run multiple times. Visit: http://localhost/SCHEME/migrate2.php
require_once 'config.php';
$pdo = getDB();
$log = [];

$steps = [
    "CREATE TABLE IF NOT EXISTS sub_strand_meta (
        id                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        learning_area_id      INT UNSIGNED NOT NULL,
        strand                VARCHAR(255) NOT NULL,
        sub_strand            VARCHAR(255) NOT NULL,
        key_inquiry_qs        TEXT,
        core_competencies     TEXT,
        values_attit          TEXT,
        pcis                  TEXT,
        links_to_other_areas  TEXT,
        resources             TEXT,
        created_at            TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_la_ss (learning_area_id, strand(100), sub_strand(100)),
        CONSTRAINT fk_ssm_la FOREIGN KEY (learning_area_id)
            REFERENCES learning_areas(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
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
<title>Migration 2 — Sub-Strand Meta</title>
<style>body{font-family:Segoe UI,sans-serif;padding:30px} .ok{color:#065f46} .warn{color:#92400e}</style>
</head><body>
<h2>Migration 2: sub_strand_meta table</h2>
<ul>
<?php foreach ($log as [$type, $msg]): ?>
  <li class="<?= $type ?>"><?= htmlspecialchars($msg) ?></li>
<?php endforeach; ?>
</ul>
<br>
<a href="curriculum.php" style="background:#1a56db;color:#fff;padding:10px 20px;border-radius:5px;text-decoration:none;font-weight:bold">Go to Curriculum &rarr;</a>
</body></html>
