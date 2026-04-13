<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: curriculum.php');
    exit;
}

$id      = isset($_POST['id'])       ? (int)$_POST['id']       : 0;
$gradeId = isset($_POST['grade_id']) ? (int)$_POST['grade_id'] : 0;

if ($id > 0) {
    $pdo = getDB();
    $pdo->prepare('DELETE FROM learning_areas WHERE id = :id')->execute([':id' => $id]);
}

header("Location: curriculum.php?grade_id=$gradeId&msg=la_deleted");
exit;
