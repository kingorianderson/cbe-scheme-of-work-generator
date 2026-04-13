<?php
// print_lessonplans.php — Print all lesson plans for a learning area (one per A4 page)
// Usage: print_lessonplans.php?learning_area_id=N  (optionally &week=N to filter)
require_once 'config.php';
$pdo = getDB();

$laId    = isset($_GET['learning_area_id']) ? (int)$_GET['learning_area_id'] : 0;
$filterW = isset($_GET['week']) ? (int)$_GET['week'] : 0;
$filterTerm = isset($_GET['term']) ? (int)$_GET['term'] : 0;

if ($laId < 1) { header('Location: curriculum.php'); exit; }

$laStmt = $pdo->prepare(
    "SELECT la.*, g.name AS grade_name, g.lesson_duration
     FROM learning_areas la
     JOIN grades g ON g.id = la.grade_id
     WHERE la.id = :id"
);
$laStmt->execute([':id' => $laId]);
$la = $laStmt->fetch();
if (!$la) { header('Location: curriculum.php'); exit; }

// Load app settings
$appSettings = [];
try {
    $rows = $pdo->query("SELECT setting_key, setting_value FROM app_settings")->fetchAll();
    foreach ($rows as $r) $appSettings[$r['setting_key']] = $r['setting_value'];
} catch (Exception $e) {}

// Fetch all SOW rows + left-join lesson_plans
$sql = "SELECT s.id AS sow_id, s.week, s.lesson, s.strand, s.sub_strand,
               s.slo_sow, s.le_sow, s.key_inquiry, s.resources AS sow_resources,
               lp.id AS lp_id,
               lp.school_name, lp.teacher_name, lp.date_taught, lp.duration, lp.num_learners,
               lp.slo1, lp.slo2, lp.slo3,
               lp.key_inquiry_question,
               lp.core_competencies, lp.pcis, lp.values_attit, lp.resources,
               lp.introduction,
               lp.step1, lp.step2, lp.step3,
               lp.conclusion, lp.reflection, lp.extended_activity,
               m.core_competencies  AS meta_cc,
               m.pcis               AS meta_pcis,
               m.values_attit       AS meta_values,
               m.key_inquiry_qs     AS meta_kiq,
               m.resources          AS meta_res
        FROM scheme_of_work s
        LEFT JOIN lesson_plans lp ON lp.sow_id = s.id
        LEFT JOIN sub_strand_meta m
               ON m.learning_area_id = s.learning_area_id
              AND m.strand            = s.strand
              AND m.sub_strand        = s.sub_strand
        WHERE s.learning_area_id = :la";
$params = [':la' => $laId];
if ($filterW > 0)    { $sql .= " AND s.week = :week"; $params[':week'] = $filterW; }
if ($filterTerm > 0) { $sql .= " AND s.term = :term"; $params[':term'] = $filterTerm; }
$sql .= " ORDER BY s.week, s.lesson";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$lessons = $stmt->fetchAll();

// ── Build display-number maps when a term is selected ─────────────────────
$lpDispWeek   = [];  // rowIndex => displayWeek
$lpDispLesson = [];  // rowIndex => displayLesson
if ($filterTerm > 0 && !empty($lessons)) {
    $displayW   = 0;
    $prevOrigW  = null;
    $lessonInWk = 0;
    foreach ($lessons as $i => $r) {
        $origW = (int)$r['week'];
        if ($origW !== $prevOrigW) {
            $displayW++;
            $lessonInWk = 0;
            $prevOrigW  = $origW;
        }
        $lessonInWk++;
        $lpDispWeek[$i]   = $displayW;
        $lpDispLesson[$i] = $lessonInWk;
    }
}

// Distinct weeks (for toolbar week-filter pills)
$weeks = array_values(array_unique(array_column($lessons, 'week')));

function e(?string $v): string { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }

// Render filled content or dotted blank lines
function pf(?string $v, int $lines = 2): string {
    $v = trim($v ?? '');
    if ($v !== '') {
        $html = ''; $inList = false;
        foreach (explode("\n", $v) as $line) {
            $line = rtrim($line);
            if (str_starts_with($line, '* ')) {
                if (!$inList) { $html .= '<ul class="lp-list">'; $inList = true; }
                $html .= '<li>' . e(ltrim(substr($line, 2))) . '</li>';
            } else {
                if ($inList) { $html .= '</ul>'; $inList = false; }
                if ($line !== '') $html .= '<p class="lp-text">' . e($line) . '</p>';
            }
        }
        if ($inList) $html .= '</ul>';
        return '<div class="lp-fill">' . $html . '</div>';
    }
    $d = '';
    for ($i = 0; $i < $lines; $i++) $d .= '<div class="dotline"></div>';
    return $d;
}

// Parse bullet text into array of plain items
function parseBullets(?string $v): array {
    $v = preg_replace('/^By the end of the lesson[^:]*:\s*/si', '', trim($v ?? ''));
    $v = preg_replace('/^Learner is guided to:\s*/si', '', $v);
    $items = [];
    foreach (explode("\n", $v) as $line) {
        $line = trim($line);
        if ($line === '') continue;
        $items[] = str_starts_with($line, '* ') ? ltrim(substr($line, 2)) : $line;
    }
    return $items;
}

// Coalesce helper: first non-empty string wins
function coalesce(string ...$vals): string {
    foreach ($vals as $v) { if (trim($v) !== '') return trim($v); }
    return '';
}

// Build effective plan data for a row (lesson_plans row + fallbacks)
function buildPlan(array $row, array $appSettings, string $lessonDuration): array {
    $sloItems = parseBullets($row['slo_sow'] ?? '');
    $leItems  = parseBullets($row['le_sow']  ?? '');
    return [
        'school_name'          => coalesce($row['school_name']    ?? '', $appSettings['school_name']  ?? ''),
        'teacher_name'         => coalesce($row['teacher_name']   ?? '', $appSettings['teacher_name'] ?? ''),
        'date_taught'          => $row['date_taught'] ?? '',
        'duration'             => coalesce($row['duration'] ?? '', $lessonDuration),
        'num_learners'         => (string)($row['num_learners'] ?? ''),
        'slo1'                 => coalesce($row['slo1'] ?? '', $sloItems[0] ?? ''),
        'slo2'                 => coalesce($row['slo2'] ?? '', $sloItems[1] ?? ''),
        'slo3'                 => coalesce($row['slo3'] ?? '', $sloItems[2] ?? ''),
        'key_inquiry_question' => coalesce($row['key_inquiry_question'] ?? '', $row['key_inquiry'] ?? '', $row['meta_kiq'] ?? ''),
        'core_competencies'    => coalesce($row['core_competencies']    ?? '', $row['meta_cc']     ?? ''),
        'pcis'                 => coalesce($row['pcis']                 ?? '', $row['meta_pcis']   ?? ''),
        'values_attit'         => coalesce($row['values_attit']         ?? '', $row['meta_values'] ?? ''),
        'resources'            => coalesce($row['resources'] ?? '', $row['sow_resources'] ?? '', $row['meta_res'] ?? ''),
        'introduction'         => $row['introduction']      ?? '',
        'step1'                => coalesce($row['step1'] ?? '', $leItems[0] ?? ''),
        'step2'                => coalesce($row['step2'] ?? '', $leItems[1] ?? ''),
        'step3'                => coalesce($row['step3'] ?? '', $leItems[2] ?? ''),
        'conclusion'           => $row['conclusion']       ?? '',
        'reflection'           => $row['reflection']       ?? '',
        'extended_activity'    => $row['extended_activity']?? '',
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Lesson Plans — <?= e($la['name']) ?> (<?= e($la['grade_name']) ?>)</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: Arial, Helvetica, sans-serif;
    font-size: 10pt;
    color: #000;
    background: #f3f4f6;
}

/* ── Screen toolbar ─────────────────────────────────────────────────── */
.toolbar {
    position: sticky; top: 0; z-index: 100;
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 10px;
    padding: 12px 20px;
    background: #fff; border-bottom: 2px solid #1a56db;
}
.toolbar-left h2 { font-size: 14pt; color: #1a56db; }
.toolbar-left small { color: #6b7280; font-size: 10pt; }
.toolbar-right { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }

.btn { padding: 8px 18px; border-radius: 6px; font-size: 11pt; font-weight: 700;
       cursor: pointer; text-decoration: none; border: none; display: inline-block; }
.btn-blue   { background: #1a56db; color: #fff; }
.btn-purple { background: #7c3aed; color: #fff; }
.btn-outline { background: #fff; color: #374151; border: 1px solid #d1d5db; }
.btn-sm { font-size: 9pt; padding: 5px 12px; }

.week-filter { display: flex; gap: 5px; flex-wrap: wrap; align-items: center; font-size: 11pt; }
.week-filter a { padding: 4px 10px; border-radius: 4px; font-size: 9pt; font-weight: 600;
                 text-decoration: none; background: #e5e7eb; color: #374151; }
.week-filter a.active, .week-filter a:hover { background: #1a56db; color: #fff; }
.week-filter span { font-size: 10pt; color: #6b7280; font-weight: 600; }

/* ── Plan cards on screen ───────────────────────────────────────────── */
.plans-wrap { max-width: 820px; margin: 24px auto; padding: 0 16px; }

.plan-card {
    background: #fff; border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,.12);
    margin-bottom: 32px; padding: 24px;
    page-break-after: always;
}
.plan-card:last-child { page-break-after: auto; }

.pt-title { font-size: 12pt; font-weight: 700; text-align: center;
            text-transform: uppercase; letter-spacing: .06em; margin-bottom: 10pt; }
.pt-info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0; margin-bottom: 14pt; }
.pt-info-cell { padding: 3pt 5pt; border: 1px solid #777; font-size: 9pt; }
.pt-info-cell strong { text-transform: uppercase; font-size: 8pt; letter-spacing: .04em; margin-right: 4pt; }

.pt-row { margin-bottom: 8pt; }
.pt-label { font-weight: 700; font-size: 9.5pt; text-transform: uppercase;
            letter-spacing: .04em; margin-bottom: 2pt; }
.pt-inline { display: flex; gap: 6pt; align-items: baseline; }
.pt-inline .pt-label { min-width: 90pt; flex-shrink: 0; }

.dotline { border-bottom: 1px dotted #666; min-height: 16pt; margin: 2pt 0; }

.lp-fill p.lp-text { margin: 0 0 2pt; font-size: 9.5pt; }
.lp-fill ul.lp-list { margin: 0 0 2pt 14pt; padding: 0; font-size: 9.5pt; }
.lp-fill ul.lp-list li { margin-bottom: 1pt; }

.pt-numbered { margin: 4pt 0 0; padding: 0; list-style: none; }
.pt-numbered li { display: grid; grid-template-columns: 16pt 1fr; gap: 4pt; margin-bottom: 6pt; font-size: 9.5pt; }
.pt-numbered li .num { font-weight: 700; }

.pt-lettered { margin: 4pt 0 0; padding: 0; list-style: none; }
.pt-lettered > li { margin-bottom: 10pt; }
.pt-lettered > li .let-label { font-weight: 700; font-size: 9.5pt; text-transform: uppercase; margin-bottom: 3pt; }
.pt-lettered > li .sub-steps { margin: 3pt 0 0 12pt; list-style: none; padding: 0; }
.pt-lettered > li .sub-steps li { display: grid; grid-template-columns: 14pt 1fr; gap: 3pt; margin-bottom: 8pt; font-size: 9.5pt; }

.pt-divider { border: none; border-top: 1px solid #000; margin: 10pt 0 12pt; }

.no-plan-note {
    color: #9ca3af; font-style: italic; font-size: 9pt;
    border: 1px dashed #d1d5db; padding: 8pt 10pt; border-radius: 4pt;
    margin: 4pt 0;
}

/* ── Print ─────────────────────────────────────────────────────────── */
@media print {
    @page { size: A4 portrait; margin: 16mm 16mm; }
    body { background: #fff; font-size: 10pt; }
    .toolbar { display: none !important; }
    .plans-wrap { max-width: none; margin: 0; padding: 0; }
    .plan-card { box-shadow: none; border: none; border-radius: 0;
                 padding: 0; margin: 0; page-break-after: always; }
    .plan-card:last-child { page-break-after: auto; }
    .no-plan-note { display: none; }
    .pt-info-cell { border-color: #555; }
}
  /* ── Hide Week & Lesson numbers only (labels stay, just the digits disappear) */
  body.hide-wk-ls .wkls-num { visibility: hidden; }

/* Toggle button */
.btn-toggle-wk {
    background: #fff; color: #374151;
    border: 1px solid #d1d5db;
    padding: 8px 14px; border-radius: 6px;
    font-size: 10pt; font-weight: 600; cursor: pointer;
    transition: all .15s; user-select: none;
}
.btn-toggle-wk.active {
    background: #fef3c7; color: #92400e;
    border-color: #fcd34d;
}</style>
</head>
<body>

<!-- ── Toolbar (screen only) ──────────────────────────────────────────────── -->
<div class="toolbar">
  <div class="toolbar-left">
    <h2><?= e($la['name']) ?> — Lesson Plans<?= $filterTerm>0 ? ' | Term '.$filterTerm : '' ?></h2>
    <small><?= e($la['grade_name']) ?> &bull;
    <?php
      $withPlan = count(array_filter($lessons, fn($r) => $r['lp_id'] !== null));
      $total    = count($lessons);
    ?>
    <?= $withPlan ?> / <?= $total ?> plans generated</small>
  </div>
  <div class="toolbar-right">
    <!-- Term filter -->
    <div class="week-filter">
      <span>Term:</span>
      <a href="print_lessonplans.php?learning_area_id=<?= $laId ?><?= $filterW>0?'&week='.$filterW:'' ?>" class="<?= $filterTerm===0?'active':'' ?>">All</a>
      <a href="print_lessonplans.php?learning_area_id=<?= $laId ?>&term=1<?= $filterW>0?'&week='.$filterW:'' ?>" class="<?= $filterTerm===1?'active':'' ?>" style="<?= $filterTerm===1?'background:#3b82f6':'' ?>">T1</a>
      <a href="print_lessonplans.php?learning_area_id=<?= $laId ?>&term=2<?= $filterW>0?'&week='.$filterW:'' ?>" class="<?= $filterTerm===2?'active':'' ?>" style="<?= $filterTerm===2?'background:#10b981':'' ?>">T2</a>
      <a href="print_lessonplans.php?learning_area_id=<?= $laId ?>&term=3<?= $filterW>0?'&week='.$filterW:'' ?>" class="<?= $filterTerm===3?'active':'' ?>" style="<?= $filterTerm===3?'background:#f59e0b':'' ?>">T3</a>
    </div>
    <!-- Week filter -->
    <div class="week-filter">
      <span>Week:</span>
      <a href="print_lessonplans.php?learning_area_id=<?= $laId ?><?= $filterTerm>0?'&term='.$filterTerm:'' ?>" class="<?= $filterW === 0 ? 'active' : '' ?>">All</a>
      <?php foreach ($weeks as $w): ?>
      <a href="print_lessonplans.php?learning_area_id=<?= $laId ?>&week=<?= $w ?><?= $filterTerm>0?'&term='.$filterTerm:'' ?>"
         class="<?= $filterW === $w ? 'active' : '' ?>"><?= $w ?></a>
      <?php endforeach; ?>
    </div>
    <a href="print_sow.php?learning_area_id=<?= $laId ?><?= $filterTerm>0?'&term='.$filterTerm:'' ?>" class="btn btn-outline">&larr; Back to SOW</a>
    <button class="btn-toggle-wk" id="toggleWkBtn" onclick="toggleWeekLesson()" title="Show/hide Week and Lesson">&#128196; Hide Wk/Ls</button>
    <button onclick="window.print()" class="btn btn-blue">&#128438; Print / Save PDF</button>
  </div>
</div>

<?php if (empty($lessons)): ?>
  <div class="plans-wrap"><p style="padding:40px;text-align:center;color:#6b7280">No lessons found.</p></div>
<?php else: ?>
<div class="plans-wrap">
<?php foreach ($lessons as $i => $row):
    $lp = buildPlan($row, $appSettings, (string)($la['lesson_duration'] ?? ''));
    $sow_strand    = $row['strand'];
    $sow_subStrand = $row['sub_strand'];
    $sow_week      = $filterTerm > 0 ? $lpDispWeek[$i]   : (int)$row['week'];
    $sow_lesson    = $filterTerm > 0 ? $lpDispLesson[$i] : (int)$row['lesson'];
    $sow_grade     = $la['grade_name'];
    $sow_la        = $la['name'];
    $hasContent    = $row['lp_id'] !== null;
?>
<div class="plan-card">

  <div class="pt-title">Lesson Plan</div>

  <!-- Info grid -->
  <div class="pt-info-grid">
    <div class="pt-info-cell"><strong>School:</strong> <?= e($lp['school_name']) ?></div>
    <div class="pt-info-cell"><strong>Teacher:</strong> <?= e($lp['teacher_name']) ?></div>
    <div class="pt-info-cell"><strong>Grade:</strong> <?= e($sow_grade) ?></div>
    <div class="pt-info-cell"><strong>Learning Area:</strong> <?= e($sow_la) ?></div>
    <div class="pt-info-cell"><strong>Date:</strong> <?= e($lp['date_taught']) ?></div>
    <div class="pt-info-cell"><strong>Duration:</strong> <?= e($lp['duration']) ?> min</div>
    <div class="pt-info-cell"><strong>Week:</strong> <span class="wkls-num"><?= $sow_week ?></span> &nbsp;&nbsp; <strong>Lesson:</strong> <span class="wkls-num"><?= $sow_lesson ?></span></div>
    <div class="pt-info-cell"><strong>No. of Learners:</strong> <?= e($lp['num_learners']) ?></div>
  </div>

  <!-- Strand / Sub-Strand -->
  <div class="pt-row pt-inline">
    <div class="pt-label">Strand:</div>
    <div style="flex:1"><?= pf($sow_strand, 1) ?></div>
  </div>
  <div class="pt-row pt-inline">
    <div class="pt-label">Sub-Strand:</div>
    <div style="flex:1"><?= pf($sow_subStrand, 1) ?></div>
  </div>

  <!-- SLOs -->
  <div class="pt-row">
    <div class="pt-label">Specific Learning Outcomes:</div>
    <p style="margin:3pt 0 4pt;font-size:9.5pt">By the end of the lesson, the learner should be able to:</p>
    <ol class="pt-numbered">
      <?php foreach ([1,2,3] as $n): ?>
      <li><span class="num"><?= $n ?>.</span><div><?= pf($lp['slo'.$n], 2) ?></div></li>
      <?php endforeach; ?>
    </ol>
  </div>

  <!-- Key Inquiry Question -->
  <div class="pt-row pt-inline">
    <div class="pt-label">Key Inquiry Question:</div>
    <div style="flex:1"><?= pf($lp['key_inquiry_question'], 1) ?></div>
  </div>

  <!-- Core Competencies -->
  <div class="pt-row">
    <div class="pt-label">Core Competencies to be Developed</div>
    <?= pf($lp['core_competencies'], 2) ?>
  </div>

  <!-- PCIs -->
  <div class="pt-row">
    <div class="pt-label">Links to Pertinent and Contemporary Issues (PCIs)</div>
    <?= pf($lp['pcis'], 2) ?>
  </div>

  <!-- Values -->
  <div class="pt-row">
    <div class="pt-label">Links to Values</div>
    <?= pf($lp['values_attit'], 2) ?>
  </div>

  <!-- Resources -->
  <div class="pt-row">
    <div class="pt-label">Teaching / Learning Resources</div>
    <?= pf($lp['resources'], 2) ?>
  </div>

  <hr class="pt-divider">

  <!-- Organisation of Learning -->
  <div class="pt-row">
    <div class="pt-label">Organisation of Learning / Learning Experiences</div>
    <ul class="pt-lettered">

      <li>
        <div class="let-label">a. &nbsp;Introduction / Getting Started</div>
        <?php if (!$hasContent && trim($lp['introduction']) === ''): ?>
          <div class="no-plan-note">Not yet generated — click &#128196; Plan to generate.</div>
        <?php else: ?>
          <?= pf($lp['introduction'], 2) ?>
        <?php endif; ?>
      </li>

      <li>
        <div class="let-label">b. &nbsp;Lesson Development</div>
        <div style="font-size:9.5pt;font-weight:700;margin:2pt 0 4pt 12pt">STEPS</div>
        <ul class="sub-steps">
          <?php foreach ([1,2,3] as $n): ?>
          <li><span class="num"><?= $n ?>.</span><div><?= pf($lp['step'.$n], 3) ?></div></li>
          <?php endforeach; ?>
        </ul>
      </li>

      <li>
        <div class="let-label">c. &nbsp;Conclusion</div>
        <?= pf($lp['conclusion'], 2) ?>
      </li>

      <li>
        <div class="let-label">d. &nbsp;Reflection on the Lesson</div>
        <?= pf($lp['reflection'], 2) ?>
      </li>

      <li>
        <div class="let-label">e. &nbsp;Extended Activity</div>
        <?= pf($lp['extended_activity'], 3) ?>
      </li>

    </ul>
  </div>

</div><!-- /plan-card -->
<?php endforeach; ?>
</div><!-- /plans-wrap -->
<?php endif; ?>

</body>
<script>
function toggleWeekLesson() {
    const body = document.body;
    const btn  = document.getElementById('toggleWkBtn');
    const hide = body.classList.toggle('hide-wk-ls');
    btn.classList.toggle('active', hide);
    btn.textContent = hide ? '\u{1F4C4} Show Wk/Ls' : '\u{1F4C4} Hide Wk/Ls';
    try { localStorage.setItem('lp_hide_wkls', hide ? '1' : '0'); } catch(e){}
}
try {
    if (localStorage.getItem('lp_hide_wkls') === '1') {
        document.body.classList.add('hide-wk-ls');
        const btn = document.getElementById('toggleWkBtn');
        btn.classList.add('active');
        btn.textContent = '\u{1F4C4} Show Wk/Ls';
    }
} catch(e){}
</script>
</html>
