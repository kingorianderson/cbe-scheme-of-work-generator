<?php
require_once 'config.php';

$pdo = getDB();
$id             = isset($_GET['id'])               ? (int)$_GET['id']               : 0;
$learningAreaId = isset($_GET['learning_area_id']) ? (int)$_GET['learning_area_id'] : 0;

// Load learning area + grade context
$learningAreaName = '';
$gradeName        = '';
if ($learningAreaId > 0) {
    $laStmt = $pdo->prepare(
        "SELECT la.name AS la_name, g.name AS grade_name
         FROM learning_areas la JOIN grades g ON g.id = la.grade_id WHERE la.id = :id"
    );
    $laStmt->execute([':id' => $learningAreaId]);
    $laRow = $laStmt->fetch();
    if ($laRow) {
        $learningAreaName = $laRow['la_name'];
        $gradeName        = $laRow['grade_name'];
    }
}

// Show AI buttons if explicitly enabled OR any API key is configured
$aiEnabled = false;
try {
    $aiRows = $pdo->query("SELECT setting_key, setting_value FROM app_settings WHERE setting_key IN ('ai_enabled','groq_api_key','ai_api_key')")->fetchAll(PDO::FETCH_KEY_PAIR);
    $aiEnabled = ($aiRows['ai_enabled'] ?? '0') === '1'
        || !empty($aiRows['groq_api_key'])
        || !empty($aiRows['ai_api_key']);
} catch (Exception $e) { /* app_settings table may not exist yet */ }

// Load existing record when editing
$record = [
    'week' => '', 'lesson' => '', 'strand' => '', 'sub_strand' => '',
    'slo_cd' => '', 'slo_sow' => '', 'le_cd' => '', 'le_sow' => '',
    'key_inquiry' => '', 'resources' => '', 'assessment' => '', 'remarks' => '',
    'learning_area_id' => $learningAreaId,
];
if ($id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM scheme_of_work WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $found = $stmt->fetch();
    if (!$found) {
        header('Location: index.php');
        exit;
    }
    $record = $found;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitise & validate
    $learningAreaId = isset($_POST['learning_area_id']) ? (int)$_POST['learning_area_id'] : 0;
    $week       = (int)($_POST['week']       ?? 0);
    $lesson     = (int)($_POST['lesson']     ?? 0);
    $strand     = trim($_POST['strand']      ?? '');
    $sub_strand = trim($_POST['sub_strand']  ?? '');
    $slo_cd     = trim($_POST['slo_cd']      ?? '');
    $slo_sow    = trim($_POST['slo_sow']     ?? '');
    $le_cd      = trim($_POST['le_cd']       ?? '');
    $le_sow     = trim($_POST['le_sow']      ?? '');

    // Enforce standard stems
    $sloPrefix = 'By the end of the lesson, the learner should be able to:';
    $lePrefix  = 'Learner is guided to:';
    if ($slo_sow !== '' && stripos($slo_sow, 'by the end of the lesson') !== 0)
        $slo_sow = $sloPrefix . "\n" . $slo_sow;
    if ($le_sow !== '' && stripos($le_sow, 'learner is guided to') !== 0)
        $le_sow = $lePrefix . "\n" . $le_sow;
    $key_inquiry = trim($_POST['key_inquiry'] ?? '');
    $resources  = trim($_POST['resources']   ?? '');
    $assessment = trim($_POST['assessment']  ?? '');
    $remarks    = trim($_POST['remarks']     ?? '');

    if ($week < 1 || $week > 52) $errors[] = 'Week must be between 1 and 52.';
    if ($lesson < 1)             $errors[] = 'Lesson must be at least 1.';
    if ($strand === '')          $errors[] = 'Strand is required.';
    if ($sub_strand === '')      $errors[] = 'Sub-Strand is required.';

    $back = $learningAreaId > 0 ? 'index.php?learning_area_id=' . $learningAreaId : 'index.php';

    if (empty($errors)) {
        $data = [
            ':learning_area_id' => $learningAreaId ?: null,
            ':week' => $week, ':lesson' => $lesson,
            ':strand' => $strand, ':sub_strand' => $sub_strand,
            ':slo_cd' => $slo_cd, ':slo_sow' => $slo_sow,
            ':le_cd' => $le_cd, ':le_sow' => $le_sow,
            ':key_inquiry' => $key_inquiry, ':resources' => $resources,
            ':assessment' => $assessment, ':remarks' => $remarks,
        ];

        if ($id > 0) {
            $sql = 'UPDATE scheme_of_work SET
                        learning_area_id=:learning_area_id,
                        week=:week, lesson=:lesson, strand=:strand, sub_strand=:sub_strand,
                        slo_cd=:slo_cd, slo_sow=:slo_sow, le_cd=:le_cd, le_sow=:le_sow,
                        key_inquiry=:key_inquiry, resources=:resources,
                        assessment=:assessment, remarks=:remarks
                    WHERE id=:id';
            $data[':id'] = $id;
            $pdo->prepare($sql)->execute($data);
            header("Location: $back&msg=updated");
        } else {
            $sql = 'INSERT INTO scheme_of_work
                        (learning_area_id,week,lesson,strand,sub_strand,slo_cd,slo_sow,le_cd,le_sow,key_inquiry,resources,assessment,remarks)
                    VALUES
                        (:learning_area_id,:week,:lesson,:strand,:sub_strand,:slo_cd,:slo_sow,:le_cd,:le_sow,:key_inquiry,:resources,:assessment,:remarks)';
            $pdo->prepare($sql)->execute($data);
            header("Location: $back&msg=added");
        }
        exit;
    }

    // Re-populate form values on error
    $record = array_merge($record, compact(
        'week','lesson','strand','sub_strand','slo_cd','slo_sow',
        'le_cd','le_sow','key_inquiry','resources','assessment','remarks'
    ));
}

function e(?string $v): string { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }
$title    = $id > 0 ? 'Edit Record' : 'Add Record';
$laId     = (int)$record['learning_area_id'];
$backUrl  = $laId > 0 ? 'index.php?learning_area_id=' . $laId : 'index.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $title ?> — Scheme of Work</title>
<link rel="stylesheet" href="style.css">
<style>
  .ai-btn {
    display: inline-flex; align-items: center; gap: 6px;
    background: #6d43d9; color: #fff; border: none;
    padding: 6px 14px; border-radius: 5px; font-size: 12px;
    font-weight: 600; cursor: pointer; margin-bottom: 6px;
    transition: background .15s;
  }
  .ai-btn:hover  { background: #5836ba; }
  .ai-btn:disabled { background: #a78bda; cursor: not-allowed; }
  .ai-banner {
    background: #f5f3ff; border: 1px solid #c4b5fd;
    border-radius: 6px; padding: 10px 14px; margin-bottom: 18px;
    font-size: 13px; color: #4c1d95; display: flex;
    align-items: center; gap: 10px;
  }
  .ai-banner svg { flex-shrink: 0; }
  .ai-status { font-size: 12px; color: #6d43d9; margin-top: 4px; min-height: 16px; }
  .ai-error  { font-size: 12px; color: #b91c1c; margin-top: 4px; }
</style>
</head>
<body>
<div class="page-wrap form-page">
  <header>
    <h1><?= $title ?></h1>
    <a href="<?= e($backUrl) ?>" class="btn btn-outline">&larr; Back</a>
  </header>

  <?php if ($errors): ?>
  <ul class="error-list">
    <?php foreach ($errors as $err): ?>
      <li><?= e($err) ?></li>
    <?php endforeach; ?>
  </ul>
  <?php endif; ?>

  <form method="post" class="sow-form" novalidate>
    <input type="hidden" name="learning_area_id" value="<?= $laId ?>">
    <div class="form-grid">

      <div class="form-group">
        <label for="week">Week <span class="req">*</span></label>
        <input type="number" id="week" name="week" min="1" max="52"
               value="<?= e((string)$record['week']) ?>" required>
      </div>

      <div class="form-group">
        <label for="lesson">Lesson <span class="req">*</span></label>
        <input type="number" id="lesson" name="lesson" min="1"
               value="<?= e((string)$record['lesson']) ?>" required>
      </div>

      <div class="form-group col-2">
        <label for="strand">Strand <span class="req">*</span></label>
        <input type="text" id="strand" name="strand"
               value="<?= e($record['strand']) ?>" required>
      </div>

      <div class="form-group col-2">
        <label for="sub_strand">Sub-Strand <span class="req">*</span></label>
        <input type="text" id="sub_strand" name="sub_strand"
               value="<?= e($record['sub_strand']) ?>" required>
      </div>

      <div class="form-group col-2">
        <label for="slo_cd">Specific Learning Outcomes (CD)</label>
        <textarea id="slo_cd" name="slo_cd" rows="4"><?= e($record['slo_cd']) ?></textarea>
      </div>

      <div class="form-group col-2">
        <label for="slo_sow">Specific Learning Outcomes (SOW)</label>
        <textarea id="slo_sow" name="slo_sow" rows="4"><?= e($record['slo_sow']) ?></textarea>
      </div>

      <div class="form-group col-2">
        <label for="le_cd">Learning Experiences (CD)</label>
        <textarea id="le_cd" name="le_cd" rows="4"><?= e($record['le_cd']) ?></textarea>
      </div>

      <div class="form-group col-2">
        <label for="le_sow">Learning Experiences (SOW)</label>
        <textarea id="le_sow" name="le_sow" rows="4"><?= e($record['le_sow']) ?></textarea>
      </div>

      <?php if ($aiEnabled): ?>
      <div class="form-group col-full" style="padding-bottom:0">
        <div class="ai-banner">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#6d43d9" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
          <div>
            <strong>AI Assist</strong> — Fill in Strand, Sub-Strand, SLO (SOW), and Learning Experience (SOW) above, then click the button to get AI suggestions.
            <div class="ai-status" id="ai-status"></div>
            <div class="ai-error"  id="ai-error"></div>
          </div>
          <button type="button" class="ai-btn" id="ai-suggest-btn" onclick="aiSuggest()">
            ✦ AI Suggest
          </button>
        </div>
      </div>
      <?php endif; ?>

      <div class="form-group col-full">
        <label for="key_inquiry">Key Inquiry Questions</label>
        <textarea id="key_inquiry" name="key_inquiry" rows="3"><?= e($record['key_inquiry']) ?></textarea>
      </div>

      <div class="form-group col-2">
        <label for="resources">Learning Resources</label>
        <textarea id="resources" name="resources" rows="3"><?= e($record['resources']) ?></textarea>
      </div>

      <div class="form-group col-2">
        <label for="assessment">Assessment</label>
        <textarea id="assessment" name="assessment" rows="3"><?= e($record['assessment']) ?></textarea>
      </div>

      <div class="form-group col-full">
        <label for="remarks">Remarks</label>
        <textarea id="remarks" name="remarks" rows="2"><?= e($record['remarks']) ?></textarea>
      </div>

    </div><!-- .form-grid -->

    <div class="form-actions">
      <button type="submit" class="btn btn-primary"><?= $id > 0 ? 'Update Record' : 'Save Record' ?></button>
      <a href="<?= e($backUrl) ?>" class="btn btn-outline">Cancel</a>
    </div>
  </form>
</div>

<?php if ($aiEnabled): ?>
<script>
async function aiSuggest() {
  const btn    = document.getElementById('ai-suggest-btn');
  const status = document.getElementById('ai-status');
  const errEl  = document.getElementById('ai-error');

  const strand    = document.getElementById('strand').value.trim();
  const subStrand = document.getElementById('sub_strand').value.trim();
  const sloSow    = document.getElementById('slo_sow').value.trim();
  const leSow     = document.getElementById('le_sow').value.trim();

  if (!strand || !subStrand) {
    errEl.textContent = 'Please fill in Strand and Sub-Strand first.';
    return;
  }

  btn.disabled = true;
  btn.textContent = '⏳ Thinking…';
  status.textContent = 'Asking AI…';
  errEl.textContent  = '';

  try {
    const res = await fetch('ai_generate.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        type:             'suggest',
        record_id:        <?= (int)$id ?>,
        learning_area_id: <?= (int)$laId ?>,
        learning_area:    <?= json_encode($learningAreaName) ?>,
        grade:            <?= json_encode($gradeName) ?>,
        strand,
        sub_strand: subStrand,
        slo_sow:    sloSow,
        le_sow:     leSow,
      }),
    });

    const data = await res.json();

    if (!data.ok) {
      errEl.textContent = 'AI error: ' + (data.error || 'Unknown error');
      status.textContent = '';
    } else {
      document.getElementById('key_inquiry').value = data.kiq;
      document.getElementById('resources').value   = data.resources;
      document.getElementById('assessment').value  = data.assessment;

      status.textContent = data.saved
        ? '✓ Suggestions applied and saved to record.'
        : '✓ Suggestions applied. Click Save Record to store.';
    }
  } catch (err) {
    errEl.textContent = 'Network error: ' + err.message;
  } finally {
    btn.disabled = false;
    btn.textContent = '✦ AI Suggest';
  }
}
</script>
<?php endif; ?>

</body>
</html>
