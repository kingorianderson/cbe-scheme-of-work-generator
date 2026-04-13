<?php
// edit_ssm.php — Edit curriculum design metadata for one sub-strand
// GET params: la=INT, strand=STR, sub_strand=STR
require_once 'config.php';
$pdo = getDB();

$laId      = isset($_GET['la'])         ? (int)$_GET['la']              : 0;
$strand    = isset($_GET['strand'])     ? trim($_GET['strand'])          : '';
$subStrand = isset($_GET['sub_strand']) ? trim($_GET['sub_strand'])      : '';

if ($laId < 1 || $strand === '' || $subStrand === '') {
    header('Location: curriculum.php'); exit;
}

// Load learning area + grade
$laStmt = $pdo->prepare(
    "SELECT la.*, g.name AS grade_name, g.id AS grade_id
     FROM learning_areas la JOIN grades g ON g.id = la.grade_id WHERE la.id = :id"
);
$laStmt->execute([':id' => $laId]);
$la = $laStmt->fetch();
if (!$la) { header('Location: curriculum.php'); exit; }

// Load existing meta (if any)
$metaStmt = $pdo->prepare(
    "SELECT * FROM sub_strand_meta
     WHERE learning_area_id = :la AND strand = :strand AND sub_strand = :ss"
);
$metaStmt->execute([':la' => $laId, ':strand' => $strand, ':ss' => $subStrand]);
$meta = $metaStmt->fetch() ?: [
    'key_inquiry_qs'       => '',
    'core_competencies'    => '',
    'values_attit'         => '',
    'pcis'                 => '',
    'links_to_other_areas' => '',
    'resources'            => '',
    'assessment'           => '',
];

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF-style: verify IDs match
    $postLa  = (int)($_POST['la']  ?? 0);
    $postSt  = trim($_POST['strand']     ?? '');
    $postSs  = trim($_POST['sub_strand'] ?? '');

    if ($postLa !== $laId || $postSt !== $strand || $postSs !== $subStrand) {
        $errors[] = 'Invalid request.';
    } else {
        $fields = [
            'key_inquiry_qs'       => trim($_POST['key_inquiry_qs']       ?? ''),
            'core_competencies'    => trim($_POST['core_competencies']     ?? ''),
            'values_attit'         => trim($_POST['values_attit']          ?? ''),
            'pcis'                 => trim($_POST['pcis']                  ?? ''),
            'links_to_other_areas' => trim($_POST['links_to_other_areas']  ?? ''),
            'resources'            => trim($_POST['resources']             ?? ''),
            'assessment'           => trim($_POST['assessment']            ?? ''),
        ];

        $pdo->prepare(
            "INSERT INTO sub_strand_meta
                 (learning_area_id, strand, sub_strand,
                  key_inquiry_qs, core_competencies, values_attit, pcis,
                  links_to_other_areas, resources, assessment)
             VALUES
                 (:la, :strand, :ss,
                  :kiq, :cc, :val, :pcis, :links, :res, :assess)
             ON DUPLICATE KEY UPDATE
                  key_inquiry_qs       = VALUES(key_inquiry_qs),
                  core_competencies    = VALUES(core_competencies),
                  values_attit         = VALUES(values_attit),
                  pcis                 = VALUES(pcis),
                  links_to_other_areas = VALUES(links_to_other_areas),
                  resources            = VALUES(resources),
                  assessment           = VALUES(assessment)"
        )->execute([
            ':la'     => $laId,
            ':strand' => $strand,
            ':ss'     => $subStrand,
            ':kiq'    => $fields['key_inquiry_qs'],
            ':cc'     => $fields['core_competencies'],
            ':val'    => $fields['values_attit'],
            ':pcis'   => $fields['pcis'],
            ':links'  => $fields['links_to_other_areas'],
            ':res'    => $fields['resources'],
            ':assess' => $fields['assessment'],
        ]);

        header('Location: sub_strand_meta.php?la=' . $laId . '&msg=saved');
        exit;
    }

    // Re-populate form on error
    $meta = array_merge($meta, $fields ?? []);
}

function e(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }

$fields = [
    ['key_inquiry_qs',       'Suggested Key Inquiry Question(s)',      'The questions that guide learning in this sub-strand.'],
    ['core_competencies',    'Core Competencies to be Developed',      'e.g. Communication, Critical thinking, Creativity, Collaboration...'],
    ['values_attit',         'Values',                                  'e.g. Integrity, Responsibility, Respect, Unity...'],
    ['pcis',                 'Pertinent and Contemporary Issues (PCIs)','e.g. Environmental education, Health, Financial literacy...'],
    ['links_to_other_areas', 'Link to Other Learning Areas',            'Name the learning areas and how they relate.'],
    ['resources',            'Learning Resources',                      'Materials, tools, digital resources needed for this sub-strand.'],
    ['assessment',           'Assessment',                              'How learner achievement will be assessed in this sub-strand (e.g. oral questions, observation, written exercise).'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Curriculum Design — <?= e($subStrand) ?></title>
<link rel="stylesheet" href="style.css">
<style>
  .ssm-form-wrap { max-width: 800px; }
  .field-group { margin-bottom: 22px; }
  .field-group label { display:block; font-weight:600; font-size:13px; margin-bottom:4px; color:#111827; }
  .field-group .hint  { font-size:12px; color:var(--muted); margin-bottom:6px; }
  .field-group textarea {
    width:100%; box-sizing:border-box;
    padding:10px 12px; border:1px solid var(--border);
    border-radius:6px; font-size:13px; font-family:inherit;
    resize:vertical; min-height:90px; line-height:1.5;
  }
  .field-group textarea:focus { outline:none; border-color:var(--primary); box-shadow:0 0 0 3px rgba(26,86,219,.12); }
  .ssm-meta-header {
    background:#f3f4f6; border:1px solid var(--border);
    border-radius:8px; padding:14px 18px; margin-bottom:24px;
  }
  .ssm-meta-header .strand-label { font-size:12px; color:var(--muted); text-transform:uppercase; letter-spacing:.05em; }
  .ssm-meta-header .ss-name { font-size:17px; font-weight:700; margin-top:3px; }
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
      <li><a href="sub_strand_meta.php?la=<?= $laId ?>">Curriculum Design</a></li>
      <li class="active">Edit Sub-Strand</li>
    </ol>
  </nav>

  <header>
    <div>
      <h1>Edit Curriculum Design</h1>
      <small style="color:var(--muted)"><?= e($la['grade_name']) ?> &bull; <?= e($la['name']) ?></small>
    </div>
    <a href="sub_strand_meta.php?la=<?= $laId ?>" class="btn btn-outline">&larr; Back</a>
  </header>

  <?php if ($errors): ?>
    <div class="flash flash-err"><?= implode('<br>', array_map('e', $errors)) ?></div>
  <?php endif; ?>

  <div class="ssm-form-wrap">
    <div class="ssm-meta-header">
      <div class="strand-label"><?= e($strand) ?></div>
      <div class="ss-name"><?= e($subStrand) ?></div>
    </div>

    <form method="post" action="edit_ssm.php?la=<?= $laId ?>&strand=<?= urlencode($strand) ?>&sub_strand=<?= urlencode($subStrand) ?>">
      <input type="hidden" name="la"         value="<?= $laId ?>">
      <input type="hidden" name="strand"     value="<?= e($strand) ?>">
      <input type="hidden" name="sub_strand" value="<?= e($subStrand) ?>">

      <?php foreach ($fields as [$name, $label, $hint]): ?>
        <div class="field-group">
          <label for="<?= $name ?>"><?= e($label) ?></label>
          <div class="hint"><?= e($hint) ?></div>
          <textarea id="<?= $name ?>" name="<?= $name ?>"><?= e($meta[$name] ?? '') ?></textarea>
        </div>
      <?php endforeach; ?>

      <div style="display:flex;gap:12px;margin-top:8px">
        <button type="submit" class="btn btn-primary">Save</button>
        <a href="sub_strand_meta.php?la=<?= $laId ?>" class="btn btn-outline">Cancel</a>
      </div>
    </form>
  </div>

</div>
</body>
</html>
