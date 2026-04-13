<?php
// migrate.php — adds grades and learning_areas tables to an existing install
// Safe to run multiple times. Visit: http://localhost/SCHEME/migrate.php
require_once 'config.php';
$pdo = getDB();
$log = [];

$steps = [
    // 1. grades table
    "CREATE TABLE IF NOT EXISTS grades (
        id               TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        level_group      VARCHAR(60)  NOT NULL,
        name             VARCHAR(50)  NOT NULL,
        lesson_duration  SMALLINT UNSIGNED DEFAULT 35,
        sort_order       TINYINT UNSIGNED DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // 2. seed grades
    "INSERT IGNORE INTO grades (id, level_group, name, lesson_duration, sort_order) VALUES
        (1,'Pre-Primary','PP 1',30,1),(2,'Pre-Primary','PP 2',30,2),
        (3,'Primary — Lower','Grade 1',35,3),(4,'Primary — Lower','Grade 2',35,4),
        (5,'Primary — Lower','Grade 3',35,5),(6,'Primary — Upper','Grade 4',35,6),
        (7,'Primary — Upper','Grade 5',35,7),(8,'Primary — Upper','Grade 6',35,8),
        (9,'Junior Secondary','Grade 7',40,9),(10,'Junior Secondary','Grade 8',40,10),
        (11,'Junior Secondary','Grade 9',40,11)",

    // 3. learning_areas table
    "CREATE TABLE IF NOT EXISTS learning_areas (
        id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        grade_id         TINYINT UNSIGNED NOT NULL,
        name             VARCHAR(255) NOT NULL,
        short_code       VARCHAR(20)  DEFAULT NULL,
        lessons_per_week TINYINT UNSIGNED NOT NULL DEFAULT 5,
        created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_grade (grade_id),
        FOREIGN KEY (grade_id) REFERENCES grades(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // 4. add learning_area_id to scheme_of_work if missing
    "ALTER TABLE scheme_of_work ADD COLUMN IF NOT EXISTS learning_area_id INT UNSIGNED DEFAULT NULL AFTER id",

    // 5. add index if missing (safe — CREATE INDEX IF NOT EXISTS is MySQL 8+; we use ALTER IGNORE)
    "ALTER TABLE scheme_of_work ADD INDEX IF NOT EXISTS idx_learning_area (learning_area_id)",
];

foreach ($steps as $i => $sql) {
    try {
        $pdo->exec($sql);
        $log[] = ['ok', "Step " . ($i+1) . ": OK"];
    } catch (PDOException $e) {
        // 'Duplicate' errors on ALTER are warnings, not fatal
        $log[] = ['warn', "Step " . ($i+1) . ": " . $e->getMessage()];
    }
}

// Add FK only if not already present
try {
    $pdo->exec("ALTER TABLE scheme_of_work ADD CONSTRAINT fk_sow_la
                FOREIGN KEY (learning_area_id) REFERENCES learning_areas(id) ON DELETE SET NULL");
    $log[] = ['ok', "Foreign key added."];
} catch (PDOException $e) {
    $log[] = ['warn', "FK: " . $e->getMessage()];
}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">
<title>Migration — Scheme of Work</title>
<style>body{font-family:Segoe UI,sans-serif;padding:30px;} .ok{color:#065f46} .warn{color:#92400e}</style>
</head><body>
<h2>Migration Results</h2>
<ul>
<?php foreach ($log as [$type, $msg]): ?>
  <li class="<?= $type ?>"><?= htmlspecialchars($msg) ?></li>
<?php endforeach; ?>
</ul>
<br>
<a href="curriculum.php" style="background:#1a56db;color:#fff;padding:10px 20px;border-radius:5px;text-decoration:none;font-weight:bold">Go to Curriculum &rarr;</a>
</body></html>
