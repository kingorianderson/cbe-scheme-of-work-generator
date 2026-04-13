<?php
// migrate7.php — Add term column to scheme_of_work
require_once 'config.php';
$pdo = getDB();

$pdo->exec("ALTER TABLE scheme_of_work ADD COLUMN IF NOT EXISTS term TINYINT UNSIGNED NULL DEFAULT NULL COMMENT '1=Term1, 2=Term2, 3=Term3'");
$pdo->exec("ALTER TABLE scheme_of_work ADD INDEX IF NOT EXISTS idx_term (term)");

echo "done — term column added to scheme_of_work\n";
