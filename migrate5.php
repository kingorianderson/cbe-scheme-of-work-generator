<?php
// migrate5.php — creates lesson_plans table; seeds school/teacher settings
// Safe to run multiple times. Visit: http://localhost/SCHEME/migrate5.php
require_once 'config.php';
$pdo = getDB();
$log = [];

$steps = [
    "CREATE TABLE IF NOT EXISTS lesson_plans (
        id                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        sow_id                INT UNSIGNED NOT NULL,
        grade                 VARCHAR(100),
        learning_area         VARCHAR(255),
        strand                VARCHAR(255),
        sub_strand            VARCHAR(255),
        school_name           VARCHAR(255),
        teacher_name          VARCHAR(255),
        date_taught           DATE NULL,
        duration              VARCHAR(60),
        num_learners          SMALLINT UNSIGNED NULL,
        slo1                  TEXT,
        slo2                  TEXT,
        slo3                  TEXT,
        key_inquiry_question  TEXT,
        core_competencies     TEXT,
        pcis                  TEXT,
        values_attit          TEXT,
        resources             TEXT,
        introduction          TEXT,
        step1                 TEXT,
        step2                 TEXT,
        step3                 TEXT,
        conclusion            TEXT,
        reflection            TEXT,
        extended_activity     TEXT,
        created_at            TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at            TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_sow (sow_id),
        CONSTRAINT fk_lp_sow FOREIGN KEY (sow_id)
            REFERENCES scheme_of_work(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "INSERT IGNORE INTO app_settings (setting_key, setting_value) VALUES
        ('school_name',  ''),
        ('teacher_name', '')",
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
<title>Migration 5 — Lesson Plans</title>
<style>body{font-family:Segoe UI,sans-serif;padding:30px} .ok{color:#065f46} .warn{color:#92400e}</style>
</head><body>
<h2>Migration 5: lesson_plans table</h2>
<ul>
<?php foreach ($log as [$type, $msg]): ?>
  <li class="<?= $type ?>"><?= htmlspecialchars($msg) ?></li>
<?php endforeach; ?>
</ul>
<br>
<a href="curriculum.php" style="background:#1a56db;color:#fff;padding:10px 20px;border-radius:5px;text-decoration:none;font-weight:bold">Go to Curriculum &rarr;</a>
<br><br>
<small style="color:#6b7280">Also adds <code>school_name</code> and <code>teacher_name</code> to app_settings. Set them in <a href="settings.php">Settings</a>.</small>
</body></html>
