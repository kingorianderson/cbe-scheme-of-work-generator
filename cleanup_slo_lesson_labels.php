<?php
// cleanup_slo_lesson_labels.php
// Removes "Lesson N:" heading lines from slo_sow for PTS G9 and Math G6.
// Safe to run multiple times (idempotent).
// Usage: http://localhost/SCHEME/cleanup_slo_lesson_labels.php?la=N[,M]
//   e.g. ?la=1,2   or  ?la=1
require_once 'config.php';
$pdo = getDB();

// Accept one or more learning area IDs via ?la=1,2
$raw = isset($_GET['la']) ? $_GET['la'] : '';
$ids = array_filter(array_map('intval', explode(',', $raw)));
if (empty($ids)) {
    echo "<p style='color:red'>Provide ?la=N (comma-separated learning area IDs, e.g. ?la=1,2).</p>";
    echo "<p><a href='curriculum.php'>Go to Curriculum to find your IDs</a></p>";
    exit;
}

// Fetch names for display
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$laRows = $pdo->prepare("SELECT la.id, la.name, g.name AS grade_name FROM learning_areas la JOIN grades g ON g.id = la.grade_id WHERE la.id IN ($placeholders)");
$laRows->execute(array_values($ids));
$laNames = [];
foreach ($laRows->fetchAll() as $r) $laNames[$r['id']] = "{$r['name']} ({$r['grade_name']})";

// Fetch all affected rows
$rows = $pdo->prepare("SELECT id, slo_sow FROM scheme_of_work WHERE learning_area_id IN ($placeholders) AND slo_sow LIKE '%Lesson %:%'");
$rows->execute(array_values($ids));
$records = $rows->fetchAll();

$updated = 0;
$unchanged = 0;
$log = [];

$updateStmt = $pdo->prepare("UPDATE scheme_of_work SET slo_sow = :v WHERE id = :id");

foreach ($records as $r) {
    $original = $r['slo_sow'];

    // Remove lines that are solely "Lesson N:" (with optional whitespace)
    $cleaned = preg_replace('/^[ \t]*Lesson\s+\d+\s*:\s*\r?\n?/im', '', $original);
    // Collapse any leading blank lines left behind
    $cleaned = ltrim($cleaned);

    if ($cleaned !== $original) {
        $updateStmt->execute([':v' => $cleaned, ':id' => $r['id']]);
        $log[] = ['ok', "ID {$r['id']} — updated"];
        $updated++;
    } else {
        $unchanged++;
    }
}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">
<title>Cleanup Lesson Labels</title>
<style>
body{font-family:Segoe UI,sans-serif;padding:30px;max-width:760px}
.ok{color:#065f46}.skip{color:#6b7280}
li{margin-bottom:4px;font-size:13px}
.summary{background:#f0fdf4;border:1px solid #bbf7d0;border-radius:6px;padding:12px 16px;margin-bottom:16px}
</style>
</head><body>
<h2>Cleanup: Remove "Lesson N:" labels from SLO (SOW)</h2>
<p style="font-size:13px;color:#374151">
  Learning areas: <?= implode(', ', array_map(fn($id) => htmlspecialchars($laNames[$id] ?? "ID $id"), $ids)) ?>
</p>
<div class="summary">
  <strong><?= $updated ?> records updated</strong> &nbsp;|&nbsp; <?= $unchanged ?> already clean
</div>
<ul>
<?php foreach ($log as [$t, $m]): ?>
  <li class="<?= $t ?>"><?= htmlspecialchars($m) ?></li>
<?php endforeach; ?>
</ul>
<br>
<?php foreach ($ids as $id): ?>
  <a href="index.php?learning_area_id=<?= $id ?>" style="background:#1a56db;color:#fff;padding:9px 18px;border-radius:5px;text-decoration:none;font-weight:bold;margin-right:8px">View SOW (LA <?= $id ?>) &rarr;</a>
<?php endforeach; ?>
</body></html>
