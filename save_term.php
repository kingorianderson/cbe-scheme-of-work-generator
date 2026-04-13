<?php
// save_term.php — AJAX endpoint: assign term to SOW rows
// POST JSON: { mode, la_id, term, strand?, sub_strand?, week_from?, week_to? }
// Returns JSON: { ok, updated }
require_once 'config.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'error' => 'POST required']); exit;
}

$pdo  = getDB();
$body = json_decode(file_get_contents('php://input'), true);
if (!is_array($body)) {
    echo json_encode(['ok' => false, 'error' => 'Invalid JSON']); exit;
}

$mode  = $body['mode']  ?? '';
$laId  = (int)($body['la_id'] ?? 0);
$term  = isset($body['term']) && $body['term'] !== '' ? (int)$body['term'] : null;

if ($laId < 1) {
    echo json_encode(['ok' => false, 'error' => 'la_id required']); exit;
}
if ($term !== null && !in_array($term, [1, 2, 3], true)) {
    echo json_encode(['ok' => false, 'error' => 'term must be 1, 2, 3 or empty']); exit;
}

// ── Mode: assign all lessons for a specific strand + sub_strand ────────────
if ($mode === 'substrand') {
    $strand    = trim($body['strand']    ?? '');
    $subStrand = trim($body['sub_strand'] ?? '');
    if ($strand === '' || $subStrand === '') {
        echo json_encode(['ok' => false, 'error' => 'strand and sub_strand required']); exit;
    }
    $stmt = $pdo->prepare(
        "UPDATE scheme_of_work
         SET term = :term
         WHERE learning_area_id = :la AND strand = :s AND sub_strand = :ss"
    );
    $stmt->execute([':term' => $term, ':la' => $laId, ':s' => $strand, ':ss' => $subStrand]);
    echo json_encode(['ok' => true, 'updated' => $stmt->rowCount()]);
    exit;
}

// ── Mode: assign lessons in a week+lesson range ────────────────────────────
// Uses composite key (week * 1000 + lesson) so "Week 10 Lesson 2" works precisely.
if ($mode === 'weekrange') {
    $weekFrom   = (int)($body['week_from']   ?? 0);
    $weekTo     = (int)($body['week_to']     ?? 0);
    $lessonFrom = (int)($body['lesson_from'] ?? 1);      // default: first lesson
    $lessonTo   = (int)($body['lesson_to']   ?? 9999);   // default: last lesson

    if ($weekFrom < 1 || $weekTo < $weekFrom) {
        echo json_encode(['ok' => false, 'error' => 'Invalid week range']); exit;
    }

    $keyFrom = $weekFrom * 1000 + max(1, $lessonFrom);
    $keyTo   = $weekTo   * 1000 + max(1, $lessonTo);

    $stmt = $pdo->prepare(
        "UPDATE scheme_of_work
         SET term = :term
         WHERE learning_area_id = :la
           AND (week * 1000 + lesson) BETWEEN :kf AND :kt"
    );
    $stmt->execute([':term' => $term, ':la' => $laId, ':kf' => $keyFrom, ':kt' => $keyTo]);
    echo json_encode(['ok' => true, 'updated' => $stmt->rowCount()]);
    exit;
}

// ── Mode: clear all terms for this learning area ───────────────────────────
if ($mode === 'clear_all') {
    $stmt = $pdo->prepare("UPDATE scheme_of_work SET term = NULL WHERE learning_area_id = :la");
    $stmt->execute([':la' => $laId]);
    echo json_encode(['ok' => true, 'updated' => $stmt->rowCount()]);
    exit;
}

echo json_encode(['ok' => false, 'error' => 'Unknown mode: ' . htmlspecialchars($mode, ENT_QUOTES, 'UTF-8')]);
