<?php
// import_process.php — AJAX backend for curriculum data import
// POST JSON: { learning_area_id, type:"sow"|"ssm"|"both", mapping:{field:colIndex}, rows:[[...]], options:{...} }
// Returns: { ok, sow_new, sow_updated, ssm_new, ssm_updated, skipped, errors:[] }
require_once 'config.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'error' => 'POST required']); exit;
}

$pdo  = getDB();
$body = json_decode(file_get_contents('php://input'), true);
if (!is_array($body)) {
    echo json_encode(['ok' => false, 'error' => 'Invalid JSON body']); exit;
}

$laId      = (int)($body['learning_area_id'] ?? 0);
$type      = $body['type']    ?? 'both';
$mapping   = (array)($body['mapping'] ?? []);
$rows      = (array)($body['rows']    ?? []);
$opts      = (array)($body['options'] ?? []);
$sourceDoc = trim((string)($body['source_doc'] ?? ''));

// Auto-create source document store if it doesn't exist yet
$pdo->exec(
    "CREATE TABLE IF NOT EXISTS la_source_docs (
        id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        learning_area_id INT UNSIGNED NOT NULL,
        content          LONGTEXT NOT NULL,
        updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_la (learning_area_id),
        FOREIGN KEY (learning_area_id) REFERENCES learning_areas(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);

if (!in_array($type, ['sow', 'ssm', 'both'], true)) $type = 'both';
if ($laId < 1) { echo json_encode(['ok' => false, 'error' => 'No learning area selected']); exit; }
if (empty($rows)) { echo json_encode(['ok' => false, 'error' => 'No data rows provided']); exit; }

// Verify learning area
$laQ = $pdo->prepare("SELECT id, lessons_per_week FROM learning_areas WHERE id = :id");
$laQ->execute([':id' => $laId]);
$laRec = $laQ->fetch();
if (!$laRec) { echo json_encode(['ok' => false, 'error' => 'Learning area not found']); exit; }

$lpw = max(1, (int)($laRec['lessons_per_week'] ?: 5));

// Helper: get trimmed string value from a row using the column mapping
function gv(array $row, array $map, string $field): string {
    if (!array_key_exists($field, $map) || $map[$field] === '' || $map[$field] === null) return '';
    $i = (int)$map[$field];
    return isset($row[$i]) ? trim((string)$row[$i]) : '';
}

$sowNew = 0; $sowUpd = 0;
$ssmNew = 0; $ssmUpd = 0;
$skip   = 0; $errs   = [];

// ══════════════════════════════════════════════════════════════════════════
// SCHEME OF WORK ROWS
// ══════════════════════════════════════════════════════════════════════════
if ($type === 'sow' || $type === 'both') {

    $autoWL  = !empty($opts['auto_week']);      // auto-assign week/lesson?
    $startWk = max(1, (int)($opts['start_week']   ?? 1));
    $startLs = max(1, (int)($opts['start_lesson'] ?? 1));

    // Optionally continue numbering from the last existing SOW row
    if ($autoWL && !empty($opts['continue_existing'])) {
        $q = $pdo->prepare(
            "SELECT week, lesson FROM scheme_of_work
             WHERE learning_area_id = :la ORDER BY week DESC, lesson DESC LIMIT 1"
        );
        $q->execute([':la' => $laId]);
        $last = $q->fetch();
        if ($last) {
            $startWk = (int)$last['week'];
            $startLs = (int)$last['lesson'] + 1;
            if ($startLs > $lpw) { $startWk++; $startLs = 1; }
        }
    }

    $cw = $startWk; $cl = $startLs;

    foreach ($rows as $ri => $row) {
        $rn     = $ri + 1;
        $strand = gv($row, $mapping, 'strand');
        $ss     = gv($row, $mapping, 'sub_strand');

        if ($strand === '' && $ss === '') { $skip++; continue; }
        if ($strand === '' || $ss === '') {
            $errs[] = "Row $rn: missing strand or sub-strand — skipped."; continue;
        }

        $week   = $autoWL ? $cw   : max(1, (int)gv($row, $mapping, 'week'));
        $lesson = $autoWL ? $cl   : max(1, (int)gv($row, $mapping, 'lesson'));
        $tStr   = gv($row, $mapping, 'term');
        $term   = ($tStr !== '' && ctype_digit($tStr)) ? (int)$tStr : null;

        $d = [
            ':la'   => $laId,   ':w'    => $week,  ':l'    => $lesson,
            ':s'    => $strand, ':ss'   => $ss,
            ':scd'  => gv($row, $mapping, 'slo_cd'),
            ':ssow' => gv($row, $mapping, 'slo_sow'),
            ':lc'   => gv($row, $mapping, 'le_cd'),
            ':lsow' => gv($row, $mapping, 'le_sow'),
            ':kiq'  => gv($row, $mapping, 'key_inquiry'),
            ':res'  => gv($row, $mapping, 'resources'),
            ':ass'  => gv($row, $mapping, 'assessment'),
            ':rem'  => gv($row, $mapping, 'remarks'),
            ':t'    => $term,
        ];

        $chk = $pdo->prepare(
            "SELECT id FROM scheme_of_work WHERE learning_area_id=:la AND week=:w AND lesson=:l"
        );
        $chk->execute([':la' => $laId, ':w' => $week, ':l' => $lesson]);
        $eid = $chk->fetchColumn();

        try {
            if ($eid) {
                $pdo->prepare(
                    "UPDATE scheme_of_work SET strand=:s, sub_strand=:ss, slo_cd=:scd,
                     slo_sow=:ssow, le_cd=:lc, le_sow=:lsow, key_inquiry=:kiq,
                     resources=:res, assessment=:ass, remarks=:rem, term=:t WHERE id=:id"
                )->execute([
                    ':s'    => $d[':s'],    ':ss'   => $d[':ss'],
                    ':scd'  => $d[':scd'],  ':ssow' => $d[':ssow'],
                    ':lc'   => $d[':lc'],   ':lsow' => $d[':lsow'],
                    ':kiq'  => $d[':kiq'],  ':res'  => $d[':res'],
                    ':ass'  => $d[':ass'],  ':rem'  => $d[':rem'],
                    ':t'    => $d[':t'],    ':id'   => $eid,
                ]);
                $sowUpd++;
            } else {
                $pdo->prepare(
                    "INSERT INTO scheme_of_work
                     (learning_area_id,week,lesson,strand,sub_strand,slo_cd,slo_sow,
                      le_cd,le_sow,key_inquiry,resources,assessment,remarks,term)
                     VALUES (:la,:w,:l,:s,:ss,:scd,:ssow,:lc,:lsow,:kiq,:res,:ass,:rem,:t)"
                )->execute($d);
                $sowNew++;
            }
        } catch (PDOException $e) {
            $errs[] = "Row $rn (Wk$week L$lesson): " . $e->getMessage();
        }

        if ($autoWL) {
            $cl++;
            if ($cl > $lpw) { $cl = 1; $cw++; }
        }
    }
}

// ══════════════════════════════════════════════════════════════════════════
// SUB-STRAND META (Curriculum Design)
// ══════════════════════════════════════════════════════════════════════════
if ($type === 'ssm' || $type === 'both') {

    // Deduplicate rows by strand+sub_strand — keep first non-empty value per field
    $groups = [];
    foreach ($rows as $row) {
        $strand = gv($row, $mapping, 'strand');
        $ss     = gv($row, $mapping, 'sub_strand');
        if ($strand === '' || $ss === '') continue;

        $k = $strand . '|||' . $ss;
        if (!isset($groups[$k])) {
            $groups[$k] = [
                'strand' => $strand, 'sub_strand' => $ss,
                'kiq' => '', 'cc' => '', 'va' => '', 'pci' => '', 'loa' => '', 'res' => '', 'ass' => '',
            ];
        }

        // In 'both' mode key_inquiry_qs can come from key_inquiry if not explicitly mapped
        $kiqVal = (array_key_exists('key_inquiry_qs', $mapping) && $mapping['key_inquiry_qs'] !== '')
            ? gv($row, $mapping, 'key_inquiry_qs')
            : gv($row, $mapping, 'key_inquiry');

        $vals = [
            'kiq' => $kiqVal,
            'cc'  => gv($row, $mapping, 'core_competencies'),
            'va'  => gv($row, $mapping, 'values_attit'),
            'pci' => gv($row, $mapping, 'pcis'),
            'loa' => gv($row, $mapping, 'links_to_other_areas'),
            'res' => gv($row, $mapping, 'resources'),
            'ass' => gv($row, $mapping, 'assessment'),
        ];
        foreach ($vals as $f => $v) {
            if ($v !== '' && $groups[$k][$f] === '') $groups[$k][$f] = $v;
        }
    }

    foreach ($groups as $g) {
        $chk = $pdo->prepare(
            "SELECT id FROM sub_strand_meta
             WHERE learning_area_id=:la AND strand=:s AND sub_strand=:ss"
        );
        $chk->execute([':la' => $laId, ':s' => $g['strand'], ':ss' => $g['sub_strand']]);
        $eid = $chk->fetchColumn();

        try {
            if ($eid) {
                // UPDATE: only pass the 8 params actually in that SQL
                $pdo->prepare(
                    "UPDATE sub_strand_meta SET key_inquiry_qs=:kiq, core_competencies=:cc,
                     values_attit=:va, pcis=:pci, links_to_other_areas=:loa,
                     resources=:res, assessment=:ass WHERE id=:id"
                )->execute([
                    ':kiq' => $g['kiq'], ':cc'  => $g['cc'],  ':va'  => $g['va'],
                    ':pci' => $g['pci'], ':loa' => $g['loa'], ':res' => $g['res'],
                    ':ass' => $g['ass'], ':id'  => $eid,
                ]);
                $ssmUpd++;
            } else {
                // INSERT: exactly 10 params matching the VALUES list
                $pdo->prepare(
                    "INSERT INTO sub_strand_meta
                     (learning_area_id,strand,sub_strand,key_inquiry_qs,core_competencies,
                      values_attit,pcis,links_to_other_areas,resources,assessment)
                     VALUES (:la,:s,:ss,:kiq,:cc,:va,:pci,:loa,:res,:ass)"
                )->execute([
                    ':la'  => $laId,       ':s'   => $g['strand'], ':ss'  => $g['sub_strand'],
                    ':kiq' => $g['kiq'],   ':cc'  => $g['cc'],     ':va'  => $g['va'],
                    ':pci' => $g['pci'],   ':loa' => $g['loa'],    ':res' => $g['res'],
                    ':ass' => $g['ass'],
                ]);
                $ssmNew++;
            }
        } catch (PDOException $e) {
            $errs[] = 'SSM ' . $g['sub_strand'] . ': ' . $e->getMessage();
        }
    }
}

// Save source document for future AI context if provided
if ($sourceDoc !== '') {
    try {
        $pdo->prepare(
            "INSERT INTO la_source_docs (learning_area_id, content)
             VALUES (:la, :content)
             ON DUPLICATE KEY UPDATE content = VALUES(content), updated_at = NOW()"
        )->execute([':la' => $laId, ':content' => $sourceDoc]);
    } catch (PDOException $e) { /* non-fatal */ }
}

echo json_encode([
    'ok'          => true,
    'sow_new'     => $sowNew,
    'sow_updated' => $sowUpd,
    'ssm_new'     => $ssmNew,
    'ssm_updated' => $ssmUpd,
    'skipped'     => $skip,
    'errors'      => $errs,
]);
