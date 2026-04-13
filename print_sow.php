<?php
// print_sow.php — Printable / downloadable Scheme of Work
// Usage: print_sow.php?learning_area_id=N
require_once 'config.php';
$pdo = getDB();

// Check AI enabled
$aiEnabled = false;
try {
    $st = $pdo->query("SELECT setting_value FROM app_settings WHERE setting_key='ai_enabled'");
    $aiEnabled = ($st && $st->fetchColumn() === '1');
} catch (Exception $e) {}

$laId = isset($_GET['learning_area_id']) ? (int)$_GET['learning_area_id'] : 0;
if ($laId < 1) { header('Location: curriculum.php'); exit; }

$laStmt = $pdo->prepare(
    "SELECT la.*, g.name AS grade_name FROM learning_areas la
     JOIN grades g ON g.id = la.grade_id WHERE la.id = :id"
);
$laStmt->execute([':id' => $laId]);
$la = $laStmt->fetch();
if (!$la) { header('Location: curriculum.php'); exit; }

// Fetch SOW rows, LEFT JOIN sub_strand_meta to get KIQ per sub-strand
$filterTerm = isset($_GET['term']) ? (int)$_GET['term'] : 0;
$rows = $pdo->prepare(
    "SELECT s.week, s.lesson, s.strand, s.sub_strand,
            s.slo_sow, s.le_sow, s.remarks,
            m.key_inquiry_qs,
            COALESCE(NULLIF(TRIM(s.resources), ''),   m.resources)     AS resources,
            COALESCE(NULLIF(TRIM(s.assessment), ''),  m.assessment)   AS assessment
     FROM scheme_of_work s
     LEFT JOIN sub_strand_meta m
       ON  m.learning_area_id = s.learning_area_id
       AND m.strand            = s.strand
       AND m.sub_strand        = s.sub_strand
     WHERE s.learning_area_id = :laId"
     . ($filterTerm > 0 ? ' AND s.term = :term' : '')
     . ' ORDER BY s.week, s.lesson'
);
$params = [':laId' => $laId];
if ($filterTerm > 0) $params[':term'] = $filterTerm;
$rows->execute($params);
$rows = $rows->fetchAll();

// ── Build display-number maps when a term is selected ─────────────────────
// Weeks renumber from 1; lessons renumber from 1 within each display-week.
$dispWeek   = [];  // rowIndex => displayWeek
$dispLesson = [];  // rowIndex => displayLesson
if ($filterTerm > 0 && !empty($rows)) {
    $displayW    = 0;
    $prevOrigW   = null;
    $lessonInWk  = 0;
    foreach ($rows as $i => $r) {
        $origW = (int)$r['week'];
        if ($origW !== $prevOrigW) {
            $displayW++;
            $lessonInWk = 0;
            $prevOrigW  = $origW;
        }
        $lessonInWk++;
        $dispWeek[$i]   = $displayW;
        $dispLesson[$i] = $lessonInWk;
    }
}

function e(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }

// Render text with '* ' lines as <li> bullets
function bullets(?string $v): string {
    $v = trim($v ?? '');
    if ($v === '') return '';
    $lines  = explode("\n", $v);
    $out    = '';
    $inList = false;
    foreach ($lines as $line) {
        $line = rtrim($line);
        if (str_starts_with($line, '* ')) {
            if (!$inList) { $out .= '<ul style="margin:2px 0 2px 12px;padding:0">'; $inList = true; }
            $out .= '<li>' . e(ltrim(substr($line, 2))) . '</li>';
        } else {
            if ($inList) { $out .= '</ul>'; $inList = false; }
            if ($line !== '') $out .= '<span style="display:block">' . e($line) . '</span>';
        }
    }
    if ($inList) $out .= '</ul>';
    return $out;
}
function cell(?string $v): string {
    return bullets($v);
}
function withSlo(?string $v): string {
    $v = trim($v ?? '');
    if ($v === '') return '';
    if (stripos($v, 'by the end of the lesson') === 0) return $v;
    return "By the end of the lesson, the learner should be able to:\n" . $v;
}
function withLe(?string $v): string {
    $v = trim($v ?? '');
    if ($v === '') return '';
    if (stripos($v, 'learner is guided to') === 0) return $v;
    return "Learner is guided to:\n" . $v;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Scheme of Work — <?= e($la['name']) ?> (<?= e($la['grade_name']) ?>)</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    font-family: Arial, Helvetica, sans-serif;
    font-size: 10pt;
    color: #000;
    background: #fff;
    padding: 16px;
  }

  /* ── Screen-only toolbar ───────────────────────────────────────────── */
  .toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 20px;
    padding: 12px 16px;
    background: #f3f4f6;
    border-radius: 6px;
  }
  .toolbar h2 { font-size: 14pt; color: #1a56db; }
  .toolbar small { color: #6b7280; font-size: 10pt; }
  .btn-print {
    background: #1a56db; color: #fff; border: none; padding: 9px 22px;
    border-radius: 6px; font-size: 11pt; font-weight: 700; cursor: pointer;
    text-decoration: none; display: inline-block;
  }
  .btn-back {
    background: #fff; color: #374151; border: 1px solid #d1d5db;
    padding: 9px 18px; border-radius: 6px; font-size: 11pt; cursor: pointer;
    text-decoration: none; display: inline-block;
  }

  /* ── Document header (visible on print) ───────────────────────────── */
  .doc-header {
    text-align: center;
    margin-bottom: 14px;
  }
  .doc-header h1 { font-size: 13pt; text-transform: uppercase; letter-spacing: .04em; }
  .doc-header p  { font-size: 10pt; color: #444; margin-top: 3px; }

  /* ── Table ────────────────────────────────────────────────────────── */
  .sow-table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
    font-size: 8.5pt;
    page-break-inside: auto;
  }

  .sow-table th {
    background: #1a56db;
    color: #fff;
    padding: 6px 5px;
    text-align: center;
    border: 1px solid #1245b0;
    font-size: 8pt;
    line-height: 1.3;
  }

  .sow-table td {
    padding: 5px 5px;
    border: 1px solid #c0c0c0;
    vertical-align: top;
    line-height: 1.45;
  }

  .sow-table tr:nth-child(even) td { background: #f7f8ff; }
  .sow-table tr { page-break-inside: avoid; }

  /* Column widths */
  .col-week   { width: 4%; }
  .col-lesson { width: 4%; }
  .col-strand { width: 9%; }
  .col-ss     { width: 9%; }
  .col-slo    { width: 13%; }
  .col-le     { width: 15%; }
  .col-kiq    { width: 12%; }
  .col-res    { width: 13%; }
  .col-assess { width: 11%; }
  .col-rem    { width: 10%; }

  .center { text-align: center; }

  /* ── Print ────────────────────────────────────────────────────────── */
  @media print {
    @page { size: A4 landscape; margin: 12mm; }
    body   { padding: 0; font-size: 8pt; }
    .toolbar { display: none !important; }
    .sow-table th { background: #1a56db !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .sow-table tr:nth-child(even) td { background: #f7f8ff !important; print-color-adjust: exact; }
  }

  /* ── Hide Week & Lesson numbers only (columns stay, just the digits disappear) */
  body.hide-wk-ls .wkls-num { visibility: hidden; }

  /* Toggle button */
  .btn-toggle-wk {
    background: #fff; color: #374151;
    border: 1px solid #d1d5db;
    padding: 9px 16px; border-radius: 6px;
    font-size: 10pt; font-weight: 600; cursor: pointer;
    transition: all .15s; user-select: none;
  }
  .btn-toggle-wk.active {
    background: #fef3c7; color: #92400e;
    border-color: #fcd34d;
  }
</style>
</head>
<body>

<!-- Screen toolbar (hidden when printed) -->
<div class="toolbar">
  <div>
    <h2><?= e($la['name']) ?></h2>
    <small><?= e($la['grade_name']) ?> &bull; <?= count($rows) ?> lessons
      <?php if ($filterTerm > 0): ?>
        &bull; <span style="background:<?= ['','#dbeafe','#d1fae5','#fef3c7'][$filterTerm] ?>;color:<?= ['','#1d4ed8','#065f46','#92400e'][$filterTerm] ?>;padding:2px 10px;border-radius:12px;font-weight:700">Term <?= $filterTerm ?></span>
      <?php endif; ?>
    </small>
  </div>
  <div style="display:flex;gap:8px;flex-wrap:wrap">
    <a href="index.php?learning_area_id=<?= $laId ?>" class="btn-back">&larr; Back to SOW</a>
    <!-- Term filter pills -->
    <a href="print_sow.php?learning_area_id=<?= $laId ?>" class="btn-back" style="<?= $filterTerm===0?'background:#e5e7eb;font-weight:700':'' ?>">All</a>
    <a href="print_sow.php?learning_area_id=<?= $laId ?>&term=1" class="btn-back" style="<?= $filterTerm===1?'background:#dbeafe;color:#1d4ed8;font-weight:700':'' ?>">Term 1</a>
    <a href="print_sow.php?learning_area_id=<?= $laId ?>&term=2" class="btn-back" style="<?= $filterTerm===2?'background:#d1fae5;color:#065f46;font-weight:700':'' ?>">Term 2</a>
    <a href="print_sow.php?learning_area_id=<?= $laId ?>&term=3" class="btn-back" style="<?= $filterTerm===3?'background:#fef3c7;color:#92400e;font-weight:700':'' ?>">Term 3</a>
    <?php if ($aiEnabled): ?>
    <a href="ai_bulk_lessonplans.php?learning_area_id=<?= $laId ?><?= $filterTerm>0?'&term='.$filterTerm:'' ?>" class="btn-print"
       style="background:#7c3aed;text-decoration:none"
       onclick="return confirm('Generate AI lesson plans for all lessons in this scheme?\n\nThis will create or update lesson plan records in the database.')">&#9889; Generate All Lesson Plans</a>
    <?php endif; ?>
    <a href="print_lessonplans.php?learning_area_id=<?= $laId ?><?= $filterTerm>0?'&term='.$filterTerm:'' ?>" class="btn-print"
       style="background:#059669;text-decoration:none">&#128196; Print Lesson Plans</a>
    <button onclick="window.print()" class="btn-print">&#128438; Print / Save as PDF</button>
    <button class="btn-toggle-wk" id="toggleWkBtn" onclick="toggleWeekLesson()" title="Show/hide Week and Lesson columns">&#128196; Hide Wk/Ls</button>
  </div>
</div>

<!-- Document heading (shows on print) -->
<div class="doc-header">
  <h1>Scheme of Work — <?= e($la['name']) ?><?= $filterTerm>0 ? ' | Term '.$filterTerm : '' ?></h1>
  <p><?= e($la['grade_name']) ?></p>
</div>

<?php if (empty($rows)): ?>
  <p style="text-align:center;padding:40px;color:#6b7280">No SOW records found for this learning area.</p>
<?php else: ?>
<table class="sow-table">
  <colgroup>
    <col class="col-week">
    <col class="col-lesson">
    <col class="col-strand">
    <col class="col-ss">
    <col class="col-slo">
    <col class="col-le">
    <col class="col-kiq">
    <col class="col-res">
    <col class="col-assess">
    <col class="col-rem">
  </colgroup>
  <thead>
    <tr>
      <th>Week</th>
      <th>Lesson</th>
      <th>Strand</th>
      <th>Sub-Strand</th>
      <th>Specific Learning Outcome</th>
      <th>Learning Experience</th>
      <th>Key Inquiry Question(s)</th>
      <th>Learning Resources</th>
      <th>Assessment</th>
      <th>Remarks</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($rows as $i => $r): ?>
    <tr>
      <td class="center"><span class="wkls-num"><?= $filterTerm > 0 ? $dispWeek[$i]   : (int)$r['week'] ?></span></td>
      <td class="center"><span class="wkls-num"><?= $filterTerm > 0 ? $dispLesson[$i] : (int)$r['lesson'] ?></span></td>
      <td><?= cell($r['strand']) ?></td>
      <td><?= cell($r['sub_strand']) ?></td>
      <td><?= bullets(withSlo($r['slo_sow'])) ?></td>
      <td><?= bullets(withLe($r['le_sow'])) ?></td>
      <td><?= bullets($r['key_inquiry_qs']) ?></td>
      <td><?= bullets($r['resources']) ?></td>
      <td><?= bullets($r['assessment']) ?></td>
      <td><?= cell($r['remarks']) ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>

</body>
<script>
function toggleWeekLesson() {
    const body = document.body;
    const btn  = document.getElementById('toggleWkBtn');
    const hide = body.classList.toggle('hide-wk-ls');
    btn.classList.toggle('active', hide);
    btn.textContent = hide ? '\u{1F4C4} Show Wk/Ls' : '\u{1F4C4} Hide Wk/Ls';
    try { localStorage.setItem('sow_hide_wkls', hide ? '1' : '0'); } catch(e){}
}
// Restore state from last visit
try {
    if (localStorage.getItem('sow_hide_wkls') === '1') {
        document.body.classList.add('hide-wk-ls');
        const btn = document.getElementById('toggleWkBtn');
        btn.classList.add('active');
        btn.textContent = '\u{1F4C4} Show Wk/Ls';
    }
} catch(e){}
</script>
</html>
