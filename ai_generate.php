<?php
// ai_generate.php — Unified AI generation endpoint
// API endpoint — suppress HTML error output and buffer to ensure clean JSON
ini_set('display_errors', '0');
error_reporting(E_ERROR); // fatal errors only — ignore notices/warnings in output
ob_start();               // catch any stray output before JSON is sent

require_once 'config.php';
header('Content-Type: application/json; charset=utf-8');
set_time_limit(180); // allow up to 3 min for full Groq→DeepSeek→Claude fallback chain

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    jsonOut(['ok' => false, 'error' => 'POST required']);
}

$pdo = getDB();

function getSetting(PDO $pdo, string $key, string $default = ''): string {
    $stmt = $pdo->prepare("SELECT setting_value FROM app_settings WHERE setting_key = :k");
    $stmt->execute([':k' => $key]);
    $v = $stmt->fetchColumn();
    return ($v !== false && $v !== '') ? (string)$v : $default;
}

$aiEnabled = getSetting($pdo, 'ai_enabled', '0');
if ($aiEnabled !== '1') {
    jsonOut(['ok' => false, 'error' => 'AI is disabled. Enable it in Settings.']);
}

require_once 'ai_call.php';

$raw  = file_get_contents('php://input');
$body = json_decode($raw, true);
if (!is_array($body)) {
    jsonOut(['ok' => false, 'error' => 'Invalid JSON body.']);
}

$type = $body['type'] ?? 'suggest';

// ── JSON output helper — always clears stray output before sending ──────────
function jsonOut(array $data): never {
    ob_end_clean();
    echo json_encode($data);
    exit;
}

// ── Shared helpers ─────────────────────────────────────────────────────────

function cleanStr(?string $v, int $max = 2000): string {
    return mb_substr(trim((string)($v ?? '')), 0, $max);
}

function aiStr(mixed $v): string {
    if (is_array($v)) return trim(implode("\n", array_map('strval', $v)));
    return trim((string)($v ?? ''));
}

function parseAiJson(string $text): array|false {
    $text = preg_replace('/^```(?:json)?\s*/i', '', trim($text));
    $text = preg_replace('/\s*```\s*$/', '', $text);
    if (preg_match('/\{.*\}/s', $text, $m)) $text = $m[0];
    // Repair literal un-escaped newlines/tabs inside JSON string values
    $text = preg_replace_callback('/"((?:[^"\\\\]|\\\\.)*)"/s', function ($m) {
        $inner = str_replace(["\r\n", "\r", "\n"], '\\n', $m[1]);
        return '"' . str_replace("\t", '\\t', $inner) . '"';
    }, $text);
    $result = json_decode($text, true);
    return is_array($result) ? $result : false;
}

// ── Helpers shared by structure_doc / reverify_doc ────────────────────────

function repairAndDecodeJson(string $raw): array|false {
    $raw = preg_replace('/^```(?:json)?\s*/i', '', trim($raw));
    $raw = preg_replace('/\s*```\s*$/', '', $raw);
    if (preg_match('/\{.*\}/s', $raw, $m)) $raw = $m[0];
    $raw = preg_replace_callback('/"((?:[^"\\\\]|\\\\.)*)"/s', function($m) {
        $inner = str_replace(["\r\n", "\r", "\n"], '\\n', $m[1]);
        return '"' . str_replace("\t", '\\t', $inner) . '"';
    }, $raw);
    $result = json_decode($raw, true);
    return (is_array($result) && isset($result['strands'])) ? $result : false;
}

function ensureSourceDocsTable(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS la_source_docs (
        id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        learning_area_id INT UNSIGNED NOT NULL,
        content          LONGTEXT NOT NULL DEFAULT '',
        parsed_doc       LONGTEXT DEFAULT NULL,
        updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_la (learning_area_id),
        FOREIGN KEY (learning_area_id) REFERENCES learning_areas(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    try {
        $col = $pdo->query(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='la_source_docs' AND COLUMN_NAME='parsed_doc'"
        )->fetchColumn();
        if (!$col) $pdo->exec("ALTER TABLE la_source_docs ADD COLUMN parsed_doc LONGTEXT DEFAULT NULL");
    } catch (PDOException $e) {}
}

function chunkTextPhp(string $text, int $size = 4500, int $overlap = 200): array {
    $text  = trim($text);
    $total = mb_strlen($text);
    if ($total <= $size) return [$text];
    $chunks = []; $start = 0;
    while ($start < $total) {
        $end = $start + $size;
        if ($end >= $total) { $chunks[] = mb_substr($text, $start); break; }
        $seg = mb_substr($text, $start, $size);
        $dbl = mb_strrpos($seg, "\n\n");
        $sgl = mb_strrpos($seg, "\n");
        if ($dbl !== false && $dbl > $size * 0.4) $end = $start + $dbl;
        elseif ($sgl !== false && $sgl > $size * 0.4) $end = $start + $sgl;
        $chunks[] = mb_substr($text, $start, $end - $start);
        $start = max($end - $overlap, $start + 1);
    }
    return $chunks;
}

function mergeStructuredChunks(array $chunks): array {
    $strandMap = [];
    foreach ($chunks as $chunk) {
        foreach ($chunk['strands'] ?? [] as $st) {
            // Key strands by their numeric code only (normalised)
            $stCode = trim((string)($st['code'] ?? ''));
            if ($stCode === '') continue;
            if (!isset($strandMap[$stCode])) {
                $strandMap[$stCode] = ['code' => $stCode, 'name' => trim((string)($st['name'] ?? '')), 'sub_strands' => []];
            }
            // Fill blank strand name from later chunks
            if ($strandMap[$stCode]['name'] === '' && trim((string)($st['name'] ?? '')) !== '') {
                $strandMap[$stCode]['name'] = trim((string)$st['name']);
            }
            foreach ($st['sub_strands'] ?? [] as $ss) {
                // Key sub-strands by numeric code ONLY — prevents "5.2 Production Unit" vs "5.2 production unit" duplicates
                $ssCode = trim((string)($ss['code'] ?? ''));
                if ($ssCode === '') $ssCode = 'nocode_' . trim((string)($ss['name'] ?? ''));
                if (!isset($strandMap[$stCode]['sub_strands'][$ssCode])) {
                    $strandMap[$stCode]['sub_strands'][$ssCode] = $ss;
                    // Ensure name is set
                    if (empty($strandMap[$stCode]['sub_strands'][$ssCode]['name'])) {
                        $strandMap[$stCode]['sub_strands'][$ssCode]['name'] = trim((string)($ss['name'] ?? ''));
                    }
                } else {
                    // Fill blank name from later chunk
                    if (empty($strandMap[$stCode]['sub_strands'][$ssCode]['name']) && !empty($ss['name'])) {
                        $strandMap[$stCode]['sub_strands'][$ssCode]['name'] = trim((string)$ss['name']);
                    }
                    // Merge non-empty fields from later chunks
                    foreach (['key_inquiry_questions','specific_learning_outcomes','learning_experiences',
                              'core_competencies','values_and_attitudes','pertinent_contemporary_issues',
                              'links_to_other_learning_areas','learning_resources','assessment'] as $f) {
                        if (empty($strandMap[$stCode]['sub_strands'][$ssCode][$f]) && !empty($ss[$f])) {
                            $strandMap[$stCode]['sub_strands'][$ssCode][$f] = $ss[$f];
                        }
                    }
                }
            }
        }
    }
    // Sort strands numerically
    uksort($strandMap, fn($a,$b) => (float)$a <=> (float)$b);
    $result = ['meta' => ['parsed_at' => date('c'), 'version' => 1], 'strands' => []];
    foreach ($strandMap as $st) {
        // Sort sub-strands numerically by code
        uksort($st['sub_strands'], fn($a,$b) => (float)$a <=> (float)$b);
        $st['sub_strands'] = array_values($st['sub_strands']);
        $result['strands'][] = $st;
    }
    return $result;
}

/**
 * Re-home sub-strands to correct strands purely by numeric code prefix.
 * Sub-strand code "X.Y" always belongs to strand code "X.0".
 * Also infers missing strand names from sub-strand context.
 */
function fixStrandAssignments(array $structured): array {
    // Build a code → strand index map
    $strandByCode = [];
    foreach ($structured['strands'] as $idx => $st) {
        $code = trim((string)($st['code'] ?? ''));
        if ($code !== '') $strandByCode[$code] = $idx;
    }

    // For each sub-strand, check it lives in the right strand
    $displaced = []; // [correct_strand_code => [sub_strands]]
    $newStrands = $structured['strands'];

    foreach ($newStrands as $sIdx => $st) {
        $stCode = trim((string)($st['code'] ?? ''));
        $keep   = [];
        foreach ($st['sub_strands'] as $ss) {
            $ssCode = trim((string)($ss['code'] ?? ''));
            if ($ssCode === '') { $keep[] = $ss; continue; }
            // Extract prefix: "1.3" → "1", "4.2" → "4"
            $prefix = (string)(int)explode('.', $ssCode)[0];
            $expectedStrandCode = $prefix . '.0';
            if ($stCode === $expectedStrandCode || $stCode === $prefix) {
                $keep[] = $ss; // correct home
            } else {
                $displaced[$expectedStrandCode][] = $ss; // needs re-homing
            }
        }
        $newStrands[$sIdx]['sub_strands'] = $keep;
    }

    // Place displaced sub-strands into correct strands (create strand if missing)
    foreach ($displaced as $targetCode => $subStrands) {
        $found = false;
        foreach ($newStrands as $sIdx => $st) {
            $c = trim((string)($st['code'] ?? ''));
            $prefix = rtrim($targetCode, '.0');
            if ($c === $targetCode || $c === $prefix) {
                $newStrands[$sIdx]['sub_strands'] = array_merge($newStrands[$sIdx]['sub_strands'], $subStrands);
                $found = true; break;
            }
        }
        if (!$found) {
            // Create a new strand placeholder
            $newStrands[] = ['code' => $targetCode, 'name' => '', 'sub_strands' => $subStrands];
        }
    }

    // Remove strands that ended up empty (were just wrong homes)
    $newStrands = array_values(array_filter($newStrands, fn($s) => !empty($s['sub_strands'])));

    // Sort strands by numeric code
    usort($newStrands, function($a, $b) {
        $ca = (float)($a['code'] ?? 0);
        $cb = (float)($b['code'] ?? 0);
        return $ca <=> $cb;
    });

    // Sort sub-strands within each strand by numeric code
    foreach ($newStrands as &$st) {
        usort($st['sub_strands'], function($a, $b) {
            $ca = (float)($a['code'] ?? 0);
            $cb = (float)($b['code'] ?? 0);
            return $ca <=> $cb;
        });
    }
    unset($st);

    $structured['strands'] = $newStrands;
    return $structured;
}

// ══════════════════════════════════════════════════════════════════════════
// TYPE: suggest
// ══════════════════════════════════════════════════════════════════════════
if ($type === 'suggest') {

    $learningArea = cleanStr($body['learning_area'] ?? '', 100);
    $grade        = cleanStr($body['grade']         ?? '', 60);
    $strand       = cleanStr($body['strand']        ?? '', 200);
    $subStrand    = cleanStr($body['sub_strand']    ?? '', 200);
    $recordId     = isset($body['record_id']) ? (int)$body['record_id'] : 0;
    $sloSow       = cleanStr($body['slo_sow']       ?? '', 1000);
    $leSow        = cleanStr($body['le_sow']        ?? '', 1000);

    if ($strand === '' || $subStrand === '') {
        jsonOut(['ok' => false, 'error' => 'Strand and Sub-Strand are required.']);
    }

    $laId    = isset($body['learning_area_id']) ? (int)$body['learning_area_id'] : 0;
    $metaCtx = '';
    $sourceDocCtx = '';
    if ($laId > 0) {
        $stmt = $pdo->prepare(
            "SELECT key_inquiry_qs, core_competencies, values_attit, pcis,
                    links_to_other_areas, resources, assessment
             FROM sub_strand_meta
             WHERE learning_area_id = :la AND strand = :s AND sub_strand = :ss LIMIT 1"
        );
        $stmt->execute([':la' => $laId, ':s' => $strand, ':ss' => $subStrand]);
        $meta = $stmt->fetch();
        if ($meta) {
            $parts = [];
            if (!empty($meta['key_inquiry_qs']))    $parts[] = "Sub-strand Key Inquiry Qs: " . $meta['key_inquiry_qs'];
            if (!empty($meta['core_competencies'])) $parts[] = "Core Competencies: " . $meta['core_competencies'];
            if (!empty($meta['resources']))         $parts[] = "Suggested resources: " . $meta['resources'];
            if (!empty($meta['assessment']))        $parts[] = "Suggested assessment: " . $meta['assessment'];
            if ($parts) $metaCtx = "\nCURRICULUM DESIGN CONTEXT:\n" . implode("\n", $parts);
        }
        // Load saved source document for additional context
        try {
            $sdStmt = $pdo->prepare(
                "SELECT content FROM la_source_docs WHERE learning_area_id = :la LIMIT 1"
            );
            $sdStmt->execute([':la' => $laId]);
            $sdContent = $sdStmt->fetchColumn();
            if ($sdContent) {
                // Limit to 3000 chars to avoid exceeding token budget
                $sdSnip = mb_substr(trim((string)$sdContent), 0, 3000);
                $sourceDocCtx = "\n\nSOURCE CURRICULUM DOCUMENT (for reference):\n" . $sdSnip;
            }
        } catch (PDOException $e) { /* table may not exist yet */ }
    }

    $prompt = <<<PROMPT
You are an expert CBC (Competency Based Curriculum) teacher support assistant for Kenyan schools.
Generate lesson-level suggestions for the following SOW lesson entry.

SCHOOL CONTEXT: A typical rural/village public school in Kenya.
- Chalkboard, chalk, and basic stationery
- Learners' textbooks (CBC approved) and exercise books
- Locally available natural materials (soil, stones, leaves, water, sand, sticks, containers)
- Limited or no computer lab; teacher may have a smartphone/tablet

LESSON DETAILS:
Grade/Class: {$grade}
Learning Area: {$learningArea}
Strand: {$strand}
Sub-Strand: {$subStrand}
Specific Learning Outcome (SOW): {$sloSow}
Learning Experience (SOW): {$leSow}
{$metaCtx}{$sourceDocCtx}

TASK: Return ONLY valid JSON (no markdown, no code fences, no explanation) with exactly these three keys:

{
  "kiq": "One clear, open-ended question that drives this specific lesson — not the whole sub-strand. No bullet points, just one question.",
  "resources": "A concise bulleted list of 3–5 resources for this exact lesson in a village school. Always include 'Learners textbook'. Add only the most relevant physical/local resources that directly match the lesson activity. Separate items with newlines starting with * .",
  "assessment": "1–2 CBC-appropriate assessment methods for this exact lesson. Choose from: oral questions, written exercise, observation, practical work, checklist, peer assessment, self-assessment, portfolio, rubrics, project. Separate with newlines starting with * ."
}
PROMPT;

    $text = aiCallWithFallback($pdo, $prompt, 600);
    if ($text === false) {
        jsonOut(['ok' => false, 'error' => $lastAiError ?: 'AI request failed.']);
    }

    $result = parseAiJson($text);
    if (!$result || !isset($result['kiq'])) {
        jsonOut(['ok' => false, 'error' => 'Could not parse AI response. Raw: ' . mb_substr($text, 0, 300)]);
    }

    $kiq        = aiStr($result['kiq']        ?? '');
    $resources  = aiStr($result['resources']  ?? '');
    $assessment = aiStr($result['assessment'] ?? '');

    $savedToDb = false;
    if ($recordId > 0) {
        try {
            $upd = $pdo->prepare(
                "UPDATE scheme_of_work
                 SET key_inquiry = :kiq, resources = :res, assessment = :ass
                 WHERE id = :id"
            );
            $upd->execute([':kiq' => $kiq, ':res' => $resources, ':ass' => $assessment, ':id' => $recordId]);
            $savedToDb = $upd->rowCount() > 0;
        } catch (PDOException $e) {}
    }

    jsonOut([
        'ok'         => true,
        'kiq'        => $kiq,
        'resources'  => $resources,
        'assessment' => $assessment,
        'saved'      => $savedToDb,
    ]);
}

// ══════════════════════════════════════════════════════════════════════════
// TYPE: lesson_plan
// ══════════════════════════════════════════════════════════════════════════
if ($type === 'lesson_plan') {

    $sowId = isset($body['sow_id']) ? (int)$body['sow_id'] : 0;
    if ($sowId < 1) {
        jsonOut(['ok' => false, 'error' => 'sow_id required.']);
    }

    $stmt = $pdo->prepare(
        "SELECT s.*, la.name AS la_name, la.id AS la_id, g.name AS grade_name
         FROM scheme_of_work s
         JOIN learning_areas la ON la.id = s.learning_area_id
         JOIN grades g ON g.id = la.grade_id
         WHERE s.id = :id"
    );
    $stmt->execute([':id' => $sowId]);
    $sow = $stmt->fetch();
    if (!$sow) {
        jsonOut(['ok' => false, 'error' => 'SOW record not found.']);
    }

    $metaStmt = $pdo->prepare(
        "SELECT core_competencies, values_attit, pcis, key_inquiry_qs
         FROM sub_strand_meta
         WHERE learning_area_id = :la AND strand = :s AND sub_strand = :ss LIMIT 1"
    );
    $metaStmt->execute([':la' => $sow['la_id'], ':s' => $sow['strand'], ':ss' => $sow['sub_strand']]);
    $meta = $metaStmt->fetch() ?: [];

    $slo1  = cleanStr($body['slo1']  ?? $sow['slo_sow'] ?? '', 600);
    $slo2  = cleanStr($body['slo2']  ?? '', 400);
    $slo3  = cleanStr($body['slo3']  ?? '', 400);
    $step1 = cleanStr($body['step1'] ?? $sow['le_sow']  ?? '', 600);
    $step2 = cleanStr($body['step2'] ?? '', 400);
    $step3 = cleanStr($body['step3'] ?? '', 400);
    $kiq   = cleanStr($body['key_inquiry_question'] ?? $sow['key_inquiry'] ?? ($meta['key_inquiry_qs'] ?? ''), 400);

    $coreComp = cleanStr($meta['core_competencies'] ?? '', 600);
    $pcis     = cleanStr($meta['pcis']              ?? '', 500);
    $values   = cleanStr($meta['values_attit']      ?? '', 400);

    function listBlock(string ...$items): string {
        $out = []; $n = 1;
        foreach ($items as $item) {
            $item = trim($item);
            if ($item !== '') $out[] = "$n. $item";
            $n++;
        }
        return $out ? implode("\n", $out) : '(not specified)';
    }

    $sloBlock  = listBlock($slo1, $slo2, $slo3);
    $stepBlock = listBlock($step1, $step2, $step3);

    $metaCtxLines = [];
    if ($coreComp) $metaCtxLines[] = "Core Competencies: $coreComp";
    if ($pcis)     $metaCtxLines[] = "PCIs: $pcis";
    if ($values)   $metaCtxLines[] = "Values: $values";
    $metaCtx = $metaCtxLines ? "\n" . implode("\n", $metaCtxLines) : '';

    // Load saved source document for richer context
    $lpSourceDocCtx = '';
    try {
        $sdSt = $pdo->prepare(
            "SELECT content FROM la_source_docs WHERE learning_area_id = :la LIMIT 1"
        );
        $sdSt->execute([':la' => $sow['la_id']]);
        $sdContent = $sdSt->fetchColumn();
        if ($sdContent) {
            $sdSnip = mb_substr(trim((string)$sdContent), 0, 3000);
            $lpSourceDocCtx = "\n\nSOURCE CURRICULUM DOCUMENT (for reference):\n" . $sdSnip;
        }
    } catch (PDOException $e) { /* table may not exist yet */ }

    $prompt = <<<PROMPT
You are an expert CBC (Competency Based Curriculum) lesson plan writer for Kenyan schools.
Generate lesson-plan sections for the lesson described below.

SCHOOL CONTEXT: A typical rural/village public school in Kenya.
- Chalkboard, chalk, basic stationery
- Learners' CBC-approved textbooks and exercise books
- Locally available materials (soil, stones, leaves, water, sand, sticks, containers)
- Limited electricity; teacher may have a smartphone or tablet but no projector

LESSON DETAILS:
Grade: {$sow['grade_name']}
Learning Area: {$sow['la_name']}
Strand: {$sow['strand']}
Sub-Strand: {$sow['sub_strand']}
Week: {$sow['week']} | Lesson: {$sow['lesson']}
Key Inquiry Question: {$kiq}
{$metaCtx}{$lpSourceDocCtx}

SPECIFIC LEARNING OUTCOMES (by end of this lesson, learner should be able to):
{$sloBlock}

LESSON DEVELOPMENT STEPS (b. Lesson body — what teacher/learners do):
{$stepBlock}

TASK: Return ONLY valid JSON (no markdown, no code fences, no explanation text) with exactly these seven keys:

{
  "introduction": "...",
  "conclusion": "...",
  "reflection": "...",
  "extended_activity": "...",
  "core_competencies": "...",
  "pcis": "...",
  "values": "..."
}

LESSON STRUCTURE CONTEXT:
Section (b) "Lesson Development Steps" already covers the main teaching content.
You are writing sections (a), (c), (d), and (e) only — do NOT repeat the lesson body.

GUIDELINES — Each section must be specific to THIS lesson. Do NOT write generic or vague content.

introduction (a. Introduction / Getting Started) — 4–5 sentences, 60–80 words:
  Follow exactly these THREE steps in order:

  STEP 1 — RECAP (1 sentence): Mention the general topic of the previous lesson with a simple
  open question to learners. Keep it brief and general — the teacher will adapt it themselves.
  e.g. "Recap of last lesson: [general prior topic]. Who can remind us what we learned?"

  STEP 2 — LESSON TITLE (1 sentence): State today's lesson title, derived from the SPECIFIC
  LEARNING OUTCOMES listed above (not the sub-strand name). Keep the title short and clear.
  e.g. "Today's lesson is: [title drawn from the SLOs]."

  STEP 3 — SCENARIO (2–3 sentences): A simple, relatable real-life scenario relevant to the
  lesson topic to capture learner attention. End with one open question.
  Keep it general enough that any teacher can use it without modification.

  Write in a simple, clean style. Do NOT over-specify details like exact numbers, names of
  people, or classroom actions — the teacher will fill those in themselves.

conclusion (c. Conclusion) — 5–6 sentences, 60–80 words:
  This section CLOSES the lesson. Follow these THREE steps in order:

  STEP 1 — Q&A REVIEW (2 sentences): Ask 2 simple, general questions to get learners to
  recall what was covered in the lesson. Keep questions open and brief.
  e.g. "Who can tell us what we learned today? Can someone give an example?"

  STEP 2 — KEY SUMMARY (1–2 sentences): Summarise the lesson using 2–3 key points or terms
  drawn from the SLOs. Short and factual.
  e.g. "Today we learned that [key point 1] and [key point 2]."

  STEP 3 — EXTENDED ACTIVITY MENTION (1 sentence): Refer learners to the extended activity.
  e.g. "As you go home, remember to complete the extended activity to practise what you have learned today."

  Write simply and generally. Do NOT over-specify — keep it easy for any teacher to use.

reflection (a single string, NEVER a JSON array):
  Exactly 3 bullet points joined with literal \n. Format MUST be:
  "* Question one?\n* Question two?\n* Question three?"
  Each question must be specific to THIS lesson's content — name the actual concept/activity.
  Max 14 words per bullet. Do NOT use a JSON array — reflection must be a JSON string.

extended_activity (2–3 sentences, 30–50 words):
  A specific, practical take-home task directly linked to TODAY's SLOs.
  Must use things found at home (no school resources). Name the actual concept/skill.

core_competencies (max 20 words per item):
  Pick only the competencies that apply to THIS lesson. For each, write:
  "[Competency name] - [one short phrase explaining how it applies to this specific lesson]"
  Separate multiple items with a comma. Keep each description under 8 words.
  e.g. "Critical thinking - learners identify place value of digits, Communication - learners discuss answers in pairs"

pcis (max 20 words per item):
  Pick only the PCIs that apply to THIS lesson. For each, write:
  "[PCI name] - [one short phrase explaining how it applies to this specific lesson]"
  Separate multiple items with a comma. Keep each description under 8 words.
  e.g. "Social cohesion - learners work together in groups"

values (max 20 words per item):
  Pick only the values that apply to THIS lesson. For each, write:
  "[Value name] - [one short phrase explaining how it applies to this specific lesson]"
  Separate multiple items with a comma. Keep each description under 8 words.
  e.g. "Unity - learners collaborate to solve number problems"
PROMPT;

    $text = aiCallWithFallback($pdo, $prompt, 1300);
    if ($text === false) {
        jsonOut(['ok' => false, 'error' => $lastAiError ?: 'AI request failed.']);
    }

    $result = parseAiJson($text);
    if (!$result || !isset($result['introduction'])) {
        jsonOut(['ok' => false, 'error' => 'Could not parse AI response. Raw: ' . mb_substr($text, 0, 500)]);
    }

    $introduction     = aiStr($result['introduction']      ?? '');
    $conclusion       = aiStr($result['conclusion']        ?? '');
    $reflection       = aiStr($result['reflection']        ?? '');
    $extendedActivity = aiStr($result['extended_activity'] ?? '');
    $aiCoreComp       = aiStr($result['core_competencies'] ?? '');
    $aiPcis           = aiStr($result['pcis']              ?? '');
    $aiValues         = aiStr($result['values']            ?? '');

    // ── UPSERT lesson_plans ────────────────────────────────────────────────
    $savedToDb = false;

    function parseBulletsLp(?string $v): array {
        $v = preg_replace('/^By the end of the lesson[^:]*:\s*/si', '', trim($v ?? ''));
        $v = preg_replace('/^Learner is guided to:\s*/si', '', $v);
        $items = [];
        foreach (explode("\n", $v) as $line) {
            $line = trim($line);
            if ($line === '') continue;
            $items[] = str_starts_with($line, '* ') ? ltrim(substr($line, 2)) : $line;
        }
        return $items;
    }

    $sloItems = parseBulletsLp($sow['slo_sow'] ?? '');
    $leItems  = parseBulletsLp($sow['le_sow']  ?? '');

    $defMeta = [];
    try {
        $ms = $pdo->prepare(
            "SELECT core_competencies, values_attit, pcis, key_inquiry_qs, resources
             FROM sub_strand_meta
             WHERE learning_area_id=:la AND strand=:s AND sub_strand=:ss LIMIT 1"
        );
        $ms->execute([':la' => $sow['la_id'], ':s' => $sow['strand'], ':ss' => $sow['sub_strand']]);
        $defMeta = $ms->fetch() ?: [];
    } catch (PDOException $e) {}

    $defSchool  = getSetting($pdo, 'school_name');
    $defTeacher = getSetting($pdo, 'teacher_name');
    $defKiq     = trim($sow['key_inquiry'] ?? ($defMeta['key_inquiry_qs'] ?? ''));
    $defRes     = trim($sow['resources']   ?? ($defMeta['resources']      ?? ''));
    $defCc      = $defMeta['core_competencies'] ?? '';
    $defPcis    = $defMeta['pcis']              ?? '';
    $defValues  = $defMeta['values_attit']      ?? '';

    try {
        $upsert = $pdo->prepare(
            "INSERT INTO lesson_plans
                (sow_id, grade, learning_area, strand, sub_strand,
                 school_name, teacher_name, duration,
                 slo1, slo2, slo3,
                 key_inquiry_question, core_competencies, pcis, values_attit, resources,
                 step1, step2, step3,
                 introduction, conclusion, reflection, extended_activity)
             VALUES
                (:sow_id, :grade, :la, :strand, :ss,
                 :school, :teacher, :duration,
                 :slo1, :slo2, :slo3,
                 :kiq, :cc, :pcis, :val, :res,
                 :step1, :step2, :step3,
                 :intro, :conc, :refl, :ext)
             ON DUPLICATE KEY UPDATE
                 introduction      = VALUES(introduction),
                 conclusion        = VALUES(conclusion),
                 reflection        = VALUES(reflection),
                 extended_activity = VALUES(extended_activity),
                 core_competencies = VALUES(core_competencies),
                 pcis              = VALUES(pcis),
                 values_attit      = VALUES(values_attit)"
        );
        $upsert->execute([
            ':sow_id'  => $sowId,
            ':grade'   => $sow['grade_name'],
            ':la'      => $sow['la_name'],
            ':strand'  => $sow['strand'],
            ':ss'      => $sow['sub_strand'],
            ':school'  => $defSchool,
            ':teacher' => $defTeacher,
            ':duration'=> $sow['lesson_duration'] ?? '',
            ':slo1'    => $sloItems[0] ?? '',
            ':slo2'    => $sloItems[1] ?? '',
            ':slo3'    => $sloItems[2] ?? '',
            ':kiq'     => $defKiq,
            ':cc'      => $aiCoreComp !== '' ? $aiCoreComp : $defCc,
            ':pcis'    => $aiPcis     !== '' ? $aiPcis     : $defPcis,
            ':val'     => $aiValues   !== '' ? $aiValues   : $defValues,
            ':res'     => $defRes,
            ':step1'   => $leItems[0] ?? '',
            ':step2'   => $leItems[1] ?? '',
            ':step3'   => $leItems[2] ?? '',
            ':intro'   => $introduction,
            ':conc'    => $conclusion,
            ':refl'    => $reflection,
            ':ext'     => $extendedActivity,
        ]);
        $savedToDb = true;
    } catch (PDOException $e) {}

    jsonOut([
        'ok'               => true,
        'introduction'     => $introduction,
        'conclusion'       => $conclusion,
        'reflection'       => $reflection,
        'extended_activity'=> $extendedActivity,
        'core_competencies'=> $aiCoreComp,
        'pcis'             => $aiPcis,
        'values'           => $aiValues,
        'saved'            => $savedToDb,
    ]);
}

// ══════════════════════════════════════════════════════════════════════════
// TYPE: extract  — parse raw curriculum document text → structured JSON rows
// ══════════════════════════════════════════════════════════════════════════
if ($type === 'extract') {

    $text = cleanStr($body['text'] ?? '', 5000);
    if ($text === '') {
        jsonOut(['ok' => false, 'error' => 'No text provided.']);
    }

    // Separate JSON-array parser that handles both arrays and single objects
    function parseAiJsonArray(string $raw): array|false {
        $raw = preg_replace('/^```(?:json)?\s*/i', '', trim($raw));
        $raw = preg_replace('/\s*```\s*$/', '', $raw);
        if (preg_match('/\[.*\]/s', $raw, $m))       $raw = $m[0];
        elseif (preg_match('/\{.*\}/s', $raw, $m))   $raw = '[' . $m[0] . ']';
        $raw = preg_replace_callback('/"((?:[^"\\\\]|\\\\.)*)"/s', function($m) {
            $inner = str_replace(["\r\n", "\r", "\n"], '\\n', $m[1]);
            return '"' . str_replace("\t", '\\t', $inner) . '"';
        }, $raw);
        $result = json_decode($raw, true);
        return (is_array($result) && isset($result[0])) ? $result : false;
    }

    $prompt = <<<PROMPT
You are a curriculum data extraction and OCR-correction expert for Kenyan CBC (Competency-Based Curriculum) documents.

The text below may have been produced by OCR scanning and can contain:
- Garbled or broken words (e.g. "cornpetency", "leamlng", "lnquiry")
- Weird/garbage characters (e.g. "â€™", "â€œ", "Ã©", lone punctuation like "|" or "~" mid-sentence)
- Inconsistent capitalisation, missing spaces, run-together words
- Spelling mistakes and grammatical errors
- Broken numbering or bullet formatting (e.g. "l.l" instead of "1.1")
- Line-break artifacts where a sentence was split across lines

STEP 1 — CLEAN: As you read each field, silently correct all of the above so the output contains proper, fluent English.
STEP 2 — EXTRACT: Pull the curriculum design data and return it as a JSON array.

Return ONLY a valid JSON array — no markdown, no code fences, no explanation.
Each element represents ONE sub-strand and must have these exact keys:

  "strand"               - strand name (e.g. "1.0 Numbers") — correct OCR errors in the name
  "sub_strand"           - sub-strand name (e.g. "1.1 Whole Numbers") — correct OCR errors
  "slo_cd"               - Specific Learning Outcomes (Curriculum Design) — each SLO on its own line, separated by \n (e.g. "By the end of ..., the learner should be able to:\n* identify ...\n* describe ...\n* demonstrate ...")
  "slo_sow"              - Specific Learning Outcomes (Scheme of Work version; same as slo_cd if no separate version) — each SLO on its own line separated by \n
  "le_cd"                - Learning Experiences (Curriculum Design) — each activity on its own line separated by \n
  "le_sow"               - Learning Experiences (Scheme of Work version; same as le_cd if no separate version) — each activity on its own line separated by \n
  "key_inquiry_qs"       - Key Inquiry Question(s) — each question on its own line separated by \n
  "core_competencies"    - Core Competencies to be developed
  "values_attit"         - Values and Attitudes
  "pcis"                 - Pertinent and Contemporary Issues
  "links_to_other_areas" - Links to other learning areas
  "resources"            - Learning/Teaching Resources — each item on its own line separated by \n
  "assessment"           - Assessment methods — each method on its own line separated by \n

Rules:
- Every object MUST have "strand" and "sub_strand"; use "" for fields not found in this chunk
- Each SLO, Learning Experience, Key Inquiry Question, Resource, and Assessment method MUST be on its own line (\n separated) — do NOT merge them into one sentence
- Preserve the introductory stem (e.g. "By the end of the sub-strand, the learner should be able to:") on the first line, then each numbered/bulleted item on its own \n line
- Fix OCR noise in every field — output must be clean, correct English
- Do NOT invent content; only correct obvious scanning errors
- Extract whatever sub-strands are present; partial documents are fine
- Return ONLY the JSON array

TEXT:
{$text}
PROMPT;

    $raw = aiCallWithFallback($pdo, $prompt, 4000);
    if ($raw === false) {
        global $lastAiError;
        jsonOut(['ok' => false, 'error' => $lastAiError ?: 'AI request failed.']);
    }

    $rows = parseAiJsonArray($raw);
    if ($rows === false) {
        jsonOut(['ok' => false, 'error' => 'Could not parse AI response. Raw: ' . mb_substr($raw, 0, 400)]);
    }

    jsonOut(['ok' => true, 'rows' => $rows, 'count' => count($rows)]);
}

// ══════════════════════════════════════════════════════════════════════════
// TYPE: structure_doc — deep-read document into labeled hierarchical JSON
// ══════════════════════════════════════════════════════════════════════════
if ($type === 'structure_doc') {

    $laId = isset($body['learning_area_id']) ? (int)$body['learning_area_id'] : 0;
    $inputText = trim((string)($body['text'] ?? ''));

    // Load saved raw text if caller did not supply text
    if ($inputText === '' && $laId > 0) {
        try {
            ensureSourceDocsTable($pdo);
            $st = $pdo->prepare("SELECT content FROM la_source_docs WHERE learning_area_id = :la");
            $st->execute([':la' => $laId]);
            $inputText = trim((string)($st->fetchColumn() ?: ''));
        } catch (PDOException $e) {}
    }

    // Persist raw text (also acts as the saved source for future references)
    if ($inputText !== '' && $laId > 0) {
        try {
            ensureSourceDocsTable($pdo);
            $pdo->prepare(
                "INSERT INTO la_source_docs (learning_area_id, content)
                 VALUES (:la, :c) ON DUPLICATE KEY UPDATE content = VALUES(content), updated_at = NOW()"
            )->execute([':la' => $laId, ':c' => $inputText]);
        } catch (PDOException $e) {}
    }

    if ($inputText === '') {
        jsonOut(['ok' => false, 'error' => 'No text to parse. Paste a document or select a learning area with saved text.']);
    }

    $chunks       = chunkTextPhp($inputText, 4500, 200);
    $parsedChunks = [];
    $chunkErrors  = [];

    foreach ($chunks as $ci => $chunk) {
        $structPrompt = <<<PROMPT
You are a CBC (Competency-Based Curriculum) document analyzer for Kenyan schools.

Carefully read the curriculum document text below.
This text may contain OCR errors — silently correct garbled characters, spelling mistakes, wrong numbers (e.g. "l.l" → "1.1"), and formatting noise as you read.

STEP 1 — UNDERSTAND: Read the entire excerpt. Identify every strand and sub-strand present.
STEP 2 — EXTRACT: For each sub-strand, fill all fields from the text.
STEP 3 — VERIFY: Before outputting, re-read your JSON. Check:
  • No two sub-strands were accidentally merged into one
  • Each SLO is a separate array element (not one long string)
  • Each Learning Experience is a separate element
  • Strand/sub-strand codes are correct numbers (not OCR garble)
  If you find an error, fix it before outputting.

CRITICAL RULES:
1. Each sub-strand = its own object in "sub_strands" — NEVER merge two sub-strands
2. "specific_learning_outcomes":
   — Element 0 = introductory stem (e.g. "By the end of the sub-strand, the learner should be able to:")
   — Each subsequent element = ONE outcome only (one numbered/bulleted item per element)
   — Do NOT put two outcomes in one string
3. "learning_experiences": same rule — stem as element 0, then each activity as its own element
4. All other list fields: each item as its own array element
5. Use [] for fields absent from this excerpt; use "" for missing code

Return ONLY valid JSON — no markdown, no code fences, no explanation:

{
  "strands": [
    {
      "code": "1.0",
      "name": "Strand Name",
      "sub_strands": [
        {
          "code": "1.1",
          "name": "Sub-strand Name",
          "key_inquiry_questions": ["Question 1?", "Question 2?"],
          "specific_learning_outcomes": [
            "By the end of the sub-strand, the learner should be able to:",
            "identify common household hazards",
            "describe safety measures in the home"
          ],
          "learning_experiences": [
            "Learner is guided to:",
            "observe surroundings and identify hazards",
            "discuss safety rules in groups"
          ],
          "core_competencies": ["Critical thinking and problem solving"],
          "values_and_attitudes": ["Responsibility", "Respect for life"],
          "pertinent_contemporary_issues": ["Safety"],
          "links_to_other_learning_areas": ["Health Education"],
          "learning_resources": ["Learners textbook", "Photographs of home environments"],
          "assessment": ["Oral questions", "Observation checklist"]
        }
      ]
    }
  ]
}

TEXT:
{$chunk}
PROMPT;

        $raw = aiCallWithFallback($pdo, $structPrompt, 3500);
        if ($raw === false) {
            $chunkErrors[] = 'Chunk ' . ($ci + 1) . ': AI request failed.';
            continue;
        }

        $parsed = repairAndDecodeJson($raw);
        if ($parsed !== false) {
            $parsedChunks[] = $parsed;
        } else {
            $chunkErrors[] = 'Chunk ' . ($ci + 1) . ': Could not parse response.';
        }
    }

    if (empty($parsedChunks)) {
        jsonOut(['ok' => false, 'error' => 'AI could not extract any structure. ' . implode(' ', $chunkErrors)]);
    }

    $structured  = mergeStructuredChunks($parsedChunks);
    $structured  = fixStrandAssignments($structured);
    $strandCount = count($structured['strands']);
    $ssCount     = array_sum(array_map(fn($s) => count($s['sub_strands'] ?? []), $structured['strands']));

    // Save the structured (parsed) document
    if ($laId > 0 && $ssCount > 0) {
        try {
            ensureSourceDocsTable($pdo);
            $pdo->prepare(
                "UPDATE la_source_docs SET parsed_doc = :pd WHERE learning_area_id = :la"
            )->execute([':pd' => json_encode($structured, JSON_UNESCAPED_UNICODE), ':la' => $laId]);
        } catch (PDOException $e) {}
    }

    jsonOut([
        'ok'               => true,
        'structured'       => $structured,
        'strand_count'     => $strandCount,
        'sub_strand_count' => $ssCount,
        'chunks_processed' => count($parsedChunks),
        'errors'           => $chunkErrors,
    ]);
}

// ══════════════════════════════════════════════════════════════════════════
// TYPE: toc_chunk — Phase 1: extract ONLY the outline (strand/sub-strand codes+names)
// Fast and cheap — AI returns a small JSON with just the document skeleton
// ══════════════════════════════════════════════════════════════════════════
if ($type === 'toc_chunk') {
    global $lastAiError;
    $chunk = trim((string)($body['chunk'] ?? ''));
    if ($chunk === '') jsonOut(['ok' => false, 'error' => 'Empty chunk text.']);
    $skipProviders = (array)($body['skip_providers'] ?? []);

    $tocPrompt = <<<PROMPT
Read this CBC curriculum text and extract ONLY the document outline — strand codes, strand names, sub-strand codes, and sub-strand names. Do NOT extract any content (SLOs, activities, resources, questions, etc.).

RULES:
- Strand codes use format "X.0" (e.g. "1.0", "4.0", "5.0")
- Sub-strand codes use format "X.Y" (e.g. "1.1", "4.2", "5.3")
- Fix OCR errors in codes: "l.l"→"1.1", "S.2"→"5.2", "O"→"0", "l"→"1"
- A sub-strand code "X.Y" ALWAYS belongs to strand "X.0" — check this
- If this excerpt shows sub-strands but not the strand heading, still include them under the correct strand code with an empty name for the strand
- Each sub-strand gets its own entry — never combine two into one
- If an item appears twice with different capitalisation ("5.2 Production Unit" vs "5.2 production unit"), include it ONCE using the properly capitalised version

Return ONLY valid JSON, no markdown, no explanation:
{"strands":[{"code":"1.0","name":"Safety in Pre-Technical Studies","sub_strands":[{"code":"1.1","name":"Workshop Safety"}]}]}

TEXT:
{$chunk}
PROMPT;

    $raw = aiCallWithFallback($pdo, $tocPrompt, 1200, $skipProviders);
    if ($raw === false) {
        $errMsg = $lastAiError ?? 'unknown error';
        $isGroq429      = (bool)preg_match('/Groq.*429|Groq.*rate.?limit|tokens per day/i', $errMsg);
        $isDeepSeekDown = (bool)preg_match('/DeepSeek.*timed out|DeepSeek.*0 bytes|DeepSeek.*Network/i', $errMsg);
        jsonOut(['ok' => false, 'error' => 'AI request failed: ' . $errMsg,
                 'groq_rate_limited' => $isGroq429, 'deepseek_down' => $isDeepSeekDown]);
    }
    $parsed = repairAndDecodeJson($raw);
    if ($parsed === false) {
        jsonOut(['ok' => false, 'error' => 'Could not parse TOC response. Raw: ' . mb_substr($raw, 0, 200)]);
    }
    jsonOut(['ok' => true, 'toc' => $parsed]);
}

// ══════════════════════════════════════════════════════════════════════════
// TYPE: structure_chunk — parse ONE chunk of text, return structured JSON
// (Called per-chunk from JS to avoid PHP/Apache timeout on large documents)
// ══════════════════════════════════════════════════════════════════════════
if ($type === 'structure_chunk') {
    global $lastAiError;
    $chunk = trim((string)($body['chunk'] ?? ''));
    // Save raw source text if this is the first chunk and laId is set
    $laId = isset($body['learning_area_id']) ? (int)$body['learning_area_id'] : 0;
    $isFirst = (bool)($body['is_first'] ?? false);
    // Providers the caller wishes to skip (e.g. ['groq'] when Groq is rate-limited)
    $skipProviders = (array)($body['skip_providers'] ?? []);
    $fullText = trim((string)($body['full_text'] ?? ''));
    if ($isFirst && $laId > 0 && $fullText !== '') {
        try {
            ensureSourceDocsTable($pdo);
            $pdo->prepare(
                "INSERT INTO la_source_docs (learning_area_id, content)
                 VALUES (:la, :c) ON DUPLICATE KEY UPDATE content = VALUES(content), updated_at = NOW()"
            )->execute([':la' => $laId, ':c' => $fullText]);
        } catch (PDOException $e) {}
    }
    if ($chunk === '') jsonOut(['ok' => false, 'error' => 'Empty chunk text.']);

    // Build authoritative TOC context if caller supplied it (Phase 1 result)
    $tocContext = '';
    $authToc = $body['authoritative_toc'] ?? [];
    if (!empty($authToc)) {
        $lines = ["AUTHORITATIVE DOCUMENT STRUCTURE (reference — use these exact codes and names):",
                  "- Sub-strand X.Y always belongs to strand X.0",
                  "- Use these names exactly as spelled (fixes OCR capitalisation errors)",
                  "- Extract ALL content you see in the text — do not skip any sub-strand's data",
                  "- If a sub-strand appears in the text but not this list, still extract it", ""];
        foreach ($authToc as $st) {
            $lines[] = "  Strand " . ($st['code'] ?? '') . ": " . ($st['name'] ?? '');
            foreach ($st['sub_strands'] ?? [] as $ss) {
                $lines[] = "    Sub-strand " . ($ss['code'] ?? '') . ": " . ($ss['name'] ?? '');
            }
        }
        $tocContext = implode("\n", $lines) . "\n\n";
    }

    $structPrompt = $tocContext . <<<PROMPT
You are a CBC (Competency-Based Curriculum) document analyzer for Kenyan schools.

Carefully read the curriculum document text below.
This text may contain OCR errors — silently correct garbled characters, spelling mistakes, wrong numbers (e.g. "l.l" → "1.1"), and formatting noise as you read.

STEP 1 — UNDERSTAND: Read the entire excerpt. Identify every strand and sub-strand present.
STEP 2 — EXTRACT: For each sub-strand, fill all fields from the text.
STEP 3 — VERIFY: Before outputting, re-read your JSON. Check:
  • No two sub-strands were accidentally merged into one
  • Each SLO is a separate array element (not one long string)
  • Each Learning Experience is a separate element
  • Strand/sub-strand codes are correct numbers (not OCR garble)
  If you find an error, fix it before outputting.

CRITICAL RULES:
1. Each sub-strand = its own object in "sub_strands" — NEVER merge two sub-strands
2. "specific_learning_outcomes":
   — Element 0 = introductory stem (e.g. "By the end of the sub-strand, the learner should be able to:")
   — Each subsequent element = ONE outcome only (one numbered/bulleted item per element)
   — Do NOT put two outcomes in one string
3. "learning_experiences": same rule — stem as element 0, then each activity as its own element
4. All other list fields: each item as its own array element
5. Use [] for fields absent from this excerpt; use "" for missing code

Return ONLY valid JSON — no markdown, no code fences, no explanation:

{
  "strands": [
    {
      "code": "1.0",
      "name": "Strand Name",
      "sub_strands": [
        {
          "code": "1.1",
          "name": "Sub-strand Name",
          "key_inquiry_questions": ["Question 1?"],
          "specific_learning_outcomes": [
            "By the end of the sub-strand, the learner should be able to:",
            "identify common household hazards"
          ],
          "learning_experiences": [
            "Learner is guided to:",
            "observe surroundings and identify hazards"
          ],
          "core_competencies": ["Critical thinking and problem solving"],
          "values_and_attitudes": ["Responsibility"],
          "pertinent_contemporary_issues": ["Safety"],
          "links_to_other_learning_areas": ["Health Education"],
          "learning_resources": ["Learners textbook"],
          "assessment": ["Oral questions"]
        }
      ]
    }
  ]
}

TEXT:
{$chunk}
PROMPT;

    $raw = aiCallWithFallback($pdo, $structPrompt, 3500, $skipProviders);
    if ($raw === false) {
        $errMsg = $lastAiError ?? 'unknown error';
        $isGroq429       = (bool)preg_match('/Groq.*429|Groq.*rate.?limit|tokens per day/i', $errMsg);
        $isDeepSeekDown  = (bool)preg_match('/DeepSeek.*timed out|DeepSeek.*0 bytes|DeepSeek.*Network/i', $errMsg);
        jsonOut(['ok' => false, 'error' => 'AI request failed: ' . $errMsg,
                 'groq_rate_limited'   => $isGroq429,
                 'deepseek_down'       => $isDeepSeekDown]);
    }
    $parsed = repairAndDecodeJson($raw);
    if ($parsed === false) {
        jsonOut(['ok' => false, 'error' => 'Could not parse AI response as JSON. Try again.']);
    }
    jsonOut(['ok' => true, 'structured' => $parsed]);
}

// ══════════════════════════════════════════════════════════════════════════
// TYPE: finalize_structure — merge chunk results, save to DB, return counts
// ══════════════════════════════════════════════════════════════════════════
if ($type === 'finalize_structure') {
    $laId         = isset($body['learning_area_id']) ? (int)$body['learning_area_id'] : 0;
    $chunkResults = $body['chunk_results'] ?? [];

    if (empty($chunkResults)) {
        jsonOut(['ok' => false, 'error' => 'No chunk results to merge.']);
    }

    $structured  = mergeStructuredChunks($chunkResults);
    $structured  = fixStrandAssignments($structured);   // re-home any misassigned sub-strands by code
    $strandCount = count($structured['strands']);
    $ssCount     = array_sum(array_map(fn($s) => count($s['sub_strands'] ?? []), $structured['strands']));

    if ($laId > 0 && $ssCount > 0) {
        try {
            ensureSourceDocsTable($pdo);
            $pdo->prepare(
                "UPDATE la_source_docs SET parsed_doc = :pd WHERE learning_area_id = :la"
            )->execute([':pd' => json_encode($structured, JSON_UNESCAPED_UNICODE), ':la' => $laId]);
        } catch (PDOException $e) {}
    }

    jsonOut([
        'ok'               => true,
        'structured'       => $structured,
        'strand_count'     => $strandCount,
        'sub_strand_count' => $ssCount,
    ]);
}

// ══════════════════════════════════════════════════════════════════════════
// TYPE: reverify_doc — compare saved structured doc against raw text, fix errors
// ══════════════════════════════════════════════════════════════════════════
if ($type === 'reverify_doc') {

    $laId = isset($body['learning_area_id']) ? (int)$body['learning_area_id'] : 0;
    if ($laId < 1) {
        jsonOut(['ok' => false, 'error' => 'learning_area_id required.']);
    }

    $rawText       = '';
    $parsedDocJson = '';
    try {
        ensureSourceDocsTable($pdo);
        $st = $pdo->prepare("SELECT content, parsed_doc FROM la_source_docs WHERE learning_area_id = :la");
        $st->execute([':la' => $laId]);
        $row = $st->fetch();
        if ($row) {
            $rawText       = trim((string)($row['content']    ?? ''));
            $parsedDocJson = trim((string)($row['parsed_doc'] ?? ''));
        }
    } catch (PDOException $e) {
        jsonOut(['ok' => false, 'error' => 'Could not load saved document.']);
    }

    if ($parsedDocJson === '') {
        jsonOut(['ok' => false, 'error' => 'No structured document saved yet. Run "Parse & Structure" first.']);
    }

    // Truncate to fit within token budget
    $parsedSnip = mb_substr($parsedDocJson, 0, 5000);
    $rawSnip    = mb_substr($rawText, 0, 3000);

    $prompt = <<<PROMPT
You are a CBC (Competency-Based Curriculum) document verifier for Kenyan schools.

You previously extracted a structured JSON from an OCR curriculum document.
Carefully review it and fix EVERY error listed below:

1. MISSING STRAND NAMES — if a strand has only a code (e.g. "4.0") and no name, find the name in the source text and fill it in.
2. MISSING SUB-STRAND NAMES — same: fill in any blank names from context.
3. BROKEN OCR SENTENCES — OCR often splits one sentence across two lines. If a list item ends mid-thought
   (e.g. ends without punctuation and the next item starts with a lowercase letter), JOIN them into one clean sentence.
4. MULTIPLE SLOs MERGED — split any element that contains two or more outcomes into separate elements.
5. MULTIPLE LEARNING EXPERIENCES MERGED — same: one activity per element.
6. OCR NOISE — fix garbled words, wrong numbers ("l.l" → "1.1", "O" → "0"), stray characters.
7. CONTENT IN WRONG FIELD — re-assign anything clearly in the wrong bucket.
8. SUB-STRANDS MISSING ENTIRELY — check the source text; if a sub-strand section is visible but missing from JSON, add it.

Return ONLY the corrected JSON (same structure), no markdown fences, no explanation.
If nothing needs fixing, return the JSON unchanged.

CURRENT EXTRACTED JSON:
{$parsedSnip}

ORIGINAL SOURCE TEXT (partial — for reference):
{$rawSnip}
PROMPT;

    $raw = aiCallWithFallback($pdo, $prompt, 4000);
    if ($raw === false) {
        global $lastAiError;
        jsonOut(['ok' => false, 'error' => $lastAiError ?: 'AI request failed.']);
    }

    $corrected = repairAndDecodeJson($raw);
    if ($corrected === false) {
        jsonOut(['ok' => false, 'error' => 'Could not parse AI correction. Raw: ' . mb_substr($raw, 0, 300)]);
    }

    // Re-home any sub-strands still in wrong strands after AI correction
    $corrected = fixStrandAssignments($corrected);

    // Save corrected version
    try {
        $pdo->prepare(
            "UPDATE la_source_docs SET parsed_doc = :pd WHERE learning_area_id = :la"
        )->execute([':pd' => json_encode($corrected, JSON_UNESCAPED_UNICODE), ':la' => $laId]);
    } catch (PDOException $e) {}

    $ssCount = array_sum(array_map(fn($s) => count($s['sub_strands'] ?? []), $corrected['strands']));

    jsonOut([
        'ok'               => true,
        'structured'       => $corrected,
        'sub_strand_count' => $ssCount,
    ]);
}

// Unknown type
jsonOut(['ok' => false, 'error' => 'Unknown type: ' . htmlspecialchars($type)]);

