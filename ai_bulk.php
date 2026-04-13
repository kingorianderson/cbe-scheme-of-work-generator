<?php
// ai_bulk.php — Bulk AI generation of Key Inquiry Questions, Learning Resources,
//               and Assessment for all lessons in a learning area.
// Usage: ai_bulk.php?learning_area_id=N
require_once 'config.php';
$pdo = getDB();

$laId = isset($_GET['learning_area_id']) ? (int)$_GET['learning_area_id'] : 0;
if ($laId < 1) { header('Location: curriculum.php'); exit; }

$laStmt = $pdo->prepare(
    "SELECT la.*, g.name AS grade_name FROM learning_areas la
     JOIN grades g ON g.id = la.grade_id WHERE la.id = :id"
);
$laStmt->execute([':id' => $laId]);
$la = $laStmt->fetch();
if (!$la) { header('Location: curriculum.php'); exit; }

// Check AI enabled
$aiEnabled = false;
try {
    $st = $pdo->query("SELECT setting_value FROM app_settings WHERE setting_key = 'ai_enabled'");
    $aiEnabled = ($st && $st->fetchColumn() === '1');
} catch (Exception $e) {}

// Fetch all lessons for this learning area
$rows = $pdo->prepare(
    "SELECT id, week, lesson, strand, sub_strand, slo_sow, le_sow,
            key_inquiry, resources, assessment
     FROM scheme_of_work
     WHERE learning_area_id = :la
     ORDER BY week, lesson"
);
$rows->execute([':la' => $laId]);
$lessons = $rows->fetchAll();

function e(string $v): string { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Bulk AI Generate — <?= e($la['name']) ?></title>
<link rel="stylesheet" href="style.css">
<style>
  .bulk-wrap { max-width: 860px; }

  .ai-info {
    background: #f5f3ff; border: 1px solid #c4b5fd;
    border-radius: 8px; padding: 14px 18px; margin-bottom: 20px;
    font-size: 13px; color: #4c1d95;
  }
  .ai-info strong { color: #3b0764; }

  .options-bar {
    display: flex; align-items: center; gap: 16px;
    flex-wrap: wrap; margin-bottom: 18px;
    padding: 12px 16px; background: #f9fafb;
    border: 1px solid var(--border); border-radius: 8px;
    font-size: 13px;
  }
  .options-bar label { display: flex; align-items: center; gap: 6px; cursor: pointer; font-weight: 500; }

  /* Progress */
  .progress-wrap { margin-bottom: 20px; }
  .progress-bar-track {
    height: 14px; background: #e5e7eb; border-radius: 7px; overflow: hidden;
  }
  .progress-bar-fill {
    height: 100%; width: 0%; background: #6d43d9;
    border-radius: 7px; transition: width .3s ease;
  }
  .progress-label { font-size: 12px; color: var(--muted); margin-top: 5px; }

  /* Log */
  .log-wrap {
    border: 1px solid var(--border); border-radius: 8px;
    max-height: 380px; overflow-y: auto;
    font-size: 12px; font-family: Consolas, monospace;
    background: #fafafa;
  }
  .log-row {
    display: grid; grid-template-columns: 60px 60px 1fr 80px;
    gap: 8px; align-items: start;
    padding: 6px 12px; border-bottom: 1px solid #f0f0f0;
  }
  .log-row:last-child { border-bottom: none; }
  .log-row.ok   { background: #f0fdf4; }
  .log-row.skip { background: #fefce8; }
  .log-row.err  { background: #fef2f2; }
  .log-row.wait { background: #fff; color: #9ca3af; }
  .log-status { font-weight: 700; text-align: center; font-size: 11px; padding: 2px 6px; border-radius: 4px; }
  .log-status.ok   { background: #dcfce7; color: #166534; }
  .log-status.skip { background: #fef9c3; color: #713f12; }
  .log-status.err  { background: #fee2e2; color: #991b1b; }
  .log-status.wait { background: #f3f4f6; color: #6b7280; }

  .summary-box {
    margin-top: 18px; padding: 14px 18px;
    background: #f0fdf4; border: 1px solid #bbf7d0;
    border-radius: 8px; font-size: 13px; color: #166534; display: none;
  }
  .btn-start {
    background: #6d43d9; color: #fff; border: none;
    padding: 10px 28px; border-radius: 7px; font-size: 14px;
    font-weight: 700; cursor: pointer;
  }
  .btn-start:hover    { background: #5836ba; }
  .btn-start:disabled { background: #a78bda; cursor: not-allowed; }
</style>
</head>
<body>
<div class="page-wrap">
  <nav class="top-nav">
    <span class="nav-brand">CBC Scheme of Work</span>
    <ol class="breadcrumb">
      <li><a href="curriculum.php">Curriculum</a></li>
      <li><a href="curriculum.php?grade_id=<?= (int)$la['grade_id'] ?>"><?= e($la['grade_name']) ?></a></li>
      <li><a href="index.php?learning_area_id=<?= $laId ?>"><?= e($la['name']) ?></a></li>
      <li class="active">Bulk AI Generate</li>
    </ol>
  </nav>

  <header>
    <div>
      <h1>Bulk AI Generate</h1>
      <small style="color:var(--muted)"><?= e($la['grade_name']) ?> &bull; <?= e($la['name']) ?> &bull; <?= count($lessons) ?> lessons</small>
    </div>
    <a href="index.php?learning_area_id=<?= $laId ?>" class="btn btn-outline">&larr; Back to SOW</a>
  </header>

  <div class="bulk-wrap">

    <?php if (!$aiEnabled): ?>
      <div class="flash flash-err">AI is disabled. Go to <a href="settings.php">Settings</a> to enable it and enter your API key.</div>
    <?php elseif (empty($lessons)): ?>
      <div class="flash flash-err">No lessons found for this learning area. Add SOW records first.</div>
    <?php else: ?>

    <div class="ai-info">
      <strong>&#9889; Bulk AI Suggest</strong> — Claude will generate a <strong>Key Inquiry Question</strong>, <strong>Learning Resources</strong>, and <strong>Assessment</strong> for each lesson individually, tailored to the specific learning outcome and learning experience of that lesson. Results are saved directly to the database.
    </div>

    <div class="options-bar">
      <label>
        <input type="checkbox" id="opt-skip" checked>
        Skip lessons that already have AI-generated content (resources &amp; assessment filled)
      </label>
      <label>
        <input type="checkbox" id="opt-kiq">
        Also overwrite Key Inquiry Questions
      </label>
    </div>

    <div style="display:flex;gap:12px;margin-bottom:18px;align-items:center">
      <button class="btn-start" id="start-btn" onclick="startBulk()">&#9654; Start Bulk Generate</button>
      <span id="overall-status" style="font-size:13px;color:var(--muted)"></span>
    </div>

    <div class="progress-wrap" id="progress-wrap" style="display:none">
      <div class="progress-bar-track"><div class="progress-bar-fill" id="prog-fill"></div></div>
      <div class="progress-label" id="prog-label">0 / <?= count($lessons) ?></div>
    </div>

    <div class="log-wrap" id="log-wrap">
      <!-- header row -->
      <div class="log-row" style="font-weight:700;color:#374151;background:#f3f4f6;font-family:inherit">
        <span>Week</span><span>Lesson</span><span>Sub-Strand</span><span>Status</span>
      </div>
      <?php foreach ($lessons as $r): ?>
      <div class="log-row wait" id="row-<?= (int)$r['id'] ?>">
        <span><?= (int)$r['week'] ?></span>
        <span><?= (int)$r['lesson'] ?></span>
        <span><?= e($r['sub_strand']) ?></span>
        <span class="log-status wait" id="st-<?= (int)$r['id'] ?>">—</span>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="summary-box" id="summary-box"></div>

    <?php endif; ?>
  </div><!-- .bulk-wrap -->
</div>

<script>
const LESSONS = <?= json_encode(array_map(fn($r) => [
    'id'         => (int)$r['id'],
    'week'       => (int)$r['week'],
    'lesson'     => (int)$r['lesson'],
    'strand'     => $r['strand'],
    'sub_strand' => $r['sub_strand'],
    'slo_sow'    => $r['slo_sow'] ?? '',
    'le_sow'     => $r['le_sow']  ?? '',
    'has_res'    => trim($r['resources']  ?? '') !== '',
    'has_ass'    => trim($r['assessment'] ?? '') !== '',
    'has_kiq'    => trim($r['key_inquiry'] ?? '') !== '',
], $lessons)) ?>;

const LA_ID   = <?= $laId ?>;
const LA_NAME = <?= json_encode($la['name']) ?>;
const GRADE   = <?= json_encode($la['grade_name']) ?>;
const TOTAL   = LESSONS.length;

let running = false;

function setRowStatus(id, cls, text) {
    const row = document.getElementById('row-' + id);
    const st  = document.getElementById('st-'  + id);
    if (row) { row.className = 'log-row ' + cls; }
    if (st)  { st.className  = 'log-status ' + cls; st.textContent = text; }
}

async function startBulk() {
    if (running) return;
    running = true;

    const skipFilled = document.getElementById('opt-skip').checked;
    const overwriteKiq = document.getElementById('opt-kiq').checked;
    const btn = document.getElementById('start-btn');
    btn.disabled = true;
    btn.textContent = '⏳ Running…';

    document.getElementById('progress-wrap').style.display = '';
    document.getElementById('overall-status').textContent = '';

    let done = 0, saved = 0, skipped = 0, errors = 0;

    for (const lesson of LESSONS) {
        const skip = skipFilled && lesson.has_res && lesson.has_ass && (!overwriteKiq || lesson.has_kiq);
        if (skip) {
            setRowStatus(lesson.id, 'skip', 'Skipped');
            skipped++;
        } else {
            setRowStatus(lesson.id, 'wait', '⏳');
            // scroll row into view
            const el = document.getElementById('row-' + lesson.id);
            if (el) el.scrollIntoView({ block: 'nearest' });

            try {
                const res = await fetch('ai_generate.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        type:             'suggest',
                        record_id:        lesson.id,
                        learning_area_id: LA_ID,
                        learning_area:    LA_NAME,
                        grade:            GRADE,
                        strand:           lesson.strand,
                        sub_strand:       lesson.sub_strand,
                        slo_sow:          lesson.slo_sow,
                        le_sow:           lesson.le_sow,
                    }),
                });
                const data = await res.json();
                if (data.ok) {
                    setRowStatus(lesson.id, 'ok', '✓ Saved');
                    saved++;
                } else {
                    setRowStatus(lesson.id, 'err', 'Error');
                    errors++;
                    console.warn('Lesson ' + lesson.id + ':', data.error);
                }
            } catch (err) {
                setRowStatus(lesson.id, 'err', 'Error');
                errors++;
            }

            // Small delay to avoid hammering the API
            await new Promise(r => setTimeout(r, 400));
        }

        done++;
        const pct = Math.round((done / TOTAL) * 100);
        document.getElementById('prog-fill').style.width  = pct + '%';
        document.getElementById('prog-label').textContent = done + ' / ' + TOTAL + ' (' + pct + '%)';
    }

    // Done
    btn.textContent = '✓ Complete';
    document.getElementById('overall-status').textContent = 'Finished.';

    const box = document.getElementById('summary-box');
    box.style.display = '';
    box.innerHTML = `<strong>&#10003; Bulk generation complete</strong><br>
        Saved: <strong>${saved}</strong> &nbsp;|&nbsp;
        Skipped: <strong>${skipped}</strong> &nbsp;|&nbsp;
        Errors: <strong>${errors}</strong><br><br>
        <a href="index.php?learning_area_id=${LA_ID}" class="btn btn-primary">View Updated SOW &rarr;</a>`;

    running = false;
}
</script>
</body>
</html>
