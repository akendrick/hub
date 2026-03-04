<?php
declare(strict_types=1);
require __DIR__ . '/auth.php';
auth_require_page();   // redirects to login.php if not authenticated
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Knotwork · Settings</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Mono:ital,wght@0,400;0,500;1,400&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg:      #c8c87a;
      --ink:     #1a1a14;
      --card:    #1e2318;
      --card2:   #252b1c;
      --border:  rgba(255,255,255,0.08);
      --accent:  #e8e050;
      --danger:  #e05050;
      --ok:      #60d080;
      --muted:   rgba(255,255,255,0.42);
      --dim:     rgba(255,255,255,0.07);
    }

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'DM Mono', monospace;
      background: var(--bg);
      background-image:
        repeating-linear-gradient(0deg, transparent, transparent 39px, rgba(0,0,0,0.04) 39px, rgba(0,0,0,0.04) 40px),
        repeating-linear-gradient(90deg, transparent, transparent 39px, rgba(0,0,0,0.04) 39px, rgba(0,0,0,0.04) 40px);
      min-height: 100vh;
      padding: 32px 20px 60px;
      color: #fff;
    }

    /* ── Page header ── */
    .page-hdr {
      max-width: 700px; margin: 0 auto 36px;
      display: flex; align-items: baseline; gap: 18px;
    }
    .page-hdr a {
      font-family: 'Bebas Neue', sans-serif;
      font-size: 14px; letter-spacing: 0.18em;
      color: var(--ink); opacity: 0.5;
      text-decoration: none;
    }
    .page-hdr a:hover { opacity: 0.9; }
    .page-title {
      font-family: 'Bebas Neue', sans-serif;
      font-size: clamp(28px, 5vw, 40px);
      letter-spacing: 0.12em;
      color: var(--ink);
    }

    /* ── Cards ── */
    .card {
      max-width: 700px; margin: 0 auto 24px;
      background: var(--card);
      border-radius: 8px;
      border: 1px solid var(--border);
      box-shadow: 0 4px 20px rgba(0,0,0,0.3);
      overflow: hidden;
    }

    .card-hdr {
      padding: 14px 22px;
      border-bottom: 1px solid var(--border);
      display: flex; align-items: center; gap: 10px;
    }
    .card-hdr h2 {
      font-size: 11px; font-weight: 500; letter-spacing: 0.16em;
      text-transform: uppercase; color: var(--accent);
    }
    .card-hdr .hdr-sub {
      font-size: 10px; color: var(--muted); letter-spacing: 0.05em; margin-left: auto;
    }

    /* ── Key list ── */
    #keyList { list-style: none; }

    .key-row {
      display: flex; align-items: center; gap: 12px;
      padding: 12px 22px;
      border-bottom: 1px solid rgba(255,255,255,0.05);
      animation: fadeIn 0.25s ease both;
    }
    .key-row:last-child { border-bottom: none; }

    @keyframes fadeIn { from { opacity:0; transform: translateY(6px); } to { opacity:1; transform:none; } }

    .key-dot {
      width: 7px; height: 7px; border-radius: 50%;
      background: var(--ok); flex-shrink: 0;
    }
    .key-dot.stale { background: #888; }

    .key-info { flex: 1; min-width: 0; }
    .key-name {
      font-size: 13px; font-weight: 500; color: #fff;
      white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .key-meta {
      font-size: 10px; color: var(--muted); margin-top: 3px;
      display: flex; flex-wrap: wrap; gap: 0 14px;
    }
    .key-preview {
      font-size: 10px; color: var(--muted);
      letter-spacing: 0.04em; flex-shrink: 0;
      font-style: italic;
    }

    .btn-revoke {
      background: none; border: 1px solid rgba(224,80,80,0.35);
      color: var(--danger); border-radius: 4px;
      font-family: 'DM Mono', monospace; font-size: 9px; font-weight: 500;
      letter-spacing: 0.12em; text-transform: uppercase;
      padding: 4px 10px; cursor: pointer; flex-shrink: 0;
      transition: background 0.15s, border-color 0.15s;
    }
    .btn-revoke:hover { background: rgba(224,80,80,0.12); border-color: var(--danger); }
    .btn-revoke:disabled { opacity: 0.35; cursor: not-allowed; }

    .empty-state {
      padding: 28px 22px; text-align: center;
      font-size: 11px; color: var(--muted); font-style: italic;
    }

    /* ── Create key form ── */
    .create-form {
      padding: 18px 22px;
      display: flex; gap: 10px; flex-wrap: wrap; align-items: flex-end;
    }
    .field {
      display: flex; flex-direction: column; gap: 5px; flex: 1; min-width: 180px;
    }
    .field label {
      font-size: 9px; font-weight: 500; letter-spacing: 0.14em;
      text-transform: uppercase; color: var(--muted);
    }
    .field input {
      background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12);
      border-radius: 4px; padding: 9px 12px;
      font-family: 'DM Mono', monospace; font-size: 13px; color: #fff;
      outline: none; transition: border-color 0.15s;
    }
    .field input:focus { border-color: var(--accent); }
    .field input::placeholder { color: rgba(255,255,255,0.25); }

    .btn-create {
      background: var(--accent); color: var(--ink);
      border: none; border-radius: 4px;
      font-family: 'Bebas Neue', sans-serif; font-size: 15px; letter-spacing: 0.1em;
      padding: 9px 22px; cursor: pointer; flex-shrink: 0;
      transition: filter 0.15s, transform 0.12s;
      align-self: flex-end;
    }
    .btn-create:hover { filter: brightness(1.1); transform: translateY(-1px); }
    .btn-create:active { transform: none; }
    .btn-create:disabled { opacity: 0.4; cursor: not-allowed; transform: none; }

    /* ── New key reveal modal ── */
    #newKeyModal {
      display: none;
      position: fixed; inset: 0;
      background: rgba(0,0,0,0.65); backdrop-filter: blur(4px);
      z-index: 100;
      align-items: center; justify-content: center;
      padding: 24px;
    }
    #newKeyModal.open { display: flex; }

    .modal-box {
      background: #1e2318; border-radius: 10px;
      border: 1px solid rgba(255,255,255,0.1);
      box-shadow: 0 20px 60px rgba(0,0,0,0.6);
      max-width: 560px; width: 100%;
      padding: 28px 28px 24px;
      animation: popIn 0.22s cubic-bezier(0.34,1.56,0.64,1) both;
    }
    @keyframes popIn { from { opacity:0; transform: scale(0.88); } to { opacity:1; transform:none; } }

    .modal-title {
      font-family: 'Bebas Neue', sans-serif; font-size: 22px;
      letter-spacing: 0.1em; color: var(--accent); margin-bottom: 6px;
    }
    .modal-sub {
      font-size: 10px; color: var(--muted); margin-bottom: 20px; line-height: 1.6;
    }
    .modal-sub strong { color: var(--danger); }

    .key-box {
      background: rgba(0,0,0,0.4);
      border: 1px solid rgba(232,224,80,0.25);
      border-radius: 6px;
      padding: 14px 16px;
      font-size: 12px; letter-spacing: 0.04em;
      word-break: break-all; color: var(--accent);
      margin-bottom: 16px;
      user-select: all; /* easy select */
      line-height: 1.7;
    }

    .modal-actions { display: flex; gap: 10px; justify-content: flex-end; }

    .btn-copy {
      background: rgba(232,224,80,0.12); border: 1px solid rgba(232,224,80,0.3);
      color: var(--accent);
      font-family: 'DM Mono', monospace; font-size: 10px; letter-spacing: 0.12em;
      text-transform: uppercase; border-radius: 4px;
      padding: 8px 16px; cursor: pointer;
      transition: background 0.15s;
    }
    .btn-copy:hover { background: rgba(232,224,80,0.22); }
    .btn-copy.copied { color: var(--ok); border-color: rgba(96,208,128,0.4); background: rgba(96,208,128,0.1); }

    .btn-done {
      background: var(--accent); color: var(--ink);
      border: none; border-radius: 4px;
      font-family: 'Bebas Neue', sans-serif; font-size: 15px; letter-spacing: 0.1em;
      padding: 8px 20px; cursor: pointer;
      transition: filter 0.15s;
    }
    .btn-done:hover { filter: brightness(1.1); }

    /* ── Error / status ── */
    .status-bar {
      max-width: 700px; margin: 0 auto 16px;
      padding: 10px 16px; border-radius: 6px;
      font-size: 11px; letter-spacing: 0.06em;
      display: none;
    }
    .status-bar.error { background: rgba(224,80,80,0.15); border: 1px solid rgba(224,80,80,0.3); color: #f09090; display: block; }
    .status-bar.ok    { background: rgba(96,208,128,0.12); border: 1px solid rgba(96,208,128,0.3); color: var(--ok); display: block; }

    /* ── Info card ── */
    .info-card {
      max-width: 700px; margin: 0 auto 0;
      padding: 16px 22px;
      background: rgba(0,0,0,0.15);
      border: 1px solid rgba(0,0,0,0.12);
      border-radius: 6px;
      font-size: 10px; color: rgba(26,26,20,0.6);
      line-height: 1.8;
    }
    .info-card code {
      background: rgba(0,0,0,0.15); border-radius: 3px;
      padding: 1px 5px; font-size: 10px; letter-spacing: 0.04em;
      color: var(--ink);
    }

    /* ── Logout link ── */
    .logout-row {
      max-width: 700px; margin: 28px auto 0;
      text-align: right;
    }
    .logout-row a {
      font-size: 9px; letter-spacing: 0.14em; text-transform: uppercase;
      color: rgba(26,26,20,0.4); text-decoration: none;
      border-bottom: 1px solid transparent;
      transition: color 0.15s, border-color 0.15s;
    }
    .logout-row a:hover { color: var(--ink); border-color: var(--ink); }
  </style>
</head>
<body>

<div class="page-hdr">
  <a href="/">← KNOTWORK</a>
  <h1 class="page-title">Settings</h1>
</div>

<div id="statusBar" class="status-bar"></div>

<!-- ── API Keys card ── -->
<div class="card">
  <div class="card-hdr">
    <h2>Device API Keys</h2>
    <span class="hdr-sub" id="keyCount"></span>
  </div>
  <ul id="keyList">
    <li class="empty-state" id="loadingState">Loading…</li>
  </ul>
</div>

<!-- ── Create new key card ── -->
<div class="card">
  <div class="card-hdr">
    <h2>Issue New Key</h2>
  </div>
  <div class="create-form">
    <div class="field">
      <label for="deviceName">Device name</label>
      <input type="text" id="deviceName" placeholder="e.g. My iPhone, Raspberry Pi" maxlength="100">
    </div>
    <button class="btn-create" id="btnCreate">Generate Key</button>
  </div>
</div>

<!-- ── Usage hint ── -->
<div class="info-card">
  Keys are used by remote apps to read and write your todo list via <code>device-api.php</code>.<br>
  Clients send: <code>Authorization: Bearer kw_&lt;key&gt;</code><br>
  Raw keys are shown <strong>once</strong> at creation and stored only as a SHA-256 hash server-side.
</div>

<div class="logout-row">
  <a href="/logout.php">Log out</a>
</div>

<!-- ── New key modal ── -->
<div id="newKeyModal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
  <div class="modal-box">
    <div class="modal-title" id="modalTitle">New API Key Created</div>
    <div class="modal-sub">
      Copy this key now — <strong>it cannot be retrieved again</strong>.<br>
      Paste it into your app's Settings screen when prompted.
    </div>
    <div class="key-box" id="modalKeyText"></div>
    <div class="modal-actions">
      <button class="btn-copy" id="btnCopy">Copy Key</button>
      <button class="btn-done" id="btnDone">Done</button>
    </div>
  </div>
</div>

<script>
// ── API helpers ────────────────────────────────────────────────────────────────

async function api(method, url, body) {
  const opts = {
    method,
    credentials: 'include',
    headers: body ? { 'Content-Type': 'application/json' } : {},
    body: body ? JSON.stringify(body) : undefined,
  };
  const r = await fetch(url, opts);
  const text = await r.text();
  let json;
  try { json = JSON.parse(text); } catch { json = { error: text }; }
  return { ok: r.ok, status: r.status, data: json };
}

function showStatus(msg, type) {
  const bar = document.getElementById('statusBar');
  bar.textContent = msg;
  bar.className = 'status-bar ' + type;
  if (type === 'ok') setTimeout(() => { bar.className = 'status-bar'; }, 3500);
}

// ── Key list ───────────────────────────────────────────────────────────────────

let keys = [];

async function loadKeys() {
  const { ok, data } = await api('GET', 'api-keys.php');
  if (!ok) { showStatus('Failed to load keys: ' + (data.error || '?'), 'error'); return; }
  keys = Array.isArray(data) ? data : [];
  renderKeys();
}

function fmtDate(iso) {
  if (!iso) return '—';
  const d = new Date(iso);
  return d.toLocaleDateString('en-CA', { year:'numeric', month:'short', day:'numeric' });
}

function renderKeys() {
  const list = document.getElementById('keyList');
  const count = document.getElementById('keyCount');
  count.textContent = keys.length + ' key' + (keys.length !== 1 ? 's' : '');

  if (!keys.length) {
    list.innerHTML = '<li class="empty-state">No keys issued yet.</li>';
    return;
  }

  list.innerHTML = '';
  keys.forEach((k, i) => {
    const now = Date.now();
    const lastUsed = k.last_used ? new Date(k.last_used) : null;
    const stale = !lastUsed || (now - lastUsed.getTime() > 30 * 24 * 60 * 60 * 1000);

    const li = document.createElement('li');
    li.className = 'key-row';
    li.style.animationDelay = (i * 0.05) + 's';
    li.dataset.id = k.id;
    li.innerHTML = `
      <span class="key-dot${stale ? ' stale' : ''}" title="${stale ? 'Not used recently' : 'Active'}"></span>
      <div class="key-info">
        <div class="key-name">${escHtml(k.device_name)}</div>
        <div class="key-meta">
          <span>Created ${fmtDate(k.created)}</span>
          <span>Last used: ${lastUsed ? fmtDate(k.last_used) : 'never'}</span>
        </div>
      </div>
      <span class="key-preview">${escHtml(k.key_preview || '')}</span>
      <button class="btn-revoke" data-id="${escHtml(k.id)}" aria-label="Revoke key for ${escHtml(k.device_name)}">Revoke</button>
    `;
    list.appendChild(li);
  });

  // Bind revoke buttons
  list.querySelectorAll('.btn-revoke').forEach(btn => {
    btn.addEventListener('click', () => revokeKey(btn.dataset.id, btn));
  });
}

async function revokeKey(id, btn) {
  if (!confirm('Revoke this key? Any device using it will immediately lose access.')) return;
  btn.disabled = true;
  const { ok, data } = await api('DELETE', 'api-keys.php?id=' + encodeURIComponent(id));
  if (!ok) {
    showStatus('Revoke failed: ' + (data.error || '?'), 'error');
    btn.disabled = false;
    return;
  }
  showStatus('Key revoked.', 'ok');
  await loadKeys();
}

// ── Create key ─────────────────────────────────────────────────────────────────

document.getElementById('btnCreate').addEventListener('click', async () => {
  const name = document.getElementById('deviceName').value.trim();
  if (!name) { document.getElementById('deviceName').focus(); return; }

  const btn = document.getElementById('btnCreate');
  btn.disabled = true;
  btn.textContent = '…';

  const { ok, status, data } = await api('POST', 'api-keys.php', { device_name: name });

  btn.disabled = false;
  btn.textContent = 'Generate Key';

  if (!ok) {
    const msg = status === 500
      ? 'Server could not save the key. ' + (data.error || '') + ' — Check file permissions on the server (the web-server user needs write access to the dashboard directory).'
      : 'Error ' + status + ': ' + (data.error || 'Could not create key');
    showStatus(msg, 'error');
    return;
  }

  if (!data.api_key) {
    showStatus('Key was created but the server did not return it — check server logs.', 'error');
    await loadKeys();
    return;
  }

  // Show key in modal — it is shown exactly once
  document.getElementById('modalKeyText').textContent = data.api_key;
  document.getElementById('newKeyModal').classList.add('open');
  document.getElementById('deviceName').value = '';
  // Reload list immediately — if save succeeded the key will now appear
  await loadKeys();
});

// Allow Enter key in device name field
document.getElementById('deviceName').addEventListener('keydown', e => {
  if (e.key === 'Enter') document.getElementById('btnCreate').click();
});

// ── Modal ──────────────────────────────────────────────────────────────────────

document.getElementById('btnDone').addEventListener('click', closeModal);
document.getElementById('newKeyModal').addEventListener('click', e => {
  if (e.target === document.getElementById('newKeyModal')) closeModal();
});

function closeModal() {
  document.getElementById('newKeyModal').classList.remove('open');
  document.getElementById('modalKeyText').textContent = '';
  document.getElementById('btnCopy').textContent = 'Copy Key';
  document.getElementById('btnCopy').className = 'btn-copy';
}

document.getElementById('btnCopy').addEventListener('click', async () => {
  const key = document.getElementById('modalKeyText').textContent.trim();
  try {
    await navigator.clipboard.writeText(key);
    const btn = document.getElementById('btnCopy');
    btn.textContent = '✓ Copied';
    btn.classList.add('copied');
    setTimeout(() => {
      btn.textContent = 'Copy Key';
      btn.classList.remove('copied');
    }, 2000);
  } catch {
    // Fallback: select the text
    const range = document.createRange();
    range.selectNodeContents(document.getElementById('modalKeyText'));
    window.getSelection().removeAllRanges();
    window.getSelection().addRange(range);
  }
});

// Close modal on Escape
window.addEventListener('keydown', e => {
  if (e.key === 'Escape') closeModal();
});

// ── Utils ──────────────────────────────────────────────────────────────────────

function escHtml(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Init ───────────────────────────────────────────────────────────────────────

loadKeys();
</script>

</body>
</html>
