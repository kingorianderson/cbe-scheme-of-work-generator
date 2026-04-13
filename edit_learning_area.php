<?php
require_once 'config.php';
$pdo     = getDB();
$id      = isset($_GET['id'])       ? (int)$_GET['id']       : 0;
$gradeId = isset($_GET['grade_id']) ? (int)$_GET['grade_id'] : 0;

if ($id < 1) { header('Location: curriculum.php'); exit; }

$stmt = $pdo->prepare('SELECT * FROM learning_areas WHERE id = :id');
$stmt->execute([':id' => $id]);
$la = $stmt->fetch();
if (!$la) { header('Location: curriculum.php'); exit; }
$gradeId = $gradeId ?: (int)$la['grade_id'];

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name           = trim($_POST['name']            ?? '');
    $short_code     = trim($_POST['short_code']      ?? '') ?: null;
    $lessonsPerWeek = isset($_POST['lessons_per_week']) ? (int)$_POST['lessons_per_week'] : 1;

    if ($name === '')        $errors[] = 'Learning area name is required.';
    if ($lessonsPerWeek < 1) $errors[] = 'Lessons per week must be at least 1.';

    if (empty($errors)) {
        $pdo->prepare('UPDATE learning_areas SET name=:name, short_code=:code, lessons_per_week=:lpw WHERE id=:id')
            ->execute([':name' => $name, ':code' => $short_code, ':lpw' => $lessonsPerWeek, ':id' => $id]);
        header("Location: curriculum.php?grade_id=$gradeId&msg=la_updated");
        exit;
    }
    $la['name']             = $name;
    $la['short_code']       = $short_code ?? '';
    $la['lessons_per_week'] = $lessonsPerWeek;
}

function e(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Learning Area — Scheme of Work</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="app-shell">
  <nav class="top-nav">
    <span class="nav-brand">CBC Scheme of Work</span>
    <ol class="breadcrumb">
      <li><a href="curriculum.php">Curriculum</a></li>
      <li><a href="curriculum.php?grade_id=<?= $gradeId ?>">Grade <?= $gradeId ?></a></li>
      <li class="active">Edit Learning Area</li>
    </ol>
  </nav>

  <div style="padding:32px;max-width:600px">
    <?php if ($errors): ?>
      <div class="flash flash-err"><?= implode('<br>', array_map('e', $errors)) ?></div>
    <?php endif; ?>

    <div class="panel">
      <h2 class="panel-title">Edit Learning Area</h2>
      <form method="post" class="la-form" novalidate>
        <div class="la-form-row full">
          <label for="la_name">Learning Area Name <span class="req">*</span></label>
          <input type="text" id="la_name" name="name"
                 value="<?= e($la['name']) ?>" required autocomplete="off">
        </div>
        <div class="la-form-row half">
          <label for="la_code">Short Code</label>
          <input type="text" id="la_code" name="short_code" maxlength="20"
                 placeholder="e.g. MATH, ENG"
                 value="<?= e($la['short_code'] ?? '') ?>">
        </div>
        <div class="la-form-row half">
          <label for="la_lpw">Lessons Per Week <span class="req">*</span></label>
          <input type="number" id="la_lpw" name="lessons_per_week" min="1" max="30"
                 value="<?= (int)$la['lessons_per_week'] ?>" required>
          <small>As per the KICD timetable allocation</small>
        </div>
        <div class="la-form-row full" style="display:flex;gap:10px;margin-top:8px">
          <button type="submit" class="btn btn-primary btn-lg">Save Changes</button>
          <a href="curriculum.php?grade_id=<?= $gradeId ?>" class="btn btn-outline btn-lg">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</div>
</body>
</html>
