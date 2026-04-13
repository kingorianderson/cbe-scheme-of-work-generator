<?php
require_once 'config.php';
$pdo = getDB();

// Selected grade
$gradeId = isset($_GET['grade_id']) ? (int)$_GET['grade_id'] : 0;

// Flash message
$flash = '';
if (isset($_GET['msg'])) {
    $msgs = ['la_added' => 'Learning area added.', 'la_deleted' => 'Learning area deleted.', 'la_updated' => 'Learning area updated.'];
    $flash = $msgs[$_GET['msg']] ?? '';
}
$flashErr = $_GET['err'] ?? '';

// Load all grades grouped by level
$allGrades = $pdo->query("SELECT * FROM grades ORDER BY sort_order")->fetchAll();
$grouped   = [];
foreach ($allGrades as $g) {
    $grouped[$g['level_group']][] = $g;
}

// Selected grade details
$selectedGrade = null;
if ($gradeId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM grades WHERE id = :id");
    $stmt->execute([':id' => $gradeId]);
    $selectedGrade = $stmt->fetch();
}

// Learning areas for selected grade
$learningAreas = [];
if ($selectedGrade) {
    $stmt = $pdo->prepare("SELECT la.*, 
        (SELECT COUNT(*) FROM scheme_of_work s WHERE s.learning_area_id = la.id) AS sow_count
        FROM learning_areas la WHERE la.grade_id = :gid ORDER BY la.name");
    $stmt->execute([':gid' => $gradeId]);
    $learningAreas = $stmt->fetchAll();
}

// Form values (re-populate on error)
$form = [
    'name'             => $_GET['f_name']    ?? '',
    'short_code'       => $_GET['f_code']    ?? '',
    'lessons_per_week' => $_GET['f_lpw']     ?? 5,
];

function e(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Curriculum — Learning Areas</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="app-shell">

  <!-- ── Top Nav ── -->
  <nav class="top-nav">
    <span class="nav-brand">CBC Scheme of Work</span>
    <ol class="breadcrumb">
      <li><a href="curriculum.php">Dashboard</a></li>
      <li><a href="curriculum.php">Curriculum</a></li>
      <li class="active">Learning Areas</li>
    </ol>
    <a href="import.php" class="btn btn-outline" style="margin-left:auto;font-size:12px;padding:5px 14px" title="Import curriculum data">&#8659; Import Data</a>
    <a href="settings.php" class="btn btn-outline" style="font-size:12px;padding:5px 14px" title="Application Settings">&#9881; Settings</a>
  </nav>

  <div class="curriculum-layout">

    <!-- ── Left Sidebar: Grade Picker ── -->
    <aside class="grade-sidebar">
      <h3 class="sidebar-title">SELECT GRADE</h3>

      <?php foreach ($grouped as $levelName => $grades): ?>
        <p class="level-label"><?= e($levelName) ?></p>
        <?php foreach ($grades as $g): ?>
          <a href="curriculum.php?grade_id=<?= $g['id'] ?>"
             class="grade-item <?= $gradeId === (int)$g['id'] ? 'active' : '' ?>">
            <span><?= e($g['name']) ?></span>
            <span class="duration"><?= (int)$g['lesson_duration'] ?>min</span>
          </a>
          <?php if ($gradeId === (int)$g['id'] && $learningAreas): ?>
            <?php foreach ($learningAreas as $la): ?>
              <a href="index.php?learning_area_id=<?= (int)$la['id'] ?>"
                 class="la-sidebar-item">
                <span class="la-sidebar-dot"></span>
                <?= e($la['name']) ?>
              </a>
            <?php endforeach; ?>
          <?php endif; ?>
        <?php endforeach; ?>
      <?php endforeach; ?>
    </aside>

    <!-- ── Main Panel ── -->
    <main class="curriculum-main">

      <?php if (!$selectedGrade): ?>
        <div class="select-prompt">
          <div class="select-prompt-inner">
            <svg width="48" height="48" fill="none" viewBox="0 0 24 24" stroke="#9ca3af" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.966 8.966 0 00-6 2.292m0-14.25v14.25"/></svg>
            <p>Select a grade from the left to manage its learning areas.</p>
          </div>
        </div>

      <?php else: ?>

        <!-- Flash -->
        <?php if ($flash): ?>
          <div class="flash"><?= e($flash) ?></div>
        <?php endif; ?>
        <?php if ($flashErr): ?>
          <div class="flash flash-err"><?= e($flashErr) ?></div>
        <?php endif; ?>

        <!-- Add Form -->
        <div class="panel">
          <h2 class="panel-title">Add Learning Area &mdash; <?= e($selectedGrade['name']) ?></h2>

          <form method="post" action="save_learning_area.php" class="la-form" novalidate>
            <input type="hidden" name="grade_id" value="<?= (int)$gradeId ?>">

            <div class="la-form-row full">
              <label for="la_name">Learning Area Name <span class="req">*</span></label>
              <input type="text" id="la_name" name="name"
                     placeholder="e.g. Mathematics, English, Kiswahili…"
                     value="<?= e($form['name']) ?>" required autocomplete="off">
            </div>

            <div class="la-form-row half">
              <label for="la_code">Short Code</label>
              <input type="text" id="la_code" name="short_code" maxlength="20"
                     placeholder="e.g. MATH, ENG"
                     value="<?= e($form['short_code']) ?>">
            </div>

            <div class="la-form-row half">
              <label for="la_lpw">Lessons Per Week <span class="req">*</span></label>
              <input type="number" id="la_lpw" name="lessons_per_week" min="1" max="30"
                     value="<?= (int)($form['lessons_per_week'] ?: 5) ?>" required>
              <small>As per the KICD timetable allocation</small>
            </div>

            <div class="la-form-row full">
              <button type="submit" class="btn btn-primary btn-lg">Add Learning Area</button>
            </div>
          </form>
        </div>

        <!-- Existing Learning Areas -->
        <?php if ($learningAreas): ?>
        <div class="panel mt">
          <div class="la-list-header">
            <h3 class="panel-title" style="margin-bottom:0">Learning Areas &mdash; <?= e($selectedGrade['name']) ?></h3>
            <span class="la-count"><?= count($learningAreas) ?> area(s)</span>
          </div>

          <div class="la-card-list">
          <?php foreach ($learningAreas as $la): ?>
            <div class="la-card">
              <div class="la-card-info">
                <strong class="la-card-name"><?= e($la['name']) ?></strong>
                <span class="la-card-meta">
                  <?= (int)$la['lessons_per_week'] ?> lessons/week
                  &bull; Code: <?= e($la['short_code'] ?: '—') ?>
                  &bull; <?= (int)$la['sow_count'] ?> strand(s)
                </span>
              </div>
              <div class="la-card-actions">
                <a href="index.php?learning_area_id=<?= (int)$la['id'] ?>" class="btn btn-primary btn-sm">Manage Curriculum</a>
                <a href="sub_strand_meta.php?la=<?= (int)$la['id'] ?>" class="btn btn-sm btn-outline-muted" title="Key Inquiry Questions, Core Competencies, Values, PCIs, Resources">Design</a>
                <a href="import.php?la=<?= (int)$la['id'] ?>" class="btn btn-sm btn-outline-muted" title="Re-import or update curriculum data for this learning area">&#8659; Import / Update</a>
                <a href="edit_learning_area.php?id=<?= (int)$la['id'] ?>&grade_id=<?= (int)$gradeId ?>" class="btn btn-sm btn-outline-muted">Edit</a>
                <form method="post" action="delete_learning_area.php" style="display:inline"
                      onsubmit="return confirm('Delete <?= e(addslashes($la['name'])) ?>?');">
                  <input type="hidden" name="id"       value="<?= (int)$la['id'] ?>">
                  <input type="hidden" name="grade_id" value="<?= (int)$gradeId ?>">
                  <button type="submit" class="btn btn-sm btn-delete-outline">Delete</button>
                </form>
              </div>
            </div>
          <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

      <?php endif; ?>
    </main>
  </div><!-- .curriculum-layout -->
</div><!-- .app-shell -->
</body>
</html>
