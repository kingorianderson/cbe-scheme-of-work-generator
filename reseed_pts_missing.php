<?php
// reseed_pts_missing.php — Inserts only MISSING lessons for the PTS learning area.
// Does NOT delete or modify any existing records.
// Usage: http://localhost/SCHEME/reseed_pts_missing.php?la=N
require_once 'config.php';
$pdo = getDB();

$learningAreaId = isset($_GET['la']) ? (int)$_GET['la'] : 0;
if ($learningAreaId < 1) {
    echo "<p style='color:red'>Provide ?la=N (the learning area ID). <a href='curriculum.php'>Go to Curriculum</a></p>"; exit;
}

$laStmt = $pdo->prepare("SELECT la.*, g.name AS grade_name FROM learning_areas la JOIN grades g ON g.id = la.grade_id WHERE la.id = :id");
$laStmt->execute([':id' => $learningAreaId]);
$la = $laStmt->fetch();
if (!$la) { echo "<p style='color:red'>Learning area ID $learningAreaId not found.</p>"; exit; }

$LESSONS_PER_WEEK = 4;

// ── Full lesson list (same order as seed_pts.php) ─────────────────────────────
$lessons = [
  // ── 1.1 Safety on Raised Platforms ──────────────────────────────────────────
  ['strand'=>'1.0 Foundations of Pre-Technical Studies','sub_strand'=>'1.1 Safety on Raised Platforms',
   'slo_cd'=>'a) identify types of raised platforms used in performing tasks',
   'slo_sow'=>"Lesson 1:\na) Define the term \"raised platforms.\"\nb) Identify various types of raised platforms used in performing tasks.\nc) Describe the characteristics of different raised platforms.",
   'le_cd'=>"• walk around the school to explore types of raised platforms (ladders, trestles, steps, stands, mobile raised platforms, work benches, ramps).\n• brainstorm on the types of raised platforms used in day-to-day life.",
   'le_sow'=>"• Define what is raised platforms thro question and answer.\n• Explore types of raised platforms, in groups, note down findings and share with the class.\n• Brainstorm on the types of raised platforms used in day-to-day life, in groups, and share findings with the rest of the class.",
   'key_inquiry'=>'What are raised platforms and where are they used?'],
  ['strand'=>'1.0 Foundations of Pre-Technical Studies','sub_strand'=>'1.1 Safety on Raised Platforms',
   'slo_cd'=>'b) describe risks associated with working on raised platforms',
   'slo_sow'=>"Lesson 2:\na) Identify potential risks associated with working on raised platforms.\nb) Describe specific hazards encountered when working at different heights.",
   'le_cd'=>'• use print or digital media to search for information on risks associated with working on raised platforms.',
   'le_sow'=>'• Search for information on risk associated with working on raised platforms, in groups.  Write down findings and share them in class.',
   'key_inquiry'=>'What risks are associated with working on raised platforms?'],
  ['strand'=>'1.0 Foundations of Pre-Technical Studies','sub_strand'=>'1.1 Safety on Raised Platforms',
   'slo_cd'=>'c) observe safety when working on raised platforms',
   'slo_sow'=>"Lesson 3:\na) Discuss effective methods of minimizing risks when working on raised platforms.\nb) Propose safety measures for preventing falls and injuries.",
   'le_cd'=>'• discuss ways of minimising risks related to working on raised platforms.',
   'le_sow'=>'• Discuss in groups, ways of minimising risks when working on raised platforms.',
   'key_inquiry'=>'How can we minimise risks when working on raised platforms?'],
  ['strand'=>'1.0 Foundations of Pre-Technical Studies','sub_strand'=>'1.1 Safety on Raised Platforms',
   'slo_cd'=>'c) observe safety when working on raised platforms',
   'slo_sow'=>"Lesson 4:\na) Demonstrate safe practices for using raised platforms through role play.\nb) Critique safety behaviors observed during the role play session.",
   'le_cd'=>'• role-play safety practices for working on raised platforms.',
   'le_sow'=>'• Role play safety practices for working on raised platforms and share what they have learnt from each other in class.',
   'key_inquiry'=>'How do we demonstrate safe practices on raised platforms?'],
  ['strand'=>'1.0 Foundations of Pre-Technical Studies','sub_strand'=>'1.1 Safety on Raised Platforms',
   'slo_cd'=>'d) appreciate the need for observing safety while working on raised platforms',
   'slo_sow'=>"Lesson 5:\na) Explain the significance of observing safety protocols while working at heights.\nb) Justify the use of Personal Protective Equipment (PPE) on raised platforms.",
   'le_cd'=>'• discuss the importance of observing safety when working on raised platforms.',
   'le_sow'=>'• Discuss in groups, the need for observing safety while working on raised platforms and share findings in class.',
   'key_inquiry'=>'Why is it important to observe safety on raised platforms?'],
  ['strand'=>'1.0 Foundations of Pre-Technical Studies','sub_strand'=>'1.1 Safety on Raised Platforms',
   'slo_cd'=>'d) appreciate the need for observing safety while working on raised platforms',
   'slo_sow'=>"Lesson 6:\na) Evaluate personal understanding of safety on raised platforms through an assessment.",
   'le_cd'=>'• visit the locality to observe safety precautions taken when working on raised platforms.',
   'le_sow'=>'• Work individually on assessment exercise on safety on raised platforms.',
   'key_inquiry'=>'How well do I understand safety on raised platforms?'],
];

// ── Build a map of already-existing (week, lesson) pairs ─────────────────────
$existing = $pdo->prepare("SELECT week, lesson FROM scheme_of_work WHERE learning_area_id = :la ORDER BY week, lesson");
$existing->execute([':la' => $learningAreaId]);
$existingSet = [];
foreach ($existing->fetchAll() as $row) {
    $existingSet[$row['week'] . '_' . $row['lesson']] = true;
}

// ── Also find which sub_strands already have rows ─────────────────────────────
$presentSS = $pdo->prepare("SELECT DISTINCT sub_strand FROM scheme_of_work WHERE learning_area_id = :la");
$presentSS->execute([':la' => $learningAreaId]);
$presentSubStrands = array_flip($presentSS->fetchAll(PDO::FETCH_COLUMN));

// ── Assign week/lesson numbers to every lesson in the full sequence ───────────
// Then insert only those whose sub_strand is not yet present
$sql = 'INSERT INTO scheme_of_work
            (learning_area_id, week, lesson, strand, sub_strand,
             slo_cd, slo_sow, le_cd, le_sow, key_inquiry, resources, assessment, remarks)
        VALUES
            (:laid, :week, :lesson, :strand, :sub_strand,
             :slo_cd, :slo_sow, :le_cd, :le_sow, :key_inquiry, \'\', \'\', \'\')';
$stmt = $pdo->prepare($sql);

$currentWeek  = 1;
$lessonInWeek = 1;
$inserted     = 0;
$skipped      = 0;
$log          = [];

foreach ($lessons as $l) {
    $w = $currentWeek;
    $li = $lessonInWeek;

    $ssPresent = isset($presentSubStrands[$l['sub_strand']]);

    if ($ssPresent) {
        $log[] = ['skip', "Week $w L$li — {$l['sub_strand']} — sub-strand already has data, skipped"];
        $skipped++;
    } else {
        $stmt->execute([
            ':laid'        => $learningAreaId,
            ':week'        => $w,
            ':lesson'      => $li,
            ':strand'      => $l['strand'],
            ':sub_strand'  => $l['sub_strand'],
            ':slo_cd'      => $l['slo_cd'],
            ':slo_sow'     => $l['slo_sow'],
            ':le_cd'       => $l['le_cd'],
            ':le_sow'      => $l['le_sow'],
            ':key_inquiry' => $l['key_inquiry'],
        ]);
        $log[] = ['ok', "Week $w L$li — Inserted: {$l['sub_strand']}"];
        $inserted++;
    }

    $lessonInWeek++;
    if ($lessonInWeek > $LESSONS_PER_WEEK) { $lessonInWeek = 1; $currentWeek++; }
}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">
<title>Reseed Missing PTS Lessons</title>
<style>
body{font-family:Segoe UI,sans-serif;padding:30px;max-width:760px}
.ok{color:#065f46} .skip{color:#92400e} .err{color:#991b1b}
li{margin-bottom:4px;font-size:13px}
.summary{background:#f0fdf4;border:1px solid #bbf7d0;border-radius:6px;padding:12px 16px;margin-bottom:16px}
</style>
</head><body>
<h2>Reseed Missing PTS Lessons</h2>
<p style="color:#374151;font-size:13px">Learning area: <strong><?= htmlspecialchars($la['name']) ?></strong> (<?= htmlspecialchars($la['grade_name']) ?>)</p>
<div class="summary">
  <strong><?= $inserted ?> lessons inserted</strong> &nbsp;|&nbsp; <?= $skipped ?> skipped (sub-strand already had data)
</div>
<ul>
<?php foreach ($log as [$type, $msg]): ?>
  <li class="<?= $type ?>"><?= htmlspecialchars($msg) ?></li>
<?php endforeach; ?>
</ul>
<br>
<a href="index.php?learning_area_id=<?= $learningAreaId ?>" style="background:#1a56db;color:#fff;padding:10px 20px;border-radius:5px;text-decoration:none;font-weight:bold">View SOW &rarr;</a>
</body></html>
