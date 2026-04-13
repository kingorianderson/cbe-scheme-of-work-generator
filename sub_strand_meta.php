<?php
// sub_strand_meta.php — Manage curriculum design data per sub-strand
// Usage: sub_strand_meta.php?la=LEARNING_AREA_ID
require_once 'config.php';
$pdo = getDB();

$laId = isset($_GET['la']) ? (int)$_GET['la'] : 0;
if ($laId < 1) { header('Location: curriculum.php'); exit; }

// Load learning area + grade
$laStmt = $pdo->prepare(
    "SELECT la.*, g.name AS grade_name, g.id AS grade_id
     FROM learning_areas la JOIN grades g ON g.id = la.grade_id WHERE la.id = :id"
);
$laStmt->execute([':id' => $laId]);
$la = $laStmt->fetch();
if (!$la) { header('Location: curriculum.php'); exit; }

// Flash
$flash    = '';
$flashErr = '';
if (isset($_GET['msg']) && $_GET['msg'] === 'saved') $flash = 'Curriculum design data saved.';

// Sub-strands from sub_strand_meta AND scheme_of_work (whichever has data)
// Primary: meta table (so CD-only imports show immediately even without SOW rows)
// Secondary: SOW rows that have no meta record yet
$rows = $pdo->prepare(
    "SELECT m.strand, m.sub_strand,
            m.id             AS meta_id,
            m.key_inquiry_qs,
            m.core_competencies,
            m.values_attit,
            m.pcis,
            m.links_to_other_areas,
            m.resources,
            m.assessment
     FROM sub_strand_meta m
     WHERE m.learning_area_id = :laId1

     UNION

     SELECT DISTINCT s.strand, s.sub_strand,
            NULL AS meta_id,
            NULL, NULL, NULL, NULL, NULL, NULL, NULL
     FROM scheme_of_work s
     WHERE s.learning_area_id = :laId2
       AND NOT EXISTS (
           SELECT 1 FROM sub_strand_meta mx
           WHERE mx.learning_area_id = s.learning_area_id
             AND mx.strand    = s.strand
             AND mx.sub_strand = s.sub_strand
       )
     ORDER BY strand, sub_strand"
);
$rows->execute([':laId1' => $laId, ':laId2' => $laId]);
$subStrands = $rows->fetchAll();

// Group by strand
$grouped = [];
foreach ($subStrands as $r) {
    $grouped[$r['strand']][] = $r;
}

function e(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
function fv(?string $v, string $empty = '—'): string {
    return (isset($v) && trim($v) !== '')
        ? '<span class="ss-field-value">' . htmlspecialchars(trim($v), ENT_QUOTES, 'UTF-8') . '</span>'
        : '<span class="ss-field-value empty">' . $empty . '</span>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Curriculum Design — <?= e($la['name']) ?></title>
<link rel="stylesheet" href="style.css">
<style>
  .strand-heading { background:var(--primary); color:#fff; font-size:12px; font-weight:700; letter-spacing:.06em; text-transform:uppercase; padding:8px 16px; margin:24px 0 0; border-radius:6px 6px 0 0; }
  .ss-card { border:1px solid var(--border); border-top:none; margin-bottom:0; }
  .ss-card + .ss-card { border-top:none; }
  .ss-card:last-child { border-radius:0 0 6px 6px; margin-bottom:20px; }
  .ss-header { display:flex; align-items:center; justify-content:space-between; padding:10px 16px; background:#f9fafb; border-bottom:1px solid var(--border); }
  .ss-name { font-weight:700; font-size:14px; color:#111; }
  .ss-body { display:grid; grid-template-columns:1fr 1fr; gap:0; }
  .ss-field { padding:11px 16px; border-bottom:1px solid #f0f1f3; }
  .ss-field:nth-last-child(-n+2) { border-bottom:none; }
  .ss-field-label { font-size:10px; font-weight:700; letter-spacing:.07em; text-transform:uppercase; color:var(--primary); margin-bottom:4px; }
  .ss-field-value { font-size:13px; color:#374151; white-space:pre-wrap; line-height:1.55; }
  .ss-field-value.empty { color:#9ca3af; font-style:italic; }
  .ss-field.full-width { grid-column:1 / -1; }
  @media(max-width:640px){ .ss-body{ grid-template-columns:1fr; } .ss-field:last-child{ border-bottom:none; } }
</style>
</head>
<body>
<div class="page-wrap">
  <nav class="top-nav">
    <span class="nav-brand">CBC Scheme of Work</span>
    <ol class="breadcrumb">
      <li><a href="curriculum.php">Curriculum</a></li>
      <li><a href="curriculum.php?grade_id=<?= (int)$la['grade_id'] ?>"><?= e($la['grade_name']) ?></a></li>
      <li><a href="index.php?learning_area_id=<?= $laId ?>"><?= e($la['name']) ?></a></li>
      <li class="active">Curriculum Design</li>
    </ol>
  </nav>

  <header>
    <div>
      <h1>Curriculum Design</h1>
      <small style="color:var(--muted)"><?= e($la['grade_name']) ?> &bull; <?= e($la['name']) ?></small>
    </div>
    <a href="index.php?learning_area_id=<?= $laId ?>" class="btn btn-outline">&larr; Back to SOW</a>
  </header>

  <p style="font-size:13px;color:var(--muted);margin-bottom:16px">
    Curriculum design data per sub-strand. Click <strong>Edit</strong> on any sub-strand to update its content.
  </p>

  <?php if ($flash): ?><div class="flash"><?= e($flash) ?></div><?php endif; ?>

  <?php if (empty($grouped)): ?>
    <div style="padding:40px;text-align:center;color:var(--muted)">
      No lessons found for this learning area. <a href="index.php?learning_area_id=<?= $laId ?>">Add SOW lessons first.</a>
    </div>
  <?php else: ?>
    <?php foreach ($grouped as $strand => $items): ?>
      <div class="strand-heading"><?= e($strand) ?></div>
      <?php foreach ($items as $r): ?>
        <?php
          $editUrl = 'edit_ssm.php?la=' . $laId
                   . '&strand='     . urlencode($r['strand'])
                   . '&sub_strand=' . urlencode($r['sub_strand']);
        ?>
        <div class="ss-card">
          <div class="ss-header">
            <span class="ss-name"><?= e($r['sub_strand']) ?></span>
            <a href="<?= $editUrl ?>" class="btn btn-outline-muted" style="font-size:12px;padding:4px 14px">Edit</a>
          </div>
          <div class="ss-body">
            <div class="ss-field">
              <div class="ss-field-label">Key Inquiry Question(s)</div>
              <?= fv($r['key_inquiry_qs']) ?>
            </div>
            <div class="ss-field">
              <div class="ss-field-label">Core Competencies</div>
              <?= fv($r['core_competencies']) ?>
            </div>
            <div class="ss-field">
              <div class="ss-field-label">Values</div>
              <?= fv($r['values_attit']) ?>
            </div>
            <div class="ss-field">
              <div class="ss-field-label">PCIs</div>
              <?= fv($r['pcis']) ?>
            </div>
            <div class="ss-field">
              <div class="ss-field-label">Links to Other Learning Areas</div>
              <?= fv($r['links_to_other_areas']) ?>
            </div>
            <div class="ss-field">
              <div class="ss-field-label">Learning Resources</div>
              <?= fv($r['resources']) ?>
            </div>
            <div class="ss-field full-width">
              <div class="ss-field-label">Assessment</div>
              <?= fv($r['assessment']) ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endforeach; ?>
  <?php endif; ?>

</div>
</body>
</html>
