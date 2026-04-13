<?php
// settings.php — Application settings (AI / Claude configuration)
require_once 'config.php';
$pdo = getDB();

// Ensure table exists before reading
try {
    $allSettings = $pdo->query("SELECT setting_key, setting_value FROM app_settings")
                       ->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (PDOException $e) {
    // Table not yet created — redirect to migration
    header('Location: migrate3.php');
    exit;
}

$aiEnabled      = (bool)($allSettings['ai_enabled']      ?? false);
$aiModel        = $allSettings['ai_model']        ?? 'claude-sonnet-4-5';
$hasKey         = !empty($allSettings['ai_api_key']);   // only show if key exists; never echo it back
$hasGroqKey     = !empty($allSettings['groq_api_key']);
$groqModel      = $allSettings['groq_model']      ?? 'llama-3.3-70b-versatile';
$hasDeepseekKey = !empty($allSettings['deepseek_api_key']);
$deepseekModel  = $allSettings['deepseek_model']  ?? 'deepseek-chat';

$flash    = '';
$flashErr = '';
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'saved')                $flash    = 'Settings saved successfully.';
    if ($_GET['msg'] === 'tested_ok')            $flash    = 'Connection successful! Claude API is reachable.';
    if ($_GET['msg'] === 'tested_fail')          $flashErr = 'Connection failed. Check your API key and try again.';
    if ($_GET['msg'] === 'invalid_key')          $flashErr = 'Invalid API key format. Anthropic keys must start with sk-ant-';
    if ($_GET['msg'] === 'invalid_groq_key')     $flashErr = 'Invalid Groq API key format. Groq keys must start with gsk_';
    if ($_GET['msg'] === 'invalid_deepseek_key') $flashErr = 'Invalid DeepSeek API key format. DeepSeek keys must start with sk-';
}

function e(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }

$models = [
    'claude-opus-4-5'    => 'Claude Opus 4.5 (Most capable, highest quality)',
    'claude-sonnet-4-5'  => 'Claude Sonnet 4.5 (Best balance of quality and speed)',
    'claude-haiku-4-5'   => 'Claude Haiku 4.5 (Fastest, most economical)',
    'claude-3-5-sonnet-20241022' => 'Claude 3.5 Sonnet',
    'claude-3-haiku-20240307'    => 'Claude 3 Haiku',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Settings — CBC Scheme of Work</title>
<link rel="stylesheet" href="style.css">
<style>
  .settings-card {
    background: #fff;
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 32px;
    max-width: 640px;
    margin-bottom: 28px;
  }
  .settings-card h2 {
    font-size: 16pt;
    font-weight: 700;
    color: #111;
    margin-bottom: 6px;
  }
  .settings-card .card-desc {
    font-size: 12pt;
    color: var(--muted);
    margin-bottom: 24px;
    line-height: 1.55;
  }
  .settings-card .card-desc a { color: #6d43d9; }

  /* Toggle option */
  .toggle-row {
    display: flex;
    align-items: center;
    gap: 14px;
    background: #f9fafb;
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 14px 16px;
    margin-bottom: 24px;
    cursor: pointer;
  }
  .toggle-row input[type=checkbox] {
    width: 18px; height: 18px; cursor: pointer; accent-color: #6d43d9;
  }
  .toggle-row label {
    font-size: 13pt;
    font-weight: 600;
    color: #111;
    cursor: pointer;
  }

  /* Form fields */
  .field { margin-bottom: 20px; }
  .field label {
    display: block;
    font-size: 11pt;
    font-weight: 700;
    margin-bottom: 7px;
    color: #111;
  }
  .field label .req { color: #dc2626; margin-left: 3px; }
  .field input[type=text],
  .field input[type=password],
  .field select {
    width: 100%;
    padding: 11px 14px;
    border: 1px solid var(--border);
    border-radius: 7px;
    font-size: 12pt;
    color: #111;
    background: #fff;
    transition: border-color .15s;
  }
  .field input:focus, .field select:focus {
    outline: none;
    border-color: #6d43d9;
    box-shadow: 0 0 0 3px rgba(109,67,217,.12);
  }
  .field .hint {
    font-size: 10pt;
    color: var(--muted);
    margin-top: 5px;
  }
  .field .key-status {
    font-size: 10pt;
    margin-top: 5px;
    color: #059669;
    font-weight: 600;
  }

  /* Show/hide key toggle */
  .key-wrap { position: relative; }
  .key-wrap input { padding-right: 48px; }
  .key-wrap .show-key {
    position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
    background: none; border: none; cursor: pointer; color: var(--muted);
    font-size: 11pt; padding: 4px;
  }
  .key-wrap .show-key:hover { color: #111; }

  /* Action buttons */
  .btn-actions { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 8px; }
  .btn-save {
    background: #6d43d9; color: #fff; border: none;
    padding: 11px 26px; border-radius: 7px; font-size: 12pt;
    font-weight: 700; cursor: pointer; transition: background .15s;
  }
  .btn-save:hover { background: #5a36b8; }
  .btn-test {
    background: #fff; color: #374151;
    border: 1px solid var(--border);
    padding: 11px 22px; border-radius: 7px; font-size: 12pt;
    font-weight: 600; cursor: pointer; transition: background .15s;
    text-decoration: none; display: inline-block;
  }
  .btn-test:hover { background: #f3f4f6; }
  .btn-test.loading { opacity: .6; pointer-events: none; }

  /* Flash */
  .flash-err {
    background: var(--err-bg, #fef2f2);
    border: 1px solid #fca5a5;
    color: #991b1b;
    padding: 12px 16px;
    border-radius: 7px;
    margin-bottom: 18px;
    font-size: 12pt;
  }

  /* Info box */
  .info-box {
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    border-radius: 8px;
    padding: 14px 16px;
    font-size: 11pt;
    color: #1e40af;
    margin-bottom: 20px;
    line-height: 1.6;
  }
  .info-box strong { color: #1e3a8a; }
</style>
</head>
<body>
<div class="page-wrap">
  <nav class="top-nav">
    <span class="nav-brand">CBC Scheme of Work</span>
    <ol class="breadcrumb">
      <li><a href="curriculum.php">Curriculum</a></li>
      <li class="active">Settings</li>
    </ol>
  </nav>

  <header>
    <div>
      <h1>Settings</h1>
      <small style="color:var(--muted)">Application configuration</small>
    </div>
    <a href="curriculum.php" class="btn btn-outline">&larr; Back</a>
  </header>

  <?php if ($flash): ?>
    <div class="flash"><?= e($flash) ?></div>
  <?php endif; ?>
  <?php if ($flashErr): ?>
    <div class="flash-err"><?= e($flashErr) ?></div>
  <?php endif; ?>

  <!-- ── Groq AI Primary Card ───────────────────────────────────── -->
  <div class="settings-card">
    <h2>Groq AI (Primary 1)</h2>
    <p class="card-desc">
      Groq is the <strong>first primary AI</strong> used for all lesson planning and suggestions.
      Get your API key at <a href="https://console.groq.com/keys" target="_blank" rel="noopener">console.groq.com/keys</a>.
      DeepSeek is tried next if Groq is unavailable or rate-limited.
    </p>

    <form method="post" action="save_settings.php" id="groqForm">
      <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token'] ?? bin2hex(random_bytes(16))) ?>">

      <!-- Enable toggle -->
      <div class="toggle-row" onclick="document.getElementById('ai_enabled').click()">
        <input type="checkbox" name="ai_enabled" id="ai_enabled" value="1"
               <?= $aiEnabled ? 'checked' : '' ?> onclick="event.stopPropagation()">
        <label for="ai_enabled">Enable AI-assisted lesson planning when generating schemes</label>
      </div>

      <!-- Groq API Key -->
      <div class="field">
        <label for="groq_api_key">Groq API Key <span class="req">*</span></label>
        <div class="key-wrap">
          <input type="password" id="groq_api_key" name="groq_api_key"
                 placeholder="<?= $hasGroqKey ? 'gsk_... (key saved — enter new key to replace)' : 'gsk_...' ?>"
                 autocomplete="off" spellcheck="false">
          <button type="button" class="show-key" onclick="toggleKey(this)" title="Show/hide key">&#128065;</button>
        </div>
        <?php if ($hasGroqKey): ?>
          <p class="key-status">&#10003; Groq API key is saved. Leave blank to keep existing key.</p>
        <?php else: ?>
          <p class="hint">Stored securely in the database. Never shared externally.</p>
        <?php endif; ?>
      </div>

      <!-- Groq Model -->
      <div class="field">
        <label for="groq_model">Groq Model</label>
        <select id="groq_model" name="groq_model">
          <?php
          $groqModels = [
              'llama-3.3-70b-versatile' => 'Llama 3.3 70B Versatile (recommended)',
              'llama3-70b-8192'         => 'Llama 3 70B',
              'mixtral-8x7b-32768'      => 'Mixtral 8x7B',
              'gemma2-9b-it'            => 'Gemma 2 9B',
          ];
          foreach ($groqModels as $val => $label): ?>
            <option value="<?= e($val) ?>" <?= $groqModel === $val ? 'selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
        <p class="hint">Llama 3.3 70B gives the best quality on Groq's free tier.</p>
      </div>

      <div class="btn-actions">
        <button type="submit" class="btn-save">Save Groq Settings</button>
        <button type="button" class="btn-test" id="testGroqBtn" onclick="testGroqConnection()">Test Groq Connection</button>
      </div>
    </form>
  </div>

  <!-- ── AI / Claude Card (Fallback) ───────────────────────────── -->
  <div class="settings-card">
    <h2>Anthropic Claude (Last Resort Fallback)</h2>
    <p class="card-desc">
      Claude is used as a <strong>last resort fallback</strong> only when both Groq and DeepSeek are unavailable.
      Get your API key at <a href="https://console.anthropic.com/settings/keys" target="_blank" rel="noopener">console.anthropic.com/settings/keys</a>.
    </p>

    <form method="post" action="save_settings.php">
      <!-- API Key -->
      <div class="field">
        <label for="ai_api_key">Anthropic API Key</label>
        <div class="key-wrap">
          <input type="password" id="ai_api_key" name="ai_api_key"
                 placeholder="<?= $hasKey ? 'sk-ant-... (key saved — enter new key to replace)' : 'sk-ant-...' ?>"
                 autocomplete="off" spellcheck="false">
          <button type="button" class="show-key" onclick="toggleKey(this)" title="Show/hide key">&#128065;</button>
        </div>
        <?php if ($hasKey): ?>
          <p class="key-status">&#10003; API key is saved. Leave blank to keep existing key.</p>
        <?php else: ?>
          <p class="hint">Optional. Stored securely in the database. Never shared externally.</p>
        <?php endif; ?>
      </div>

      <!-- Model -->
      <div class="field">
        <label for="ai_model">Claude Model</label>
        <select id="ai_model" name="ai_model">
          <?php foreach ($models as $val => $label): ?>
            <option value="<?= e($val) ?>" <?= $aiModel === $val ? 'selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
        <p class="hint">Claude Sonnet gives the best balance of quality and speed. Use Haiku for faster generation.</p>
      </div>

      <div class="btn-actions">
        <button type="submit" class="btn-save">Save Claude Settings</button>
        <button type="button" class="btn-test" id="testBtn" onclick="testConnection()">Test Connection</button>
      </div>
    </form>
  </div>

  <!-- ── DeepSeek Card ─────────────────────────────────────────── -->
  <div class="settings-card">
    <h2>DeepSeek AI (Primary 2)</h2>
    <p class="card-desc">
      DeepSeek is the <strong>second primary AI</strong>, used whenever Groq is unavailable or rate-limited.
      Get your API key at <a href="https://platform.deepseek.com/api_keys" target="_blank" rel="noopener">platform.deepseek.com</a>.
      Claude is tried as a last resort if both Groq and DeepSeek fail.
    </p>

    <form method="post" action="save_settings.php">
      <!-- DeepSeek API Key -->
      <div class="field">
        <label for="deepseek_api_key">DeepSeek API Key</label>
        <div class="key-wrap">
          <input type="password" id="deepseek_api_key" name="deepseek_api_key"
                 placeholder="<?= $hasDeepseekKey ? 'sk-... (key saved — enter new key to replace)' : 'sk-...' ?>"
                 autocomplete="off" spellcheck="false">
          <button type="button" class="show-key" onclick="toggleKey(this)" title="Show/hide key">&#128065;</button>
        </div>
        <?php if ($hasDeepseekKey): ?>
          <p class="key-status">&#10003; DeepSeek API key is saved. Leave blank to keep existing key.</p>
        <?php else: ?>
          <p class="hint">Optional. Stored securely in the database. Never shared externally.</p>
        <?php endif; ?>
      </div>

      <!-- DeepSeek Model -->
      <div class="field">
        <label for="deepseek_model">DeepSeek Model</label>
        <select id="deepseek_model" name="deepseek_model">
          <?php
          $dsModels = [
              'deepseek-chat'     => 'DeepSeek V3 Chat (fast, recommended)',
              'deepseek-reasoner' => 'DeepSeek R1 Reasoner (slower, deeper reasoning)',
          ];
          foreach ($dsModels as $val => $label): ?>
            <option value="<?= e($val) ?>" <?= $deepseekModel === $val ? 'selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
        <p class="hint">DeepSeek Chat (V3) is fastest and most cost-effective for lesson planning.</p>
      </div>

      <div class="btn-actions">
        <button type="submit" class="btn-save">Save DeepSeek Settings</button>
        <button type="button" class="btn-test" id="testDeepseekBtn" onclick="testDeepseekConnection()">Test Connection</button>
      </div>
    </form>
  </div>

</div><!-- /.page-wrap -->
</body>
<script>
function toggleKey(btn) {
    const input = btn.closest('.key-wrap').querySelector('input');
    input.type = input.type === 'password' ? 'text' : 'password';
}

async function testGroqConnection() {
    const btn = document.getElementById('testGroqBtn');
    btn.classList.add('loading'); btn.textContent = 'Testing…';
    try {
        const r = await fetch('test_ai_connection.php?provider=groq');
        const d = await r.json();
        alert(d.ok ? '✓ Groq connection successful!' : '✗ Groq: ' + d.error);
    } catch { alert('Network error.'); }
    btn.classList.remove('loading'); btn.textContent = 'Test Groq Connection';
}

async function testConnection() {
    const btn = document.getElementById('testBtn');
    btn.classList.add('loading'); btn.textContent = 'Testing…';
    try {
        const r = await fetch('test_ai_connection.php?provider=claude');
        const d = await r.json();
        alert(d.ok ? '✓ Claude connection successful!' : '✗ Claude: ' + d.error);
    } catch { alert('Network error.'); }
    btn.classList.remove('loading'); btn.textContent = 'Test Connection';
}

async function testDeepseekConnection() {
    const btn = document.getElementById('testDeepseekBtn');
    btn.classList.add('loading'); btn.textContent = 'Testing…';
    try {
        const r = await fetch('test_ai_connection.php?provider=deepseek');
        const d = await r.json();
        alert(d.ok ? '✓ DeepSeek connection successful!' : '✗ DeepSeek: ' + d.error);
    } catch { alert('Network error.'); }
    btn.classList.remove('loading'); btn.textContent = 'Test Connection';
}
</script>
</html>

