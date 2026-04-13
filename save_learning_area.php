<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: curriculum.php');
    exit;
}

$gradeId        = isset($_POST['grade_id'])        ? (int)$_POST['grade_id']        : 0;
$name           = trim($_POST['name']              ?? '');
$short_code     = trim($_POST['short_code']        ?? '') ?: null;
$lessonsPerWeek = isset($_POST['lessons_per_week']) ? (int)$_POST['lessons_per_week'] : 5;

$errors = [];
if ($gradeId < 1)          $errors[] = 'Invalid grade.';
if ($name === '')          $errors[] = 'Learning area name is required.';
if ($lessonsPerWeek < 1)   $errors[] = 'Lessons per week must be at least 1.';

if ($errors) {
    $q = http_build_query([
        'grade_id' => $gradeId,
        'err'      => implode(' ', $errors),
        'f_name'   => $name,
        'f_code'   => $short_code ?? '',
        'f_lpw'    => $lessonsPerWeek,
    ]);
    header("Location: curriculum.php?$q");
    exit;
}

$pdo = getDB();
$pdo->prepare(
    'INSERT INTO learning_areas (grade_id, name, short_code, lessons_per_week) VALUES (:gid, :name, :code, :lpw)'
)->execute([':gid' => $gradeId, ':name' => $name, ':code' => $short_code, ':lpw' => $lessonsPerWeek]);

header("Location: curriculum.php?grade_id=$gradeId&msg=la_added");
exit;
