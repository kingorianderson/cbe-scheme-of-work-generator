<?php
// import.php — Curriculum data import wizard
// Supports paste from Excel (TSV), CSV, and JSON array formats
// Imports into: scheme_of_work (SOW rows) and/or sub_strand_meta (Curriculum Design)
require_once 'config.php';
$pdo = getDB();

// ── Handle inline Learning Area creation ───────────────────────────────────
$createErr = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_la') {
    $gradeId = (int)($_POST['grade_id'] ?? 0);
    $name    = trim($_POST['la_name'] ?? '');
    $code    = trim($_POST['short_code'] ?? '') ?: null;
    $lpw     = max(1, (int)($_POST['lessons_per_week'] ?? 5));
    if ($gradeId < 1)   $createErr = 'Please select a grade.';
    elseif ($name === '') $createErr = 'Learning area name is required.';
    else {
        $st = $pdo->prepare(
            'INSERT INTO learning_areas (grade_id, name, short_code, lessons_per_week)
             VALUES (:g, :n, :c, :l)'
        );
        $st->execute([':g' => $gradeId, ':n' => $name, ':c' => $code, ':l' => $lpw]);
        $newId = (int)$pdo->lastInsertId();
        header("Location: import.php?la=$newId&created=1");
        exit;
    }
}

$grades = $pdo->query(
    "SELECT id, level_group, name FROM grades ORDER BY sort_order"
)->fetchAll();

$learningAreas = $pdo->query(
    "SELECT la.id, la.name, la.lessons_per_week, g.name AS grade_name, g.id AS grade_id
     FROM learning_areas la
     JOIN grades g ON g.id = la.grade_id
     ORDER BY g.sort_order, la.name"
)->fetchAll();

$laId = isset($_GET['la']) ? (int)$_GET['la'] : 0;
$selectedLa = null;
foreach ($learningAreas as $la) {
    if ((int)$la['id'] === $laId) { $selectedLa = $la; break; }
}

// Load existing source document and record counts for this LA (if pre-selected)
$savedSourceDoc   = '';
$savedParsedDoc   = '';
$existingSowCount = 0;
$existingSsmCount = 0;
$sourceDocSaved   = '';
if ($laId > 0) {
    // Auto-create the table + add parsed_doc column if missing
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS la_source_docs (
            id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            learning_area_id INT UNSIGNED NOT NULL,
            content          LONGTEXT NOT NULL DEFAULT '',
            parsed_doc       LONGTEXT DEFAULT NULL,
            updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_la (learning_area_id),
            FOREIGN KEY (learning_area_id) REFERENCES learning_areas(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    try {
        $colChk = $pdo->query(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='la_source_docs' AND COLUMN_NAME='parsed_doc'"
        )->fetchColumn();
        if (!$colChk) $pdo->exec("ALTER TABLE la_source_docs ADD COLUMN parsed_doc LONGTEXT DEFAULT NULL");
    } catch (PDOException $e) {}

    $sdQ = $pdo->prepare("SELECT content, parsed_doc, updated_at FROM la_source_docs WHERE learning_area_id = :la");
    $sdQ->execute([':la' => $laId]);
    $sdRow = $sdQ->fetch();
    if ($sdRow) {
        $savedSourceDoc = $sdRow['content'];
        $savedParsedDoc = $sdRow['parsed_doc'] ?? '';
        $sourceDocSaved = $sdRow['updated_at'];
    }
    $sowCnt = $pdo->prepare("SELECT COUNT(*) FROM scheme_of_work WHERE learning_area_id = :la");
    $sowCnt->execute([':la' => $laId]);
    $existingSowCount = (int)$sowCnt->fetchColumn();
    $ssmCnt = $pdo->prepare("SELECT COUNT(*) FROM sub_strand_meta WHERE learning_area_id = :la");
    $ssmCnt->execute([':la' => $laId]);
    $existingSsmCount = (int)$ssmCnt->fetchColumn();
}

function e($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }

// Group LAs by grade for the select dropdown
$laByGrade = [];
foreach ($learningAreas as $la) {
    $laByGrade[$la['grade_name']][] = $la;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Import Curriculum Data — CBC Scheme of Work</title>
<link rel="stylesheet" href="style.css">
<style>
/* ── Import wizard ──────────────────────────────────────────────────────── */
.import-wrap   { max-width: 900px; }
.import-card   {
    background: #fff; border: 1px solid var(--border);
    border-radius: 10px; padding: 24px 28px; margin-bottom: 20px;
}
.step-header   {
    display: flex; align-items: center; gap: 12px;
    margin-bottom: 18px; padding-bottom: 14px;
    border-bottom: 1px solid var(--border);
}
.step-badge    {
    background: var(--primary); color: #fff; border-radius: 50%;
    width: 28px; height: 28px; display: flex; align-items: center;
    justify-content: center; font-size: 13px; font-weight: 700; flex-shrink: 0;
}
.step-title    { font-size: 15px; font-weight: 700; color: #111; margin: 0; }
.step-desc     { font-size: 12px; color: var(--muted); margin: 0; }

/* LA selector */
.la-select-row { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; }
.la-select-row select { flex: 1; min-width: 220px; }
.la-selected-badge {
    display: inline-flex; align-items: center; gap: 8px;
    background: #ecfdf5; border: 1px solid #6ee7b7;
    color: #065f46; padding: 6px 14px; border-radius: 20px;
    font-size: 13px; font-weight: 600;
}
.la-create-panel {
    margin-top: 16px; padding: 16px; background: #f8f9ff;
    border: 1px solid #c7d2fe; border-radius: 8px;
}
.la-create-panel h3 { font-size: 13px; font-weight: 700; margin: 0 0 12px; color: #3730a3; }
.la-create-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; }
.field-sm label { font-size: 11px; font-weight: 700; color: #374151; display: block; margin-bottom: 4px; }
.field-sm input, .field-sm select {
    width: 100%; padding: 7px 10px; border: 1px solid var(--border);
    border-radius: 5px; font-size: 13px;
}

/* Type selector */
.type-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
.type-card {
    border: 2px solid var(--border); border-radius: 8px;
    padding: 14px 16px; cursor: pointer; transition: all .15s;
    position: relative;
}
.type-card:hover { border-color: var(--primary); background: #f5f3ff; }
.type-card.active { border-color: var(--primary); background: #f5f3ff; }
.type-card input[type=radio] { position: absolute; opacity: 0; }
.type-card-title { font-size: 13px; font-weight: 700; color: #111; margin-bottom: 4px; }
.type-card-desc  { font-size: 11px; color: var(--muted); line-height: 1.5; }
.type-card-tag   {
    font-size: 10px; font-weight: 700; letter-spacing: .05em;
    text-transform: uppercase; padding: 2px 8px; border-radius: 10px;
    display: inline-block; margin-bottom: 8px;
}
.tag-sow  { background: #dbeafe; color: #1e40af; }
.tag-ssm  { background: #d1fae5; color: #065f46; }
.tag-both { background: #ede9fe; color: #4c1d95; }

/* Paste area */
.format-pills { display: flex; gap: 6px; margin-bottom: 10px; flex-wrap: wrap; }
.fmt-pill {
    font-size: 11px; padding: 3px 10px; border-radius: 12px;
    border: 1px solid var(--border); background: #f3f4f6;
    color: #374151; cursor: pointer; transition: all .15s;
}
.fmt-pill.detected {
    background: #ecfdf5; border-color: #6ee7b7; color: #065f46; font-weight: 700;
}
.paste-area {
    width: 100%; min-height: 160px; font-family: Consolas, monospace;
    font-size: 12px; padding: 10px 12px; border: 1px solid var(--border);
    border-radius: 6px; resize: vertical; line-height: 1.5;
    transition: border-color .15s;
}
.paste-area:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(26,86,219,.08); }
.parse-hint {
    font-size: 11px; color: var(--muted); margin-top: 6px;
    display: flex; gap: 16px; flex-wrap: wrap;
}
.parse-hint span::before { content: '📋 '; }

/* Mapping table */
.map-wrap { overflow-x: auto; margin-top: 16px; }
.map-table { width: 100%; border-collapse: collapse; font-size: 12px; }
.map-table th {
    background: #f3f4f6; padding: 8px 12px; text-align: left;
    font-size: 10px; font-weight: 700; letter-spacing: .06em;
    text-transform: uppercase; color: #6b7280; white-space: nowrap;
    border-bottom: 2px solid var(--border);
}
.map-table td { padding: 6px 10px; border-bottom: 1px solid #f0f1f3; vertical-align: middle; }
.map-table tr:last-child td { border-bottom: none; }
.map-table tr.mapped   td { background: #f0fdf4; }
.map-table tr.ignored  td { background: #fafafa; color: #9ca3af; }
.col-header { font-weight: 600; color: #111; }
.col-header.ignored { color: #9ca3af; }
.col-sample {
    font-family: Consolas, monospace; font-size: 11px;
    color: #4b5563; max-width: 180px; overflow: hidden;
    text-overflow: ellipsis; white-space: nowrap;
}
.map-select {
    padding: 4px 8px; border: 1px solid var(--border);
    border-radius: 4px; font-size: 12px; min-width: 190px;
    cursor: pointer;
}
.map-select:focus { outline: none; border-color: var(--primary); }

/* Auto week options */
.auto-week-box {
    background: #fffbeb; border: 1px solid #fde68a;
    border-radius: 7px; padding: 12px 16px; margin-top: 16px;
    font-size: 12px;
}
.auto-week-box label { display: flex; gap: 8px; align-items: center; cursor: pointer; font-weight: 500; }
.auto-week-row { display: flex; gap: 12px; align-items: center; margin-top: 10px; flex-wrap: wrap; }
.auto-week-row label { font-size: 11px; font-weight: 700; color: #374151; margin-right: 4px; }
.auto-week-row input[type=number] {
    width: 60px; padding: 4px 8px; border: 1px solid var(--border);
    border-radius: 4px; font-size: 12px;
}

/* Parse info bar */
.parse-info {
    display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
    padding: 10px 14px; background: #eff6ff; border: 1px solid #bfdbfe;
    border-radius: 7px; font-size: 12px; color: #1e40af; margin-bottom: 16px;
}
.parse-info strong { color: #1e3a8a; }

/* Preview table */
.preview-wrap { overflow-x: auto; max-height: 280px; overflow-y: auto; border: 1px solid var(--border); border-radius: 6px; margin-top: 14px; }
.preview-table { border-collapse: collapse; font-size: 11px; min-width: 100%; }
.preview-table th {
    background: #1a56db; color: #fff; padding: 6px 10px;
    white-space: nowrap; position: sticky; top: 0;
    font-size: 10px; font-weight: 700;
}
.preview-table th.mapped-col { background: #059669; }
.preview-table td { padding: 5px 10px; border-bottom: 1px solid #f0f1f3; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.preview-table tr:nth-child(even) td { background: #fafafa; }

/* Import button */
.btn-import {
    background: #7c3aed; color: #fff; border: none;
    padding: 11px 30px; border-radius: 7px; font-size: 14px;
    font-weight: 700; cursor: pointer; transition: background .15s;
}
.btn-import:hover    { background: #5b21b6; }
.btn-import:disabled { background: #a78bda; cursor: not-allowed; }

/* Results */
.result-box {
    padding: 18px 22px; border-radius: 8px;
    font-size: 13px; line-height: 1.8;
}
.result-ok  { background: #f0fdf4; border: 1px solid #bbf7d0; color: #14532d; }
.result-err { background: #fef2f2; border: 1px solid #fca5a5; color: #7f1d1d; }
.result-stat { font-size: 18px; font-weight: 700; margin-bottom: 6px; }
.result-errors { margin-top: 10px; font-size: 11px; font-family: Consolas, monospace; }
.result-errors li { margin-bottom: 3px; }

/* Misc */
.detected-fmt { font-size: 11px; padding: 2px 8px; border-radius: 10px; font-weight: 700; }
.fmt-tsv  { background: #dbeafe; color: #1e40af; }
.fmt-csv  { background: #d1fae5; color: #065f46; }
.fmt-json { background: #fef3c7; color: #92400e; }

@media (max-width: 640px) {
    .type-grid { grid-template-columns: 1fr; }
    .la-create-grid { grid-template-columns: 1fr; }
}

/* ── Structured document tree ──────────────────────────────────────────── */
.struct-strand     { border:1px solid #e5e7eb; border-radius:7px; margin-bottom:8px; overflow:hidden; }
.struct-strand-hdr { padding:7px 13px; background:#f9fafb; font-size:12px; font-weight:700; cursor:pointer; display:flex; align-items:center; gap:8px; user-select:none; }
.struct-strand-hdr:hover { background:#f3f4f6; }
.struct-ss-count   { font-size:11px; font-weight:400; color:var(--muted); margin-left:auto; }
.struct-sub-list   { padding:4px 0; }
.struct-sub-list.collapsed { display:none; }
.struct-ss         { border-top:1px solid #f3f4f6; padding:5px 13px; }
.struct-ss-empty   { opacity:.55; }
.struct-ss-hdr     { font-size:12px; font-weight:600; cursor:pointer; display:flex; gap:8px; align-items:center; padding:2px 0; user-select:none; }
.struct-ss-hdr:hover { color:var(--primary); }
.struct-ss-meta    { font-size:10px; font-weight:400; color:var(--muted); }
.struct-ss-detail  { padding:7px 0 3px 10px; border-left:2px solid #e5e7eb; margin-left:6px; margin-top:4px; }
.struct-ss-detail.collapsed { display:none; }
.struct-field      { margin-bottom:6px; }
.struct-field-lbl  { font-size:10px; font-weight:700; letter-spacing:.06em; text-transform:uppercase; color:var(--primary); margin-bottom:2px; }
.struct-field-list { margin:0; padding-left:14px; line-height:1.7; color:#374151; }

/* ── Full document view modal ───────────────────────────────────────────── */
#full-doc-modal { display:none; position:fixed; inset:0; z-index:9000; background:rgba(0,0,0,.55); overflow-y:auto; }
#full-doc-modal.open { display:block; }
.fdm-inner { background:#fff; margin:24px auto 40px; width:min(940px,97vw); border-radius:12px; box-shadow:0 20px 60px rgba(0,0,0,.3); overflow:visible; }
.fdm-topbar { background:#065f46; color:#fff; padding:13px 22px; display:flex; align-items:center; gap:12px; flex-wrap:wrap; position:sticky; top:0; z-index:2; }
.fdm-topbar h2 { margin:0; font-size:15px; font-weight:700; flex:1; }
.fdm-topbar-stats { font-size:12px; opacity:.8; background:rgba(255,255,255,.15); padding:2px 10px; border-radius:10px; }
.fdm-tbtn { background:rgba(255,255,255,.15); color:#fff; border:1px solid rgba(255,255,255,.35); padding:5px 14px; border-radius:5px; font-size:12px; font-weight:700; cursor:pointer; }
.fdm-tbtn:hover { background:rgba(255,255,255,.3); }
.fdm-tbtn-primary { background:#059669; border-color:#059669; }
.fdm-tbtn-primary:hover { background:#047857; }
.fdm-body { padding:24px 30px; }
.fdm-strand     { margin-bottom:36px; }
.fdm-strand-hdr { background:#ecfdf5; border-left:5px solid #059669; padding:11px 18px; margin-bottom:16px; font-size:16px; font-weight:800; color:#065f46; border-radius:0 7px 7px 0; letter-spacing:.01em; }
.fdm-ss         { margin-bottom:16px; padding:16px 18px; border:1px solid #e5e7eb; border-radius:8px; background:#fafafa; }
.fdm-ss-hdr     { font-size:14px; font-weight:700; color:#111827; margin-bottom:12px; padding-bottom:8px; border-bottom:2px solid #e5e7eb; }
.fdm-ss-grid    { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
.fdm-field      { background:#fff; border:1px solid #f3f4f6; border-radius:6px; padding:10px 12px; }
.fdm-field.full { grid-column:1/-1; }
.fdm-field-lbl  { font-size:10px; font-weight:700; letter-spacing:.07em; text-transform:uppercase; color:#059669; margin-bottom:5px; }
.fdm-field-val  { font-size:12px; color:#374151; line-height:1.8; }
.fdm-field-val ul { margin:0; padding-left:16px; }
.fdm-field-val li { margin-bottom:1px; }
.fdm-field-val .stem { color:#6b7280; font-style:italic; margin-bottom:2px; }
.fdm-empty      { color:#d1d5db; font-style:italic; font-size:11px; }
@media (max-width:620px) { .fdm-ss-grid { grid-template-columns:1fr; } .fdm-field.full { grid-column:1; } }
</style>
</head>
<body>
<div class="page-wrap">

<nav class="top-nav">
  <span class="nav-brand">CBC Scheme of Work</span>
  <ol class="breadcrumb">
    <li><a href="curriculum.php">Curriculum</a></li>
    <li class="active">Import Data</li>
  </ol>
</nav>

<header>
  <div>
    <h1>Import Curriculum Data</h1>
    <small style="color:var(--muted)">Paste from Excel, CSV, or JSON to populate your scheme and curriculum design records</small>
  </div>
  <a href="curriculum.php" class="btn btn-outline">&larr; Back</a>
</header>

<?php if (isset($_GET['created'])): ?>
  <div class="flash">&#10003; Learning area created — now paste your curriculum data below.</div>
<?php endif; ?>

<div class="import-wrap">

<!-- ══ STEP 1: Learning Area ══════════════════════════════════════════════ -->
<div class="import-card">
  <div class="step-header">
    <div class="step-badge">1</div>
    <div>
      <p class="step-title">Learning Area</p>
      <p class="step-desc">Select an existing learning area or create a new one</p>
    </div>
  </div>

  <div class="la-select-row">
    <select id="la-select" onchange="onLaChange(this.value)" style="max-width:400px;padding:9px 12px;border:1px solid var(--border);border-radius:6px;font-size:13px">
      <option value="">— Select a learning area —</option>
      <?php foreach ($laByGrade as $gradeName => $areas): ?>
        <optgroup label="<?= e($gradeName) ?>">
          <?php foreach ($areas as $la): ?>
            <option value="<?= $la['id'] ?>" <?= $laId === (int)$la['id'] ? 'selected' : '' ?>>
              <?= e($la['name']) ?>
            </option>
          <?php endforeach; ?>
        </optgroup>
      <?php endforeach; ?>
    </select>
    <button type="button" class="btn btn-outline" onclick="toggleCreateForm()"
            style="white-space:nowrap">+ Create New</button>
  </div>

  <?php if ($selectedLa): ?>
  <div style="margin-top:12px;display:flex;flex-wrap:wrap;align-items:center;gap:10px">
    <span class="la-selected-badge">
      &#10003; <?= e($selectedLa['grade_name']) ?> &mdash; <?= e($selectedLa['name']) ?>
      &nbsp;(<?= (int)$selectedLa['lessons_per_week'] ?> lessons/week)
    </span>
    <?php if ($existingSowCount > 0 || $existingSsmCount > 0): ?>
    <span style="font-size:11px;background:#fef9c3;border:1px solid #fde047;padding:2px 10px;border-radius:10px;color:#854d0e">
      &#9998; Existing data:
      <?php if ($existingSowCount > 0): ?><?= $existingSowCount ?> SOW row<?= $existingSowCount !== 1 ? 's' : '' ?><?php endif; ?>
      <?php if ($existingSowCount > 0 && $existingSsmCount > 0): ?> &bull;<?php endif; ?>
      <?php if ($existingSsmCount > 0): ?><?= $existingSsmCount ?> sub-strand<?= $existingSsmCount !== 1 ? 's' : '' ?><?php endif; ?>
      &mdash; re-importing will <strong>update</strong> existing records
    </span>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <!-- Create new LA form -->
  <div class="la-create-panel" id="create-panel" style="display:none">
    <h3>Create New Learning Area</h3>
    <?php if ($createErr): ?>
      <p style="color:#dc2626;font-size:12px;margin-bottom:10px"><?= e($createErr) ?></p>
    <?php endif; ?>
    <form method="post">
      <input type="hidden" name="action" value="create_la">
      <div class="la-create-grid">
        <div class="field-sm">
          <label>Grade <span style="color:#dc2626">*</span></label>
          <select name="grade_id" required>
            <option value="">— Grade —</option>
            <?php foreach ($grades as $g): ?>
              <option value="<?= $g['id'] ?>"><?= e($g['level_group'] . ' › ' . $g['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field-sm">
          <label>Learning Area Name <span style="color:#dc2626">*</span></label>
          <input type="text" name="la_name" placeholder="e.g. Mathematics" required>
        </div>
        <div class="field-sm">
          <label>Short Code</label>
          <input type="text" name="short_code" placeholder="e.g. MATH">
        </div>
        <div class="field-sm">
          <label>Lessons Per Week</label>
          <input type="number" name="lessons_per_week" value="5" min="1" max="20">
        </div>
        <div class="field-sm" style="display:flex;align-items:flex-end">
          <button type="submit" class="btn btn-primary" style="width:100%">Create &amp; Continue</button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- ══ STEP 2: Import Type ════════════════════════════════════════════════ -->
<div class="import-card">
  <div class="step-header">
    <div class="step-badge">2</div>
    <div>
      <p class="step-title">What to Import</p>
      <p class="step-desc">Choose what data you are pasting</p>
    </div>
  </div>

  <div class="type-grid">
    <label class="type-card" id="card-both" onclick="setType('both')">
      <input type="radio" name="import_type" value="both" checked>
      <div class="type-card-tag tag-both">Recommended</div>
      <div class="type-card-title">Both (Full Curriculum)</div>
      <div class="type-card-desc">Import SOW rows <strong>and</strong> Curriculum Design meta in one paste. All columns supported.</div>
    </label>
    <label class="type-card" id="card-sow" onclick="setType('sow')">
      <input type="radio" name="import_type" value="sow">
      <div class="type-card-tag tag-sow">SOW Only</div>
      <div class="type-card-title">Scheme of Work Rows</div>
      <div class="type-card-desc">Week, Lesson, Strand, Sub-Strand, SLOs, Learning Experiences, Key Inquiry, Resources, Assessment.</div>
    </label>
    <label class="type-card" id="card-ssm" onclick="setType('ssm')">
      <input type="radio" name="import_type" value="ssm">
      <div class="type-card-tag tag-ssm">CD Only</div>
      <div class="type-card-title">Curriculum Design</div>
      <div class="type-card-desc">Strand, Sub-Strand, Core Competencies, Values, PCIs, Links to Other Areas — one row per sub-strand.</div>
    </label>
  </div>
</div>

<!-- ══ STEP 3: Paste Data ════════════════════════════════════════════════ -->
<div class="import-card">
  <div class="step-header">
    <div class="step-badge">3</div>
    <div>
      <p class="step-title">Paste Your Data</p>
      <p class="step-desc">Use structured paste (Excel/CSV/JSON) or let AI extract from any document text</p>
    </div>
  </div>

  <!-- Mode toggle -->
  <div style="display:flex;gap:0;margin-bottom:18px;border:1px solid var(--border);border-radius:7px;overflow:hidden;width:fit-content">
    <button type="button" id="mode-btn-structured"
      onclick="setInputMode('structured')"
      style="padding:8px 20px;font-size:13px;font-weight:700;border:none;cursor:pointer;background:#7c3aed;color:#fff">
      📋 Structured Paste
    </button>
    <button type="button" id="mode-btn-ai"
      onclick="setInputMode('ai')"
      style="padding:8px 20px;font-size:13px;font-weight:600;border:none;cursor:pointer;background:#f3f4f6;color:#374151;border-left:1px solid var(--border)">
      ⚡ AI Extract from Document
    </button>
  </div>

  <!-- ── Structured paste panel ─────────────────────────────────────────── -->
  <div id="panel-structured">
    <div class="format-pills">
      <span class="fmt-pill" id="pill-auto">Auto-detect</span>
      <span class="fmt-pill" id="pill-tsv">TSV (Excel paste)</span>
      <span class="fmt-pill" id="pill-csv">CSV</span>
      <span class="fmt-pill" id="pill-json">JSON</span>
    </div>
    <textarea class="paste-area" id="paste-area"
      placeholder="Paste your data here...

Excel / Google Sheets: copy cells including the header row and paste.
CSV: comma-separated with a header row.
JSON: array of objects, e.g. [{&quot;Strand&quot;:&quot;1.0 Numbers&quot;, &quot;Sub-Strand&quot;:&quot;1.1 Whole Numbers&quot;, ...}]"
      oninput="onPasteInput(this.value)"></textarea>
    <div class="parse-hint">
      <span>First row must be column headers</span>
      <span>JSON must be an array of objects</span>
      <span>Empty rows are ignored</span>
    </div>
  </div>

  <!-- ── AI Extract panel ─────────────────────────────────────────────── -->
  <div id="panel-ai" style="display:none">
    <div style="background:#f5f3ff;border:1px solid #c4b5fd;border-radius:8px;padding:12px 16px;margin-bottom:14px;font-size:12px;color:#4c1d95;line-height:1.7">
      <strong>Two actions available:</strong><br>
      <strong>📖 Parse &amp; Structure</strong> — AI reads the <em>entire</em> document carefully,
      labels every sentence as strand / sub-strand / SLO / learning experience / etc.,
      builds a clean hierarchical format, self-checks for errors, and saves it to the database.
      Use this for a thorough, reliable first-time import or when you suspect missing data.<br>
      <strong>⚡ Quick Extract</strong> — faster chunked extraction (good for small pastes or re-runs).
      <span style="display:block;margin-top:6px;color:#7c3aed">
        ✔ Pasted text is <strong>always saved</strong> so you never need to paste again —
        click <em>Re-verify &amp; Fix</em> any time to ask the AI to re-check what it captured.
      </span>
    </div>

    <?php if ($sourceDocSaved): ?>
    <div id="saved-doc-notice" style="font-size:11px;background:#f0fdf4;border:1px solid #bbf7d0;padding:6px 12px;border-radius:6px;margin-bottom:10px;color:#14532d">
      &#10003; Previously saved document loaded (<?= e($sourceDocSaved) ?>) —
      <a href="#" onclick="document.getElementById('ai-paste-area').value='';document.getElementById('saved-doc-notice').style.display='none';return false"
         style="color:#dc2626">Clear &amp; repaste</a>
    </div>
    <?php endif; ?>

    <textarea class="paste-area" id="ai-paste-area"
      style="min-height:200px"
      placeholder="Paste the full curriculum document text here — any format, any length.

• Raw text copied from a KICD curriculum design PDF
• Text from a Word document (.docx)
• Any formatted or unformatted curriculum content
• Partial sections are fine — the AI will handle them"><?= $savedSourceDoc !== '' ? e($savedSourceDoc) : '' ?></textarea>

    <div style="display:flex;gap:10px;align-items:center;margin-top:12px;flex-wrap:wrap">
      <button type="button" id="btn-structure-doc" onclick="doStructureDoc()"
        style="background:#059669;color:#fff;border:none;padding:9px 20px;border-radius:6px;font-size:13px;font-weight:700;cursor:pointer">
        📖 Parse &amp; Structure
      </button>
      <button type="button" id="btn-ai-extract" onclick="doAiExtract()"
        style="background:#7c3aed;color:#fff;border:none;padding:9px 20px;border-radius:6px;font-size:13px;font-weight:600;cursor:pointer">
        ⚡ Quick Extract
      </button>
      <div id="ai-extract-progress" style="display:none;flex:1;min-width:180px">
        <div style="height:7px;background:#e5e7eb;border-radius:4px;overflow:hidden;margin-bottom:4px">
          <div id="ai-prog-fill" style="height:100%;background:#7c3aed;border-radius:4px;transition:width .4s ease;width:0%"></div>
        </div>
        <div id="ai-prog-label" style="font-size:11px;color:var(--muted)">Processing…</div>
      </div>
      <span id="ai-extract-status" style="font-size:12px;color:var(--muted)"></span>
    </div>

    <!-- Structured document preview — shown once a parsed_doc exists -->
    <div id="parsed-doc-section" style="display:<?= $savedParsedDoc ? '' : 'none' ?>;margin-top:18px">
      <div style="border:1px solid #d1fae5;border-radius:8px;overflow:hidden">
        <div style="background:#ecfdf5;padding:10px 16px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;border-bottom:1px solid #d1fae5">
          <span style="font-size:13px;font-weight:700;color:#065f46">📋 Structured Document</span>
          <span id="parsed-doc-stats" style="font-size:12px;color:#065f46;background:#d1fae5;padding:2px 10px;border-radius:10px">
            <?php
            if ($savedParsedDoc) {
                $sp = json_decode($savedParsedDoc, true);
                $sc = count($sp['strands'] ?? []);
                $ss = array_sum(array_map(fn($s) => count($s['sub_strands'] ?? []), $sp['strands'] ?? []));
                echo "$sc strand(s) · $ss sub-strand(s)";
            }
            ?>
          </span>
          <button type="button" onclick="openFullDocView()"
            style="background:#059669;color:#fff;border:none;padding:5px 16px;border-radius:5px;font-size:12px;font-weight:700;cursor:pointer">
            📄 View Full Document
          </button>
          <button type="button" id="btn-reverify" onclick="doReverify()"
            style="background:#fff;color:#065f46;border:1px solid #6ee7b7;padding:5px 14px;border-radius:5px;font-size:12px;font-weight:700;cursor:pointer">
            🔄 Re-verify &amp; Fix
          </button>
          <button type="button" onclick="doExtractFromParsed()"
            style="margin-left:auto;background:#7c3aed;color:#fff;border:none;padding:5px 14px;border-radius:5px;font-size:12px;font-weight:700;cursor:pointer">
            ↓ Extract Rows to Import
          </button>
        </div>
        <div id="parsed-doc-tree" style="max-height:380px;overflow-y:auto;padding:10px 14px;background:#fff;font-size:12px">
          <?php if ($savedParsedDoc): ?>
            <div style="color:var(--muted);font-style:italic">Loading preview…</div>
          <?php else: ?>
            <div style="color:var(--muted)">No structured document yet — run "Parse &amp; Structure" above.</div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ══ STEP 4: Map Columns ═══════════════════════════════════════════════ -->
<div id="step-map" style="display:none">
<div class="import-card">
  <div class="step-header">
    <div class="step-badge">4</div>
    <div>
      <p class="step-title">Map Columns</p>
      <p class="step-desc">Confirm how your columns map to database fields</p>
    </div>
  </div>

  <div id="parse-info-bar" class="parse-info"></div>

  <div class="map-wrap">
    <table class="map-table" id="map-table">
      <thead>
        <tr>
          <th>Your Column Header</th>
          <th>Maps To</th>
          <th>Sample Value</th>
        </tr>
      </thead>
      <tbody id="map-body"></tbody>
    </table>
  </div>

  <!-- Auto week/lesson options (shown for sow/both types) -->
  <div class="auto-week-box" id="auto-week-box">
    <label>
      <input type="checkbox" id="chk-auto-week" checked onchange="toggleAutoWeek()">
      <strong>Auto-assign Week &amp; Lesson numbers</strong>
      <span style="color:var(--muted);font-weight:400"> — assign sequentially (use when your data has no Week/Lesson columns)</span>
    </label>
    <div class="auto-week-row" id="auto-week-opts">
      <label>Start Week: <input type="number" id="start-week" value="1" min="1" style="width:60px"></label>
      <label>Start Lesson: <input type="number" id="start-lesson" value="1" min="1" style="width:60px"></label>
      <label style="display:flex;align-items:center;gap:6px">
        <input type="checkbox" id="chk-continue"> Continue from last existing row
      </label>
    </div>
  </div>

  <!-- Preview -->
  <div style="margin-top:18px">
    <p style="font-size:12px;font-weight:700;color:#374151;margin-bottom:6px">Preview (first 5 data rows)</p>
    <div class="preview-wrap">
      <table class="preview-table" id="preview-table"></table>
    </div>
  </div>

  <div style="display:flex;gap:12px;align-items:center;margin-top:20px;flex-wrap:wrap">
    <button class="btn-import" id="import-btn" onclick="doImport()" disabled>
      &#9889; Import Data
    </button>
    <span id="import-status" style="font-size:13px;color:var(--muted)"></span>
  </div>
</div>
</div>

<!-- ══ STEP 5: Results ════════════════════════════════════════════════════ -->
<div id="step-results" style="display:none">
<div class="import-card">
  <div class="step-header">
    <div class="step-badge">5</div>
    <div>
      <p class="step-title">Import Results</p>
      <p class="step-desc">Summary of what was saved</p>
    </div>
  </div>
  <div id="results-content"></div>
  <div style="display:flex;gap:10px;margin-top:16px;flex-wrap:wrap" id="results-actions"></div>
</div>
</div>

</div><!-- /.import-wrap -->
</div><!-- /.page-wrap -->

<script>
// ══ Field Definitions ════════════════════════════════════════════════════════
const DEFS = {
  sow: [
    { key:'strand',       label:'Strand',                   req:true,  aliases:['strand'] },
    { key:'sub_strand',   label:'Sub-Strand',               req:true,  aliases:['sub-strand','sub strand','substrand','sub_strand'] },
    { key:'week',         label:'Week',                     req:false, aliases:['week','wk','week no','week number'] },
    { key:'lesson',       label:'Lesson',                   req:false, aliases:['lesson','ls','less','lesson no','lesson number'] },
    { key:'term',         label:'Term',                     req:false, aliases:['term'] },
    { key:'slo_cd',       label:'SLO (Curriculum Design)',  req:false, aliases:['slo cd','slo (cd)','slo_cd','specific learning outcomes (cd)','specific learning outcomes cd'] },
    { key:'slo_sow',      label:'SLO (Scheme of Work)',     req:false, aliases:['slo','slo sow','slo (sow)','slo_sow','specific learning outcomes','specific learning outcomes (sow)','specific learning outcomes sow'] },
    { key:'le_cd',        label:'Learning Exp. (CD)',        req:false, aliases:['le cd','le (cd)','le_cd','learning experiences (cd)','learning experiences cd'] },
    { key:'le_sow',       label:'Learning Exp. (SOW)',       req:false, aliases:['le','le sow','le (sow)','le_sow','learning experiences','learning experiences (sow)','learning experiences sow'] },
    { key:'key_inquiry',  label:'Key Inquiry Question',     req:false, aliases:['key inquiry','key inquiry question','key inquiry questions','kiq','key_inquiry'] },
    { key:'resources',    label:'Resources',                req:false, aliases:['resources','learning resources','resource'] },
    { key:'assessment',   label:'Assessment',               req:false, aliases:['assessment','assessments'] },
    { key:'remarks',      label:'Remarks',                  req:false, aliases:['remarks','notes','remark','comment'] },
  ],
  ssm: [
    { key:'strand',               label:'Strand',                 req:true,  aliases:['strand'] },
    { key:'sub_strand',           label:'Sub-Strand',             req:true,  aliases:['sub-strand','sub strand','substrand','sub_strand'] },
    { key:'key_inquiry_qs',       label:'Key Inquiry Questions',  req:false, aliases:['key inquiry questions','key inquiry qs','key_inquiry_qs','key inquiry','kiq'] },
    { key:'core_competencies',    label:'Core Competencies',      req:false, aliases:['core competencies','competencies','core_competencies','core competency'] },
    { key:'values_attit',         label:'Values & Attitudes',     req:false, aliases:['values','values & attitudes','values/attitudes','values and attitudes','values attit','values_attit'] },
    { key:'pcis',                 label:'PCIs',                   req:false, aliases:['pcis','pci','pertinent and contemporary issues','pertinent contemporary issues'] },
    { key:'links_to_other_areas', label:'Links to Other Areas',   req:false, aliases:['links to other areas','links','cross curriculum','cross-curriculum','links_to_other_areas'] },
    { key:'resources',            label:'Resources',              req:false, aliases:['resources','learning resources'] },
    { key:'assessment',           label:'Assessment',             req:false, aliases:['assessment','assessments'] },
  ],
};

// 'both' = merged unique keys (sow first, then ssm-only)
DEFS.both = [...DEFS.sow];
for (const d of DEFS.ssm) {
    if (!DEFS.both.find(x => x.key === d.key)) DEFS.both.push(d);
}

// ══ State ════════════════════════════════════════════════════════════════════
let parsed    = null;   // { headers:[], rows:[], format:'tsv'|'csv'|'json' }
let mapping   = {};     // { fieldKey: colIndex }
let importType = 'both';
let forceFormat = '';   // '' = auto

// ══ LA / Type selection ══════════════════════════════════════════════════════
function onLaChange(val) {
    if (val) window.location = 'import.php?la=' + val;
}

function toggleCreateForm() {
    const p = document.getElementById('create-panel');
    p.style.display = p.style.display === 'none' ? '' : 'none';
}

function setType(t) {
    importType = t;
    ['sow','ssm','both'].forEach(x => {
        document.getElementById('card-' + x).classList.toggle('active', x === t);
    });
    document.getElementById('auto-week-box').style.display =
        (t === 'ssm') ? 'none' : '';
    if (parsed) rebuildMapping();
}

// init type card state
document.getElementById('card-both').classList.add('active');

// ══ Format detection & parsing ════════════════════════════════════════════════
function detectFmt(text) {
    if (forceFormat) return forceFormat;
    text = text.trim();
    if (text.startsWith('[') || text.startsWith('{')) {
        try { JSON.parse(text); return 'json'; } catch (_) {}
    }
    return text.split('\n')[0].includes('\t') ? 'tsv' : 'csv';
}

function parseLine(line, delim) {
    if (delim === '\t') return line.split('\t').map(v => v.trim());
    const result = []; let cur = ''; let inQ = false;
    for (let i = 0; i < line.length; i++) {
        const c = line[i];
        if (c === '"') {
            if (inQ && line[i+1] === '"') { cur += '"'; i++; }
            else inQ = !inQ;
        } else if (c === ',' && !inQ) { result.push(cur.trim()); cur = ''; }
        else cur += c;
    }
    result.push(cur.trim());
    return result;
}

function parseData(text) {
    text = text.trim();
    const fmt = detectFmt(text);
    setPillActive(fmt);

    if (fmt === 'json') {
        let data;
        try { data = JSON.parse(text); } catch (e) {
            return { error: 'Invalid JSON: ' + e.message };
        }
        if (!Array.isArray(data)) data = [data];
        if (data.length === 0) return { error: 'JSON array is empty' };
        const headers = Object.keys(data[0]);
        const rows = data.map(obj => headers.map(h => String(obj[h] ?? '')));
        return { format: 'json', headers, rows };
    }

    const delim = fmt === 'tsv' ? '\t' : ',';
    const lines = text.split('\n').map(l => l.replace(/\r$/, '')).filter(l => l.trim() !== '');
    if (lines.length < 2) return { error: 'Need at least a header row and one data row' };

    const headers = parseLine(lines[0], delim).map(h => h.replace(/^"|"$/g, '').trim());
    const rows = lines.slice(1).map(l => parseLine(l, delim));
    return { format: fmt, headers, rows };
}

function setPillActive(fmt) {
    ['auto','tsv','csv','json'].forEach(f => {
        document.getElementById('pill-' + f)?.classList.remove('detected');
    });
    document.getElementById('pill-' + (forceFormat || 'auto'))?.classList.add('detected');
}

// Format pill clicks
['auto','tsv','csv','json'].forEach(f => {
    document.getElementById('pill-' + f).addEventListener('click', () => {
        forceFormat = (f === 'auto') ? '' : f;
        const txt = document.getElementById('paste-area').value;
        if (txt.trim()) onPasteInput(txt);
        setPillActive(forceFormat || 'auto');
    });
});
setPillActive('auto');

// ══ Auto-mapper ═══════════════════════════════════════════════════════════════
function autoMap(headers, type) {
    const defs = DEFS[type] || DEFS.both;
    const result = {};
    const used = new Set();

    for (const def of defs) {
        for (let i = 0; i < headers.length; i++) {
            if (used.has(i)) continue;
            const h = headers[i].toLowerCase().trim();
            const matched = def.aliases.some(a =>
                h === a || h.replace(/[^a-z0-9]/g, ' ').trim() === a
            );
            if (matched) { result[def.key] = i; used.add(i); break; }
        }
    }
    return result;
}

// ══ Mapping UI ════════════════════════════════════════════════════════════════
function buildFieldOptions(type, selectedKey) {
    const defs = DEFS[type] || DEFS.both;
    let html = '<option value="">— Ignore —</option>';
    for (const d of defs) {
        const sel = d.key === selectedKey ? 'selected' : '';
        const req = d.req ? ' ⁕' : '';
        html += `<option value="${d.key}" ${sel}>${d.label}${req}</option>`;
    }
    return html;
}

function rebuildMapping() {
    if (!parsed || !parsed.headers) return;
    mapping = autoMap(parsed.headers, importType);

    const body = document.getElementById('map-body');
    const sampleRow = parsed.rows[0] || [];
    body.innerHTML = '';

    parsed.headers.forEach((h, i) => {
        const mappedKey = Object.entries(mapping).find(([_, idx]) => idx === i)?.[0] || '';
        const sample = String(sampleRow[i] || '').substring(0, 80);
        const tr = document.createElement('tr');
        tr.className = mappedKey ? 'mapped' : 'ignored';
        tr.innerHTML = `
            <td class="col-header ${mappedKey ? '' : 'ignored'}">${esc(h)}</td>
            <td><select class="map-select" data-col="${i}" onchange="onMapChange(this)">
                ${buildFieldOptions(importType, mappedKey)}
            </select></td>
            <td class="col-sample" title="${esc(sample)}">${esc(sample)}</td>`;
        body.appendChild(tr);
    });

    renderParseInfo();
    renderPreview();
    checkImportReady();
}

function onMapChange(sel) {
    const col = parseInt(sel.dataset.col);
    const newKey = sel.value;

    // Remove any existing mapping for this field from other columns
    if (newKey) {
        for (const [k, v] of Object.entries(mapping)) {
            if (v === col || k === newKey) delete mapping[k];
        }
        mapping[newKey] = col;
    } else {
        for (const [k, v] of Object.entries(mapping)) {
            if (v === col) { delete mapping[k]; break; }
        }
    }

    // Update row class
    const tr = sel.closest('tr');
    tr.className = newKey ? 'mapped' : 'ignored';
    tr.querySelector('.col-header').classList.toggle('ignored', !newKey);

    renderPreview();
    checkImportReady();
}

// ══ Parse info bar ════════════════════════════════════════════════════════════
function renderParseInfo() {
    if (!parsed) return;
    const fmtLabels = { tsv:'TSV (Excel)', csv:'CSV', json:'JSON' };
    const fmtClasses = { tsv:'fmt-tsv', csv:'fmt-csv', json:'fmt-json' };
    const mappedCount = Object.keys(mapping).length;
    document.getElementById('parse-info-bar').innerHTML =
        `<strong>${parsed.rows.length}</strong> data rows &times; <strong>${parsed.headers.length}</strong> columns detected
         &nbsp;&bull;&nbsp; Format: <span class="detected-fmt ${fmtClasses[parsed.format]}">${fmtLabels[parsed.format]}</span>
         &nbsp;&bull;&nbsp; <strong>${mappedCount}</strong> columns mapped`;
}

// ══ Preview table ═════════════════════════════════════════════════════════════
function renderPreview() {
    if (!parsed) return;
    const previewRows = parsed.rows.slice(0, 5);
    const reverseMappings = {}; // colIndex → fieldLabel
    for (const [k, i] of Object.entries(mapping)) {
        const def = (DEFS[importType] || DEFS.both).find(d => d.key === k);
        reverseMappings[i] = def ? def.label : k;
    }

    let html = '<thead><tr>';
    parsed.headers.forEach((h, i) => {
        const lbl = reverseMappings[i];
        html += lbl
            ? `<th class="mapped-col" title="${esc(h)}">${esc(lbl)}</th>`
            : `<th style="opacity:.4">${esc(h)}</th>`;
    });
    html += '</tr></thead><tbody>';
    previewRows.forEach(row => {
        html += '<tr>';
        parsed.headers.forEach((_, i) => {
            const v = String(row[i] || '').substring(0, 60);
            const mapped = !!reverseMappings[i];
            html += `<td style="${mapped ? '' : 'opacity:.35'}">${esc(v)}</td>`;
        });
        html += '</tr>';
    });
    html += '</tbody>';
    document.getElementById('preview-table').innerHTML = html;
}

// ══ Auto-week toggle ══════════════════════════════════════════════════════════
function toggleAutoWeek() {
    const checked = document.getElementById('chk-auto-week').checked;
    document.getElementById('auto-week-opts').style.display = checked ? '' : 'none';
}

// ══ Import readiness check ════════════════════════════════════════════════════
function checkImportReady() {
    const laId = <?= $laId ?: 0 ?>;
    const hasStrand    = Object.hasOwn(mapping, 'strand');
    const hasSubStrand = Object.hasOwn(mapping, 'sub_strand');
    const ready = laId > 0 && hasStrand && hasSubStrand && parsed && parsed.rows.length > 0;
    const btn = document.getElementById('import-btn');
    btn.disabled = !ready;

    let msg = '';
    if (laId === 0)         msg = 'Select a learning area first.';
    else if (!hasStrand)    msg = 'Map at least the Strand and Sub-Strand columns.';
    else if (!hasSubStrand) msg = 'Map the Sub-Strand column.';
    document.getElementById('import-status').textContent = msg;
}

// ══ Main parse event ══════════════════════════════════════════════════════════
let parseTimer = null;
function onPasteInput(text) {
    clearTimeout(parseTimer);
    if (text.trim().length < 5) return;
    parseTimer = setTimeout(() => {
        const result = parseData(text);
        if (result.error) {
            document.getElementById('step-map').style.display = 'none';
            document.getElementById('parse-info-bar').textContent = '⚠ ' + result.error;
            return;
        }
        parsed = result;
        document.getElementById('step-map').style.display = '';
        rebuildMapping();
        document.getElementById('step-map').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }, 300);
}

// ══ Import submission ══════════════════════════════════════════════════════════
async function doImport() {
    const laId = <?= $laId ?: 0 ?>;
    if (!laId || !parsed) return;

    const autoWeek   = document.getElementById('chk-auto-week').checked && importType !== 'ssm';
    const startWeek  = parseInt(document.getElementById('start-week').value) || 1;
    const startLesson= parseInt(document.getElementById('start-lesson').value) || 1;
    const continueEx = document.getElementById('chk-continue').checked;

    const btn = document.getElementById('import-btn');
    btn.disabled = true;
    btn.textContent = '⏳ Importing…';
    document.getElementById('import-status').textContent = '';

    try {
        const res = await fetch('import_process.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                learning_area_id: laId,
                type:    importType,
                mapping: mapping,
                rows:    parsed.rows,
                // Save the AI textarea text as source doc for future AI context
                source_doc: (document.getElementById('ai-paste-area').value || '').trim(),
                options: {
                    auto_week:          autoWeek,
                    start_week:         startWeek,
                    start_lesson:       startLesson,
                    continue_existing:  continueEx,
                },
            }),
        });
        const d = await res.json();
        showResults(d);
    } catch (err) {
        showResults({ ok: false, error: String(err) });
    }

    btn.disabled = false;
    btn.textContent = '⚡ Import Data';
}

function showResults(d) {
    const box = document.getElementById('step-results');
    const content = document.getElementById('results-content');
    const actions = document.getElementById('results-actions');
    box.style.display = '';
    box.scrollIntoView({ behavior: 'smooth', block: 'start' });

    const laId = <?= $laId ?: 0 ?>;

    if (!d.ok) {
        content.innerHTML = `<div class="result-box result-err">
            <div class="result-stat">&#10007; Import failed</div>
            <div>${esc(d.error || 'Unknown error')}</div></div>`;
        return;
    }

    const hasSow = (d.sow_new + d.sow_updated) > 0;
    const hasSsm = (d.ssm_new + d.ssm_updated) > 0;
    const hasErr = d.errors && d.errors.length > 0;

    let summary = '<div class="result-box result-ok">';
    summary += '<div class="result-stat">&#10003; Import Complete</div>';
    if (hasSow) summary += `<div>📋 <strong>SOW:</strong> ${d.sow_new} new row${d.sow_new!==1?'s':''} added, ${d.sow_updated} updated</div>`;
    if (hasSsm) summary += `<div>📚 <strong>Curriculum Design:</strong> ${d.ssm_new} new sub-strand${d.ssm_new!==1?'s':''} added, ${d.ssm_updated} updated</div>`;
    if (d.skipped) summary += `<div style="color:#92400e">⏭ ${d.skipped} blank row${d.skipped!==1?'s':''} skipped</div>`;
    if (!hasSow && !hasSsm) summary += '<div style="color:#b45309">No records were saved — check your column mapping.</div>';
    summary += '</div>';

    if (hasErr) {
        summary += `<div class="result-box result-err" style="margin-top:10px">
            <strong>${d.errors.length} error${d.errors.length!==1?'s':''}</strong>
            <ul class="result-errors">${d.errors.map(e => `<li>${esc(e)}</li>`).join('')}</ul>
        </div>`;
    }

    content.innerHTML = summary;
    actions.innerHTML = `
        <a href="index.php?learning_area_id=${laId}" class="btn btn-primary">&#128196; View Scheme of Work</a>
        <a href="sub_strand_meta.php?la=${laId}" class="btn btn-outline">&#128214; View Curriculum Design</a>
        <a href="import.php?la=${laId}" class="btn btn-outline">&#8635; Import More</a>`;
}

// ══ Escape utility ═══════════════════════════════════════════════════════════
function esc(str) {
    return String(str)
        .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
        .replace(/"/g,'&quot;');
}

// ══ Input mode (structured vs AI) ═══════════════════════════════════════════
let inputMode = 'structured';
function setInputMode(mode) {
    inputMode = mode;
    document.getElementById('panel-structured').style.display = mode === 'structured' ? '' : 'none';
    document.getElementById('panel-ai').style.display = mode === 'ai' ? '' : 'none';
    const btnS = document.getElementById('mode-btn-structured');
    const btnA = document.getElementById('mode-btn-ai');
    btnS.style.background = mode === 'structured' ? '#7c3aed' : '#f3f4f6';
    btnS.style.color       = mode === 'structured' ? '#fff'    : '#374151';
    btnA.style.background  = mode === 'ai'         ? '#7c3aed' : '#f3f4f6';
    btnA.style.color       = mode === 'ai'         ? '#fff'    : '#374151';
    if (mode === 'structured') { document.getElementById('step-map').style.display = parsed ? '' : 'none'; }
    else { document.getElementById('step-map').style.display = parsed ? '' : 'none'; }
}

// ══ AI Extraction ════════════════════════════════════════════════════════════
const CHUNK_SIZE = 4500; // chars per AI call — fits within token limits
const CHUNK_OVERLAP = 200; // overlap chars so OCR-broken content at boundaries isn't lost

function chunkText(text) {
    text = text.trim();
    if (text.length <= CHUNK_SIZE) return [text];
    const chunks = [];
    let start = 0;
    while (start < text.length) {
        let end = start + CHUNK_SIZE;
        if (end >= text.length) { chunks.push(text.slice(start)); break; }
        // Split at double-newline (paragraph boundary) if possible
        const dbl = text.lastIndexOf('\n\n', end);
        if (dbl > start + CHUNK_SIZE * 0.4) end = dbl;
        else {
            const sgl = text.lastIndexOf('\n', end);
            if (sgl > start + CHUNK_SIZE * 0.4) end = sgl;
        }
        chunks.push(text.slice(start, end));
        // Step forward but keep an overlap so nothing at the boundary is lost
        start = Math.max(end - CHUNK_OVERLAP, end + 1);
    }
    return chunks;
}

async function doAiExtract() {
    const text = document.getElementById('ai-paste-area').value.trim();
    if (!text) { alert('Please paste some document text first.'); return; }

    const btn = document.getElementById('btn-ai-extract');
    btn.disabled = true;
    btn.textContent = '⏳ Extracting…';
    document.getElementById('ai-extract-progress').style.display = '';
    document.getElementById('ai-extract-status').textContent = '';

    const chunks = chunkText(text);
    let allRows = [];
    let errors  = [];

    for (let i = 0; i < chunks.length; i++) {
        const pct = Math.round(((i) / chunks.length) * 100);
        document.getElementById('ai-prog-fill').style.width  = pct + '%';
        document.getElementById('ai-prog-label').textContent =
            `Chunk ${i+1} of ${chunks.length} — extracting…`;

        try {
            const res = await fetch('ai_generate.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ type: 'extract', text: chunks[i] }),
            });
            const data = await res.json();
            if (data.ok && Array.isArray(data.rows)) {
                allRows = allRows.concat(data.rows);
            } else {
                errors.push(`Chunk ${i+1}: ${data.error || 'Unknown error'}`);
            }
        } catch (err) {
            errors.push(`Chunk ${i+1}: Network error — ${err}`);
        }
    }

    document.getElementById('ai-prog-fill').style.width  = '100%';
    document.getElementById('ai-prog-label').textContent = 'Done';

    if (allRows.length === 0) {
        document.getElementById('ai-extract-status').textContent =
            '⚠ No rows extracted. ' + (errors[0] || 'Try again or check your text.');
        btn.disabled = false; btn.textContent = '⚡ Extract with AI';
        return;
    }

    // Deduplicate by strand+sub_strand (keep last occurrence wins merge)
    const seen = {};
    for (const r of allRows) {
        const k = (r.strand||'') + '|||' + (r.sub_strand||'');
        if (!seen[k]) { seen[k] = r; continue; }
        // Merge: fill empty fields from duplicate
        for (const f of Object.keys(r)) {
            if (!seen[k][f] && r[f]) seen[k][f] = r[f];
        }
    }
    allRows = Object.values(seen);

    const status = `✓ Extracted ${allRows.length} sub-strand${allRows.length!==1?'s':''}`
        + (errors.length ? ` (${errors.length} chunk error${errors.length!==1?'s':''})` : '');
    document.getElementById('ai-extract-status').textContent = status;

    // Build field-name list from all rows
    const allKeys = new Set();
    for (const r of allRows) Object.keys(r).forEach(k => allKeys.add(k));
    const headers = [...allKeys];

    // Convert to rows array (array of arrays) matching headers order
    const rowArrays = allRows.map(r => headers.map(h => String(r[h] || '')));

    // Feed into the existing structured pipeline as JSON
    parsed = { format: 'json', headers, rows: rowArrays };
    document.getElementById('step-map').style.display = '';
    rebuildMapping();
    document.getElementById('step-map').scrollIntoView({ behavior: 'smooth', block: 'start' });

    btn.disabled = false; btn.textContent = '⚡ Extract with AI';
    if (errors.length) console.warn('AI extract errors:', errors);
}

// ══ Parse & Structure Document ════════════════════════════════════════════════
let parsedDocStructure = null;

// Initialize from server-side saved parsed_doc
<?php if ($savedParsedDoc): ?>
try {
    parsedDocStructure = <?= $savedParsedDoc; ?>;
    renderParsedDocSection(parsedDocStructure);
} catch(e) { parsedDocStructure = null; }
<?php endif; ?>

// Split text into overlapping chunks (mirrors PHP chunkTextPhp)
function chunkTextJs(text, size = 4500, overlap = 200) {
    const chunks = [];
    let start = 0;
    while (start < text.length) {
        chunks.push(text.slice(start, start + size));
        start += (size - overlap);
    }
    return chunks;
}

// ── Parse progress saved to localStorage so a retry can resume ─────────
const PARSE_KEY = 'struct_progress_<?= $laId ?: 0 ?>';

function saveParseProgress(chunks, results, index, skipProviders, authToc, phase) {
    try { localStorage.setItem(PARSE_KEY, JSON.stringify({
        chunks, results, index,
        skipProviders: skipProviders || [],
        authToc: authToc || null,
        phase: phase || 2,
    })); } catch(e) {}
}
function loadParseProgress() {
    try { const v = localStorage.getItem(PARSE_KEY); return v ? JSON.parse(v) : null; } catch(e) { return null; }
}
function clearParseProgress() {
    try { localStorage.removeItem(PARSE_KEY); } catch(e) {}
}

function friendlyAiError(msg) {
    if (!msg) return 'Unknown error — please try again.';
    if (/429|rate.?limit|tokens per day|TPD/i.test(msg)) {
        const wait = msg.match(/try again in ([\dm\s\.]+)/i);
        return '⏳ Groq daily token limit reached.' + (wait ? ' Wait ' + wait[1].trim() + ' then click Resume.' : ' Wait 15 min then click Resume.');
    }
    if (/credit balance|too low|billing/i.test(msg)) {
        return '💳 Claude credit balance is empty. Add credits at console.anthropic.com, or wait for Groq to reset.';
    }
    if (/timed out|0 bytes/i.test(msg)) {
        return '🌐 DeepSeek timed out (no network response). Click Resume to retry this section.';
    }
    return msg;
}

function showParseError(label, errMsg, onResume) {
    const statusEl = document.getElementById('ai-extract-status');
    const friendly = friendlyAiError(errMsg);
    statusEl.innerHTML = `<span style="color:#c00">${label}</span><br>
        <span style="font-size:12px;color:#555">${friendly}</span>
        <button onclick="(${onResume.toString()})()" style="margin-left:10px;padding:3px 12px;background:#e67e00;color:#fff;border:none;border-radius:5px;cursor:pointer;font-size:12px">⟳ Resume</button>`;
}

// ── Merge TOC chunks client-side: deduplicate by code, sort numerically ─
function mergeTocJs(tocChunks) {
    const strandMap = {};
    for (const toc of tocChunks) {
        for (const strand of toc.strands || []) {
            const code = (strand.code || '').trim();
            if (!code) continue;
            if (!strandMap[code]) strandMap[code] = { code, name: strand.name || '', sub_strands: {} };
            // Fill blank strand name from any chunk that has it
            if (!strandMap[code].name && strand.name) strandMap[code].name = strand.name;
            for (const ss of strand.sub_strands || []) {
                const ssCode = (ss.code || '').trim();
                if (!ssCode) continue;
                if (!strandMap[code].sub_strands[ssCode]) {
                    strandMap[code].sub_strands[ssCode] = { code: ssCode, name: ss.name || '' };
                } else if (!strandMap[code].sub_strands[ssCode].name && ss.name) {
                    strandMap[code].sub_strands[ssCode].name = ss.name;
                }
            }
        }
    }
    // Sort strands and sub-strands numerically
    return Object.values(strandMap)
        .sort((a, b) => parseFloat(a.code) - parseFloat(b.code))
        .map(s => ({
            code: s.code, name: s.name,
            sub_strands: Object.values(s.sub_strands)
                .sort((a, b) => parseFloat(a.code) - parseFloat(b.code))
        }));
}

// Handle provider auto-skip (shared between phases)
function handleProviderError(d, skipProviders) {
    if (d.deepseek_down && !skipProviders.includes('deepseek')) return [...skipProviders, 'deepseek'];
    if (d.groq_rate_limited && !skipProviders.includes('groq'))  return [...skipProviders, 'groq'];
    return null; // no auto-skip possible
}

// ── Phase 1: Extract TOC (outline only) from all chunks ─────────────────
async function runTocPhase(chunks, skipProviders) {
    const laId  = <?= $laId ?: 0 ?>;
    const total = chunks.length;
    const tocChunks = [];

    for (let i = 0; i < total; i++) {
        document.getElementById('ai-prog-fill').style.width = Math.round((i / total) * 35 + 3) + '%';
        document.getElementById('ai-prog-label').textContent = `Phase 1 of 2: Reading outline (${i+1}/${total})…`;

        let d;
        try {
            const res = await fetch('ai_generate.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ type: 'toc_chunk', chunk: chunks[i], skip_providers: skipProviders }),
            });
            d = await res.json();
        } catch(e) {
            return { ok: false, error: 'Network error on TOC chunk ' + (i+1) + ': ' + e, skipProviders };
        }

        if (!d.ok) {
            const newSkip = handleProviderError(d, skipProviders);
            if (newSkip) {
                skipProviders = newSkip;
                i--; continue; // retry same chunk with different provider
            }
            return { ok: false, error: d.error || 'TOC extraction failed', skipProviders };
        }
        tocChunks.push(d.toc);
    }

    const authToc = mergeTocJs(tocChunks);
    const ssTotal = authToc.reduce((n, s) => n + s.sub_strands.length, 0);
    return { ok: true, authToc, skipProviders, summary: `${authToc.length} strand(s), ${ssTotal} sub-strand(s)` };
}

// ── Phase 2: Fill content for each chunk, guided by authoritative TOC ───
async function runStructureChunks(chunks, startIndex, savedResults, skipProviders, authToc) {
    skipProviders = skipProviders || [];
    authToc = authToc || null;
    const laId   = <?= $laId ?: 0 ?>;
    const total  = chunks.length;
    const btn    = document.getElementById('btn-structure-doc');
    const sBtn   = document.getElementById('btn-ai-extract');
    const chunkResults = savedResults ? [...savedResults] : [];

    for (let i = startIndex; i < total; i++) {
        const pct = Math.round(40 + (i / total) * 52);
        document.getElementById('ai-prog-fill').style.width = pct + '%';
        document.getElementById('ai-prog-label').textContent =
            `Phase 2 of 2: Filling content (${i+1}/${total})…` +
            (skipProviders.includes('deepseek') ? ' [via Groq]' : skipProviders.includes('groq') ? ' [via DeepSeek]' : '');

        let d;
        try {
            const res = await fetch('ai_generate.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    type: 'structure_chunk',
                    chunk: chunks[i],
                    is_first: i === 0,
                    full_text: i === 0 ? document.getElementById('ai-paste-area').value.trim() : '',
                    learning_area_id: laId,
                    skip_providers: skipProviders,
                    authoritative_toc: authToc,
                }),
            });
            d = await res.json();
        } catch(e) {
            saveParseProgress(chunks, chunkResults, i, skipProviders, authToc, 2);
            showParseError(`✗ Section ${i+1} network error: ${e}`, e.toString(),
                () => { btn.disabled = sBtn.disabled = true; btn.textContent = '⏳ Resuming…';
                        runStructureChunks(chunks, i, chunkResults, skipProviders, authToc); });
            btn.disabled = sBtn.disabled = false;
            btn.textContent = '📖 Parse & Structure';
            return;
        }

        if (!d.ok) {
            const newSkip = handleProviderError(d, skipProviders);
            if (newSkip) {
                const pLabel = newSkip.includes('deepseek') ? 'Groq' : 'DeepSeek';
                skipProviders = newSkip;
                document.getElementById('ai-prog-label').textContent =
                    `Provider switched — retrying section ${i+1} via ${pLabel}…`;
                i--; continue;
            }
            saveParseProgress(chunks, chunkResults, i, skipProviders, authToc, 2);
            showParseError(`✗ Section ${i+1} of ${total} failed.`, d.error || 'Unknown error',
                () => { btn.disabled = sBtn.disabled = true; btn.textContent = '⏳ Resuming…';
                        runStructureChunks(chunks, i, chunkResults, skipProviders, authToc); });
            btn.disabled = sBtn.disabled = false;
            btn.textContent = '📖 Parse & Structure';
            return;
        }
        chunkResults.push(d.structured);
        saveParseProgress(chunks, chunkResults, i + 1, skipProviders, authToc, 2);
    }

    // All chunks done — merge, fix assignments, save
    document.getElementById('ai-prog-fill').style.width = '94%';
    document.getElementById('ai-prog-label').textContent = 'Merging and saving…';

    try {
        const finalRes = await fetch('ai_generate.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ type: 'finalize_structure', learning_area_id: laId, chunk_results: chunkResults }),
        });
        const finalD = await finalRes.json();
        document.getElementById('ai-prog-fill').style.width = '100%';

        if (!finalD.ok) {
            document.getElementById('ai-extract-status').textContent = '✗ ' + (finalD.error || 'Finalize failed');
        } else {
            clearParseProgress();
            parsedDocStructure = finalD.structured;
            renderParsedDocSection(finalD.structured);
            document.getElementById('ai-extract-status').textContent =
                `✓ Structured: ${finalD.strand_count} strand(s), ${finalD.sub_strand_count} sub-strand(s) — saved to database`;
            document.getElementById('parsed-doc-section').style.display = '';
            openFullDocView();
        }
    } catch(e) {
        document.getElementById('ai-extract-status').textContent = '✗ Final merge failed: ' + e;
    }
    btn.disabled = sBtn.disabled = false;
    btn.textContent = '📖 Parse & Structure';
}

async function doStructureDoc() {
    const text = document.getElementById('ai-paste-area').value.trim();
    const laId = <?= $laId ?: 0 ?>;
    if (!text && !laId) {
        alert('Please paste your curriculum document text first, or select a learning area that already has a saved document.');
        return;
    }
    const btn  = document.getElementById('btn-structure-doc');
    const sBtn = document.getElementById('btn-ai-extract');

    // Check for saved partial Phase 2 progress
    const saved = loadParseProgress();
    if (saved && saved.phase === 2 && saved.results && saved.results.length > 0 && saved.index < saved.chunks.length) {
        const resume = confirm(
            `Saved progress found: ${saved.results.length} of ${saved.chunks.length} content sections completed.\n\nResume from section ${saved.index + 1}? (Cancel = start fresh)`
        );
        if (resume) {
            btn.disabled = sBtn.disabled = true;
            btn.textContent = '⏳ Resuming…';
            document.getElementById('ai-extract-progress').style.display = '';
            document.getElementById('ai-prog-fill').style.width = Math.round(40 + (saved.index / saved.chunks.length) * 52) + '%';
            document.getElementById('ai-prog-label').textContent = `Resuming Phase 2 from section ${saved.index + 1}…`;
            document.getElementById('ai-extract-status').textContent = '';
            await runStructureChunks(saved.chunks, saved.index, saved.results, saved.skipProviders || [], saved.authToc || null);
            return;
        }
        clearParseProgress();
    }

    btn.disabled = sBtn.disabled = true;
    btn.textContent = '⏳ Parsing…';
    document.getElementById('ai-extract-progress').style.display = '';
    document.getElementById('ai-prog-fill').style.width = '3%';
    document.getElementById('ai-prog-label').textContent = 'Preparing…';
    document.getElementById('ai-extract-status').textContent = '';

    const chunks = chunkTextJs(text);

    // Phase 1: Extract document outline (TOC)
    const tocResult = await runTocPhase(chunks, []);
    if (!tocResult.ok) {
        document.getElementById('ai-extract-status').innerHTML =
            `<span style="color:#c00">✗ Outline extraction failed: ${tocResult.error}</span>`;
        btn.disabled = sBtn.disabled = false;
        btn.textContent = '📖 Parse & Structure';
        return;
    }
    const { authToc, skipProviders } = tocResult;
    const ssTotal = authToc.reduce((n, s) => n + s.sub_strands.length, 0);
    document.getElementById('ai-extract-status').textContent =
        `✓ Outline: ${authToc.length} strand(s), ${ssTotal} sub-strand(s) found — now filling content…`;
    document.getElementById('ai-prog-fill').style.width = '40%';

    // Phase 2: Fill content
    await runStructureChunks(chunks, 0, [], skipProviders, authToc);
}

async function doReverify() {
    const laId = <?= $laId ?: 0 ?>;
    if (!laId) { alert('Please select a learning area first.'); return; }
    const btn = document.getElementById('btn-reverify');
    btn.disabled = true;
    btn.textContent = '⏳ Checking…';
    document.getElementById('ai-extract-status').textContent = 'Re-reading original text and checking for errors…';

    try {
        const res = await fetch('ai_generate.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ type: 'reverify_doc', learning_area_id: laId }),
        });
        const d = await res.json();
        if (!d.ok) {
            document.getElementById('ai-extract-status').textContent = '✗ Re-verify failed: ' + (d.error || 'Unknown error');
        } else {
            parsedDocStructure = d.structured;
            renderParsedDocSection(d.structured);
            document.getElementById('ai-extract-status').textContent =
                `✓ Re-verified: ${d.sub_strand_count} sub-strand(s) — corrections saved`;
            openFullDocView();
        }
    } catch(e) {
        document.getElementById('ai-extract-status').textContent = '✗ Network error: ' + e;
    }
    btn.disabled = false;
    btn.textContent = '🔄 Re-verify & Fix';
}

function renderParsedDocSection(structured) {
    const statsEl = document.getElementById('parsed-doc-stats');
    const treeEl  = document.getElementById('parsed-doc-tree');
    const strands = structured.strands || [];
    const totalSS = strands.reduce((n, s) => n + (s.sub_strands||[]).length, 0);
    const summary = `${strands.length} strand(s) · ${totalSS} sub-strand(s)`;
    statsEl.textContent = summary;
    document.getElementById('fdm-stats').textContent = summary;

    let html = '';
    for (const strand of strands) {
        const ssCount = (strand.sub_strands||[]).length;
        html += `<div class="struct-strand">
          <div class="struct-strand-hdr" onclick="this.nextElementSibling.classList.toggle('collapsed')">
            ▶ ${esc(strand.code||'')} ${esc(strand.name||'')}
            <span class="struct-ss-count">${ssCount} sub-strand(s)</span>
          </div>
          <div class="struct-sub-list">`;

        for (const ss of strand.sub_strands || []) {
            const sloItems = (ss.specific_learning_outcomes||[]).filter(s => !s.toLowerCase().includes('by the end'));
            const hasData  = sloItems.length > 0 || (ss.key_inquiry_questions||[]).length > 0;
            html += `<div class="struct-ss ${hasData ? '' : 'struct-ss-empty'}">
              <div class="struct-ss-hdr" onclick="this.nextElementSibling.classList.toggle('collapsed')">
                ◦ ${esc(ss.code||'')} ${esc(ss.name||'')}
                <span class="struct-ss-meta">${sloItems.length} SLO(s)</span>
              </div>
              <div class="struct-ss-detail collapsed">`;

            const fieldDefs = [
                ['key_inquiry_questions',      'Key Inquiry Questions'],
                ['specific_learning_outcomes', 'Specific Learning Outcomes'],
                ['learning_experiences',       'Learning Experiences'],
                ['core_competencies',          'Core Competencies'],
                ['values_and_attitudes',       'Values & Attitudes'],
                ['pertinent_contemporary_issues', 'PCIs'],
                ['links_to_other_learning_areas', 'Links to Other Areas'],
                ['learning_resources',         'Resources'],
                ['assessment',                 'Assessment'],
            ];
            for (const [fkey, flabel] of fieldDefs) {
                const items = ss[fkey] || [];
                if (!items.length) continue;
                html += `<div class="struct-field">
                  <div class="struct-field-lbl">${flabel}</div>
                  <ul class="struct-field-list">${items.map(i => `<li>${esc(i)}</li>`).join('')}</ul>
                </div>`;
            }
            html += `</div></div>`;
        }
        html += `</div></div>`;
    }
    treeEl.innerHTML = html || '<div style="color:var(--muted)">No structure extracted yet.</div>';
}

// ══ Full-page document modal ══════════════════════════════════════════════════
function openFullDocView() {
    if (!parsedDocStructure) { alert('No structured document yet. Run "Parse & Structure" first.'); return; }
    document.getElementById('fdm-body').innerHTML = buildFullDocHtml(parsedDocStructure);
    document.getElementById('full-doc-modal').classList.add('open');
    document.body.style.overflow = 'hidden';
}
function closeFullDocView() {
    document.getElementById('full-doc-modal').classList.remove('open');
    document.body.style.overflow = '';
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeFullDocView(); });

function buildFullDocHtml(structured) {
    const fieldDefs = [
        ['key_inquiry_questions',        'Key Inquiry Questions',     true],
        ['specific_learning_outcomes',   'Specific Learning Outcomes',true],
        ['learning_experiences',         'Learning Experiences',      true],
        ['core_competencies',            'Core Competencies',         false],
        ['values_and_attitudes',         'Values & Attitudes',        false],
        ['pertinent_contemporary_issues','PCIs',                      false],
        ['links_to_other_learning_areas','Links to Other Areas',      false],
        ['learning_resources',           'Learning Resources',        false],
        ['assessment',                   'Assessment',                false],
    ];

    let html = '';
    for (const strand of structured.strands || []) {
        html += `<div class="fdm-strand">
            <div class="fdm-strand-hdr">${esc((strand.code||'') + ' ' + (strand.name||''))}</div>`;

        for (const ss of strand.sub_strands || []) {
            html += `<div class="fdm-ss">
                <div class="fdm-ss-hdr">${esc((ss.code||'') + ' ' + (ss.name||''))}</div>
                <div class="fdm-ss-grid">`;

            for (const [fkey, flabel, full] of fieldDefs) {
                const items = (ss[fkey] || []).filter(i => i.trim() !== '');
                if (!items.length) continue; // skip empty fields — no ghost boxes
                html += `<div class="fdm-field${full ? ' full' : ''}">
                    <div class="fdm-field-lbl">${flabel}</div>
                    <div class="fdm-field-val">`;
                    // First item that is an intro stem (contains "by the end" or "learner is guided")
                    const stemIdx = items.findIndex(i =>
                        /by the end|learner is guided|the learner should/i.test(i));
                    if (stemIdx === 0 && items.length > 1) {
                        html += `<div class="stem">${esc(items[0])}</div><ul>`;
                        html += items.slice(1).map(i => `<li>${esc(i)}</li>`).join('');
                        html += '</ul>';
                    } else {
                        html += `<ul>${items.map(i => `<li>${esc(i)}</li>`).join('')}</ul>`;
                    }
                html += `</div></div>`;
            }
            html += `</div></div>`;
        }
        html += `</div>`;
    }
    return html || '<p style="color:var(--muted)">No content structured yet.</p>';
}

function doExtractFromParsed() {
    if (!parsedDocStructure || !(parsedDocStructure.strands||[]).length) {
        alert('No structured document loaded. Run "Parse & Structure" first.');
        return;
    }
    const rows = [];
    for (const strand of parsedDocStructure.strands || []) {
        const sName = [strand.code, strand.name].filter(Boolean).join(' ').trim();
        for (const ss of strand.sub_strands || []) {
            const ssName = [ss.code, ss.name].filter(Boolean).join(' ').trim();
            rows.push({
                strand:               sName,
                sub_strand:           ssName,
                slo_cd:               (ss.specific_learning_outcomes || []).join('\n'),
                slo_sow:              (ss.specific_learning_outcomes || []).join('\n'),
                le_cd:                (ss.learning_experiences        || []).join('\n'),
                le_sow:               (ss.learning_experiences        || []).join('\n'),
                key_inquiry_qs:       (ss.key_inquiry_questions       || []).join('\n'),
                core_competencies:    (ss.core_competencies           || []).join(', '),
                values_attit:         (ss.values_and_attitudes        || []).join(', '),
                pcis:                 (ss.pertinent_contemporary_issues || []).join(', '),
                links_to_other_areas: (ss.links_to_other_learning_areas || []).join(', '),
                resources:            (ss.learning_resources          || []).join('\n'),
                assessment:           (ss.assessment                  || []).join('\n'),
            });
        }
    }
    if (!rows.length) { alert('No sub-strands found in structured document.'); return; }

    const headers  = ['strand','sub_strand','slo_cd','slo_sow','le_cd','le_sow',
                      'key_inquiry_qs','core_competencies','values_attit','pcis',
                      'links_to_other_areas','resources','assessment'];
    const rowArrays = rows.map(r => headers.map(h => r[h] || ''));

    parsed = { format: 'json', headers, rows: rowArrays };
    document.getElementById('step-map').style.display = '';
    rebuildMapping();
    document.getElementById('step-map').scrollIntoView({ behavior: 'smooth', block: 'start' });
    document.getElementById('ai-extract-status').textContent =
        `↓ ${rows.length} sub-strand(s) from structured document — ready to map and import`;
}

// ══ Init ══════════════════════════════════════════════════════════════════════
toggleAutoWeek(); // sync initial state
checkImportReady();
<?php if ($laId): ?>
document.getElementById('la-select').value = '<?= $laId ?>';
<?php endif; ?>
</script>

<!-- ══ Full Document View Modal ══════════════════════════════════════════ -->
<div id="full-doc-modal" onclick="if(event.target===this)closeFullDocView()">
  <div class="fdm-inner">
    <div class="fdm-topbar">
      <h2>📄 Structured Curriculum Document</h2>
      <span class="fdm-topbar-stats" id="fdm-stats"></span>
      <button class="fdm-tbtn fdm-tbtn-primary" onclick="doExtractFromParsed();closeFullDocView()">↓ Extract &amp; Import</button>
      <button class="fdm-tbtn" onclick="doReverify()">🔄 Re-verify</button>
      <button class="fdm-tbtn" onclick="closeFullDocView()" style="background:rgba(255,255,255,.08)">✕ Close</button>
    </div>
    <div class="fdm-body" id="fdm-body"></div>
  </div>
</div>
</body>
</html>
