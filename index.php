<?php
require_once 'config.php';

$pdo = getDB();

// Learning area context
$learningAreaId = isset($_GET['learning_area_id']) ? (int)$_GET['learning_area_id'] : 0;
$learningArea   = null;
$gradeName      = '';
if ($learningAreaId > 0) {
    $stmt = $pdo->prepare(
        "SELECT la.*, g.name AS grade_name FROM learning_areas la
         JOIN grades g ON g.id = la.grade_id WHERE la.id = :id"
    );
    $stmt->execute([':id' => $learningAreaId]);
    $learningArea = $stmt->fetch();
    if ($learningArea) $gradeName = $learningArea['grade_name'];
}

// Filters
$filterWeek   = isset($_GET['week'])   ? (int)$_GET['week']   : 0;
$filterStrand = isset($_GET['strand']) ? trim($_GET['strand']) : '';
$filterTerm   = isset($_GET['term'])   ? (int)$_GET['term']   : 0;
$search       = isset($_GET['search']) ? trim($_GET['search']) : '';

$where  = [];
$params = [];

if ($learningAreaId > 0) {
    $where[]                   = 's.learning_area_id = :laid';
    $params[':laid']           = $learningAreaId;
}
if ($filterWeek > 0) {
    $where[]        = 's.week = :week';
    $params[':week'] = $filterWeek;
}
if ($filterStrand !== '') {
    $where[]           = 's.strand = :strand';
    $params[':strand'] = $filterStrand;
}
if ($filterTerm > 0) {
    $where[]          = 's.term = :term';
    $params[':term']  = $filterTerm;
}
if ($search !== '') {
    $where[]       = '(s.strand LIKE :s OR s.sub_strand LIKE :s OR s.slo_sow LIKE :s OR s.key_inquiry LIKE :s)';
    $params[':s']  = '%' . $search . '%';
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$rows     = $pdo->prepare(
    "SELECT s.*,
            COALESCE(NULLIF(TRIM(s.resources), ''),   m.resources)   AS resources,
            COALESCE(NULLIF(TRIM(s.assessment), ''),  m.assessment)  AS assessment
     FROM scheme_of_work s
     LEFT JOIN sub_strand_meta m
       ON  m.learning_area_id = s.learning_area_id
       AND m.strand            = s.strand
       AND m.sub_strand        = s.sub_strand
     $whereSql ORDER BY s.week, s.lesson"
);
$rows->execute($params);
$rows = $rows->fetchAll();

// Distinct weeks & strands for filter dropdowns (scoped to learning area if set)
$scopeSql = $learningAreaId > 0 ? "WHERE learning_area_id = $learningAreaId" : '';
$weeks   = $pdo->query("SELECT DISTINCT week FROM scheme_of_work $scopeSql ORDER BY week")->fetchAll(PDO::FETCH_COLUMN);
$strands = $pdo->query("SELECT DISTINCT strand FROM scheme_of_work $scopeSql ORDER BY strand")->fetchAll(PDO::FETCH_COLUMN);

// Show AI buttons if explicitly enabled OR any API key is configured
$aiEnabled = false;
try {
    $aiRows = $pdo->query("SELECT setting_key, setting_value FROM app_settings WHERE setting_key IN ('ai_enabled','groq_api_key','ai_api_key')")->fetchAll(PDO::FETCH_KEY_PAIR);
    $aiEnabled = ($aiRows['ai_enabled'] ?? '0') === '1'
        || !empty($aiRows['groq_api_key'])
        || !empty($aiRows['ai_api_key']);
} catch (Exception $e) {}

$flash = '';
if (isset($_GET['msg'])) {
    $msgs = [
        'added'   => 'Record added successfully.',
        'updated' => 'Record updated successfully.',
        'deleted' => 'Record deleted.',
    ];
    $flash = $msgs[$_GET['msg']] ?? '';
}

function e(?string $v): string { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }

// Render text: lines starting with '* ' become <li> bullets; other lines are plain <span> blocks.
function bullets(?string $v): string {
    $v = trim($v ?? '');
    if ($v === '') return '';
    $lines   = explode("\n", $v);
    $out     = '';
    $inList  = false;
    foreach ($lines as $line) {
        $line = rtrim($line);
        if (str_starts_with($line, '* ')) {
            if (!$inList) { $out .= '<ul style="margin:2px 0 2px 14px;padding:0">'; $inList = true; }
            $out .= '<li>' . e(ltrim(substr($line, 2))) . '</li>';
        } else {
            if ($inList) { $out .= '</ul>'; $inList = false; }
            if ($line !== '') $out .= '<span style="display:block">' . e($line) . '</span>';
        }
    }
    if ($inList) $out .= '</ul>';
    return $out;
}

// Prepend standard stems at display time for existing records that pre-date the rule
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
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $learningArea ? e($learningArea['name']) . ' — ' . e($gradeName) : 'Scheme of Work' ?></title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="page-wrap">
  <nav class="top-nav">
    <span class="nav-brand">CBC Scheme of Work</span>
    <ol class="breadcrumb">
      <li><a href="curriculum.php">Curriculum</a></li>
      <?php if ($learningArea): ?>
        <li><a href="curriculum.php?grade_id=<?= (int)$learningArea['grade_id'] ?>"><?= e($gradeName) ?></a></li>
        <li class="active"><?= e($learningArea['name']) ?></li>
      <?php else: ?>
        <li class="active">All SOW Records</li>
      <?php endif; ?>
    </ol>
  </nav>
  <header>
    <div>
      <h1><?= $learningArea ? e($learningArea['name']) : 'Scheme of Work' ?></h1>
      <?php if ($learningArea): ?>
        <small style="color:var(--muted)"><?= e($gradeName) ?> &bull; <?= (int)$learningArea['lessons_per_week'] ?> lessons/week</small>
      <?php endif; ?>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
      <?php if ($learningArea): ?>
        <a href="curriculum.php?grade_id=<?= (int)$learningArea['grade_id'] ?>" class="btn btn-outline">&larr; Back</a>
        <a href="sub_strand_meta.php?la=<?= $learningAreaId ?>" class="btn btn-outline" title="Key Inquiry Questions, Core Competencies, Values, PCIs, Resources">&#128196; Curriculum Design</a>
        <a href="term_assign.php?learning_area_id=<?= $learningAreaId ?>" class="btn btn-outline" title="Assign terms to strands/sub-strands">&#128197; Terms</a>
        <a href="print_sow.php?learning_area_id=<?= $learningAreaId ?>" class="btn btn-outline" title="Printable Scheme of Work" target="_blank">&#128438; Download SOW</a>
        <?php if ($aiEnabled): ?>
          <a href="ai_bulk.php?learning_area_id=<?= $learningAreaId ?>" class="btn" style="background:#6d43d9;color:#fff" title="Bulk generate KIQ, Resources &amp; Assessment with AI">&#9889; Bulk AI Generate</a>
        <?php endif; ?>
      <?php endif; ?>
      <a href="form.php<?= $learningAreaId ? '?learning_area_id='.$learningAreaId : '' ?>" class="btn btn-primary">+ Add Record</a>
    </div>
  </header>

  <?php if ($flash): ?>
  <div class="flash"><?= e($flash) ?></div>
  <?php endif; ?>

  <!-- Filters -->
  <form method="get" class="filter-bar">
    <?php if ($learningAreaId > 0): ?>
      <input type="hidden" name="learning_area_id" value="<?= $learningAreaId ?>">
    <?php endif; ?>
    <select name="week">
      <option value="">All Weeks</option>
      <?php foreach ($weeks as $w): ?>
        <option value="<?= (int)$w ?>" <?= $filterWeek === (int)$w ? 'selected' : '' ?>>Week <?= (int)$w ?></option>
      <?php endforeach; ?>
    </select>
    <select name="strand">
      <option value="">All Strands</option>
      <?php foreach ($strands as $s): ?>
        <option value="<?= e($s) ?>" <?= $filterStrand === $s ? 'selected' : '' ?>><?= e($s) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="term">
      <option value="">All Terms</option>
      <option value="1" <?= $filterTerm===1 ? 'selected' : '' ?>>Term 1</option>
      <option value="2" <?= $filterTerm===2 ? 'selected' : '' ?>>Term 2</option>
      <option value="3" <?= $filterTerm===3 ? 'selected' : '' ?>>Term 3</option>
    </select>
    <input type="search" name="search" placeholder="Search…" value="<?= e($search) ?>">
    <button type="submit" class="btn">Filter</button>
    <a href="index.php<?= $learningAreaId ? '?learning_area_id='.$learningAreaId : '' ?>" class="btn btn-outline">Clear</a>
  </form>

  <!-- Table -->
  <div class="table-scroll">
  <?php if (empty($rows)): ?>
    <p class="empty">No records found. <a href="form.php">Add the first one.</a></p>
  <?php else: ?>
  <table>
    <thead>
      <tr>
        <th>#</th>
        <th>Week</th>
        <th>Lesson</th>
        <th>Strand</th>
        <th>Sub-Strand</th>
        <th>Specific Learning Outcomes (CD)</th>
        <th>Specific Learning Outcomes (SOW)</th>
        <th>Learning Experiences (CD)</th>
        <th>Learning Experiences (SOW)</th>
        <th>Key Inquiry Questions</th>
        <th>Learning Resources</th>
        <th>Assessment</th>
        <th>Remarks</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php
    // Pre-compute rowspan counts per week
    $weekCounts = [];
    foreach ($rows as $r) {
        $w = (int)$r['week'];
        $weekCounts[$w] = ($weekCounts[$w] ?? 0) + 1;
    }
    $weekSeen = [];
    foreach ($rows as $i => $r):
        $w = (int)$r['week'];
        $isFirstInWeek = !isset($weekSeen[$w]);
        if ($isFirstInWeek) $weekSeen[$w] = true;
    ?>
      <tr>
        <td><?= $i + 1 ?></td>
        <?php if ($isFirstInWeek): ?>
        <td class="center" rowspan="<?= $weekCounts[$w] ?>" style="vertical-align:middle"><?= $w ?></td>
        <?php endif; ?>
        <td class="center"><?= (int)$r['lesson'] ?></td>
        <td><?= e($r['strand']) ?></td>
        <td><?= e($r['sub_strand']) ?></td>
        <td><?= bullets($r['slo_cd']) ?></td>
        <td><?= bullets(withSlo($r['slo_sow'])) ?></td>
        <td><?= bullets($r['le_cd']) ?></td>
        <td><?= bullets(withLe($r['le_sow'])) ?></td>
        <td><?= bullets($r['key_inquiry']) ?></td>
        <td><?= bullets($r['resources']) ?></td>
        <td><?= bullets($r['assessment']) ?></td>
        <td><?= nl2br(e($r['remarks'])) ?></td>
        <td class="actions">
          <a href="lesson_plan.php?sow_id=<?= (int)$r['id'] ?>&auto_ai=1" class="btn btn-sm" style="background:#6d43d9;color:#fff">&#128196; Plan</a>
          <a href="form.php?id=<?= (int)$r['id'] ?>" class="btn btn-sm btn-edit">Edit</a>
          <form method="post" action="delete.php" onsubmit="return confirm('Delete this record?');">
            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
            <button type="submit" class="btn btn-sm btn-delete">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
  </div>

  <footer>
    <small>Total records: <?= count($rows) ?></small>
  </footer>
</div>
</body>
</html>
