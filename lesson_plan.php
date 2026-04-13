<?php
// lesson_plan.php — CBC Lesson Plan: edit form + printable A4 template
// Usage: lesson_plan.php?sow_id=N
require_once 'config.php';
$pdo = getDB();

$sowId = isset($_GET['sow_id']) ? (int)$_GET['sow_id'] : 0;
if ($sowId < 1) { header('Location: curriculum.php'); exit; }

// Load SOW + learning area + grade
$stmt = $pdo->prepare(
    "SELECT s.*, la.name AS la_name, la.id AS la_id,
            g.name AS grade_name, g.id AS grade_id, g.lesson_duration
     FROM scheme_of_work s
     JOIN learning_areas la ON la.id = s.learning_area_id
     JOIN grades g ON g.id = la.grade_id
     WHERE s.id = :id"
);
$stmt->execute([':id' => $sowId]);
$sow = $stmt->fetch();
if (!$sow) { header('Location: curriculum.php'); exit; }

$laId = (int)$sow['la_id'];

// Load sub_strand_meta
$metaStmt = $pdo->prepare(
    "SELECT * FROM sub_strand_meta
     WHERE learning_area_id = :la AND strand = :s AND sub_strand = :ss LIMIT 1"
);
$metaStmt->execute([':la' => $laId, ':s' => $sow['strand'], ':ss' => $sow['sub_strand']]);
$meta = $metaStmt->fetch() ?: [];

// Load app settings (school/teacher names)
$appSettings = [];
try {
    $rows = $pdo->query("SELECT setting_key, setting_value FROM app_settings")->fetchAll();
    foreach ($rows as $r) $appSettings[$r['setting_key']] = $r['setting_value'];
} catch (Exception $e) {}

// Load existing lesson plan
$lpStmt = $pdo->prepare("SELECT * FROM lesson_plans WHERE sow_id = :id");
$lpStmt->execute([':id' => $sowId]);
$lp = $lpStmt->fetch();

// Show AI buttons if explicitly enabled OR any API key is configured
$aiEnabled = ($appSettings['ai_enabled'] ?? '0') === '1'
    || !empty($appSettings['groq_api_key'])
    || !empty($appSettings['ai_api_key']);

// ── Parse bullet items from a seeded text block ────────────────────────────
function parseBullets(?string $v): array {
    $v = preg_replace('/^By the end of the lesson[^:]*:\s*/si', '', trim($v ?? ''));
    $v = preg_replace('/^Learner is guided to:\s*/si', '', $v);
    $lines = explode("\n", $v);
    $items = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') continue;
        $items[] = str_starts_with($line, '* ') ? ltrim(substr($line, 2)) : $line;
    }
    return $items;
}

// ── Handle POST (save) ─────────────────────────────────────────────────────
$flash = '';
$doPrint = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $keys = [
        'school_name','teacher_name','date_taught','duration','num_learners',
        'slo1','slo2','slo3',
        'key_inquiry_question','core_competencies','pcis','values_attit','resources',
        'introduction','step1','step2','step3',
        'conclusion','reflection','extended_activity',
    ];
    $f = [];
    foreach ($keys as $k) $f[$k] = trim($_POST[$k] ?? '');
    $f['num_learners'] = ($f['num_learners'] !== '' ? (int)$f['num_learners'] : null);
    $f['date_taught']  = ($f['date_taught'] !== '' ? $f['date_taught'] : null);

    if ($lp) {
        $sets = implode(', ', array_map(fn($k) => "$k = :$k", array_keys($f)));
        $pdo->prepare("UPDATE lesson_plans SET $sets WHERE sow_id = :sow_id")
            ->execute(array_merge([':sow_id' => $sowId],
                array_combine(array_map(fn($k) => ":$k", array_keys($f)), array_values($f))));
    } else {
        $ctx = [
            'sow_id'       => $sowId,
            'grade'        => $sow['grade_name'],
            'learning_area'=> $sow['la_name'],
            'strand'       => $sow['strand'],
            'sub_strand'   => $sow['sub_strand'],
        ];
        $all    = array_merge($ctx, $f);
        $cols   = implode(', ', array_keys($all));
        $params = implode(', ', array_map(fn($k) => ":$k", array_keys($all)));
        $pdo->prepare("INSERT INTO lesson_plans ($cols) VALUES ($params)")
            ->execute(array_combine(array_map(fn($k) => ":$k", array_keys($all)), array_values($all)));
    }

    $printFlag = !empty($_POST['_print']) ? '&print=1' : '';
    header('Location: lesson_plan.php?sow_id=' . $sowId . '&msg=saved' . $printFlag);
    exit;
}

// Flash
if (isset($_GET['msg']) && $_GET['msg'] === 'saved') $flash = 'Lesson plan saved.';
$doPrint = isset($_GET['print']);

// Reload lesson plan after save
$lpStmt->execute([':id' => $sowId]);
$lp = $lpStmt->fetch();

// ── Default values when no saved plan ─────────────────────────────────────
$sloItems  = parseBullets($sow['slo_sow'] ?? '');
$stepItems = parseBullets($sow['le_sow']  ?? '');

if (!$lp) {
    $lp = [
        'school_name'          => $appSettings['school_name']  ?? '',
        'teacher_name'         => $appSettings['teacher_name'] ?? '',
        'date_taught'          => '',
        'duration'             => $sow['lesson_duration'] ?? '',
        'num_learners'         => '',
        'slo1'                 => $sloItems[0]  ?? '',
        'slo2'                 => $sloItems[1]  ?? '',
        'slo3'                 => $sloItems[2]  ?? '',
        'key_inquiry_question' => trim($sow['key_inquiry'] ?? ($meta['key_inquiry_qs'] ?? '')),
        'core_competencies'    => $meta['core_competencies'] ?? '',
        'pcis'                 => $meta['pcis'] ?? '',
        'values_attit'         => $meta['values_attit'] ?? '',
        'resources'            => trim($sow['resources'] ?? ($meta['resources'] ?? '')),
        'introduction'         => '',
        'step1'                => $stepItems[0] ?? '',
        'step2'                => $stepItems[1] ?? '',
        'step3'                => $stepItems[2] ?? '',
        'conclusion'           => '',
        'reflection'           => '',
        'extended_activity'    => '',
    ];
} else {
    // Plan was saved before — back-fill any empty fields from SOW + meta
    $fallbacks = [
        'key_inquiry_question' => trim($sow['key_inquiry'] ?? ($meta['key_inquiry_qs'] ?? '')),
        'core_competencies'    => $meta['core_competencies'] ?? '',
        'pcis'                 => $meta['pcis'] ?? '',
        'values_attit'         => $meta['values_attit'] ?? '',
        'resources'            => trim($sow['resources'] ?? ($meta['resources'] ?? '')),
        'slo1'                 => $sloItems[0] ?? '',
        'slo2'                 => $sloItems[1] ?? '',
        'slo3'                 => $sloItems[2] ?? '',
        'step1'                => $stepItems[0] ?? '',
        'step2'                => $stepItems[1] ?? '',
        'step3'                => $stepItems[2] ?? '',
    ];
    foreach ($fallbacks as $key => $fallback) {
        if (trim($lp[$key] ?? '') === '' && $fallback !== '') {
            $lp[$key] = $fallback;
        }
    }
}

function e(?string $v): string { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }

// Print field: show content or dotted blank lines
function pf(?string $v, int $lines = 2): string {
    $v = trim($v ?? '');
    if ($v !== '') {
        $html   = '';
        $parts  = explode("\n", $v);
        $inList = false;
        foreach ($parts as $line) {
            $line = rtrim($line);
            if (str_starts_with($line, '* ')) {
                if (!$inList) { $html .= '<ul class="lp-list">'; $inList = true; }
                $html .= '<li>' . e(ltrim(substr($line, 2))) . '</li>';
            } else {
                if ($inList) { $html .= '</ul>'; $inList = false; }
                if ($line !== '') $html .= '<p class="lp-text">' . e($line) . '</p>';
            }
        }
        if ($inList) $html .= '</ul>';
        return '<div class="lp-fill">' . $html . '</div>';
    }
    $d = '';
    for ($i = 0; $i < $lines; $i++) $d .= '<div class="dotline"></div>';
    return $d;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Lesson Plan — Wk <?= (int)$sow['week'] ?> L<?= (int)$sow['lesson'] ?> — <?= e($sow['sub_strand']) ?></title>
<link rel="stylesheet" href="style.css">
<style>
/* ── Screen form ─────────────────────────────────────────────── */
.lp-form-wrap { max-width: 820px; }
.lp-section { margin-bottom: 28px; }
.lp-section-title {
    font-size: 11px; font-weight: 700; letter-spacing: .08em;
    text-transform: uppercase; color: var(--primary);
    border-bottom: 2px solid var(--primary);
    padding-bottom: 4px; margin-bottom: 14px;
}
.lp-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.lp-grid.thirds { grid-template-columns: 1fr 1fr 1fr; }
.lp-field { display: flex; flex-direction: column; gap: 4px; }
.lp-field.full { grid-column: 1 / -1; }
.lp-field label { font-size: 12px; font-weight: 600; color: #374151; }
.lp-field textarea, .lp-field input {
    padding: 8px 10px; border: 1px solid var(--border);
    border-radius: 5px; font-size: 13px; font-family: inherit;
    resize: vertical;
}
.lp-field textarea:focus, .lp-field input:focus {
    outline: none; border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(26,86,219,.1);
}
.lp-readonly { background: #f3f4f6; color: #6b7280; }
.lp-step-label {
    font-size: 11px; font-weight: 700; color: #fff;
    background: var(--primary); padding: 3px 10px;
    border-radius: 4px; display: inline-block; margin-bottom: 4px;
}
.btn-save-print {
    background: #6d43d9; color: #fff; border: none;
    padding: 9px 22px; border-radius: 6px; font-size: 13px;
    font-weight: 700; cursor: pointer;
}
.btn-save-print:hover { background: #5836ba; }

/* ── Print template (hidden on screen) ───────────────────────── */
.print-template { display: none; }

@media print {
    @page { size: A4 portrait; margin: 18mm 16mm; }
    body { font-family: Arial, Helvetica, sans-serif; font-size: 10pt; color: #000; }
    .screen-only { display: none !important; }
    nav, header, .flash, form { display: none !important; }
    .print-template { display: block !important; }

    /* Grid for two-column info row */
    .pt-info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0; margin-bottom: 14pt; }
    .pt-info-cell { padding: 3pt 4pt; border: 1px solid #555; font-size: 9pt; }
    .pt-info-cell strong { text-transform: uppercase; font-size: 8pt; letter-spacing:.04em; margin-right: 4pt; }
    .pt-title { font-size: 12pt; font-weight: 700; text-align: center; text-transform: uppercase; letter-spacing: .06em; margin-bottom: 10pt; }

    .pt-row { margin-bottom: 8pt; }
    .pt-label { font-weight: 700; font-size: 9.5pt; text-transform: uppercase; letter-spacing: .04em; margin-bottom: 2pt; }
    .pt-inline { display: flex; gap: 6pt; align-items: baseline; }
    .pt-inline .pt-label { min-width: 90pt; flex-shrink: 0; }

    .dotline { border-bottom: 1px dotted #333; height: 16pt; margin: 2pt 0; }
    .lp-fill p.lp-text { margin: 0 0 2pt; }
    .lp-fill ul.lp-list { margin: 0 0 2pt 14pt; padding: 0; }
    .lp-fill ul.lp-list li { margin-bottom: 1pt; }

    .pt-numbered { margin: 4pt 0 0 0; padding: 0; list-style: none; }
    .pt-numbered li { display: grid; grid-template-columns: 16pt 1fr; gap: 4pt; margin-bottom: 8pt; }
    .pt-numbered li .num { font-weight: 700; }

    .pt-lettered { margin: 4pt 0 0 0; padding: 0; list-style: none; }
    .pt-lettered > li { margin-bottom: 10pt; }
    .pt-lettered > li .let-label { font-weight: 700; font-size: 9.5pt; text-transform: uppercase; margin-bottom: 3pt; }
    .pt-lettered > li .sub-steps { margin: 3pt 0 0 12pt; list-style: none; padding: 0; }
    .pt-lettered > li .sub-steps li { display: grid; grid-template-columns: 14pt 1fr; gap: 3pt; margin-bottom: 8pt; }

    .pt-divider { border: none; border-top: 1px solid #000; margin: 10pt 0 12pt; }
}
</style>
</head>
<body>
<div class="page-wrap">

<!-- ══ SCREEN: Nav + Header ══════════════════════════════════════════════ -->
<nav class="top-nav screen-only">
  <span class="nav-brand">CBC Scheme of Work</span>
  <ol class="breadcrumb">
    <li><a href="curriculum.php">Curriculum</a></li>
    <li><a href="curriculum.php?grade_id=<?= (int)$sow['grade_id'] ?>"><?= e($sow['grade_name']) ?></a></li>
    <li><a href="index.php?learning_area_id=<?= $laId ?>"><?= e($sow['la_name']) ?></a></li>
    <li class="active">Lesson Plan</li>
  </ol>
</nav>

<header class="screen-only">
  <div>
    <h1>Lesson Plan</h1>
    <small style="color:var(--muted)">
      <?= e($sow['grade_name']) ?> &bull; <?= e($sow['la_name']) ?> &bull;
      Week <?= (int)$sow['week'] ?>, Lesson <?= (int)$sow['lesson'] ?> &bull;
      <?= e($sow['strand']) ?> &rsaquo; <?= e($sow['sub_strand']) ?>
    </small>
  </div>
  <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
    <?php if ($aiEnabled): ?>
    <button type="button" id="btn-lp-ai" onclick="lpAiGenerate()"
      style="background:#7c3aed;color:#fff;border:none;padding:7px 16px;border-radius:6px;font-size:13px;font-weight:700;cursor:pointer;letter-spacing:.03em">
      &#9889; AI Generate
    </button>
    <?php endif; ?>
    <a href="index.php?learning_area_id=<?= $laId ?>" class="btn btn-outline">&larr; Back to SOW</a>
  </div>
</header>
<?php if ($aiEnabled): ?>
<div id="lp-ai-status" class="screen-only" style="font-size:12px;padding:4px 0 2px;color:#057a55;min-height:16px"></div>
<?php endif; ?>

<?php if ($flash): ?>
  <div class="flash screen-only"><?= e($flash) ?></div>
<?php endif; ?>

<!-- ══ SCREEN: Edit Form ══════════════════════════════════════════════════ -->
<form method="post" class="lp-form-wrap screen-only">

  <!-- Header Info -->
  <div class="lp-section">
    <div class="lp-section-title">Lesson Header</div>
    <div class="lp-grid">
      <div class="lp-field">
        <label>School Name</label>
        <input type="text" name="school_name" value="<?= e($lp['school_name']) ?>">
      </div>
      <div class="lp-field">
        <label>Teacher Name</label>
        <input type="text" name="teacher_name" value="<?= e($lp['teacher_name']) ?>">
      </div>
      <div class="lp-field">
        <label>Grade / Class</label>
        <input type="text" class="lp-readonly" readonly value="<?= e($sow['grade_name']) ?>">
      </div>
      <div class="lp-field">
        <label>Learning Area</label>
        <input type="text" class="lp-readonly" readonly value="<?= e($sow['la_name']) ?>">
      </div>
      <div class="lp-field">
        <label>Date</label>
        <input type="date" name="date_taught" value="<?= e($lp['date_taught'] ?? '') ?>">
      </div>
      <div class="lp-field">
        <label>Duration (minutes)</label>
        <input type="text" name="duration" placeholder="e.g. 40" value="<?= e($lp['duration']) ?>">
      </div>
      <div class="lp-field">
        <label>No. of Learners</label>
        <input type="number" name="num_learners" min="1" value="<?= e((string)($lp['num_learners'] ?? '')) ?>">
      </div>
      <div class="lp-field">
        <label>Week / Lesson</label>
        <input type="text" class="lp-readonly" readonly value="Week <?= (int)$sow['week'] ?>, Lesson <?= (int)$sow['lesson'] ?>">
      </div>
    </div>
  </div>

  <!-- Strand & Sub-Strand (readonly context) -->
  <div class="lp-section">
    <div class="lp-section-title">Strand Information</div>
    <div class="lp-grid">
      <div class="lp-field">
        <label>Strand</label>
        <input type="text" class="lp-readonly" readonly value="<?= e($sow['strand']) ?>">
      </div>
      <div class="lp-field">
        <label>Sub-Strand</label>
        <input type="text" class="lp-readonly" readonly value="<?= e($sow['sub_strand']) ?>">
      </div>
    </div>
  </div>

  <!-- Specific Learning Outcomes -->
  <div class="lp-section">
    <div class="lp-section-title">Specific Learning Outcomes</div>
    <small style="font-size:12px;color:var(--muted);display:block;margin-bottom:10px">By the end of the lesson, the learner should be able to:</small>
    <?php foreach ([1,2,3] as $n): ?>
    <div class="lp-field" style="margin-bottom:10px">
      <span class="lp-step-label"><?= $n ?>.</span>
      <textarea name="slo<?= $n ?>" rows="2"><?= e($lp['slo'.$n]) ?></textarea>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Background Information -->
  <div class="lp-section">
    <div class="lp-section-title">Background Information</div>
    <div class="lp-field" style="margin-bottom:12px">
      <label>Key Inquiry Question</label>
      <textarea name="key_inquiry_question" rows="2"><?= e($lp['key_inquiry_question']) ?></textarea>
    </div>
    <div class="lp-grid">
      <div class="lp-field">
        <label>Core Competencies to be Developed</label>
        <textarea name="core_competencies" rows="3"><?= e($lp['core_competencies']) ?></textarea>
      </div>
      <div class="lp-field">
        <label>Links to Pertinent &amp; Contemporary Issues (PCIs)</label>
        <textarea name="pcis" rows="3"><?= e($lp['pcis']) ?></textarea>
      </div>
      <div class="lp-field">
        <label>Links to Values</label>
        <textarea name="values_attit" rows="3"><?= e($lp['values_attit']) ?></textarea>
      </div>
      <div class="lp-field">
        <label>Teaching / Learning Resources</label>
        <textarea name="resources" rows="3"><?= e($lp['resources']) ?></textarea>
      </div>
    </div>
  </div>

  <!-- Organisation of Learning -->
  <div class="lp-section">
    <div class="lp-section-title">Organisation of Learning / Learning Experiences</div>

    <div class="lp-field" style="margin-bottom:14px">
      <span class="lp-step-label">a. Introduction / Getting Started</span>
      <textarea name="introduction" rows="3"><?= e($lp['introduction']) ?></textarea>
    </div>

    <div style="margin-bottom:14px">
      <span class="lp-step-label">b. Lesson Development Steps</span>
      <?php foreach ([1,2,3] as $n): ?>
      <div class="lp-field" style="margin-top:8px;padding-left:14px">
        <label style="font-size:12px">Step <?= $n ?></label>
        <textarea name="step<?= $n ?>" rows="3"><?= e($lp['step'.$n]) ?></textarea>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="lp-field" style="margin-bottom:14px">
      <span class="lp-step-label">c. Conclusion</span>
      <textarea name="conclusion" rows="3"><?= e($lp['conclusion']) ?></textarea>
    </div>

    <div class="lp-field" style="margin-bottom:14px">
      <span class="lp-step-label">d. Reflection on the Lesson</span>
      <textarea name="reflection" rows="3"><?= e($lp['reflection']) ?></textarea>
    </div>

    <div class="lp-field">
      <span class="lp-step-label">e. Extended Activity</span>
      <textarea name="extended_activity" rows="3"><?= e($lp['extended_activity']) ?></textarea>
    </div>
  </div>

  <!-- Actions -->
  <div style="display:flex;gap:10px;margin-top:6px;flex-wrap:wrap">
    <button type="submit" class="btn btn-primary">&#128190; Save</button>
    <button type="submit" name="_print" value="1" class="btn-save-print">&#128438; Save &amp; Print</button>
    <a href="index.php?learning_area_id=<?= $laId ?>" class="btn btn-outline">Cancel</a>
  </div>

</form><!-- /form -->

<?php if ($aiEnabled): ?>
<script>
// Auto-fire when arriving via Plan button and fields are still empty
document.addEventListener('DOMContentLoaded', function () {
    const autoAi = <?= isset($_GET['auto_ai']) && $_GET['auto_ai'] === '1' ? 'true' : 'false' ?>;
    const empty  = document.querySelector('[name=introduction]').value.trim() === '';
    if (autoAi && empty) lpAiGenerate();
});

function lpAiGenerate() {
    const btn    = document.getElementById('btn-lp-ai');
    const status = document.getElementById('lp-ai-status');
    btn.disabled = true;
    btn.textContent = '\u23F3 Generating\u2026';
    status.style.color = '#6b7280';
    status.textContent = 'Asking AI\u2026 this may take a few seconds.';

    const payload = {
        sow_id: <?= $sowId ?>,
        slo1:   document.querySelector('[name=slo1]').value,
        slo2:   document.querySelector('[name=slo2]').value,
        slo3:   document.querySelector('[name=slo3]').value,
        step1:  document.querySelector('[name=step1]').value,
        step2:  document.querySelector('[name=step2]').value,
        step3:  document.querySelector('[name=step3]').value,
        key_inquiry_question: document.querySelector('[name=key_inquiry_question]').value,
    };

    payload.type = 'lesson_plan';
    fetch('ai_generate.php', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify(payload),
    })
    .then(r => r.json())
    .then(data => {
        if (!data.ok) {
            status.style.color = '#dc2626';
            status.textContent = '\u274C ' + data.error;
            return;
        }
        document.querySelector('[name=introduction]').value      = data.introduction      || '';
        document.querySelector('[name=conclusion]').value        = data.conclusion        || '';
        document.querySelector('[name=reflection]').value        = data.reflection        || '';
        document.querySelector('[name=extended_activity]').value = data.extended_activity || '';
        if (data.core_competencies) document.querySelector('[name=core_competencies]').value = data.core_competencies;
        if (data.pcis)              document.querySelector('[name=pcis]').value             = data.pcis;
        if (data.values)            document.querySelector('[name=values_attit]').value     = data.values;
        status.style.color = '#057a55';
        status.textContent = data.saved
            ? '\u2713 Generated and saved to lesson plan.'
            : '\u2713 Generated \u2014 click Save to keep.';
    })
    .catch(() => {
        status.style.color = '#dc2626';
        status.textContent = '\u274C Request failed. Check your connection.';
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '&#9889; AI Generate';
    });
}
</script>
<?php endif; ?>

<!-- ══ PRINT TEMPLATE (hidden on screen, shown when printing) ═════════════ -->
<div class="print-template">

  <div class="pt-title">Lesson Plan</div>

  <!-- Info header grid -->
  <div class="pt-info-grid">
    <div class="pt-info-cell"><strong>School:</strong> <?= e($lp['school_name']) ?></div>
    <div class="pt-info-cell"><strong>Teacher:</strong> <?= e($lp['teacher_name']) ?></div>
    <div class="pt-info-cell"><strong>Grade:</strong> <?= e($sow['grade_name']) ?></div>
    <div class="pt-info-cell"><strong>Learning Area:</strong> <?= e($sow['la_name']) ?></div>
    <div class="pt-info-cell"><strong>Date:</strong> <?= e($lp['date_taught'] ?? '') ?></div>
    <div class="pt-info-cell"><strong>Duration:</strong> <?= e($lp['duration']) ?> min</div>
    <div class="pt-info-cell"><strong>Week:</strong> <?= (int)$sow['week'] ?> &nbsp;&nbsp; <strong>Lesson:</strong> <?= (int)$sow['lesson'] ?></div>
    <div class="pt-info-cell"><strong>No. of Learners:</strong> <?= e((string)($lp['num_learners'] ?? '')) ?></div>
  </div>

  <!-- Strand / Sub-Strand -->
  <div class="pt-row pt-inline">
    <div class="pt-label">Strand:</div>
    <div style="flex:1"><?= pf($sow['strand'], 1) ?></div>
  </div>
  <div class="pt-row pt-inline">
    <div class="pt-label">Sub-Strand:</div>
    <div style="flex:1"><?= pf($sow['sub_strand'], 1) ?></div>
  </div>

  <!-- SLOs -->
  <div class="pt-row">
    <div class="pt-label">Specific Learning Outcomes:</div>
    <p style="margin:3pt 0 4pt;font-size:10pt">By the end of the lesson, the learner should be able to:</p>
    <ol class="pt-numbered">
      <?php foreach ([1,2,3] as $n): ?>
      <li>
        <span class="num"><?= $n ?>.</span>
        <div><?= pf($lp['slo'.$n], 2) ?></div>
      </li>
      <?php endforeach; ?>
    </ol>
  </div>

  <!-- Key Inquiry Question -->
  <div class="pt-row pt-inline">
    <div class="pt-label">Key Inquiry Question:</div>
    <div style="flex:1"><?= pf($lp['key_inquiry_question'], 1) ?></div>
  </div>

  <!-- Core Competencies -->
  <div class="pt-row">
    <div class="pt-label">Core Competencies to be Developed</div>
    <?= pf($lp['core_competencies'], 2) ?>
  </div>

  <!-- PCIs -->
  <div class="pt-row">
    <div class="pt-label">Links to Pertinent and Contemporary Issues (PCIs)</div>
    <?= pf($lp['pcis'], 2) ?>
  </div>

  <!-- Values -->
  <div class="pt-row">
    <div class="pt-label">Links to Values</div>
    <?= pf($lp['values_attit'], 2) ?>
  </div>

  <!-- Resources -->
  <div class="pt-row">
    <div class="pt-label">Teaching / Learning Resources</div>
    <?= pf($lp['resources'], 2) ?>
  </div>

  <hr class="pt-divider">

  <!-- Organisation of Learning -->
  <div class="pt-row">
    <div class="pt-label">Organisation of Learning / Learning Experiences</div>
    <ul class="pt-lettered">

      <li>
        <div class="let-label">a. &nbsp;Introduction / Getting Started</div>
        <?= pf($lp['introduction'], 2) ?>
      </li>

      <li>
        <div class="let-label">b. &nbsp;Lesson Development</div>
        <div style="font-size:9.5pt;font-weight:700;margin:2pt 0 4pt 12pt">STEPS</div>
        <ul class="sub-steps">
          <?php foreach ([1,2,3] as $n): ?>
          <li>
            <span class="num"><?= $n ?>.</span>
            <div><?= pf($lp['step'.$n], 3) ?></div>
          </li>
          <?php endforeach; ?>
        </ul>
      </li>

      <li>
        <div class="let-label">c. &nbsp;Conclusion</div>
        <?= pf($lp['conclusion'], 2) ?>
      </li>

      <li>
        <div class="let-label">d. &nbsp;Reflection on the Lesson</div>
        <?= pf($lp['reflection'], 2) ?>
      </li>

      <li>
        <div class="let-label">e. &nbsp;Extended Activity</div>
        <?= pf($lp['extended_activity'], 3) ?>
      </li>

    </ul>
  </div>

</div><!-- /print-template -->

</div><!-- /page-wrap -->

<?php if ($doPrint): ?>
<script>window.onload = function(){ window.print(); };</script>
<?php endif; ?>

</body>
</html>
