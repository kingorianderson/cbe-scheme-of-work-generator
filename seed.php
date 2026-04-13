<?php
// Seed script — Pre-populates Grade 9 Pre-Technical Studies SOW
// Run once at: http://localhost/SCHEME/seed.php?la=1
require_once 'config.php';

$pdo = getDB();

// Learning area ID to seed into (default 1)
$learningAreaId = isset($_GET['la']) ? (int)$_GET['la'] : 1;

// Verify the learning area exists
$laStmt = $pdo->prepare("SELECT la.*, g.name AS grade_name FROM learning_areas la JOIN grades g ON g.id = la.grade_id WHERE la.id = :id");
$laStmt->execute([':id' => $learningAreaId]);
$la = $laStmt->fetch();
if (!$la) {
    echo "<p style='color:red'>Learning area ID $learningAreaId not found. <a href='curriculum.php'>Go to Curriculum</a></p>";
    exit;
}

// Check if already seeded for this learning area
$count = $pdo->prepare("SELECT COUNT(*) FROM scheme_of_work WHERE learning_area_id = :id");
$count->execute([':id' => $learningAreaId]);
$count = $count->fetchColumn();

if ($count > 0) {
    echo "<p style='color:orange;font-weight:bold'>Learning area already has $count record(s). Seed skipped.</p>";
    echo "<p><a href='index.php?learning_area_id=$learningAreaId'>Go to SOW &rarr;</a> &nbsp;|&nbsp; 
          <a href='seed.php?la=$learningAreaId&force=1' onclick=\"return confirm('Delete all existing rows for this learning area and re-seed?')\">Force re-seed</a></p>";
    if (empty($_GET['force'])) exit;
    $pdo->prepare("DELETE FROM scheme_of_work WHERE learning_area_id = :id")->execute([':id' => $learningAreaId]);
    echo "<p>Records cleared. Re-seeding…</p>";
}

// ── Subject map ─────────────────────────────────────────────────────────────
// Each entry: [strand, sub_strand, suggested_lessons]
$syllabus = [
    ['1.0 Foundations of Pre-Technical Studies', '1.1 Safety on Raised Platforms',          8],
    ['1.0 Foundations of Pre-Technical Studies', '1.2 Handling Hazardous Substances',        9],
    ['1.0 Foundations of Pre-Technical Studies', '1.3 Self-Exploration and Career Development', 6],
    ['2.0 Communication in Pre-Technical Studies', '2.1 Oblique Projection',                14],
    ['2.0 Communication in Pre-Technical Studies', '2.2 Visual Programming',                15],
    ['3.0 Materials for Production',               '3.1 Wood',                               8],
    ['3.0 Materials for Production',               '3.2 Handling Waste Materials',           8],
    ['4.0 Tools and Production',                   '4.1 Holding Tools',                      8],
    ['4.0 Tools and Production',                   '4.2 Driving Tools',                      8],
    ['4.0 Tools and Production',                   '4.3 Project',                           20],
    ['5.0 Entrepreneurship',                       '5.1 Financial Services',                 4],
    ['5.0 Entrepreneurship',                       '5.2 Government and Business',            6],
    ['5.0 Entrepreneurship',                       '5.3 Business Plan',                      6],
];

// ── Insert rows ──────────────────────────────────────────────────────────────
$sql = 'INSERT INTO scheme_of_work
            (learning_area_id, week, lesson, strand, sub_strand, slo_cd, slo_sow, le_cd, le_sow,
             key_inquiry, resources, assessment, remarks)
        VALUES
            (:laid, :week, :lesson, :strand, :sub_strand, :slo_cd, :slo_sow, :le_cd, :le_sow,
             :key_inquiry, :resources, :assessment, :remarks)';
$stmt = $pdo->prepare($sql);

$LESSONS_PER_WEEK = 4;
$currentWeek   = 1;
$lessonInWeek  = 1;   // 1-3
$totalInserted = 0;

foreach ($syllabus as [$strand, $sub_strand, $totalLessons]) {
    for ($i = 1; $i <= $totalLessons; $i++) {
        $stmt->execute([
            ':laid'        => $learningAreaId,
            ':week'        => $currentWeek,
            ':lesson'      => $lessonInWeek,
            ':strand'      => $strand,
            ':sub_strand'  => $sub_strand,
            ':slo_cd'      => '',
            ':slo_sow'     => '',
            ':le_cd'       => '',
            ':le_sow'      => '',
            ':key_inquiry' => '',
            ':resources'   => '',
            ':assessment'  => '',
            ':remarks'     => '',
        ]);
        $totalInserted++;

        $lessonInWeek++;
        if ($lessonInWeek > $LESSONS_PER_WEEK) {
            $lessonInWeek = 1;
            $currentWeek++;
        }
    }
}

echo "<style>body{font-family:Segoe UI,sans-serif;padding:30px;} table{border-collapse:collapse;width:100%;} th,td{border:1px solid #ccc;padding:8px 12px;} th{background:#1a56db;color:#fff;} tr:nth-child(even){background:#f3f4f6;}</style>";
echo "<h2 style='color:#1a56db'>Grade 9 Pre-Technical Studies — Seeded Successfully</h2>";
echo "<p>Learning Area: <strong>" . htmlspecialchars($la['name']) . "</strong> ({$la['grade_name']})</p>";
echo "<p><strong>$totalInserted lesson rows</strong> inserted across <strong>" . ($currentWeek - ($lessonInWeek === 1 ? 1 : 0)) . " weeks</strong> at <strong>$LESSONS_PER_WEEK lessons/week</strong>.</p>";

echo "<h3>Summary</h3>";
echo "<table><tr><th>#</th><th>Strand</th><th>Sub-Strand</th><th>Lessons</th></tr>";
$n = 1;
foreach ($syllabus as [$strand, $sub_strand, $totalLessons]) {
    echo "<tr><td>$n</td><td>" . htmlspecialchars($strand) . "</td><td>" . htmlspecialchars($sub_strand) . "</td><td style='text-align:center'>$totalLessons</td></tr>";
    $n++;
}
echo "<tr><td colspan='3'><strong>Total</strong></td><td style='text-align:center'><strong>120</strong></td></tr>";
echo "</table>";
echo "<br><a href='index.php?learning_area_id=$learningAreaId' style='background:#1a56db;color:#fff;padding:10px 20px;border-radius:5px;text-decoration:none;font-weight:bold'>Open Scheme of Work &rarr;</a>";
