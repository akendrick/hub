<?php
declare(strict_types=1);
require __DIR__ . '/auth.php';
auth_require_page();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Knotwork · Darkroom</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Mono:ital,wght@0,400;0,500;1,400&display=swap" rel="stylesheet">
<style>
/* ── Reset & base ─────────────────────────────────────────── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html { height: 100%; }
body {
  font-family: 'DM Mono', monospace;
  background: #c8c87a;
  background-image:
    repeating-linear-gradient(0deg,   transparent, transparent 39px, rgba(0,0,0,.04) 39px, rgba(0,0,0,.04) 40px),
    repeating-linear-gradient(90deg,  transparent, transparent 39px, rgba(0,0,0,.04) 39px, rgba(0,0,0,.04) 40px);
  min-height: 100vh;
  color: #fff;
}

/* ── Layout shell ─────────────────────────────────────────── */
.shell { max-width: 1100px; margin: 0 auto; padding: 28px 20px 80px; }

.page-hdr {
  display: flex; align-items: baseline; gap: 18px; margin-bottom: 28px;
}
.page-hdr a {
  font-family: 'Bebas Neue', sans-serif;
  font-size: 13px; letter-spacing: .18em;
  color: #1a1a14; opacity: .5; text-decoration: none;
}
.page-hdr a:hover { opacity: .9; }
.page-title {
  font-family: 'Bebas Neue', sans-serif;
  font-size: clamp(28px,5vw,42px);
  letter-spacing: .12em; color: #1a1a14;
}

/* ── Tab bar ──────────────────────────────────────────────── */
.tabs {
  display: flex; gap: 2px; margin-bottom: 24px;
  border-bottom: 2px solid rgba(0,0,0,.2);
}
.tab-btn {
  font-family: 'Bebas Neue', sans-serif;
  font-size: 15px; letter-spacing: .14em;
  padding: 10px 20px; background: rgba(0,0,0,.12);
  border: none; color: rgba(26,26,20,.6);
  cursor: pointer; transition: background .15s, color .15s;
  border-radius: 4px 4px 0 0;
}
.tab-btn:hover { background: rgba(0,0,0,.2); color: #1a1a14; }
.tab-btn.active {
  background: #1e2318; color: #e8e050;
}

/* ── Card ─────────────────────────────────────────────────── */
.card {
  background: #1e2318; border-radius: 6px;
  border: 1px solid rgba(255,255,255,.07);
  padding: 24px; margin-bottom: 20px;
}
.card-title {
  font-family: 'Bebas Neue', sans-serif;
  font-size: 18px; letter-spacing: .14em;
  color: #e8e050; margin-bottom: 18px;
}

/* ── Toolbar ──────────────────────────────────────────────── */
.toolbar {
  display: flex; align-items: center;
  justify-content: space-between; margin-bottom: 16px;
}
.section-label {
  font-size: 9px; font-weight: 500; letter-spacing: .2em;
  text-transform: uppercase; color: rgba(255,255,255,.4);
}

/* ── Buttons ──────────────────────────────────────────────── */
.btn {
  font-family: 'DM Mono', monospace;
  font-size: 11px; font-weight: 500;
  letter-spacing: .1em; text-transform: uppercase;
  padding: 7px 16px; border-radius: 3px;
  border: none; cursor: pointer; transition: background .15s;
}
.btn-accent { background: #e8e050; color: #1a1a14; }
.btn-accent:hover { background: #f0e860; }
.btn-ghost  { background: rgba(255,255,255,.08); color: rgba(255,255,255,.7); }
.btn-ghost:hover  { background: rgba(255,255,255,.15); }
.btn-danger { background: rgba(224,80,80,.15); color: #e05050; }
.btn-danger:hover { background: rgba(224,80,80,.3); }
.btn-small  { padding: 4px 10px; font-size: 10px; }
.btn-icon   { background: none; border: 1px solid rgba(255,255,255,.15); color: rgba(255,255,255,.5); padding: 3px 8px; font-size: 14px; cursor: pointer; border-radius: 3px; }
.btn-icon:hover { border-color: rgba(255,255,255,.4); color: #fff; }

/* ── Form ─────────────────────────────────────────────────── */
.form-panel {
  background: #252b1c; border: 1px solid rgba(255,255,255,.1);
  border-left: 3px solid #e8e050;
  border-radius: 0 6px 6px 0; padding: 20px 24px;
  margin-bottom: 20px; display: none;
}
.form-panel.open { display: block; }
.form-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 14px 20px;
}
.form-group { display: flex; flex-direction: column; gap: 5px; }
.form-group.wide { grid-column: 1 / -1; }
.form-group.half { grid-column: span 2; }

label.fld {
  font-size: 8px; font-weight: 500;
  letter-spacing: .18em; text-transform: uppercase;
  color: rgba(255,255,255,.4);
}
input[type=text],
input[type=number],
input[type=date],
select,
textarea {
  background: rgba(0,0,0,.25);
  border: 1px solid rgba(255,255,255,.1);
  border-radius: 3px; color: #fff;
  font-family: 'DM Mono', monospace;
  font-size: 13px; padding: 7px 10px;
  outline: none; width: 100%;
  transition: border-color .15s;
}
input:focus, select:focus, textarea:focus {
  border-color: #e8e050;
}
select option { background: #1e2318; }
textarea { resize: vertical; min-height: 60px; }

.checkbox-row {
  display: flex; align-items: center; gap: 10px;
  padding: 8px 0;
}
.checkbox-row input[type=checkbox] {
  width: 16px; height: 16px;
  accent-color: #e8e050;
}
.checkbox-row label {
  font-size: 12px; color: rgba(255,255,255,.7);
}

.form-footer {
  display: flex; align-items: center; gap: 12px; margin-top: 18px;
}
.form-msg { font-size: 11px; }
.form-msg.ok  { color: #60d080; }
.form-msg.err { color: #e05050; }

/* ── Dynamic multi-row fields ─────────────────────────────── */
.multi-rows { display: flex; flex-direction: column; gap: 8px; margin-bottom: 8px; }
.multi-row { display: flex; align-items: center; gap: 8px; }
.multi-row input, .multi-row select { flex: 1; }
.add-row-btn {
  background: none; border: 1px dashed rgba(255,255,255,.25);
  color: rgba(255,255,255,.5); font-size: 11px; letter-spacing: .1em;
  padding: 6px 14px; border-radius: 3px; cursor: pointer; width: 100%;
  transition: border-color .15s, color .15s;
}
.add-row-btn:hover { border-color: #e8e050; color: #e8e050; }

/* ── Data table ───────────────────────────────────────────── */
.data-table {
  width: 100%; border-collapse: collapse; font-size: 12px;
}
.data-table th {
  text-align: left; font-size: 8px; font-weight: 500;
  letter-spacing: .18em; text-transform: uppercase;
  color: rgba(255,255,255,.35); padding: 0 10px 10px;
  border-bottom: 1px solid rgba(255,255,255,.08);
}
.data-table td {
  padding: 9px 10px; border-bottom: 1px solid rgba(255,255,255,.05);
  color: rgba(255,255,255,.8); vertical-align: top;
}
.data-table tr:last-child td { border-bottom: none; }
.data-table tr:hover td { background: rgba(255,255,255,.03); }
.data-table .mono { font-family: 'DM Mono', monospace; }
.pill-yes { background: rgba(96,208,128,.15); color: #60d080; padding: 2px 8px; border-radius: 12px; font-size: 10px; }
.pill-no  { background: rgba(255,255,255,.07); color: rgba(255,255,255,.35); padding: 2px 8px; border-radius: 12px; font-size: 10px; }
.badge-link { background: rgba(232,224,80,.12); color: #e8e050; padding: 2px 8px; border-radius: 3px; font-size: 10px; white-space: nowrap; }
.times-list { display: flex; gap: 4px; flex-wrap: wrap; }
.time-chip { background: rgba(255,255,255,.1); padding: 1px 7px; border-radius: 3px; font-size: 11px; }
.empty-state { text-align: center; padding: 40px; color: rgba(255,255,255,.25); font-size: 12px; letter-spacing: .1em; }

/* ── Loading spinner ──────────────────────────────────────── */
.spinner { display: inline-block; width: 14px; height: 14px; border: 2px solid rgba(255,255,255,.2); border-top-color: #e8e050; border-radius: 50%; animation: spin .7s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

/* ── Responsive ───────────────────────────────────────────── */
@media (max-width: 640px) {
  .tabs { flex-wrap: wrap; }
  .tab-btn { font-size: 12px; padding: 8px 12px; }
  .form-grid { grid-template-columns: 1fr; }
}
</style>
</head>
<body>
<div class="shell">

  <div class="page-hdr">
    <a href="index.html">← KNOTWORK</a>
    <h1 class="page-title">DARKROOM</h1>
  </div>

  <!-- Tab bar -->
  <div class="tabs" role="tablist">
    <button class="tab-btn active" data-tab="chemistry"  onclick="switchTab('chemistry')">Chemistry</button>
    <button class="tab-btn"        data-tab="paper"      onclick="switchTab('paper')">Paper</button>
    <button class="tab-btn"        data-tab="negative"   onclick="switchTab('negative')">Negatives</button>
    <button class="tab-btn"        data-tab="exposure"   onclick="switchTab('exposure')">Exposures</button>
    <button class="tab-btn"        data-tab="photo"      onclick="switchTab('photo')">Photos</button>
  </div>

  <!-- ── CHEMISTRY ───────────────────────────────────────── -->
  <div id="tab-chemistry" class="tab-pane">

    <div class="toolbar">
      <span class="section-label">Chemical solutions &amp; mixtures</span>
      <button class="btn btn-accent" onclick="toggleForm('form-chemistry')">+ New Chemistry</button>
    </div>

    <div class="form-panel" id="form-chemistry">
      <div class="card-title">New Chemistry Record</div>
      <div class="form-grid">
        <div class="form-group">
          <label class="fld">Date Created *</label>
          <input type="date" id="c-date_created">
        </div>
        <div class="form-group">
          <label class="fld">Type</label>
          <select id="c-type_id">
            <option value="">— select —</option>
          </select>
        </div>
        <div class="form-group">
          <label class="fld">% Solution</label>
          <input type="number" id="c-percent_solution" step="0.001" min="0" max="100" placeholder="e.g. 5.0">
        </div>
        <div class="form-group">
          <label class="fld">Created From (parent chemistry)</label>
          <select id="c-created_from_id">
            <option value="">— none —</option>
          </select>
        </div>
        <div class="form-group wide">
          <label class="fld">Notes</label>
          <textarea id="c-notes" rows="2" placeholder="Materials used, batch details…"></textarea>
        </div>
      </div>
      <div class="form-footer">
        <button class="btn btn-accent" onclick="submitChemistry()">Save Chemistry</button>
        <button class="btn btn-ghost"  onclick="toggleForm('form-chemistry')">Cancel</button>
        <span class="form-msg" id="c-msg"></span>
      </div>
    </div>

    <div class="card">
      <table class="data-table" id="tbl-chemistry">
        <thead>
          <tr>
            <th>#</th><th>Date</th><th>Type</th><th>% Sol.</th>
            <th>Derived From</th><th>Notes</th><th></th>
          </tr>
        </thead>
        <tbody><tr><td colspan="7" class="empty-state"><div class="spinner"></div></td></tr></tbody>
      </table>
    </div>
  </div>

  <!-- ── PAPER ───────────────────────────────────────────── -->
  <div id="tab-paper" class="tab-pane" style="display:none">

    <div class="toolbar">
      <span class="section-label">Paper stock records</span>
      <button class="btn btn-accent" onclick="toggleForm('form-paper')">+ New Paper</button>
    </div>

    <div class="form-panel" id="form-paper">
      <div class="card-title">New Paper Record</div>
      <div class="form-grid">
        <div class="form-group">
          <label class="fld">Manufacturer</label>
          <input type="text" id="p-manufacturer" placeholder="e.g. Fabriano">
        </div>
        <div class="form-group">
          <label class="fld">Label / Name</label>
          <input type="text" id="p-label" placeholder="e.g. Artistico">
        </div>
        <div class="form-group">
          <label class="fld">Weight (gsm)</label>
          <input type="number" id="p-weight" step="0.1" min="0" placeholder="300">
        </div>
        <div class="form-group">
          <label class="fld">Treatment Chemistry</label>
          <select id="p-treatment_chemistry_id">
            <option value="">— none —</option>
          </select>
        </div>
        <div class="form-group">
          <label class="fld">Surface</label>
          <div class="checkbox-row">
            <input type="checkbox" id="p-hot_press">
            <label for="p-hot_press">Hot Press</label>
          </div>
        </div>
        <div class="form-group wide">
          <label class="fld">Notes</label>
          <textarea id="p-notes" rows="2" placeholder="Texture, observations…"></textarea>
        </div>
      </div>
      <div class="form-footer">
        <button class="btn btn-accent" onclick="submitPaper()">Save Paper</button>
        <button class="btn btn-ghost"  onclick="toggleForm('form-paper')">Cancel</button>
        <span class="form-msg" id="p-msg"></span>
      </div>
    </div>

    <div class="card">
      <table class="data-table" id="tbl-paper">
        <thead>
          <tr>
            <th>#</th><th>Manufacturer</th><th>Label</th>
            <th>Weight</th><th>Hot Press</th><th>Treatment</th><th>Notes</th><th></th>
          </tr>
        </thead>
        <tbody><tr><td colspan="8" class="empty-state"><div class="spinner"></div></td></tr></tbody>
      </table>
    </div>
  </div>

  <!-- ── NEGATIVES ───────────────────────────────────────── -->
  <div id="tab-negative" class="tab-pane" style="display:none">

    <div class="toolbar">
      <span class="section-label">Negative records</span>
      <button class="btn btn-accent" onclick="toggleForm('form-negative')">+ New Negative</button>
    </div>

    <div class="form-panel" id="form-negative">
      <div class="card-title">New Negative</div>
      <div class="form-grid">
        <div class="form-group">
          <label class="fld">Date Created *</label>
          <input type="date" id="n-date_created">
        </div>
        <div class="form-group">
          <label class="fld">Type</label>
          <select id="n-type_id">
            <option value="">— select —</option>
          </select>
        </div>
        <div class="form-group wide">
          <label class="fld">Settings / Notes</label>
          <textarea id="n-settings_notes" rows="2" placeholder="Print profile, resolution, ink, wax method…"></textarea>
        </div>
      </div>
      <div class="form-footer">
        <button class="btn btn-accent" onclick="submitNegative()">Save Negative</button>
        <button class="btn btn-ghost"  onclick="toggleForm('form-negative')">Cancel</button>
        <span class="form-msg" id="n-msg"></span>
      </div>
    </div>

    <div class="card">
      <table class="data-table" id="tbl-negative">
        <thead>
          <tr><th>#</th><th>Date</th><th>Type</th><th>Settings / Notes</th><th></th></tr>
        </thead>
        <tbody><tr><td colspan="5" class="empty-state"><div class="spinner"></div></td></tr></tbody>
      </table>
    </div>
  </div>

  <!-- ── EXPOSURES ───────────────────────────────────────── -->
  <div id="tab-exposure" class="tab-pane" style="display:none">

    <div class="toolbar">
      <span class="section-label">Exposure &amp; development data</span>
      <button class="btn btn-accent" onclick="toggleForm('form-exposure')">+ New Exposure</button>
    </div>

    <div class="form-panel" id="form-exposure">
      <div class="card-title">New Exposure Record</div>
      <div class="form-grid">
        <div class="form-group">
          <label class="fld">Date Exposed *</label>
          <input type="date" id="e-date_exposed">
        </div>
        <div class="form-group">
          <label class="fld">Negative Used</label>
          <select id="e-negative_id">
            <option value="">— none —</option>
          </select>
        </div>
        <div class="form-group">
          <label class="fld">Test Strip?</label>
          <div class="checkbox-row">
            <input type="checkbox" id="e-test_strip">
            <label for="e-test_strip">Yes, this is a test strip</label>
          </div>
        </div>

        <!-- Exposure Times (multi-row) -->
        <div class="form-group wide">
          <label class="fld">Exposure Times (minutes)</label>
          <div class="multi-rows" id="exp-times-rows">
            <div class="multi-row">
              <input type="number" step="0.5" min="0" placeholder="e.g. 10" class="exp-time-input">
              <button class="btn-icon" onclick="removeRow(this)">✕</button>
            </div>
          </div>
          <button class="add-row-btn" onclick="addExpTimeRow()">+ Add Another Interval</button>
        </div>

        <div class="form-group" style="grid-column: span 2">
          <label class="fld">Paper Soak</label>
          <div style="display:flex; gap:8px">
            <input type="number" id="e-paper_soak_time" step="1" min="0" placeholder="Time (min)" style="flex:1">
            <input type="number" id="e-paper_soak_temp" step="0.5" placeholder="Temp (°C)" style="flex:1">
          </div>
        </div>

        <div class="form-group" style="grid-column: span 2">
          <label class="fld">HOT Develop</label>
          <div style="display:flex; gap:8px">
            <input type="number" id="e-hot_develop_time" step="1" min="0" placeholder="Time (sec)" style="flex:1">
            <input type="number" id="e-hot_develop_temp" step="0.5" placeholder="Temp (°C)" style="flex:1">
          </div>
        </div>

        <div class="form-group" style="grid-column: span 2">
          <label class="fld">COOL Develop</label>
          <div style="display:flex; gap:8px">
            <input type="number" id="e-cool_develop_time" step="1" min="0" placeholder="Time (sec)" style="flex:1">
            <input type="number" id="e-cool_develop_temp" step="0.5" placeholder="Temp (°C)" style="flex:1">
          </div>
        </div>

        <div class="form-group wide">
          <label class="fld">Notes</label>
          <textarea id="e-notes" rows="2" placeholder="Observations, conditions…"></textarea>
        </div>
      </div>
      <div class="form-footer">
        <button class="btn btn-accent" onclick="submitExposure()">Save Exposure</button>
        <button class="btn btn-ghost"  onclick="toggleForm('form-exposure')">Cancel</button>
        <span class="form-msg" id="e-msg"></span>
      </div>
    </div>

    <div class="card">
      <table class="data-table" id="tbl-exposure">
        <thead>
          <tr>
            <th>#</th><th>Date</th><th>Test Strip</th><th>Negative</th>
            <th>Exposure Times</th><th>Soak</th><th>HOT Dev.</th><th>COOL Dev.</th><th>Notes</th><th></th>
          </tr>
        </thead>
        <tbody><tr><td colspan="10" class="empty-state"><div class="spinner"></div></td></tr></tbody>
      </table>
    </div>
  </div>

  <!-- ── PHOTOS ──────────────────────────────────────────── -->
  <div id="tab-photo" class="tab-pane" style="display:none">

    <div class="toolbar">
      <span class="section-label">Photo records</span>
      <button class="btn btn-accent" onclick="toggleForm('form-photo')">+ New Photo</button>
    </div>

    <div class="form-panel" id="form-photo">
      <div class="card-title">New Photo Record</div>
      <div class="form-grid">
        <div class="form-group">
          <label class="fld">Paper Used</label>
          <select id="ph-paper_id">
            <option value="">— select —</option>
          </select>
        </div>
        <div class="form-group">
          <label class="fld">Photo Size</label>
          <input type="text" id="ph-photo_size" placeholder="e.g. 8×10, 4×5">
        </div>
        <div class="form-group">
          <label class="fld">Gelatin Chemistry</label>
          <select id="ph-gelatin_chemistry_id">
            <option value="">— select —</option>
          </select>
        </div>
        <div class="form-group">
          <label class="fld">Gelatin Amount Used</label>
          <input type="text" id="ph-amount_used" placeholder="e.g. 20ml">
        </div>

        <!-- Sensitizers (multi-row) -->
        <div class="form-group wide">
          <label class="fld">Sensitizer Layers (chemistry + amount)</label>
          <div class="multi-rows" id="sensitizer-rows">
            <div class="multi-row">
              <select class="sens-chem-sel"><option value="">— chemistry —</option></select>
              <input type="text" class="sens-amt-inp" placeholder="Amount (e.g. 5ml)" style="max-width:140px">
              <button class="btn-icon" onclick="removeRow(this)">✕</button>
            </div>
          </div>
          <button class="add-row-btn" onclick="addSensitizerRow()">+ Add Sensitizer Layer</button>
        </div>

        <div class="form-group">
          <label class="fld">Date Sensitized</label>
          <input type="date" id="ph-date_sensitized">
        </div>
        <div class="form-group">
          <label class="fld">Date Exposed</label>
          <input type="date" id="ph-date_exposed">
        </div>
        <div class="form-group">
          <label class="fld">Exposure Record</label>
          <select id="ph-exposure_id">
            <option value="">— select —</option>
          </select>
        </div>
        <div class="form-group wide">
          <label class="fld">Notes</label>
          <textarea id="ph-notes" rows="2" placeholder="Results, observations…"></textarea>
        </div>
      </div>
      <div class="form-footer">
        <button class="btn btn-accent" onclick="submitPhoto()">Save Photo</button>
        <button class="btn btn-ghost"  onclick="toggleForm('form-photo')">Cancel</button>
        <span class="form-msg" id="ph-msg"></span>
      </div>
    </div>

    <div class="card">
      <table class="data-table" id="tbl-photo">
        <thead>
          <tr>
            <th>#</th><th>Sensitized</th><th>Exposed</th><th>Paper</th>
            <th>Size</th><th>Gelatin</th><th>Sensitizers</th><th>Exposure</th><th>Notes</th><th></th>
          </tr>
        </thead>
        <tbody><tr><td colspan="10" class="empty-state"><div class="spinner"></div></td></tr></tbody>
      </table>
    </div>
  </div>

</div><!-- /.shell -->

<script>
// ═══════════════════════════════════════════════════════════
// State
// ═══════════════════════════════════════════════════════════
const S = {
  chemistryTypes: [],
  negativeTypes:  [],
  chemistry:      [],
  paper:          [],
  negative:       [],
  exposure:       [],
  photo:          [],
};

let currentTab = 'chemistry';

// ═══════════════════════════════════════════════════════════
// API helper
// ═══════════════════════════════════════════════════════════
async function api(res, opts = {}) {
  const { method = 'GET', id = null, body = null } = opts;
  let url = `darkroom-api.php?res=${res}`;
  if (id) url += `&id=${id}`;
  const init = { method, headers: { 'Content-Type': 'application/json' } };
  if (body) init.body = JSON.stringify(body);
  const r = await fetch(url, init);
  const j = await r.json();
  if (!j.ok) throw new Error(j.error || 'API error');
  return j.data;
}

// ═══════════════════════════════════════════════════════════
// Bootstrap — load lookup tables once on page load
// ═══════════════════════════════════════════════════════════
async function init() {
  try {
    [S.chemistryTypes, S.negativeTypes] = await Promise.all([
      api('chemistry_types'),
      api('negative_types'),
    ]);
    populateSelect('c-type_id',   S.chemistryTypes, 'id', 'name');
    populateSelect('n-type_id',   S.negativeTypes,  'id', 'name');
  } catch (ex) { console.error('Lookup load failed', ex); }

  await loadTab('chemistry');
}

// ═══════════════════════════════════════════════════════════
// Tab switching
// ═══════════════════════════════════════════════════════════
async function switchTab(name) {
  document.querySelectorAll('.tab-pane').forEach(el => el.style.display = 'none');
  document.querySelectorAll('.tab-btn').forEach(el => el.classList.toggle('active', el.dataset.tab === name));
  document.getElementById('tab-' + name).style.display = '';
  currentTab = name;
  await loadTab(name);
}

async function loadTab(name) {
  try {
    switch (name) {
      case 'chemistry':
        S.chemistry = await api('chemistry');
        renderChemistry();
        // refresh parent-chemistry selects
        populateSelect('c-created_from_id', S.chemistry, 'id', r => `#${r.id} ${r.type_name||''} ${r.date_created}`, true);
        populateSelect('ph-gelatin_chemistry_id', S.chemistry, 'id', r => `#${r.id} ${r.type_name||''} ${r.date_created}`, true);
        refreshSensitizerSelects();
        break;
      case 'paper':
        S.paper = await api('paper');
        S.chemistry = S.chemistry.length ? S.chemistry : await api('chemistry');
        populateSelect('p-treatment_chemistry_id', S.chemistry, 'id', r => `#${r.id} ${r.type_name||''} ${r.date_created}`, true);
        renderPaper();
        break;
      case 'negative':
        S.negative = await api('negative');
        renderNegative();
        break;
      case 'exposure':
        [S.exposure, S.negative] = await Promise.all([api('exposure'), api('negative')]);
        populateSelect('e-negative_id', S.negative, 'id', r => `#${r.id} ${r.type_name||''} ${r.date_created}`, true);
        renderExposure();
        break;
      case 'photo':
        [S.photo, S.paper, S.exposure, S.chemistry] = await Promise.all([
          api('photo'), api('paper'), api('exposure'), api('chemistry'),
        ]);
        populateSelect('ph-paper_id', S.paper, 'id',
          r => [r.manufacturer, r.label].filter(Boolean).join(' ') || `Paper #${r.id}`, true);
        populateSelect('ph-exposure_id', S.exposure, 'id',
          r => `#${r.id} ${r.date_exposed}`, true);
        populateSelect('ph-gelatin_chemistry_id', S.chemistry, 'id',
          r => `#${r.id} ${r.type_name||''} ${r.date_created}`, true);
        refreshSensitizerSelects();
        renderPhoto();
        break;
    }
  } catch (ex) {
    console.error('Load tab error', ex);
  }
}

// ═══════════════════════════════════════════════════════════
// Select helpers
// ═══════════════════════════════════════════════════════════
function populateSelect(elId, items, valKey, labelFn, withNone = false) {
  const sel = document.getElementById(elId);
  if (!sel) return;
  const cur = sel.value;
  sel.innerHTML = '';
  if (withNone) sel.innerHTML = '<option value="">— none —</option>';
  const fn = typeof labelFn === 'string' ? (r => r[labelFn]) : labelFn;
  items.forEach(r => {
    const opt = document.createElement('option');
    opt.value = r[valKey];
    opt.textContent = fn(r);
    sel.appendChild(opt);
  });
  sel.value = cur || '';
}

function buildChemistryOptions(withNone = true) {
  let html = withNone ? '<option value="">— none —</option>' : '';
  S.chemistry.forEach(r => {
    html += `<option value="${r.id}">#${r.id} ${esc(r.type_name || '')} ${esc(r.date_created)}</option>`;
  });
  return html;
}

// ═══════════════════════════════════════════════════════════
// Form panel toggle
// ═══════════════════════════════════════════════════════════
function toggleForm(id) {
  const el = document.getElementById(id);
  el.classList.toggle('open');
}

// ═══════════════════════════════════════════════════════════
// Dynamic multi-row fields
// ═══════════════════════════════════════════════════════════
function addExpTimeRow() {
  const container = document.getElementById('exp-times-rows');
  const row = document.createElement('div');
  row.className = 'multi-row';
  row.innerHTML = `<input type="number" step="0.5" min="0" placeholder="e.g. 10" class="exp-time-input">
                   <button class="btn-icon" onclick="removeRow(this)">✕</button>`;
  container.appendChild(row);
}

function addSensitizerRow() {
  const container = document.getElementById('sensitizer-rows');
  const row = document.createElement('div');
  row.className = 'multi-row';
  row.innerHTML = `<select class="sens-chem-sel">${buildChemistryOptions()}</select>
                   <input type="text" class="sens-amt-inp" placeholder="Amount (e.g. 5ml)" style="max-width:140px">
                   <button class="btn-icon" onclick="removeRow(this)">✕</button>`;
  container.appendChild(row);
}

function refreshSensitizerSelects() {
  document.querySelectorAll('.sens-chem-sel').forEach(sel => {
    const cur = sel.value;
    sel.innerHTML = buildChemistryOptions();
    sel.value = cur || '';
  });
}

function removeRow(btn) {
  const container = btn.closest('.multi-rows');
  if (container.children.length > 1) btn.closest('.multi-row').remove();
}

// ═══════════════════════════════════════════════════════════
// Form submissions
// ═══════════════════════════════════════════════════════════
function setMsg(id, text, type = 'ok') {
  const el = document.getElementById(id);
  el.textContent = text;
  el.className = 'form-msg ' + type;
}

function v(id) { return document.getElementById(id)?.value || ''; }
function b(id) { return document.getElementById(id)?.checked || false; }

async function submitChemistry() {
  try {
    await api('chemistry', { method: 'POST', body: {
      date_created:     v('c-date_created'),
      type_id:          v('c-type_id'),
      percent_solution: v('c-percent_solution'),
      created_from_id:  v('c-created_from_id'),
      notes:            v('c-notes'),
    }});
    setMsg('c-msg', '✓ Saved');
    ['c-date_created','c-type_id','c-percent_solution','c-created_from_id','c-notes']
      .forEach(id => { const el = document.getElementById(id); if(el) el.value = ''; });
    await loadTab('chemistry');
  } catch (ex) { setMsg('c-msg', ex.message, 'err'); }
}

async function submitPaper() {
  try {
    await api('paper', { method: 'POST', body: {
      manufacturer:           v('p-manufacturer'),
      label:                  v('p-label'),
      weight:                 v('p-weight'),
      hot_press:              b('p-hot_press'),
      treatment_chemistry_id: v('p-treatment_chemistry_id'),
      notes:                  v('p-notes'),
    }});
    setMsg('p-msg', '✓ Saved');
    ['p-manufacturer','p-label','p-weight','p-treatment_chemistry_id','p-notes']
      .forEach(id => { const el = document.getElementById(id); if(el) el.value = ''; });
    document.getElementById('p-hot_press').checked = false;
    await loadTab('paper');
  } catch (ex) { setMsg('p-msg', ex.message, 'err'); }
}

async function submitNegative() {
  try {
    await api('negative', { method: 'POST', body: {
      date_created:   v('n-date_created'),
      type_id:        v('n-type_id'),
      settings_notes: v('n-settings_notes'),
    }});
    setMsg('n-msg', '✓ Saved');
    ['n-date_created','n-type_id','n-settings_notes']
      .forEach(id => { const el = document.getElementById(id); if(el) el.value = ''; });
    await loadTab('negative');
  } catch (ex) { setMsg('n-msg', ex.message, 'err'); }
}

async function submitExposure() {
  try {
    const times = [...document.querySelectorAll('.exp-time-input')]
      .map(el => ({ duration_minutes: el.value }))
      .filter(t => t.duration_minutes !== '');

    await api('exposure', { method: 'POST', body: {
      date_exposed:      v('e-date_exposed'),
      test_strip:        b('e-test_strip'),
      negative_id:       v('e-negative_id'),
      paper_soak_time:   v('e-paper_soak_time'),
      paper_soak_temp:   v('e-paper_soak_temp'),
      hot_develop_time:  v('e-hot_develop_time'),
      hot_develop_temp:  v('e-hot_develop_temp'),
      cool_develop_time: v('e-cool_develop_time'),
      cool_develop_temp: v('e-cool_develop_temp'),
      notes:             v('e-notes'),
      times,
    }});
    setMsg('e-msg', '✓ Saved');
    ['e-date_exposed','e-negative_id','e-paper_soak_time','e-paper_soak_temp',
     'e-hot_develop_time','e-hot_develop_temp','e-cool_develop_time','e-cool_develop_temp','e-notes']
      .forEach(id => { const el = document.getElementById(id); if(el) el.value = ''; });
    document.getElementById('e-test_strip').checked = false;
    // Reset to single time row
    const rows = document.getElementById('exp-times-rows');
    rows.innerHTML = `<div class="multi-row">
      <input type="number" step="0.5" min="0" placeholder="e.g. 10" class="exp-time-input">
      <button class="btn-icon" onclick="removeRow(this)">✕</button></div>`;
    await loadTab('exposure');
  } catch (ex) { setMsg('e-msg', ex.message, 'err'); }
}

async function submitPhoto() {
  try {
    const sensitizers = [...document.querySelectorAll('#sensitizer-rows .multi-row')]
      .map(row => ({
        chemistry_id: row.querySelector('.sens-chem-sel')?.value || '',
        amount_used:  row.querySelector('.sens-amt-inp')?.value  || '',
      }))
      .filter(s => s.chemistry_id);

    await api('photo', { method: 'POST', body: {
      paper_id:             v('ph-paper_id'),
      photo_size:           v('ph-photo_size'),
      gelatin_chemistry_id: v('ph-gelatin_chemistry_id'),
      amount_used:          v('ph-amount_used'),
      date_sensitized:      v('ph-date_sensitized'),
      date_exposed:         v('ph-date_exposed'),
      exposure_id:          v('ph-exposure_id'),
      notes:                v('ph-notes'),
      sensitizers,
    }});
    setMsg('ph-msg', '✓ Saved');
    ['ph-paper_id','ph-photo_size','ph-gelatin_chemistry_id','ph-amount_used',
     'ph-date_sensitized','ph-date_exposed','ph-exposure_id','ph-notes']
      .forEach(id => { const el = document.getElementById(id); if(el) el.value = ''; });
    document.getElementById('sensitizer-rows').innerHTML = `
      <div class="multi-row">
        <select class="sens-chem-sel">${buildChemistryOptions()}</select>
        <input type="text" class="sens-amt-inp" placeholder="Amount (e.g. 5ml)" style="max-width:140px">
        <button class="btn-icon" onclick="removeRow(this)">✕</button>
      </div>`;
    await loadTab('photo');
  } catch (ex) { setMsg('ph-msg', ex.message, 'err'); }
}

// Delete helpers
async function deleteRecord(res, id, tabName) {
  if (!confirm(`Delete #${id}? This cannot be undone.`)) return;
  try {
    await api(res, { method: 'DELETE', id });
    await loadTab(tabName);
  } catch (ex) { alert('Delete failed: ' + ex.message); }
}

// ═══════════════════════════════════════════════════════════
// Renderers
// ═══════════════════════════════════════════════════════════
function esc(s) {
  return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function renderChemistry() {
  const tbody = document.querySelector('#tbl-chemistry tbody');
  if (!S.chemistry.length) {
    tbody.innerHTML = '<tr><td colspan="7" class="empty-state">No chemistry records yet</td></tr>';
    return;
  }
  tbody.innerHTML = S.chemistry.map(r => `
    <tr>
      <td style="color:rgba(255,255,255,.35)">${r.id}</td>
      <td>${esc(r.date_created)}</td>
      <td>${r.type_name ? `<span class="badge-link">${esc(r.type_name)}</span>` : '—'}</td>
      <td>${r.percent_solution != null ? Number(r.percent_solution).toFixed(2) + '%' : '—'}</td>
      <td>${r.created_from_ids ? r.created_from_ids.split(',').map(id=>`<span class="badge-link">#${esc(id)}</span>`).join(' ') : '—'}</td>
      <td style="color:rgba(255,255,255,.5);max-width:220px;white-space:pre-wrap">${esc(r.notes || '—')}</td>
      <td><button class="btn btn-danger btn-small" onclick="deleteRecord('chemistry',${r.id},'chemistry')">Delete</button></td>
    </tr>`).join('');
}

function renderPaper() {
  const tbody = document.querySelector('#tbl-paper tbody');
  if (!S.paper.length) {
    tbody.innerHTML = '<tr><td colspan="8" class="empty-state">No paper records yet</td></tr>';
    return;
  }
  tbody.innerHTML = S.paper.map(r => `
    <tr>
      <td style="color:rgba(255,255,255,.35)">${r.id}</td>
      <td>${esc(r.manufacturer || '—')}</td>
      <td>${esc(r.label || '—')}</td>
      <td>${r.weight != null ? Number(r.weight).toFixed(1) + ' gsm' : '—'}</td>
      <td>${r.hot_press ? '<span class="pill-yes">Yes</span>' : '<span class="pill-no">No</span>'}</td>
      <td>${r.treatment_chemistry_id ? `<span class="badge-link">#${r.treatment_chemistry_id} ${esc(r.treatment_label||'')}</span>` : '—'}</td>
      <td style="color:rgba(255,255,255,.5);max-width:180px">${esc(r.notes || '—')}</td>
      <td><button class="btn btn-danger btn-small" onclick="deleteRecord('paper',${r.id},'paper')">Delete</button></td>
    </tr>`).join('');
}

function renderNegative() {
  const tbody = document.querySelector('#tbl-negative tbody');
  if (!S.negative.length) {
    tbody.innerHTML = '<tr><td colspan="5" class="empty-state">No negatives yet</td></tr>';
    return;
  }
  tbody.innerHTML = S.negative.map(r => `
    <tr>
      <td style="color:rgba(255,255,255,.35)">${r.id}</td>
      <td>${esc(r.date_created)}</td>
      <td>${r.type_name ? `<span class="badge-link">${esc(r.type_name)}</span>` : '—'}</td>
      <td style="color:rgba(255,255,255,.5);max-width:300px;white-space:pre-wrap">${esc(r.settings_notes || '—')}</td>
      <td><button class="btn btn-danger btn-small" onclick="deleteRecord('negative',${r.id},'negative')">Delete</button></td>
    </tr>`).join('');
}

function renderExposure() {
  const tbody = document.querySelector('#tbl-exposure tbody');
  if (!S.exposure.length) {
    tbody.innerHTML = '<tr><td colspan="10" class="empty-state">No exposure records yet</td></tr>';
    return;
  }
  tbody.innerHTML = S.exposure.map(r => {
    const times = (r.times||[]).map(t => `<span class="time-chip">${t.duration_minutes}min</span>`).join('');
    const negLabel = r.negative_id
      ? `<span class="badge-link">#${r.negative_id} ${esc(r.neg_type||'')} ${esc(r.neg_date||'')}</span>`
      : '—';
    return `<tr>
      <td style="color:rgba(255,255,255,.35)">${r.id}</td>
      <td>${esc(r.date_exposed)}</td>
      <td>${r.test_strip ? '<span class="pill-yes">Yes</span>' : '<span class="pill-no">No</span>'}</td>
      <td>${negLabel}</td>
      <td><div class="times-list">${times || '—'}</div></td>
      <td>${r.paper_soak_time != null ? r.paper_soak_time + 'min' : '—'}${r.paper_soak_temp != null ? ' / ' + r.paper_soak_temp + '°C' : ''}</td>
      <td>${r.hot_develop_time != null ? r.hot_develop_time + 's' : '—'}${r.hot_develop_temp != null ? ' / ' + r.hot_develop_temp + '°C' : ''}</td>
      <td>${r.cool_develop_time != null ? r.cool_develop_time + 's' : '—'}${r.cool_develop_temp != null ? ' / ' + r.cool_develop_temp + '°C' : ''}</td>
      <td style="color:rgba(255,255,255,.5);max-width:180px">${esc(r.notes || '—')}</td>
      <td><button class="btn btn-danger btn-small" onclick="deleteRecord('exposure',${r.id},'exposure')">Delete</button></td>
    </tr>`;
  }).join('');
}

function renderPhoto() {
  const tbody = document.querySelector('#tbl-photo tbody');
  if (!S.photo.length) {
    tbody.innerHTML = '<tr><td colspan="10" class="empty-state">No photos yet</td></tr>';
    return;
  }
  tbody.innerHTML = S.photo.map(r => {
    const sens = (r.sensitizers||[]).map(s =>
      `<span class="badge-link">#${s.chemistry_id} ${esc(s.chem_type||'')}${s.amount_used ? ' / ' + esc(s.amount_used) : ''}</span>`
    ).join(' ');
    return `<tr>
      <td style="color:rgba(255,255,255,.35)">${r.id}</td>
      <td>${esc(r.date_sensitized || '—')}</td>
      <td>${esc(r.date_exposed || '—')}</td>
      <td>${r.paper_id ? `<span class="badge-link">#${r.paper_id} ${esc(r.paper_label||'')}</span>` : '—'}</td>
      <td>${esc(r.photo_size || '—')}</td>
      <td>${r.gelatin_chemistry_id ? `<span class="badge-link">#${r.gelatin_chemistry_id} ${esc(r.gelatin_type||'')} ${esc(r.amount_used||'')}</span>` : '—'}</td>
      <td>${sens || '—'}</td>
      <td>${r.exposure_id ? `<span class="badge-link">#${r.exposure_id} ${esc(r.exposure_date||'')}</span>` : '—'}</td>
      <td style="color:rgba(255,255,255,.5);max-width:160px">${esc(r.notes || '—')}</td>
      <td><button class="btn btn-danger btn-small" onclick="deleteRecord('photo',${r.id},'photo')">Delete</button></td>
    </tr>`;
  }).join('');
}

// ═══════════════════════════════════════════════════════════
// Boot
// ═══════════════════════════════════════════════════════════
init();
</script>
</body>
</html>
