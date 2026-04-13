<?php
require_once 'config.php';
$pdo = getDB();
$pdo->exec("TRUNCATE TABLE scheme_of_work");
// Also clear any orphaned learning areas if requested
if (isset($_GET['full'])) {
    $pdo->exec("DELETE FROM learning_areas");
}
header('Location: curriculum.php');
exit;
