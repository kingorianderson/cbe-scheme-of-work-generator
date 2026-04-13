<?php
// DEPRECATED: All AI generation is now handled by ai_generate.php
// This file is kept for reference only. All callers have been updated.
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['ok'=>false,'error'=>'This endpoint is deprecated. Use ai_generate.php with type=lesson_plan.']);
