<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id > 0) {
    $pdo = getDB();
    $pdo->prepare('DELETE FROM scheme_of_work WHERE id = :id')->execute([':id' => $id]);
}

header('Location: index.php?msg=deleted');
exit;
