<?php
// term_assign.php — Assign Term 1 / 2 / 3 to strands & sub-strands
// Usage: term_assign.php?learning_area_id=N
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

// Load distinct sub-strands in order, with week range, lesson count, and current term
$groups = $pdo->prepare(
    "SELECT strand, sub_strand,
            MIN(week) AS min_week, MAX(week) AS max_week,
            COUNT(*) AS lesson_count,
            MAX(term) AS cur_term
     FROM scheme_of_work
     WHERE learning_area_id = :la
     GROUP BY strand, sub_strand
     ORDER BY MIN(week), MIN(lesson)"
);
$groups->execute([':la' => $laId]);
$groups = $groups->fetchAll();

// Get overall week range for range-assign UI
$range = $pdo->prepare(
    "SELECT MIN(week) AS min_w, MAX(week) AS max_w FROM scheme_of_work WHERE learning_area_id = :la"
);
$range->execute([':la' => $laId]);
$range = $range->fetch();
$minWeek = (int)($range['min_w'] ?? 1);
$maxWeek = (int)($range['max_w'] ?? 1);

// Load current week-range assignments per term (for pre-fill) — include lesson boundaries
$termRanges = [1 => ['from'=>'','lfrom'=>'','to'=>'','lto'=>''],
               2 => ['from'=>'','lfrom'=>'','to'=>'','lto'=>''],
               3 => ['from'=>'','lfrom'=>'','to'=>'','lto'=>'']];
foreach ([1,2,3] as $t) {
    $tr = $pdo->prepare(
        "SELECT MIN(week) AS f, MAX(week) AS tt,
                MIN(lesson) AS lf, MAX(lesson) AS lt
         FROM scheme_of_work
         WHERE learning_area_id = :la AND term = :t"
    );
    $tr->execute([':la' => $laId, ':t' => $t]);
    $tr = $tr->fetch();
    if ($tr && $tr['f']) {
        // Find the actual first/last lesson within the boundary weeks
        $first = $pdo->prepare("SELECT lesson FROM scheme_of_work WHERE learning_area_id=:la AND term=:t ORDER BY week, lesson LIMIT 1");
        $first->execute([':la'=>$laId,':t'=>$t]); $first = $first->fetch();
        $last  = $pdo->prepare("SELECT week, lesson FROM scheme_of_work WHERE learning_area_id=:la AND term=:t ORDER BY week DESC, lesson DESC LIMIT 1");
        $last->execute([':la'=>$laId,':t'=>$t]); $last = $last->fetch();
        $termRanges[$t] = [
            'from'  => (int)$tr['f'],
            'lfrom' => $first ? (int)$first['lesson'] : '',
            'to'    => $last  ? (int)$last['week']    : (int)$tr['tt'],
            'lto'   => $last  ? (int)$last['lesson']  : '',
        ];
    }
}

// Count assigned totals per term
$termCounts = [0=>0, 1=>0, 2=>0, 3=>0];
$assigned   = 0;
foreach ($groups as $g) {
    $t = (int)($g['cur_term'] ?? 0);
    if ($t >= 1 && $t <= 3) {
        $termCounts[$t] += (int)$g['lesson_count'];
        $assigned += (int)$g['lesson_count'];
    } else {
        $termCounts[0] += (int)$g['lesson_count'];
    }
}
$total = array_sum(array_column($groups, 'lesson_count'));

function e(?string $v): string { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Term Assignment — <?= e($la['name']) ?></title>
<link rel="stylesheet" href="style.css">
<style>
  /* ── Page layout ──────────────────────────────────────────────────── */
  .ta-section {
    background: #fff;
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 24px 28px;
    margin-bottom: 24px;
    max-width: 900px;
  }
  .ta-section h2 {
    font-size: 14pt;
    font-weight: 700;
    margin-bottom: 6px;
  }
  .ta-section .desc {
    font-size: 11pt;
    color: var(--muted);
    margin-bottom: 20px;
    line-height: 1.55;
  }

  /* ── Progress bar ─────────────────────────────────────────────────── */
  .progress-bar { display:flex; height:12px; border-radius:6px; overflow:hidden; background:#e5e7eb; margin-bottom:8px; }
  .progress-bar .seg { transition: width .4s; }
  .bar-t1 { background: #3b82f6; }
  .bar-t2 { background: #10b981; }
  .bar-t3 { background: #f59e0b; }
  .bar-none { background: #e5e7eb; }
  .progress-legend { display:flex; gap:16px; font-size:10pt; color:#374151; flex-wrap:wrap; }
  .progress-legend span { display:flex; align-items:center; gap:5px; }
  .leg-dot { width:10px;height:10px;border-radius:2px;display:inline-block; }

  /* ── Range assign ─────────────────────────────────────────────────── */
  .range-grid {
    display: grid;
    grid-template-columns: 110px 1fr;
    align-items: center;
    gap: 10px 12px;
    margin-bottom: 18px;
  }
  .range-grid label { font-size: 11pt; font-weight: 700; white-space:nowrap; }
  .range-inputs { display:flex; align-items:center; gap:6px; flex-wrap:wrap; }
  .range-inputs input[type=number] {
    width: 68px;
    padding: 8px 10px;
    border: 1px solid var(--border);
    border-radius: 6px;
    font-size: 11pt;
    text-align:center;
  }
  .range-inputs .seg-label { font-size:10pt; color:#374151; font-weight:600; }
  .range-inputs .range-arrow { font-size:14pt; color:#9ca3af; margin:0 4px; }
  .badge-t1 { background:#dbeafe; color:#1d4ed8; }
  .badge-t2 { background:#d1fae5; color:#065f46; }
  .badge-t3 { background:#fef3c7; color:#92400e; }
  .badge-none { background:#f3f4f6; color:#6b7280; }
  .btn-apply {
    background:#6d43d9; color:#fff; border:none; padding:9px 20px;
    border-radius:6px; font-size:11pt; font-weight:700; cursor:pointer;
    transition: background .15s;
  }
  .btn-apply:hover { background:#5a36b8; }
  .btn-clear-all {
    background:#fff; color:#dc2626; border:1px solid #fca5a5;
    padding:9px 18px; border-radius:6px; font-size:11pt; cursor:pointer;
  }
  .btn-clear-all:hover { background:#fef2f2; }
  .apply-status { font-size:10pt; color:#059669; font-weight:600; display:none; margin-left:8px; }

  /* ── Sub-strand table ─────────────────────────────────────────────── */
  .ss-table { width:100%; border-collapse:collapse; font-size:11pt; }
  .ss-table th {
    background:#f3f4f6; text-align:left; padding:9px 12px;
    border-bottom:2px solid var(--border); font-size:10pt; color:#374151;
  }
  .ss-table td {
    padding:9px 12px; border-bottom:1px solid var(--border);
    vertical-align:middle;
  }
  .ss-table tr:last-child td { border-bottom:none; }
  .ss-table tr:hover td { background:#fafafa; }

  /* ── Term badge pills ─────────────────────────────────────────────── */
  .term-btns { display:flex; gap:6px; }
  .tb {
    padding:5px 13px; border-radius:20px; border:1.5px solid transparent;
    font-size:10pt; font-weight:700; cursor:pointer; transition: all .15s;
    background:transparent;
  }
  .tb-1  { border-color:#93c5fd; color:#1d4ed8; }
  .tb-1:hover, .tb-1.active { background:#3b82f6; color:#fff; border-color:#3b82f6; }
  .tb-2  { border-color:#6ee7b7; color:#065f46; }
  .tb-2:hover, .tb-2.active { background:#10b981; color:#fff; border-color:#10b981; }
  .tb-3  { border-color:#fcd34d; color:#92400e; }
  .tb-3:hover, .tb-3.active { background:#f59e0b; color:#fff; border-color:#f59e0b; }
  .tb-0  { border-color:#d1d5db; color:#9ca3af; }
  .tb-0:hover, .tb-0.active { background:#e5e7eb; color:#374151; border-color:#9ca3af; }

  /* ── Row status ───────────────────────────────────────────────────── */
  .row-saving { opacity:.5; pointer-events:none; }
  .row-ok .save-indicator { color:#059669; }
  .save-indicator { font-size:10pt; min-width:16px; display:inline-block; }

  /* ── Links ────────────────────────────────────────────────────────── */
  .print-links { display:flex; gap:10px; flex-wrap:wrap; margin-top:20px; }
  .print-link {
    display:inline-flex; align-items:center; gap:6px;
    padding:10px 18px; border-radius:7px; font-size:11pt; font-weight:700;
    text-decoration:none; border:none; cursor:pointer; transition: background .15s;
  }
  .pl-sow       { background:#1a56db; color:#fff; }
  .pl-sow:hover { background:#1245b0; }
  .pl-lp        { background:#059669; color:#fff; }
  .pl-lp:hover  { background:#047857; }
  .pl-t { font-size:10pt; color:var(--muted); align-self:center; }
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
      <li class="active">Term Assignment</li>
    </ol>
  </nav>

  <header>
    <div>
      <h1>Term Assignment</h1>
      <small style="color:var(--muted)"><?= e($la['name']) ?> &bull; <?= e($la['grade_name']) ?></small>
    </div>
    <a href="index.php?learning_area_id=<?= $laId ?>" class="btn btn-outline">&larr; Back to SOW</a>
  </header>

  <!-- ── Progress ─────────────────────────────────────────────────── -->
  <div class="ta-section" style="max-width:900px">
    <div class="progress-bar" id="progressBar">
      <div class="seg bar-t1" id="pT1" style="width:<?= $total ? round($termCounts[1]/$total*100) : 0 ?>%"></div>
      <div class="seg bar-t2" id="pT2" style="width:<?= $total ? round($termCounts[2]/$total*100) : 0 ?>%"></div>
      <div class="seg bar-t3" id="pT3" style="width:<?= $total ? round($termCounts[3]/$total*100) : 0 ?>%"></div>
      <div class="seg bar-none" id="pNone" style="width:<?= $total ? round($termCounts[0]/$total*100) : 0 ?>%"></div>
    </div>
    <div class="progress-legend">
      <span><i class="leg-dot" style="background:#3b82f6"></i> Term 1 — <b id="cntT1"><?= $termCounts[1] ?></b> lessons</span>
      <span><i class="leg-dot" style="background:#10b981"></i> Term 2 — <b id="cntT2"><?= $termCounts[2] ?></b> lessons</span>
      <span><i class="leg-dot" style="background:#f59e0b"></i> Term 3 — <b id="cntT3"><?= $termCounts[3] ?></b> lessons</span>
      <span><i class="leg-dot" style="background:#e5e7eb"></i> Unassigned — <b id="cntNone"><?= $termCounts[0] ?></b> lessons</span>
      <span style="margin-left:auto;color:var(--muted)">Total: <?= $total ?> lessons</span>
    </div>
  </div>

  <!-- ── Quick range assignment ─────────────────────────────────────── -->
  <div class="ta-section">
    <h2>Quick Range Assignment</h2>
    <p class="desc">
      Set the exact start and end (week &amp; lesson) for each term.
      Clicking “Apply All Ranges” will assign every lesson inside those boundaries at once.
    </p>
    <div class="range-grid">
      <?php foreach ([1=>['#dbeafe','#93c5fd','#1d4ed8'], 2=>['#d1fae5','#6ee7b7','#065f46'], 3=>['#fef3c7','#fcd34d','#92400e']] as $t => [$bg,$border,$fg]): ?>
      <label style="background:<?= $bg ?>;color:<?= $fg ?>;padding:5px 14px;border-radius:20px;border:1.5px solid <?= $border ?>">Term <?= $t ?></label>
      <div class="range-inputs">
        <span class="seg-label">From</span>
        Week&nbsp;<input type="number" id="t<?= $t ?>from" min="<?= $minWeek ?>" max="<?= $maxWeek ?>"
          value="<?= $termRanges[$t]['from'] ?>" placeholder="">
        Lesson&nbsp;<input type="number" id="t<?= $t ?>lfrom" min="1" max="20"
          value="<?= $termRanges[$t]['lfrom'] ?>" placeholder="1">
        <span class="range-arrow">&rarr;</span>
        <span class="seg-label">To</span>
        Week&nbsp;<input type="number" id="t<?= $t ?>to" min="<?= $minWeek ?>" max="<?= $maxWeek ?>"
          value="<?= $termRanges[$t]['to'] ?>" placeholder="">
        Lesson&nbsp;<input type="number" id="t<?= $t ?>lto" min="1" max="20"
          value="<?= $termRanges[$t]['lto'] ?>" placeholder="">
      </div>
      <?php endforeach; ?>
    </div>
    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
      <button class="btn-apply" onclick="applyAllRanges()">&#10003; Apply All Ranges</button>
      <button class="btn-clear-all" onclick="clearAll()">&#215; Clear All Term Assignments</button>
      <span class="apply-status" id="rangeStatus"></span>
    </div>
    <p style="margin-top:12px;font-size:10pt;color:var(--muted)">
      Week range: <?= $minWeek ?> &ndash; <?= $maxWeek ?> &nbsp;&bull;&nbsp;
      Typical CBC: Term 1 = Wk 1 L1 → Wk 12 L5 &nbsp;|&nbsp; Term 2 = Wk 13 L1 → Wk 25 L5 &nbsp;|&nbsp; Term 3 = Wk 26 L1 → Wk 37 L5
    </p>
  </div>

  <!-- ── Sub-strand assignment table ──────────────────────────────────── -->
  <div class="ta-section" style="max-width:900px">
    <h2>Sub-Strand Term Assignment</h2>
    <p class="desc">
      Click a term button on any row to assign all lessons in that sub-strand to that term.
      Changes save instantly.
    </p>
    <table class="ss-table">
      <thead>
        <tr>
          <th>Strand</th>
          <th>Sub-Strand</th>
          <th style="text-align:center">Weeks</th>
          <th style="text-align:center">Lessons</th>
          <th style="text-align:center">Term</th>
          <th style="width:20px"></th>
        </tr>
      </thead>
      <tbody id="ssBody">
      <?php foreach ($groups as $g):
        $ct = (int)($g['cur_term'] ?? 0);
        $wk = $g['min_week'] === $g['max_week']
            ? $g['min_week']
            : $g['min_week'] . '–' . $g['max_week'];
      ?>
        <tr data-strand="<?= e($g['strand']) ?>" data-ss="<?= e($g['sub_strand']) ?>"
            data-term="<?= $ct ?>">
          <td><?= e($g['strand']) ?></td>
          <td><?= e($g['sub_strand']) ?></td>
          <td style="text-align:center;white-space:nowrap"><?= $wk ?></td>
          <td style="text-align:center"><?= (int)$g['lesson_count'] ?></td>
          <td>
            <div class="term-btns">
              <button class="tb tb-1 <?= $ct===1?'active':'' ?>" onclick="setTerm(this,1)">T1</button>
              <button class="tb tb-2 <?= $ct===2?'active':'' ?>" onclick="setTerm(this,2)">T2</button>
              <button class="tb tb-3 <?= $ct===3?'active':'' ?>" onclick="setTerm(this,3)">T3</button>
              <button class="tb tb-0 <?= $ct===0?'active':'' ?>" onclick="setTerm(this,0)" title="Remove term">—</button>
            </div>
          </td>
          <td><span class="save-indicator"></span></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- ── Print links ──────────────────────────────────────────────── -->
  <div class="ta-section" style="max-width:900px">
    <h2>Print by Term</h2>
    <p class="desc">Once terms are assigned, use these links to print the SOW or lesson plans for a specific term.</p>
    <div style="display:flex;gap:12px;flex-wrap:wrap">
      <?php foreach ([1,2,3] as $t): ?>
      <div style="border:1px solid var(--border);border-radius:8px;padding:14px 18px;min-width:180px">
        <div style="font-size:12pt;font-weight:700;margin-bottom:10px">
          <?php if ($t===1): ?><span style="color:#1d4ed8">&#9632;</span><?php endif;?>
          <?php if ($t===2): ?><span style="color:#065f46">&#9632;</span><?php endif;?>
          <?php if ($t===3): ?><span style="color:#92400e">&#9632;</span><?php endif;?>
          Term <?= $t ?>
          <span style="font-size:10pt;font-weight:400;color:var(--muted);margin-left:6px">
            (<?= $termCounts[$t] ?> lessons)
          </span>
        </div>
        <div style="display:flex;flex-direction:column;gap:7px">
          <a href="print_sow.php?learning_area_id=<?= $laId ?>&term=<?= $t ?>"
             class="print-link pl-sow" target="_blank">&#128438; Download SOW</a>
          <a href="print_lessonplans.php?learning_area_id=<?= $laId ?>&term=<?= $t ?>"
             class="print-link pl-lp" target="_blank">&#128196; Print Lesson Plans</a>
        </div>
      </div>
      <?php endforeach; ?>
      <div style="border:1px solid var(--border);border-radius:8px;padding:14px 18px;min-width:180px">
        <div style="font-size:12pt;font-weight:700;margin-bottom:10px;color:#374151">
          &#9632; All Terms
        </div>
        <div style="display:flex;flex-direction:column;gap:7px">
          <a href="print_sow.php?learning_area_id=<?= $laId ?>"
             class="print-link pl-sow" target="_blank">&#128438; Download SOW</a>
          <a href="print_lessonplans.php?learning_area_id=<?= $laId ?>"
             class="print-link pl-lp" target="_blank">&#128196; Print Lesson Plans</a>
        </div>
      </div>
    </div>
  </div>

</div><!-- /page-wrap -->

<script>
const LA_ID = <?= $laId ?>;
const TOTAL = <?= $total ?>;
let counts  = {
  1: <?= $termCounts[1] ?>,
  2: <?= $termCounts[2] ?>,
  3: <?= $termCounts[3] ?>,
  0: <?= $termCounts[0] ?>
};

// ── Set term for a single sub-strand row ───────────────────────────────────
async function setTerm(btn, term) {
  const row    = btn.closest('tr');
  const strand = row.dataset.strand;
  const ss     = row.dataset.ss;
  const oldTerm = parseInt(row.dataset.term) || 0;
  const lessons = parseInt(row.querySelector('td:nth-child(4)').textContent);

  row.classList.add('row-saving');

  try {
    const res = await fetch('save_term.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({ mode:'substrand', la_id: LA_ID, strand, sub_strand: ss,
                             term: term === 0 ? '' : term })
    });
    const data = await res.json();
    if (!data.ok) throw new Error(data.error || 'Error');

    // Update buttons
    row.querySelectorAll('.tb').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    row.dataset.term = term;

    // Update counts
    counts[oldTerm] = Math.max(0, (counts[oldTerm] || 0) - lessons);
    counts[term]    = (counts[term] || 0) + lessons;
    updateProgress();

    // Flash tick
    const ind = row.querySelector('.save-indicator');
    ind.textContent = '✓';
    setTimeout(() => { ind.textContent = ''; }, 1500);
  } catch (e) {
    const ind = row.querySelector('.save-indicator');
    ind.textContent = '✗';
    ind.style.color = '#dc2626';
    setTimeout(() => { ind.textContent = ''; ind.style.color = ''; }, 2000);
  }
  row.classList.remove('row-saving');
}

// ── Apply all three week+lesson ranges at once ───────────────────────────
async function applyAllRanges() {
  const v = id => { const el = document.getElementById(id); return el ? +el.value || 0 : 0; };
  const pairs = [
    { term:1, week_from:v('t1from'), lesson_from:v('t1lfrom')||1, week_to:v('t1to'), lesson_to:v('t1lto')||99 },
    { term:2, week_from:v('t2from'), lesson_from:v('t2lfrom')||1, week_to:v('t2to'), lesson_to:v('t2lto')||99 },
    { term:3, week_from:v('t3from'), lesson_from:v('t3lfrom')||1, week_to:v('t3to'), lesson_to:v('t3lto')||99 },
  ].filter(p => p.week_from > 0 && p.week_to >= p.week_from);

  if (!pairs.length) {
    alert('Please enter at least one valid range (From Week ≤ To Week).');
    return;
  }

  const st = document.getElementById('rangeStatus');
  st.textContent = 'Saving…'; st.style.display = 'inline'; st.style.color = '#6b7280';

  try {
    for (const p of pairs) {
      const res = await fetch('save_term.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ mode:'weekrange', la_id: LA_ID, term: p.term,
                               week_from: p.week_from, lesson_from: p.lesson_from,
                               week_to:   p.week_to,   lesson_to:   p.lesson_to })
      });
      const data = await res.json();
      if (!data.ok) throw new Error(data.error);
    }
    st.textContent = '✓ Saved — reloading…'; st.style.color = '#059669';
    setTimeout(() => location.reload(), 700);
  } catch(e) {
    st.textContent = '✗ ' + e.message; st.style.color = '#dc2626';
  }
}

// ── Clear all term assignments ─────────────────────────────────────────────
async function clearAll() {
  if (!confirm('Remove all term assignments for this learning area?')) return;
  const res = await fetch('save_term.php', {
    method:'POST', headers:{'Content-Type':'application/json'},
    body: JSON.stringify({ mode:'clear_all', la_id: LA_ID })
  });
  const data = await res.json();
  if (data.ok) location.reload();
  else alert('Error: ' + data.error);
}

// ── Update progress bar ────────────────────────────────────────────────────
function updateProgress() {
  if (!TOTAL) return;
  document.getElementById('pT1').style.width   = (counts[1]/TOTAL*100) + '%';
  document.getElementById('pT2').style.width   = (counts[2]/TOTAL*100) + '%';
  document.getElementById('pT3').style.width   = (counts[3]/TOTAL*100) + '%';
  document.getElementById('pNone').style.width = (counts[0]/TOTAL*100) + '%';
  document.getElementById('cntT1').textContent   = counts[1];
  document.getElementById('cntT2').textContent   = counts[2];
  document.getElementById('cntT3').textContent   = counts[3];
  document.getElementById('cntNone').textContent = counts[0];
}
</script>
</body>
</html>
