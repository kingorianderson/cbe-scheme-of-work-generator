<?php
// insert_pts_11_lessons1to5.php
// Inserts the 5 missing 1.1 Safety on Raised Platforms lessons at their
// original positions: Week 1 L1-L4 and Week 2 L1.
// The existing Lesson 6 record stays at Week 2 L2 untouched.
// Usage: http://localhost/SCHEME/insert_pts_11_lessons1to5.php?la=N
require_once 'config.php';
$pdo = getDB();

$learningAreaId = isset($_GET['la']) ? (int)$_GET['la'] : 0;
if ($learningAreaId < 1) {
    echo "<p style='color:red'>Provide ?la=N (your Pre-Technical Studies learning area ID).</p>"; exit;
}

$laStmt = $pdo->prepare("SELECT la.*, g.name AS grade_name FROM learning_areas la JOIN grades g ON g.id = la.grade_id WHERE la.id = :id");
$laStmt->execute([':id' => $learningAreaId]);
$la = $laStmt->fetch();
if (!$la) { echo "<p style='color:red'>Learning area ID $learningAreaId not found.</p>"; exit; }

// The 5 missing lessons with their exact target week/lesson positions
$toInsert = [
  ['week'=>1,'lesson'=>1,
   'slo_cd'   =>'a) identify types of raised platforms used in performing tasks',
   'slo_sow'  =>"Lesson 1:\na) Define the term \"raised platforms.\"\nb) Identify various types of raised platforms used in performing tasks.\nc) Describe the characteristics of different raised platforms.",
   'le_cd'    =>"• walk around the school to explore types of raised platforms (ladders, trestles, steps, stands, mobile raised platforms, work benches, ramps).\n• brainstorm on the types of raised platforms used in day-to-day life.",
   'le_sow'   =>"• Define what is raised platforms thro question and answer.\n• Explore types of raised platforms, in groups, note down findings and share with the class.\n• Brainstorm on the types of raised platforms used in day-to-day life, in groups, and share findings with the rest of the class.",
   'key_inquiry'=>'What are raised platforms and where are they used?'],

  ['week'=>1,'lesson'=>2,
   'slo_cd'   =>'b) describe risks associated with working on raised platforms',
   'slo_sow'  =>"Lesson 2:\na) Identify potential risks associated with working on raised platforms.\nb) Describe specific hazards encountered when working at different heights.",
   'le_cd'    =>'• use print or digital media to search for information on risks associated with working on raised platforms.',
   'le_sow'   =>'• Search for information on risk associated with working on raised platforms, in groups.  Write down findings and share them in class.',
   'key_inquiry'=>'What risks are associated with working on raised platforms?'],

  ['week'=>1,'lesson'=>3,
   'slo_cd'   =>'c) observe safety when working on raised platforms',
   'slo_sow'  =>"Lesson 3:\na) Discuss effective methods of minimizing risks when working on raised platforms.\nb) Propose safety measures for preventing falls and injuries.",
   'le_cd'    =>'• discuss ways of minimising risks related to working on raised platforms.',
   'le_sow'   =>'• Discuss in groups, ways of minimising risks when working on raised platforms.',
   'key_inquiry'=>'How can we minimise risks when working on raised platforms?'],

  ['week'=>1,'lesson'=>4,
   'slo_cd'   =>'c) observe safety when working on raised platforms',
   'slo_sow'  =>"Lesson 4:\na) Demonstrate safe practices for using raised platforms through role play.\nb) Critique safety behaviors observed during the role play session.",
   'le_cd'    =>'• role-play safety practices for working on raised platforms.',
   'le_sow'   =>'• Role play safety practices for working on raised platforms and share what they have learnt from each other in class.',
   'key_inquiry'=>'How do we demonstrate safe practices on raised platforms?'],

  ['week'=>2,'lesson'=>1,
   'slo_cd'   =>'d) appreciate the need for observing safety while working on raised platforms',
   'slo_sow'  =>"Lesson 5:\na) Explain the significance of observing safety protocols while working at heights.\nb) Justify the use of Personal Protective Equipment (PPE) on raised platforms.",
   'le_cd'    =>'• discuss the importance of observing safety when working on raised platforms.',
   'le_sow'   =>'• Discuss in groups, the need for observing safety while working on raised platforms and share findings in class.',
   'key_inquiry'=>'Why is it important to observe safety on raised platforms?'],
];

$sql = 'INSERT INTO scheme_of_work
            (learning_area_id, week, lesson, strand, sub_strand,
             slo_cd, slo_sow, le_cd, le_sow, key_inquiry, resources, assessment, remarks)
        VALUES
            (:laid, :week, :lesson,
             \'1.0 Foundations of Pre-Technical Studies\',
             \'1.1 Safety on Raised Platforms\',
             :slo_cd, :slo_sow, :le_cd, :le_sow, :key_inquiry,
             \'\', \'\', \'\')';
$stmt = $pdo->prepare($sql);

$log = []; $inserted = 0; $skipped = 0;

foreach ($toInsert as $r) {
    // Check if this week/lesson slot is already occupied
    $chk = $pdo->prepare("SELECT id, sub_strand FROM scheme_of_work WHERE learning_area_id=:la AND week=:w AND lesson=:l");
    $chk->execute([':la'=>$learningAreaId,':w'=>$r['week'],':l'=>$r['lesson']]);
    $existing = $chk->fetch();
    if ($existing) {
        $log[] = ['skip', "W{$r['week']} L{$r['lesson']} — SKIPPED: slot occupied by \"{$existing['sub_strand']}\" (id={$existing['id']})"];
        $skipped++;
    } else {
        $stmt->execute([
            ':laid'       => $learningAreaId,
            ':week'       => $r['week'],
            ':lesson'     => $r['lesson'],
            ':slo_cd'     => $r['slo_cd'],
            ':slo_sow'    => $r['slo_sow'],
            ':le_cd'      => $r['le_cd'],
            ':le_sow'     => $r['le_sow'],
            ':key_inquiry'=> $r['key_inquiry'],
        ]);
        $log[] = ['ok', "W{$r['week']} L{$r['lesson']} — Inserted: {$r['slo_cd']}"];
        $inserted++;
    }
}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">
<title>Insert PTS 1.1 Lessons 1–5</title>
<style>body{font-family:Segoe UI,sans-serif;padding:30px;max-width:760px}
.ok{color:#065f46}.skip{color:#92400e}
li{margin-bottom:5px;font-size:13px}
.summary{background:#f0fdf4;border:1px solid #bbf7d0;border-radius:6px;padding:12px 16px;margin-bottom:16px}</style>
</head><body>
<h2>Insert PTS 1.1 Safety on Raised Platforms — Lessons 1–5</h2>
<p style="color:#374151;font-size:13px">Learning area: <strong><?= htmlspecialchars($la['name']) ?></strong> (<?= htmlspecialchars($la['grade_name']) ?>)</p>
<div class="summary">
  <strong><?= $inserted ?> inserted</strong> &nbsp;|&nbsp; <?= $skipped ?> skipped (slot already occupied)
</div>
<ul>
<?php foreach ($log as [$t,$m]): ?>
  <li class="<?= $t ?>"><?= htmlspecialchars($m) ?></li>
<?php endforeach; ?>
</ul>
<br>
<a href="index.php?learning_area_id=<?= $learningAreaId ?>" style="background:#1a56db;color:#fff;padding:10px 20px;border-radius:5px;text-decoration:none;font-weight:bold">View SOW &rarr;</a>
</body></html>
