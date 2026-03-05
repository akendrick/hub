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
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{height:100%}
body{font-family:'DM Mono',monospace;background:#c8c87a;background-image:repeating-linear-gradient(0deg,transparent,transparent 39px,rgba(0,0,0,.04) 39px,rgba(0,0,0,.04) 40px),repeating-linear-gradient(90deg,transparent,transparent 39px,rgba(0,0,0,.04) 39px,rgba(0,0,0,.04) 40px);min-height:100vh;color:#fff}
.shell{max-width:1160px;margin:0 auto;padding:28px 20px 80px}
.page-hdr{display:flex;align-items:baseline;gap:18px;margin-bottom:24px}
.page-hdr a{font-family:'Bebas Neue',sans-serif;font-size:13px;letter-spacing:.18em;color:#1a1a14;opacity:.5;text-decoration:none}
.page-hdr a:hover{opacity:.9}
.page-title{font-family:'Bebas Neue',sans-serif;font-size:clamp(28px,5vw,42px);letter-spacing:.12em;color:#1a1a14}
.tabs{display:flex;gap:2px;flex-wrap:wrap;margin-bottom:24px;border-bottom:2px solid rgba(0,0,0,.2)}
.tab-btn{font-family:'Bebas Neue',sans-serif;font-size:14px;letter-spacing:.13em;padding:9px 17px;background:rgba(0,0,0,.12);border:none;color:rgba(26,26,20,.6);cursor:pointer;transition:background .15s,color .15s;border-radius:4px 4px 0 0}
.tab-btn:hover{background:rgba(0,0,0,.2);color:#1a1a14}
.tab-btn.active{background:#1e2318;color:#e8e050}
.card{background:#1e2318;border-radius:6px;border:1px solid rgba(255,255,255,.07);padding:22px;margin-bottom:18px}
.toolbar{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;gap:10px;flex-wrap:wrap}
.section-label{font-size:9px;font-weight:500;letter-spacing:.2em;text-transform:uppercase;color:rgba(255,255,255,.4)}
.btn{font-family:'DM Mono',monospace;font-size:11px;font-weight:500;letter-spacing:.1em;text-transform:uppercase;padding:7px 16px;border-radius:3px;border:none;cursor:pointer;transition:background .15s}
.btn-accent{background:#e8e050;color:#1a1a14}.btn-accent:hover{background:#f0e860}
.btn-ghost{background:rgba(255,255,255,.08);color:rgba(255,255,255,.7)}.btn-ghost:hover{background:rgba(255,255,255,.15)}
.btn-danger{background:rgba(224,80,80,.15);color:#e05050}.btn-danger:hover{background:rgba(224,80,80,.3)}
.btn-edit{background:rgba(232,224,80,.1);color:#e8e050;border:1px solid rgba(232,224,80,.25)}.btn-edit:hover{background:rgba(232,224,80,.2)}
.btn-small{padding:4px 10px;font-size:10px}
.btn-icon{background:none;border:1px solid rgba(255,255,255,.15);color:rgba(255,255,255,.5);padding:3px 8px;font-size:13px;cursor:pointer;border-radius:3px;font-family:'DM Mono',monospace}
.btn-icon:hover{border-color:rgba(255,255,255,.4);color:#fff}
.btn-repeat{background:rgba(96,208,128,.1);border:1px solid rgba(96,208,128,.3);color:#60d080;padding:5px 12px;font-size:11px;cursor:pointer;border-radius:3px;font-family:'DM Mono',monospace;letter-spacing:.05em}
.btn-repeat:hover{background:rgba(96,208,128,.2)}
.form-panel{background:#252b1c;border:1px solid rgba(255,255,255,.1);border-left:3px solid #e8e050;border-radius:0 6px 6px 0;padding:20px 24px;margin-bottom:18px;display:none}
.form-panel.open{display:block}
.form-panel.editing{border-left-color:#7ec860}
.panel-title{font-family:'Bebas Neue',sans-serif;font-size:18px;letter-spacing:.14em;color:#e8e050;margin-bottom:16px}
.form-panel.editing .panel-title{color:#7ec860}
.form-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(185px,1fr));gap:13px 18px}
.form-group{display:flex;flex-direction:column;gap:5px}
.form-group.wide{grid-column:1/-1}
.form-group.span2{grid-column:span 2}
label.fld{font-size:8px;font-weight:500;letter-spacing:.18em;text-transform:uppercase;color:rgba(255,255,255,.4)}
input[type=text],input[type=number],input[type=date],select,textarea{background:rgba(0,0,0,.25);border:1px solid rgba(255,255,255,.1);border-radius:3px;color:#fff;font-family:'DM Mono',monospace;font-size:13px;padding:7px 10px;outline:none;width:100%;transition:border-color .15s}
input:focus,select:focus,textarea:focus{border-color:#e8e050}
select option{background:#1e2318}
textarea{resize:vertical;min-height:58px}
.checkbox-row{display:flex;align-items:center;gap:10px;padding:7px 0}
.checkbox-row input[type=checkbox]{width:16px;height:16px;accent-color:#e8e050}
.checkbox-row label{font-size:12px;color:rgba(255,255,255,.7)}
.form-footer{display:flex;align-items:center;gap:10px;margin-top:16px;flex-wrap:wrap}
.form-msg{font-size:11px}.form-msg.ok{color:#60d080}.form-msg.err{color:#e05050}
.multi-rows{display:flex;flex-direction:column;gap:7px;margin-bottom:8px}
.multi-row{display:flex;align-items:center;gap:6px}
.multi-row input,.multi-row select{flex:1;min-width:0}
.add-row-btn{background:none;border:1px dashed rgba(255,255,255,.22);color:rgba(255,255,255,.45);font-size:11px;letter-spacing:.08em;padding:6px 14px;border-radius:3px;cursor:pointer;width:100%;transition:border-color .15s,color .15s;font-family:'DM Mono',monospace}
.add-row-btn:hover{border-color:#e8e050;color:#e8e050}
.exp-time-bar{display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:8px}
.data-table{width:100%;border-collapse:collapse;font-size:12px}
.data-table th{text-align:left;font-size:8px;font-weight:500;letter-spacing:.18em;text-transform:uppercase;color:rgba(255,255,255,.35);padding:0 10px 10px;border-bottom:1px solid rgba(255,255,255,.08)}
.data-table td{padding:8px 10px;border-bottom:1px solid rgba(255,255,255,.05);color:rgba(255,255,255,.8);vertical-align:top}
.data-table tr:last-child td{border-bottom:none}
.data-table tr:hover td{background:rgba(255,255,255,.03)}
.pill-yes{background:rgba(96,208,128,.15);color:#60d080;padding:2px 8px;border-radius:12px;font-size:10px}
.pill-no{background:rgba(255,255,255,.07);color:rgba(255,255,255,.35);padding:2px 8px;border-radius:12px;font-size:10px}
.badge{background:rgba(232,224,80,.12);color:#e8e050;padding:2px 7px;border-radius:3px;font-size:10px;display:inline-block;margin:1px}
.badge-g{background:rgba(96,208,128,.12);color:#60d080;padding:2px 7px;border-radius:3px;font-size:10px;display:inline-block;margin:1px}
.badge-b{background:rgba(80,160,224,.12);color:#50a0e0;padding:2px 7px;border-radius:3px;font-size:10px;display:inline-block;margin:1px}
.time-chip{background:rgba(255,255,255,.1);padding:1px 7px;border-radius:3px;font-size:11px;display:inline-block;margin:1px}
.times-list{display:flex;flex-wrap:wrap;gap:3px}
.empty-state{text-align:center;padding:36px;color:rgba(255,255,255,.22);font-size:12px;letter-spacing:.1em}
.td-actions{white-space:nowrap;display:flex;gap:5px}
.opts-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:20px}
.opts-sec{background:#252b1c;border-radius:6px;border:1px solid rgba(255,255,255,.08);padding:18px}
.opts-sec h3{font-family:'Bebas Neue',sans-serif;font-size:16px;letter-spacing:.14em;color:#e8e050;margin-bottom:14px}
.opts-list{display:flex;flex-direction:column;gap:7px;margin-bottom:14px}
.opt-row{display:flex;align-items:center;gap:7px}
.opt-name{flex:1;font-size:12px;color:rgba(255,255,255,.8)}
.opt-input{flex:1;font-size:12px;padding:5px 8px}
.opts-add{display:flex;gap:8px}
.opts-add input{flex:1}
.spinner{display:inline-block;width:14px;height:14px;border:2px solid rgba(255,255,255,.2);border-top-color:#e8e050;border-radius:50%;animation:spin .7s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}
@media(max-width:680px){.tab-btn{font-size:11px;padding:7px 10px}.form-grid{grid-template-columns:1fr}.opts-grid{grid-template-columns:1fr}}
</style>
</head>
<body>
<div class="shell">
<div class="page-hdr">
  <a href="index.html">&#8592; KNOTWORK</a>
  <h1 class="page-title">DARKROOM</h1>
</div>

<div class="tabs" id="tab-bar">
  <button class="tab-btn active"      data-tab="chemistry"     onclick="switchTab('chemistry')">Chemistry</button>
  <button class="tab-btn"             data-tab="paper"         onclick="switchTab('paper')">Paper</button>
  <button class="tab-btn"             data-tab="support_paper" onclick="switchTab('support_paper')">Support Paper</button>
  <button class="tab-btn"             data-tab="carbon_tissue" onclick="switchTab('carbon_tissue')">Carbon Tissue</button>
  <button class="tab-btn"             data-tab="negative"      onclick="switchTab('negative')">Negatives</button>
  <button class="tab-btn"             data-tab="exposure"      onclick="switchTab('exposure')">Exposures</button>
  <button class="tab-btn"             data-tab="photo"         onclick="switchTab('photo')">Photos</button>
  <button class="tab-btn"             data-tab="options"       onclick="switchTab('options')">&#9881; Options</button>
</div>

<!-- CHEMISTRY -->
<div id="tab-chemistry" class="tab-pane">
  <div class="toolbar">
    <span class="section-label">Chemical solutions &amp; mixtures</span>
    <button class="btn btn-accent" onclick="openForm('chemistry')">+ New Chemistry</button>
  </div>
  <div class="form-panel" id="form-chemistry">
    <div class="panel-title" id="form-chemistry-title">New Chemistry Record</div>
    <div class="form-grid">
      <div class="form-group"><label class="fld">Date Created *</label><input type="date" id="c-date_created"></div>
      <div class="form-group"><label class="fld">Type</label><select id="c-type_id"><option value="">-- select --</option></select></div>
      <div class="form-group"><label class="fld">% Solution</label><input type="number" id="c-percent_solution" step="0.001" min="0" max="100" placeholder="e.g. 5.0"></div>
      <div class="form-group"><label class="fld">Created From (parent)</label><select id="c-created_from_id"><option value="">-- none --</option></select></div>
      <div class="form-group wide"><label class="fld">Notes</label><textarea id="c-notes" rows="2" placeholder="Materials used, batch details..."></textarea></div>
    </div>
    <div class="form-footer">
      <button class="btn btn-accent" id="c-save-btn" onclick="submitChemistry()">Save Chemistry</button>
      <button class="btn btn-ghost" onclick="closeForm('chemistry')">Cancel</button>
      <span class="form-msg" id="c-msg"></span>
    </div>
  </div>
  <div class="card">
    <table class="data-table" id="tbl-chemistry">
      <thead><tr><th>#</th><th>Date</th><th>Type</th><th>% Sol.</th><th>Derived From</th><th>Notes</th><th></th></tr></thead>
      <tbody><tr><td colspan="7" class="empty-state"><div class="spinner"></div></td></tr></tbody>
    </table>
  </div>
</div>

<!-- PAPER -->
<div id="tab-paper" class="tab-pane" style="display:none">
  <div class="toolbar">
    <span class="section-label">Paper stock records</span>
    <button class="btn btn-accent" onclick="openForm('paper')">+ New Paper</button>
  </div>
  <div class="form-panel" id="form-paper">
    <div class="panel-title" id="form-paper-title">New Paper Record</div>
    <div class="form-grid">
      <div class="form-group"><label class="fld">Manufacturer</label><input type="text" id="p-manufacturer" placeholder="e.g. Fabriano"></div>
      <div class="form-group"><label class="fld">Label / Name</label><input type="text" id="p-label" placeholder="e.g. Artistico"></div>
      <div class="form-group"><label class="fld">Weight (gsm)</label><input type="number" id="p-weight" step="0.1" min="0" placeholder="300"></div>
      <div class="form-group"><label class="fld">Treatment Chemistry</label><select id="p-treatment_chemistry_id"><option value="">-- none --</option></select></div>
      <div class="form-group"><label class="fld">Surface</label><div class="checkbox-row"><input type="checkbox" id="p-hot_press"><label for="p-hot_press">Hot Press</label></div></div>
      <div class="form-group wide"><label class="fld">Notes</label><textarea id="p-notes" rows="2" placeholder="Texture, observations..."></textarea></div>
    </div>
    <div class="form-footer">
      <button class="btn btn-accent" id="p-save-btn" onclick="submitPaper()">Save Paper</button>
      <button class="btn btn-ghost" onclick="closeForm('paper')">Cancel</button>
      <span class="form-msg" id="p-msg"></span>
    </div>
  </div>
  <div class="card">
    <table class="data-table" id="tbl-paper">
      <thead><tr><th>#</th><th>Manufacturer</th><th>Label</th><th>Weight</th><th>Hot Press</th><th>Treatment</th><th>Notes</th><th></th></tr></thead>
      <tbody><tr><td colspan="8" class="empty-state"><div class="spinner"></div></td></tr></tbody>
    </table>
  </div>
</div>

<!-- SUPPORT PAPER -->
<div id="tab-support_paper" class="tab-pane" style="display:none">
  <div class="toolbar">
    <span class="section-label">Prepared support sheets with field marks</span>
    <button class="btn btn-accent" onclick="openForm('support_paper')">+ New Support Paper</button>
  </div>
  <div class="form-panel" id="form-support_paper">
    <div class="panel-title" id="form-support_paper-title">New Support Paper</div>
    <div class="form-grid">
      <div class="form-group"><label class="fld">Base Paper *</label><select id="sp-paper_id"><option value="">-- select paper --</option></select></div>
      <div class="form-group"><label class="fld">ID / Mark</label><input type="text" id="sp-mark" placeholder="e.g. A1, B3, C12" maxlength="20" style="text-transform:uppercase"></div>
      <div class="form-group wide"><label class="fld">Notes</label><textarea id="sp-notes" rows="2" placeholder="Sizing, preparation notes..."></textarea></div>
    </div>
    <div class="form-footer">
      <button class="btn btn-accent" id="sp-save-btn" onclick="submitSupportPaper()">Save Support Paper</button>
      <button class="btn btn-ghost" onclick="closeForm('support_paper')">Cancel</button>
      <span class="form-msg" id="sp-msg"></span>
    </div>
  </div>
  <div class="card">
    <table class="data-table" id="tbl-support_paper">
      <thead><tr><th>#</th><th>Mark</th><th>Base Paper</th><th>Weight</th><th>Hot Press</th><th>Notes</th><th></th></tr></thead>
      <tbody><tr><td colspan="7" class="empty-state"><div class="spinner"></div></td></tr></tbody>
    </table>
  </div>
</div>

<!-- CARBON TISSUE -->
<div id="tab-carbon_tissue" class="tab-pane" style="display:none">
  <div class="toolbar">
    <span class="section-label">Carbon tissue sheets</span>
    <button class="btn btn-accent" onclick="openForm('carbon_tissue')">+ New Carbon Tissue</button>
  </div>
  <div class="form-panel" id="form-carbon_tissue">
    <div class="panel-title" id="form-carbon_tissue-title">New Carbon Tissue</div>
    <div class="form-grid">
      <div class="form-group"><label class="fld">Date Poured *</label><input type="date" id="ct-date_poured"></div>
      <div class="form-group"><label class="fld">Size</label><input type="text" id="ct-size" placeholder="e.g. 8x10, 4x5"></div>
      <div class="form-group"><label class="fld">Chemistry Used</label><select id="ct-chemistry_id"><option value="">-- none --</option></select></div>
      <div class="form-group"><label class="fld">Amount Poured</label><input type="text" id="ct-amount_poured" placeholder="e.g. 45ml"></div>
      <div class="form-group wide"><label class="fld">Notes</label><textarea id="ct-notes" rows="2" placeholder="Batch conditions, pigment, observations..."></textarea></div>
    </div>
    <div class="form-footer">
      <button class="btn btn-accent" id="ct-save-btn" onclick="submitCarbonTissue()">Save Carbon Tissue</button>
      <button class="btn btn-ghost" onclick="closeForm('carbon_tissue')">Cancel</button>
      <span class="form-msg" id="ct-msg"></span>
    </div>
  </div>
  <div class="card">
    <table class="data-table" id="tbl-carbon_tissue">
      <thead><tr><th>#</th><th>Date Poured</th><th>Size</th><th>Chemistry</th><th>Amount</th><th>Notes</th><th></th></tr></thead>
      <tbody><tr><td colspan="7" class="empty-state"><div class="spinner"></div></td></tr></tbody>
    </table>
  </div>
</div>

<!-- NEGATIVES -->
<div id="tab-negative" class="tab-pane" style="display:none">
  <div class="toolbar">
    <span class="section-label">Negative records</span>
    <button class="btn btn-accent" onclick="openForm('negative')">+ New Negative</button>
  </div>
  <div class="form-panel" id="form-negative">
    <div class="panel-title" id="form-negative-title">New Negative</div>
    <div class="form-grid">
      <div class="form-group"><label class="fld">Date Created *</label><input type="date" id="n-date_created"></div>
      <div class="form-group"><label class="fld">Type</label><select id="n-type_id"><option value="">-- select --</option></select></div>
      <div class="form-group wide"><label class="fld">Settings / Notes</label><textarea id="n-settings_notes" rows="2" placeholder="Print profile, resolution, ink, wax method..."></textarea></div>
    </div>
    <div class="form-footer">
      <button class="btn btn-accent" id="n-save-btn" onclick="submitNegative()">Save Negative</button>
      <button class="btn btn-ghost" onclick="closeForm('negative')">Cancel</button>
      <span class="form-msg" id="n-msg"></span>
    </div>
  </div>
  <div class="card">
    <table class="data-table" id="tbl-negative">
      <thead><tr><th>#</th><th>Date</th><th>Type</th><th>Settings / Notes</th><th></th></tr></thead>
      <tbody><tr><td colspan="5" class="empty-state"><div class="spinner"></div></td></tr></tbody>
    </table>
  </div>
</div>

<!-- EXPOSURES -->
<div id="tab-exposure" class="tab-pane" style="display:none">
  <div class="toolbar">
    <span class="section-label">Exposure &amp; development data</span>
    <button class="btn btn-accent" onclick="openForm('exposure')">+ New Exposure</button>
  </div>
  <div class="form-panel" id="form-exposure">
    <div class="panel-title" id="form-exposure-title">New Exposure Record</div>
    <div class="form-grid">
      <div class="form-group"><label class="fld">Date Exposed *</label><input type="date" id="e-date_exposed"></div>
      <div class="form-group"><label class="fld">Negative Used</label><select id="e-negative_id"><option value="">-- none --</option></select></div>
      <div class="form-group"><label class="fld">Test Strip?</label>
        <div class="checkbox-row"><input type="checkbox" id="e-test_strip" onchange="onTestStripChange()"><label for="e-test_strip">Yes, test strip</label></div>
      </div>
      <div class="form-group wide">
        <label class="fld">Exposure Times (minutes)</label>
        <div class="exp-time-bar">
          <button class="add-row-btn" style="width:auto;padding:5px 14px" onclick="addExpTimeRow()">+ Add Interval</button>
          <button class="btn-repeat" id="btn-repeat-time" style="display:none" onclick="repeatLastExpTime()">&#8635; Repeat Last</button>
        </div>
        <div class="multi-rows" id="exp-times-rows">
          <div class="multi-row"><input type="number" step="0.5" min="0" placeholder="minutes" class="exp-time-input"><button class="btn-icon" onclick="removeRow(this)">&#10005;</button></div>
        </div>
      </div>
      <div class="form-group span2"><label class="fld">Paper Soak</label>
        <div style="display:flex;gap:8px">
          <input type="number" id="e-paper_soak_time" step="1" min="0" placeholder="Time (min)" style="flex:1">
          <input type="number" id="e-paper_soak_temp" step="0.5" placeholder="Temp (C)" style="flex:1">
        </div>
      </div>
      <div class="form-group span2"><label class="fld">HOT Develop</label>
        <div style="display:flex;gap:8px">
          <input type="number" id="e-hot_develop_time" step="1" min="0" placeholder="Time (sec)" style="flex:1">
          <input type="number" id="e-hot_develop_temp" step="0.5" placeholder="Temp (C)" style="flex:1">
        </div>
      </div>
      <div class="form-group span2"><label class="fld">COOL Develop</label>
        <div style="display:flex;gap:8px">
          <input type="number" id="e-cool_develop_time" step="1" min="0" placeholder="Time (sec)" style="flex:1">
          <input type="number" id="e-cool_develop_temp" step="0.5" placeholder="Temp (C)" style="flex:1">
        </div>
      </div>
      <div class="form-group wide"><label class="fld">Notes</label><textarea id="e-notes" rows="2" placeholder="Conditions, observations..."></textarea></div>
    </div>
    <div class="form-footer">
      <button class="btn btn-accent" id="e-save-btn" onclick="submitExposure()">Save Exposure</button>
      <button class="btn btn-ghost" onclick="closeForm('exposure')">Cancel</button>
      <span class="form-msg" id="e-msg"></span>
    </div>
  </div>
  <div class="card">
    <table class="data-table" id="tbl-exposure">
      <thead><tr><th>#</th><th>Date</th><th>Strip?</th><th>Negative</th><th>Times</th><th>Soak</th><th>HOT Dev</th><th>COOL Dev</th><th>Notes</th><th></th></tr></thead>
      <tbody><tr><td colspan="10" class="empty-state"><div class="spinner"></div></td></tr></tbody>
    </table>
  </div>
</div>

<!-- PHOTOS -->
<div id="tab-photo" class="tab-pane" style="display:none">
  <div class="toolbar">
    <span class="section-label">Photo records</span>
    <button class="btn btn-accent" onclick="openForm('photo')">+ New Photo</button>
  </div>
  <div class="form-panel" id="form-photo">
    <div class="panel-title" id="form-photo-title">New Photo Record</div>
    <div class="form-grid">
      <div class="form-group"><label class="fld">Photo Size</label><input type="text" id="ph-photo_size" placeholder="e.g. 8x10, 4x5"></div>
      <div class="form-group"><label class="fld">Gelatin Chemistry</label><select id="ph-gelatin_chemistry_id"><option value="">-- none --</option></select></div>
      <div class="form-group"><label class="fld">Gelatin Amount</label><input type="text" id="ph-amount_used" placeholder="e.g. 20ml"></div>
      <div class="form-group"><label class="fld">Paper</label><select id="ph-paper_id"><option value="">-- none --</option></select></div>
      <div class="form-group"><label class="fld">Date Sensitized</label><input type="date" id="ph-date_sensitized"></div>
      <div class="form-group"><label class="fld">Date Exposed</label><input type="date" id="ph-date_exposed"></div>
      <div class="form-group"><label class="fld">Exposure Record</label><select id="ph-exposure_id"><option value="">-- none --</option></select></div>
      <div class="form-group wide">
        <label class="fld">Layers &mdash; Support Paper / Negative / Carbon Tissue</label>
        <div class="multi-rows" id="layer-rows"></div>
        <button class="add-row-btn" onclick="addLayerRow()">+ Add Layer</button>
      </div>
      <div class="form-group wide"><label class="fld">Notes</label><textarea id="ph-notes" rows="2" placeholder="Results, observations..."></textarea></div>
    </div>
    <div class="form-footer">
      <button class="btn btn-accent" id="ph-save-btn" onclick="submitPhoto()">Save Photo</button>
      <button class="btn btn-ghost" onclick="closeForm('photo')">Cancel</button>
      <span class="form-msg" id="ph-msg"></span>
    </div>
  </div>
  <div class="card">
    <table class="data-table" id="tbl-photo">
      <thead><tr><th>#</th><th>Sensitized</th><th>Exposed</th><th>Size</th><th>Gelatin</th><th>Layers</th><th>Exposure</th><th>Notes</th><th></th></tr></thead>
      <tbody><tr><td colspan="9" class="empty-state"><div class="spinner"></div></td></tr></tbody>
    </table>
  </div>
</div>

<!-- OPTIONS -->
<div id="tab-options" class="tab-pane" style="display:none">
  <div class="toolbar"><span class="section-label">Manage dropdown options</span></div>
  <div class="opts-grid">
    <div class="opts-sec">
      <h3>Chemistry Types</h3>
      <div class="opts-list" id="opt-chemistry_types"></div>
      <div class="opts-add">
        <input type="text" id="new-chemistry-type" placeholder="New type name..." onkeydown="if(event.key==='Enter')addOption('chemistry_types')">
        <button class="btn btn-accent btn-small" onclick="addOption('chemistry_types')">Add</button>
      </div>
    </div>
    <div class="opts-sec">
      <h3>Negative Types</h3>
      <div class="opts-list" id="opt-negative_types"></div>
      <div class="opts-add">
        <input type="text" id="new-negative-type" placeholder="New type name..." onkeydown="if(event.key==='Enter')addOption('negative_types')">
        <button class="btn btn-accent btn-small" onclick="addOption('negative_types')">Add</button>
      </div>
    </div>
  </div>
</div>

</div>
<script>
const TODAY = new Date().toISOString().split('T')[0];
const S = { chemistryTypes:[], negativeTypes:[], chemistry:[], paper:[], support_paper:[], carbon_tissue:[], negative:[], exposure:[], photo:[] };
const editId = { chemistry:null, paper:null, support_paper:null, carbon_tissue:null, negative:null, exposure:null, photo:null };

// ── API ──────────────────────────────────────────────────────────────────────
async function api(res, {method='GET',id=null,body=null}={}) {
  let url = 'darkroom-api.php?res='+res;
  if (id) url += '&id='+id;
  const init = {method, headers:{'Content-Type':'application/json'}};
  if (body) init.body = JSON.stringify(body);
  const r = await fetch(url, init);
  const j = await r.json();
  if (!j.ok) throw new Error(j.error||'API error');
  return j.data;
}

// ── Utils ─────────────────────────────────────────────────────────────────────
const esc = s => String(s??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
const v   = id => document.getElementById(id)?.value?.trim()??'';
const b   = id => document.getElementById(id)?.checked??false;
const setVal = (id,val) => { const e=document.getElementById(id); if(e) e.value=val??''; };
const setChk = (id,val) => { const e=document.getElementById(id); if(e) e.checked=!!val; };
const setMsg = (id,txt,t='ok') => { const e=document.getElementById(id); if(e){e.textContent=txt;e.className='form-msg '+t;} };

function populateSelect(elId, items, valKey, labelFn, withNone=true) {
  const sel=document.getElementById(elId); if(!sel) return;
  const cur=sel.value;
  sel.innerHTML = withNone ? '<option value="">-- none --</option>' : '';
  const fn = typeof labelFn==='string' ? r=>r[labelFn] : labelFn;
  items.forEach(r=>{ const o=document.createElement('option'); o.value=r[valKey]; o.textContent=fn(r); sel.appendChild(o); });
  sel.value = cur||'';
}

function buildOpts(items, valKey, labelFn, withNone=true) {
  const fn = typeof labelFn==='string' ? r=>r[labelFn] : labelFn;
  let h = withNone ? '<option value="">-- none --</option>' : '';
  items.forEach(r=>{ h+=`<option value="${esc(String(r[valKey]))}">${esc(String(fn(r)))}</option>`; });
  return h;
}

// ── Init ──────────────────────────────────────────────────────────────────────
async function init() {
  try {
    [S.chemistryTypes,S.negativeTypes] = await Promise.all([api('chemistry_types'),api('negative_types')]);
    populateSelect('c-type_id',S.chemistryTypes,'id','name');
    populateSelect('n-type_id',S.negativeTypes,'id','name');
  } catch(ex){ console.error('Lookup load',ex); }
  await loadTab('chemistry');
}

// ── Tab switching ─────────────────────────────────────────────────────────────
async function switchTab(name) {
  document.querySelectorAll('.tab-pane').forEach(e=>e.style.display='none');
  document.querySelectorAll('.tab-btn').forEach(e=>e.classList.toggle('active',e.dataset.tab===name));
  document.getElementById('tab-'+name).style.display='';
  await loadTab(name);
}

async function loadTab(name) {
  try {
    switch(name) {
      case 'chemistry':
        S.chemistry = await api('chemistry');
        populateSelect('c-type_id',S.chemistryTypes,'id','name');
        populateSelect('c-created_from_id',S.chemistry,'id',r=>`#${r.id} ${r.type_name||''} ${r.date_created}`,true);
        populateSelect('ph-gelatin_chemistry_id',S.chemistry,'id',r=>`#${r.id} ${r.type_name||''} ${r.date_created}`,true);
        populateSelect('ct-chemistry_id',S.chemistry,'id',r=>`#${r.id} ${r.type_name||''} ${r.date_created}`,true);
        renderChemistry(); break;

      case 'paper':
        S.paper = await api('paper');
        if(!S.chemistry.length) S.chemistry = await api('chemistry');
        populateSelect('p-treatment_chemistry_id',S.chemistry,'id',r=>`#${r.id} ${r.type_name||''} ${r.date_created}`,true);
        populateSelect('ph-paper_id',S.paper,'id',r=>[r.manufacturer,r.label].filter(Boolean).join(' ')||`Paper #${r.id}`,true);
        populateSelect('sp-paper_id',S.paper,'id',r=>[r.manufacturer,r.label].filter(Boolean).join(' ')||`Paper #${r.id}`,false);
        renderPaper(); break;

      case 'support_paper':
        [S.support_paper] = await Promise.all([api('support_paper')]);
        if(!S.paper.length) S.paper = await api('paper');
        populateSelect('sp-paper_id',S.paper,'id',r=>[r.manufacturer,r.label].filter(Boolean).join(' ')||`Paper #${r.id}`,false);
        refreshLayerSelects(); renderSupportPaper(); break;

      case 'carbon_tissue':
        S.carbon_tissue = await api('carbon_tissue');
        if(!S.chemistry.length) S.chemistry = await api('chemistry');
        populateSelect('ct-chemistry_id',S.chemistry,'id',r=>`#${r.id} ${r.type_name||''} ${r.date_created}`,true);
        refreshLayerSelects(); renderCarbonTissue(); break;

      case 'negative':
        S.negative = await api('negative');
        populateSelect('n-type_id',S.negativeTypes,'id','name');
        populateSelect('e-negative_id',S.negative,'id',r=>`#${r.id} ${r.type_name||''} ${r.date_created}`,true);
        refreshLayerSelects(); renderNegative(); break;

      case 'exposure':
        [S.exposure] = await Promise.all([api('exposure')]);
        if(!S.negative.length) S.negative = await api('negative');
        populateSelect('e-negative_id',S.negative,'id',r=>`#${r.id} ${r.type_name||''} ${r.date_created}`,true);
        populateSelect('ph-exposure_id',S.exposure,'id',r=>`#${r.id} ${r.date_exposed}`,true);
        renderExposure(); break;

      case 'photo':
        [S.photo,S.paper,S.exposure,S.chemistry,S.support_paper,S.negative,S.carbon_tissue] = await Promise.all([
          api('photo'),api('paper'),api('exposure'),api('chemistry'),api('support_paper'),api('negative'),api('carbon_tissue')
        ]);
        populateSelect('ph-paper_id',S.paper,'id',r=>[r.manufacturer,r.label].filter(Boolean).join(' ')||`Paper #${r.id}`,true);
        populateSelect('ph-exposure_id',S.exposure,'id',r=>`#${r.id} ${r.date_exposed}`,true);
        populateSelect('ph-gelatin_chemistry_id',S.chemistry,'id',r=>`#${r.id} ${r.type_name||''} ${r.date_created}`,true);
        refreshLayerSelects(); renderPhoto(); break;

      case 'options':
        [S.chemistryTypes,S.negativeTypes] = await Promise.all([api('chemistry_types'),api('negative_types')]);
        renderOptions(); break;
    }
  } catch(ex){ console.error('loadTab',name,ex); }
}

// ── Form open / close ─────────────────────────────────────────────────────────
const TAB_LABELS = {chemistry:'Chemistry',paper:'Paper',support_paper:'Support Paper',carbon_tissue:'Carbon Tissue',negative:'Negative',exposure:'Exposure',photo:'Photo'};

function openForm(tab, record=null) {
  const panel = document.getElementById('form-'+tab);
  const titleEl = document.getElementById('form-'+tab+'-title');
  const saveBtn = panel.querySelector('[id$="-save-btn"]');
  if (record) {
    editId[tab]=record.id;
    panel.classList.add('editing');
    if(titleEl) titleEl.textContent = `Edit ${TAB_LABELS[tab]||tab} #${record.id}`;
    if(saveBtn) saveBtn.textContent = 'Save Changes';
    populateFormForEdit(tab,record);
  } else {
    editId[tab]=null;
    panel.classList.remove('editing');
    if(titleEl) titleEl.textContent = `New ${TAB_LABELS[tab]||tab}`;
    if(saveBtn) saveBtn.textContent = `Save ${TAB_LABELS[tab]||tab}`;
    clearForm(tab);
  }
  panel.classList.add('open');
  panel.scrollIntoView({behavior:'smooth',block:'nearest'});
}

function closeForm(tab) {
  const panel=document.getElementById('form-'+tab);
  panel.classList.remove('open','editing');
  editId[tab]=null;
}

function clearForm(tab) {
  switch(tab) {
    case 'chemistry':
      setVal('c-date_created',TODAY); setVal('c-type_id',''); setVal('c-percent_solution',''); setVal('c-created_from_id',''); setVal('c-notes',''); break;
    case 'paper':
      setVal('p-manufacturer',''); setVal('p-label',''); setVal('p-weight',''); setChk('p-hot_press',false); setVal('p-treatment_chemistry_id',''); setVal('p-notes',''); break;
    case 'support_paper':
      setVal('sp-paper_id',''); setVal('sp-mark',''); setVal('sp-notes',''); break;
    case 'carbon_tissue':
      setVal('ct-date_poured',TODAY); setVal('ct-size',''); setVal('ct-chemistry_id',''); setVal('ct-amount_poured',''); setVal('ct-notes',''); break;
    case 'negative':
      setVal('n-date_created',TODAY); setVal('n-type_id',''); setVal('n-settings_notes',''); break;
    case 'exposure':
      setVal('e-date_exposed',TODAY); setVal('e-negative_id',''); setChk('e-test_strip',false); onTestStripChange();
      ['e-paper_soak_time','e-paper_soak_temp','e-hot_develop_time','e-hot_develop_temp','e-cool_develop_time','e-cool_develop_temp','e-notes'].forEach(id=>setVal(id,''));
      document.getElementById('exp-times-rows').innerHTML='<div class="multi-row"><input type="number" step="0.5" min="0" placeholder="minutes" class="exp-time-input"><button class="btn-icon" onclick="removeRow(this)">&#10005;</button></div>';
      break;
    case 'photo':
      ['ph-photo_size','ph-gelatin_chemistry_id','ph-amount_used','ph-paper_id','ph-exposure_id','ph-notes'].forEach(id=>setVal(id,''));
      setVal('ph-date_sensitized',TODAY); setVal('ph-date_exposed',TODAY);
      const lr=document.getElementById('layer-rows'); lr.innerHTML=''; addLayerRow(); break;
  }
}

function populateFormForEdit(tab, r) {
  switch(tab) {
    case 'chemistry':
      setVal('c-date_created',r.date_created); setVal('c-type_id',r.type_id||''); setVal('c-percent_solution',r.percent_solution??'');
      setVal('c-created_from_id',r.created_from_ids?r.created_from_ids.split(',')[0]:''); setVal('c-notes',r.notes||''); break;
    case 'paper':
      setVal('p-manufacturer',r.manufacturer||''); setVal('p-label',r.label||''); setVal('p-weight',r.weight??'');
      setChk('p-hot_press',!!r.hot_press); setVal('p-treatment_chemistry_id',r.treatment_chemistry_id||''); setVal('p-notes',r.notes||''); break;
    case 'support_paper':
      setVal('sp-paper_id',r.paper_id||''); setVal('sp-mark',r.mark||''); setVal('sp-notes',r.notes||''); break;
    case 'carbon_tissue':
      setVal('ct-date_poured',r.date_poured); setVal('ct-size',r.size||''); setVal('ct-chemistry_id',r.chemistry_id||''); setVal('ct-amount_poured',r.amount_poured||''); setVal('ct-notes',r.notes||''); break;
    case 'negative':
      setVal('n-date_created',r.date_created); setVal('n-type_id',r.type_id||''); setVal('n-settings_notes',r.settings_notes||''); break;
    case 'exposure':
      setVal('e-date_exposed',r.date_exposed); setVal('e-negative_id',r.negative_id||'');
      setChk('e-test_strip',!!r.test_strip); onTestStripChange();
      setVal('e-paper_soak_time',r.paper_soak_time??''); setVal('e-paper_soak_temp',r.paper_soak_temp??'');
      setVal('e-hot_develop_time',r.hot_develop_time??''); setVal('e-hot_develop_temp',r.hot_develop_temp??'');
      setVal('e-cool_develop_time',r.cool_develop_time??''); setVal('e-cool_develop_temp',r.cool_develop_temp??''); setVal('e-notes',r.notes||'');
      const rows=document.getElementById('exp-times-rows'); rows.innerHTML='';
      (r.times||[]).forEach(t=>addExpTimeRow(t.duration_minutes)); if(!rows.children.length) addExpTimeRow(); break;
    case 'photo':
      setVal('ph-photo_size',r.photo_size||''); setVal('ph-gelatin_chemistry_id',r.gelatin_chemistry_id||'');
      setVal('ph-amount_used',r.amount_used||''); setVal('ph-paper_id',r.paper_id||'');
      setVal('ph-date_sensitized',r.date_sensitized||TODAY); setVal('ph-date_exposed',r.date_exposed||TODAY);
      setVal('ph-exposure_id',r.exposure_id||''); setVal('ph-notes',r.notes||'');
      const lr=document.getElementById('layer-rows'); lr.innerHTML='';
      (r.layers||[]).forEach(l=>addLayerRow(l)); if(!lr.children.length) addLayerRow(); break;
  }
}

async function editRecord(tab, id) {
  try { const rec=await api(tab,{id}); openForm(tab,rec); }
  catch(ex){ alert('Could not load record: '+ex.message); }
}

// ── Exposure time rows ────────────────────────────────────────────────────────
function addExpTimeRow(value='') {
  const c=document.getElementById('exp-times-rows');
  const row=document.createElement('div'); row.className='multi-row';
  row.innerHTML=`<input type="number" step="0.5" min="0" placeholder="minutes" class="exp-time-input" value="${esc(String(value??''))}"><button class="btn-icon" onclick="removeRow(this)">&#10005;</button>`;
  c.appendChild(row);
}
function repeatLastExpTime() {
  const inputs=[...document.querySelectorAll('.exp-time-input')];
  addExpTimeRow(inputs.length ? inputs[inputs.length-1].value : '');
}
function onTestStripChange() {
  const show=document.getElementById('e-test_strip')?.checked;
  const btn=document.getElementById('btn-repeat-time');
  if(btn) btn.style.display=show?'':'none';
}
function removeRow(btn) {
  const c=btn.closest('.multi-rows');
  if(c&&c.children.length>1) btn.closest('.multi-row').remove();
}

// ── Layer rows ────────────────────────────────────────────────────────────────
function layerRowHTML() {
  const spO=buildOpts(S.support_paper,'id',r=>`${r.mark||'--'} (${esc(r.paper_label||'')})`);
  const nO=buildOpts(S.negative,'id',r=>`#${r.id} ${esc(r.type_name||'')} ${esc(r.date_created)}`);
  const ctO=buildOpts(S.carbon_tissue,'id',r=>`#${r.id} ${esc(r.size||'')} ${esc(r.date_poured)}`);
  return `<select class="layer-sp" title="Support Paper">${spO}</select><select class="layer-neg" title="Negative">${nO}</select><select class="layer-ct" title="Carbon Tissue">${ctO}</select><button class="btn-icon" onclick="removeRow(this)">&#10005;</button>`;
}
function addLayerRow(data={}) {
  const c=document.getElementById('layer-rows');
  const row=document.createElement('div'); row.className='multi-row'; row.innerHTML=layerRowHTML();
  if(data.support_paper_id) row.querySelector('.layer-sp').value=data.support_paper_id;
  if(data.negative_id)      row.querySelector('.layer-neg').value=data.negative_id;
  if(data.carbon_tissue_id) row.querySelector('.layer-ct').value=data.carbon_tissue_id;
  c.appendChild(row);
}
function refreshLayerSelects() {
  document.querySelectorAll('#layer-rows .multi-row').forEach(row=>{
    const sp=row.querySelector('.layer-sp'),neg=row.querySelector('.layer-neg'),ct=row.querySelector('.layer-ct');
    const sv=sp?.value,nv=neg?.value,cv=ct?.value;
    if(sp){ sp.innerHTML=buildOpts(S.support_paper,'id',r=>`${r.mark||'--'} (${esc(r.paper_label||'')})`); sp.value=sv||''; }
    if(neg){ neg.innerHTML=buildOpts(S.negative,'id',r=>`#${r.id} ${esc(r.type_name||'')} ${esc(r.date_created)}`); neg.value=nv||''; }
    if(ct){ ct.innerHTML=buildOpts(S.carbon_tissue,'id',r=>`#${r.id} ${esc(r.size||'')} ${esc(r.date_poured)}`); ct.value=cv||''; }
  });
}

// ── Submit functions ──────────────────────────────────────────────────────────
async function submitChemistry() {
  try {
    const body={date_created:v('c-date_created'),type_id:v('c-type_id'),percent_solution:v('c-percent_solution'),created_from_id:v('c-created_from_id'),notes:v('c-notes')};
    editId.chemistry ? await api('chemistry',{method:'PATCH',id:editId.chemistry,body}) : await api('chemistry',{method:'POST',body});
    setMsg('c-msg','Saved'); closeForm('chemistry'); await loadTab('chemistry');
  } catch(ex){ setMsg('c-msg',ex.message,'err'); }
}
async function submitPaper() {
  try {
    const body={manufacturer:v('p-manufacturer'),label:v('p-label'),weight:v('p-weight'),hot_press:b('p-hot_press'),treatment_chemistry_id:v('p-treatment_chemistry_id'),notes:v('p-notes')};
    editId.paper ? await api('paper',{method:'PATCH',id:editId.paper,body}) : await api('paper',{method:'POST',body});
    setMsg('p-msg','Saved'); closeForm('paper'); await loadTab('paper');
  } catch(ex){ setMsg('p-msg',ex.message,'err'); }
}
async function submitSupportPaper() {
  if(!v('sp-paper_id')) return setMsg('sp-msg','Select a base paper','err');
  try {
    const body={paper_id:v('sp-paper_id'),mark:v('sp-mark').toUpperCase(),notes:v('sp-notes')};
    editId.support_paper ? await api('support_paper',{method:'PATCH',id:editId.support_paper,body}) : await api('support_paper',{method:'POST',body});
    setMsg('sp-msg','Saved'); closeForm('support_paper'); await loadTab('support_paper');
  } catch(ex){ setMsg('sp-msg',ex.message,'err'); }
}
async function submitCarbonTissue() {
  if(!v('ct-date_poured')) return setMsg('ct-msg','Date poured required','err');
  try {
    const body={date_poured:v('ct-date_poured'),size:v('ct-size'),chemistry_id:v('ct-chemistry_id'),amount_poured:v('ct-amount_poured'),notes:v('ct-notes')};
    editId.carbon_tissue ? await api('carbon_tissue',{method:'PATCH',id:editId.carbon_tissue,body}) : await api('carbon_tissue',{method:'POST',body});
    setMsg('ct-msg','Saved'); closeForm('carbon_tissue'); await loadTab('carbon_tissue');
  } catch(ex){ setMsg('ct-msg',ex.message,'err'); }
}
async function submitNegative() {
  try {
    const body={date_created:v('n-date_created'),type_id:v('n-type_id'),settings_notes:v('n-settings_notes')};
    editId.negative ? await api('negative',{method:'PATCH',id:editId.negative,body}) : await api('negative',{method:'POST',body});
    setMsg('n-msg','Saved'); closeForm('negative'); await loadTab('negative');
  } catch(ex){ setMsg('n-msg',ex.message,'err'); }
}
async function submitExposure() {
  try {
    const times=[...document.querySelectorAll('.exp-time-input')].map(e=>({duration_minutes:e.value})).filter(t=>t.duration_minutes!=='');
    const body={date_exposed:v('e-date_exposed'),test_strip:b('e-test_strip'),negative_id:v('e-negative_id'),paper_soak_time:v('e-paper_soak_time'),paper_soak_temp:v('e-paper_soak_temp'),hot_develop_time:v('e-hot_develop_time'),hot_develop_temp:v('e-hot_develop_temp'),cool_develop_time:v('e-cool_develop_time'),cool_develop_temp:v('e-cool_develop_temp'),notes:v('e-notes'),times};
    editId.exposure ? await api('exposure',{method:'PATCH',id:editId.exposure,body}) : await api('exposure',{method:'POST',body});
    setMsg('e-msg','Saved'); closeForm('exposure'); await loadTab('exposure');
  } catch(ex){ setMsg('e-msg',ex.message,'err'); }
}
async function submitPhoto() {
  try {
    const layers=[...document.querySelectorAll('#layer-rows .multi-row')].map(row=>({support_paper_id:row.querySelector('.layer-sp')?.value||'',negative_id:row.querySelector('.layer-neg')?.value||'',carbon_tissue_id:row.querySelector('.layer-ct')?.value||''})).filter(l=>l.support_paper_id||l.negative_id||l.carbon_tissue_id);
    const body={photo_size:v('ph-photo_size'),gelatin_chemistry_id:v('ph-gelatin_chemistry_id'),amount_used:v('ph-amount_used'),paper_id:v('ph-paper_id'),date_sensitized:v('ph-date_sensitized'),date_exposed:v('ph-date_exposed'),exposure_id:v('ph-exposure_id'),notes:v('ph-notes'),layers};
    editId.photo ? await api('photo',{method:'PATCH',id:editId.photo,body}) : await api('photo',{method:'POST',body});
    setMsg('ph-msg','Saved'); closeForm('photo'); await loadTab('photo');
  } catch(ex){ setMsg('ph-msg',ex.message,'err'); }
}

// ── Delete ────────────────────────────────────────────────────────────────────
async function deleteRecord(res,id,tab) {
  if(!confirm(`Delete #${id}? Cannot be undone.`)) return;
  try { await api(res,{method:'DELETE',id}); await loadTab(tab); }
  catch(ex){ alert('Delete failed: '+ex.message); }
}

// ── Options ───────────────────────────────────────────────────────────────────
function renderOptions() {
  renderOptList('chemistry_types',S.chemistryTypes,'opt-chemistry_types');
  renderOptList('negative_types',S.negativeTypes,'opt-negative_types');
}
function renderOptList(res,items,cid) {
  const el=document.getElementById(cid); if(!el) return;
  if(!items.length){el.innerHTML='<div style="color:rgba(255,255,255,.3);font-size:11px">No options yet</div>';return;}
  el.innerHTML=items.map(r=>`<div class="opt-row" id="orow-${r.id}">
    <span class="opt-name" id="oname-${r.id}">${esc(r.name)}</span>
    <button class="btn btn-edit btn-small" onclick="startRename('${res}',${r.id})">Rename</button>
    <button class="btn btn-danger btn-small" onclick="deleteOption('${res}',${r.id})">&#10005;</button>
  </div>`).join('');
}
function startRename(res,id) {
  const nameEl=document.getElementById('oname-'+id); if(!nameEl) return;
  const cur=nameEl.textContent;
  nameEl.outerHTML=`<input type="text" class="opt-input" id="oname-${id}" value="${esc(cur)}" onkeydown="if(event.key==='Enter')saveRename('${res}',${id});if(event.key==='Escape')loadTab('options')">`;
  document.getElementById('oname-'+id)?.focus();
  const btn=document.querySelector(`#orow-${id} .btn-edit`);
  if(btn){btn.textContent='Save';btn.onclick=()=>saveRename(res,id);}
}
async function saveRename(res,id) {
  const inp=document.getElementById('oname-'+id); if(!inp) return;
  const name=inp.value.trim(); if(!name) return;
  try { await api(res,{method:'PATCH',id,body:{name}}); await loadTab('options'); }
  catch(ex){ alert('Rename failed: '+ex.message); }
}
async function addOption(res) {
  const inputId=res==='chemistry_types'?'new-chemistry-type':'new-negative-type';
  const inp=document.getElementById(inputId); if(!inp) return;
  const name=inp.value.trim(); if(!name) return;
  try {
    await api(res,{method:'POST',body:{name}}); inp.value='';
    [S.chemistryTypes,S.negativeTypes]=await Promise.all([api('chemistry_types'),api('negative_types')]);
    populateSelect('c-type_id',S.chemistryTypes,'id','name');
    populateSelect('n-type_id',S.negativeTypes,'id','name');
    renderOptions();
  } catch(ex){ alert('Add failed: '+ex.message); }
}
async function deleteOption(res,id) {
  if(!confirm('Delete this option? Existing records lose this label.')) return;
  try {
    await api(res,{method:'DELETE',id});
    [S.chemistryTypes,S.negativeTypes]=await Promise.all([api('chemistry_types'),api('negative_types')]);
    renderOptions();
  } catch(ex){ alert('Delete failed: '+ex.message); }
}

// ── Renderers ─────────────────────────────────────────────────────────────────
function acts(tab,id){return `<div class="td-actions"><button class="btn btn-edit btn-small" onclick="editRecord('${tab}',${id})">Edit</button><button class="btn btn-danger btn-small" onclick="deleteRecord('${tab}',${id},'${tab}')">Del</button></div>`;}

function renderChemistry() {
  const tb=document.querySelector('#tbl-chemistry tbody');
  if(!S.chemistry.length){tb.innerHTML='<tr><td colspan="7" class="empty-state">No chemistry records yet</td></tr>';return;}
  tb.innerHTML=S.chemistry.map(r=>`<tr>
    <td style="color:rgba(255,255,255,.35)">${r.id}</td>
    <td>${esc(r.date_created)}</td>
    <td>${r.type_name?`<span class="badge">${esc(r.type_name)}</span>`:'--'}</td>
    <td>${r.percent_solution!=null?Number(r.percent_solution).toFixed(2)+'%':'--'}</td>
    <td>${r.created_from_ids?r.created_from_ids.split(',').map(x=>`<span class="badge">#${esc(x)}</span>`).join(' '):'--'}</td>
    <td style="color:rgba(255,255,255,.5);max-width:200px;white-space:pre-wrap">${esc(r.notes||'--')}</td>
    <td>${acts('chemistry',r.id)}</td></tr>`).join('');
}
function renderPaper() {
  const tb=document.querySelector('#tbl-paper tbody');
  if(!S.paper.length){tb.innerHTML='<tr><td colspan="8" class="empty-state">No paper records yet</td></tr>';return;}
  tb.innerHTML=S.paper.map(r=>`<tr>
    <td style="color:rgba(255,255,255,.35)">${r.id}</td>
    <td>${esc(r.manufacturer||'--')}</td><td>${esc(r.label||'--')}</td>
    <td>${r.weight!=null?Number(r.weight).toFixed(1)+' gsm':'--'}</td>
    <td>${r.hot_press?'<span class="pill-yes">Yes</span>':'<span class="pill-no">No</span>'}</td>
    <td>${r.treatment_chemistry_id?`<span class="badge">#${r.treatment_chemistry_id} ${esc(r.treatment_label||'')}</span>`:'--'}</td>
    <td style="color:rgba(255,255,255,.5);max-width:140px">${esc(r.notes||'--')}</td>
    <td>${acts('paper',r.id)}</td></tr>`).join('');
}
function renderSupportPaper() {
  const tb=document.querySelector('#tbl-support_paper tbody');
  if(!S.support_paper.length){tb.innerHTML='<tr><td colspan="7" class="empty-state">No support paper records yet</td></tr>';return;}
  tb.innerHTML=S.support_paper.map(r=>`<tr>
    <td style="color:rgba(255,255,255,.35)">${r.id}</td>
    <td><span class="badge-g" style="font-size:14px;font-weight:bold;letter-spacing:.05em">${esc(r.mark||'--')}</span></td>
    <td>${esc(r.paper_label||'--')}</td>
    <td>${r.weight!=null?Number(r.weight).toFixed(1)+' gsm':'--'}</td>
    <td>${r.hot_press?'<span class="pill-yes">Yes</span>':'<span class="pill-no">No</span>'}</td>
    <td style="color:rgba(255,255,255,.5);max-width:200px">${esc(r.notes||'--')}</td>
    <td>${acts('support_paper',r.id)}</td></tr>`).join('');
}
function renderCarbonTissue() {
  const tb=document.querySelector('#tbl-carbon_tissue tbody');
  if(!S.carbon_tissue.length){tb.innerHTML='<tr><td colspan="7" class="empty-state">No carbon tissue records yet</td></tr>';return;}
  tb.innerHTML=S.carbon_tissue.map(r=>`<tr>
    <td style="color:rgba(255,255,255,.35)">${r.id}</td>
    <td>${esc(r.date_poured)}</td><td>${esc(r.size||'--')}</td>
    <td>${r.chemistry_id?`<span class="badge">#${r.chemistry_id} ${esc(r.chem_type||'')}</span>`:'--'}</td>
    <td>${esc(r.amount_poured||'--')}</td>
    <td style="color:rgba(255,255,255,.5);max-width:200px">${esc(r.notes||'--')}</td>
    <td>${acts('carbon_tissue',r.id)}</td></tr>`).join('');
}
function renderNegative() {
  const tb=document.querySelector('#tbl-negative tbody');
  if(!S.negative.length){tb.innerHTML='<tr><td colspan="5" class="empty-state">No negatives yet</td></tr>';return;}
  tb.innerHTML=S.negative.map(r=>`<tr>
    <td style="color:rgba(255,255,255,.35)">${r.id}</td>
    <td>${esc(r.date_created)}</td>
    <td>${r.type_name?`<span class="badge">${esc(r.type_name)}</span>`:'--'}</td>
    <td style="color:rgba(255,255,255,.5);max-width:280px;white-space:pre-wrap">${esc(r.settings_notes||'--')}</td>
    <td>${acts('negative',r.id)}</td></tr>`).join('');
}
function renderExposure() {
  const tb=document.querySelector('#tbl-exposure tbody');
  if(!S.exposure.length){tb.innerHTML='<tr><td colspan="10" class="empty-state">No exposure records yet</td></tr>';return;}
  tb.innerHTML=S.exposure.map(r=>{
    const times=(r.times||[]).map(t=>`<span class="time-chip">${t.duration_minutes}min</span>`).join('');
    return `<tr>
      <td style="color:rgba(255,255,255,.35)">${r.id}</td>
      <td>${esc(r.date_exposed)}</td>
      <td>${r.test_strip?'<span class="pill-yes">Yes</span>':'<span class="pill-no">No</span>'}</td>
      <td>${r.negative_id?`<span class="badge">#${r.negative_id} ${esc(r.neg_type||'')} ${esc(r.neg_date||'')}</span>`:'--'}</td>
      <td><div class="times-list">${times||'--'}</div></td>
      <td>${r.paper_soak_time!=null?r.paper_soak_time+'min':'--'}${r.paper_soak_temp!=null?' / '+r.paper_soak_temp+'C':''}</td>
      <td>${r.hot_develop_time!=null?r.hot_develop_time+'s':'--'}${r.hot_develop_temp!=null?' / '+r.hot_develop_temp+'C':''}</td>
      <td>${r.cool_develop_time!=null?r.cool_develop_time+'s':'--'}${r.cool_develop_temp!=null?' / '+r.cool_develop_temp+'C':''}</td>
      <td style="color:rgba(255,255,255,.5);max-width:140px">${esc(r.notes||'--')}</td>
      <td>${acts('exposure',r.id)}</td></tr>`;
  }).join('');
}
function renderPhoto() {
  const tb=document.querySelector('#tbl-photo tbody');
  if(!S.photo.length){tb.innerHTML='<tr><td colspan="9" class="empty-state">No photos yet</td></tr>';return;}
  tb.innerHTML=S.photo.map(r=>{
    const layers=(r.layers||[]).map((l,i)=>{
      let p=[];
      if(l.support_paper_id) p.push(`<span class="badge-g">SP:${esc(l.sp_mark||l.support_paper_id)}</span>`);
      if(l.negative_id)      p.push(`<span class="badge">Neg:#${l.negative_id}</span>`);
      if(l.carbon_tissue_id) p.push(`<span class="badge-b">CT:#${l.carbon_tissue_id}</span>`);
      return `<div style="margin-bottom:2px">L${i+1}: ${p.join(' ')}</div>`;
    }).join('');
    return `<tr>
      <td style="color:rgba(255,255,255,.35)">${r.id}</td>
      <td>${esc(r.date_sensitized||'--')}</td>
      <td>${esc(r.date_exposed||'--')}</td>
      <td>${esc(r.photo_size||'--')}</td>
      <td>${r.gelatin_chemistry_id?`<span class="badge">#${r.gelatin_chemistry_id} ${esc(r.gelatin_type||'')}${r.amount_used?' / '+esc(r.amount_used):''}</span>`:'--'}</td>
      <td style="min-width:130px">${layers||'--'}</td>
      <td>${r.exposure_id?`<span class="badge">#${r.exposure_id} ${esc(r.exposure_date||'')}</span>`:'--'}</td>
      <td style="color:rgba(255,255,255,.5);max-width:140px">${esc(r.notes||'--')}</td>
      <td>${acts('photo',r.id)}</td></tr>`;
  }).join('');
}

init();
</script>
</body>
</html>
