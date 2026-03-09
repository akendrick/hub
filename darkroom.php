<?php declare(strict_types=1); require __DIR__.'/auth.php'; auth_require_page(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Knotwork · Darkroom</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Mono:ital,wght@0,400;0,500;1,400&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'DM Mono',monospace;background:#c8c87a;background-image:repeating-linear-gradient(0deg,transparent,transparent 39px,rgba(0,0,0,.04) 39px,rgba(0,0,0,.04) 40px),repeating-linear-gradient(90deg,transparent,transparent 39px,rgba(0,0,0,.04) 39px,rgba(0,0,0,.04) 40px);min-height:100vh;color:#fff}
.shell{max-width:1200px;margin:0 auto;padding:28px 20px 80px}
.page-hdr{display:flex;align-items:baseline;gap:18px;margin-bottom:22px}
.page-hdr a{font-family:'Bebas Neue',sans-serif;font-size:13px;letter-spacing:.18em;color:#1a1a14;opacity:.5;text-decoration:none}.page-hdr a:hover{opacity:.9}
.page-title{font-family:'Bebas Neue',sans-serif;font-size:clamp(28px,5vw,42px);letter-spacing:.12em;color:#1a1a14}
/* Tabs */
.tabs{display:flex;gap:2px;flex-wrap:wrap;margin-bottom:22px;border-bottom:2px solid rgba(0,0,0,.2)}
.tab-btn{font-family:'Bebas Neue',sans-serif;font-size:14px;letter-spacing:.13em;padding:9px 17px;background:rgba(0,0,0,.12);border:none;color:rgba(26,26,20,.6);cursor:pointer;border-radius:4px 4px 0 0;transition:background .15s,color .15s}
.tab-btn:hover{background:rgba(0,0,0,.2);color:#1a1a14}
.tab-btn.active{background:#1e2318;color:#e8e050}
/* Buttons */
.btn{font-family:'DM Mono',monospace;font-size:11px;font-weight:500;letter-spacing:.1em;text-transform:uppercase;padding:7px 16px;border-radius:3px;border:none;cursor:pointer;transition:background .15s}
.btn-accent{background:#e8e050;color:#1a1a14}.btn-accent:hover{background:#f0e860}
.btn-ghost{background:rgba(255,255,255,.08);color:rgba(255,255,255,.7)}.btn-ghost:hover{background:rgba(255,255,255,.15)}
.btn-danger{background:rgba(224,80,80,.15);color:#e05050}.btn-danger:hover{background:rgba(224,80,80,.3)}
.btn-edit{background:rgba(232,224,80,.1);color:#e8e050;border:1px solid rgba(232,224,80,.25)}.btn-edit:hover{background:rgba(232,224,80,.2)}
.btn-small{padding:4px 10px;font-size:10px}
.btn-icon{background:none;border:1px solid rgba(255,255,255,.15);color:rgba(255,255,255,.5);padding:3px 8px;font-size:13px;cursor:pointer;border-radius:3px;font-family:'DM Mono',monospace}.btn-icon:hover{border-color:rgba(255,255,255,.4);color:#fff}
.btn-repeat{background:rgba(96,208,128,.1);border:1px solid rgba(96,208,128,.3);color:#60d080;padding:5px 12px;font-size:11px;cursor:pointer;border-radius:3px;font-family:'DM Mono',monospace;letter-spacing:.05em}.btn-repeat:hover{background:rgba(96,208,128,.2)}
/* Cards */
.card{background:#1e2318;border-radius:6px;border:1px solid rgba(255,255,255,.07);padding:22px;margin-bottom:18px}
.toolbar{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;gap:10px;flex-wrap:wrap}
.section-label{font-size:9px;font-weight:500;letter-spacing:.2em;text-transform:uppercase;color:rgba(255,255,255,.4)}
/* Form Panel */
.form-panel{background:#1a2014;border:1px solid rgba(255,255,255,.1);border-left:3px solid #e8e050;border-radius:0 6px 6px 0;padding:0;margin-bottom:18px;display:none;overflow:hidden}
.form-panel.open{display:block}
.form-panel.editing{border-left-color:#7ec860}
.panel-header{padding:16px 22px 12px;border-bottom:1px solid rgba(255,255,255,.06)}
.panel-title{font-family:'Bebas Neue',sans-serif;font-size:20px;letter-spacing:.14em;color:#e8e050}
.form-panel.editing .panel-title{color:#7ec860}
/* Form sections (inside photo form) */
.fsec{padding:16px 22px;border-bottom:1px solid rgba(255,255,255,.06)}
.fsec:last-of-type{border-bottom:none}
.fsec-photo  {background:rgba(255,255,255,.02)}
.fsec-paper  {background:rgba(96,208,128,.04);border-left:3px solid rgba(96,208,128,.3)}
.fsec-ct     {background:rgba(80,160,224,.04);border-left:3px solid rgba(80,160,224,.3)}
.fsec-neg    {background:rgba(232,160,80,.04);border-left:3px solid rgba(232,160,80,.3)}
.fsec-exp    {background:rgba(232,224,80,.04);border-left:3px solid rgba(232,224,80,.3)}
.fsec-dev    {background:rgba(160,80,224,.04);border-left:3px solid rgba(160,80,224,.3)}
.fsec-label{font-size:8px;font-weight:500;letter-spacing:.2em;text-transform:uppercase;margin-bottom:12px}
.fsec-paper .fsec-label{color:rgba(96,208,128,.7)}
.fsec-ct    .fsec-label{color:rgba(80,160,224,.7)}
.fsec-neg   .fsec-label{color:rgba(232,160,80,.7)}
.fsec-exp   .fsec-label{color:rgba(232,224,80,.7)}
.fsec-dev   .fsec-label{color:rgba(160,80,224,.7)}
.fsec-photo .fsec-label{color:rgba(255,255,255,.4)}
/* Form grid */
.form-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(185px,1fr));gap:11px 16px}
.form-group{display:flex;flex-direction:column;gap:5px}
.form-group.wide{grid-column:1/-1}
.form-group.span2{grid-column:span 2}
label.fld{font-size:8px;font-weight:500;letter-spacing:.18em;text-transform:uppercase;color:rgba(255,255,255,.4)}
input[type=text],input[type=number],input[type=date],select,textarea{background:rgba(0,0,0,.3);border:1px solid rgba(255,255,255,.1);border-radius:3px;color:#fff;font-family:'DM Mono',monospace;font-size:13px;padding:7px 10px;outline:none;width:100%;transition:border-color .15s}
input:focus,select:focus,textarea:focus{border-color:#e8e050}
select option{background:#1e2318}
textarea{resize:vertical;min-height:58px}
.checkbox-row{display:flex;align-items:center;gap:10px;padding:7px 0}
.checkbox-row input[type=checkbox]{width:16px;height:16px;accent-color:#e8e050}
.checkbox-row label{font-size:12px;color:rgba(255,255,255,.7)}
.form-footer{display:flex;align-items:center;gap:10px;margin-top:0;flex-wrap:wrap;padding:14px 22px;background:rgba(0,0,0,.2);border-top:1px solid rgba(255,255,255,.06)}
.form-msg{font-size:11px}.form-msg.ok{color:#60d080}.form-msg.err{color:#e05050}
/* Dynamic info box beneath select */
.dyn-info{background:rgba(0,0,0,.25);border-radius:4px;padding:10px 12px;margin-top:6px;font-size:11px;color:rgba(255,255,255,.7);line-height:1.6;display:none}
.dyn-info.show{display:block}
.dyn-row{display:flex;gap:14px;flex-wrap:wrap}
.dyn-kv{display:flex;flex-direction:column;gap:2px}
.dyn-k{font-size:8px;letter-spacing:.15em;text-transform:uppercase;color:rgba(255,255,255,.35)}
.dyn-v{font-size:12px;color:rgba(255,255,255,.8)}
/* Layer block */
.layer-block{background:rgba(0,0,0,.2);border:1px solid rgba(255,255,255,.07);border-radius:4px;margin-bottom:10px;overflow:hidden}
.layer-header{display:flex;align-items:center;justify-content:space-between;padding:8px 12px;background:rgba(255,255,255,.04);font-size:9px;letter-spacing:.18em;text-transform:uppercase;color:rgba(255,255,255,.5)}
.layer-body{padding:12px;display:flex;flex-direction:column;gap:8px}
.layer-sub{padding:10px 12px;border-radius:3px;border-left:2px solid}
.layer-sub-sp {background:rgba(96,208,128,.06);border-color:rgba(96,208,128,.4)}
.layer-sub-ct {background:rgba(80,160,224,.06);border-color:rgba(80,160,224,.4)}
.layer-sub-neg{background:rgba(232,160,80,.06);border-color:rgba(232,160,80,.4)}
.layer-sub-label{font-size:8px;letter-spacing:.18em;text-transform:uppercase;margin-bottom:6px}
.layer-sub-sp  .layer-sub-label{color:rgba(96,208,128,.7)}
.layer-sub-ct  .layer-sub-label{color:rgba(80,160,224,.7)}
.layer-sub-neg .layer-sub-label{color:rgba(232,160,80,.7)}
/* Exposure times */
.exp-time-bar{display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:8px}
.multi-rows{display:flex;flex-direction:column;gap:7px;margin-bottom:6px}
.multi-row{display:flex;align-items:center;gap:6px}
.multi-row input{flex:1;min-width:0}
.add-row-btn{background:none;border:1px dashed rgba(255,255,255,.22);color:rgba(255,255,255,.45);font-size:11px;letter-spacing:.08em;padding:6px 14px;border-radius:3px;cursor:pointer;transition:border-color .15s,color .15s;font-family:'DM Mono',monospace}
.add-row-btn:hover{border-color:#e8e050;color:#e8e050}
/* Table */
.data-table{width:100%;border-collapse:collapse;font-size:12px}
.data-table th{text-align:left;font-size:8px;font-weight:500;letter-spacing:.18em;text-transform:uppercase;color:rgba(255,255,255,.35);padding:0 10px 10px;border-bottom:1px solid rgba(255,255,255,.08)}
.data-table td{padding:8px 10px;border-bottom:1px solid rgba(255,255,255,.05);color:rgba(255,255,255,.8);vertical-align:top}
.data-table tr:last-child td{border-bottom:none}
.data-table tr:hover td{background:rgba(255,255,255,.03)}
.td-actions{white-space:nowrap;display:flex;gap:5px}
.empty-state{text-align:center;padding:36px;color:rgba(255,255,255,.22);font-size:12px;letter-spacing:.1em}
/* Pills / badges */
.pill-yes{background:rgba(96,208,128,.15);color:#60d080;padding:2px 8px;border-radius:12px;font-size:10px}
.pill-no {background:rgba(255,255,255,.07);color:rgba(255,255,255,.35);padding:2px 8px;border-radius:12px;font-size:10px}
.badge  {background:rgba(232,224,80,.12);color:#e8e050;padding:2px 7px;border-radius:3px;font-size:10px;display:inline-block;margin:1px}
.badge-g{background:rgba(96,208,128,.12);color:#60d080;padding:2px 7px;border-radius:3px;font-size:10px;display:inline-block;margin:1px}
.badge-b{background:rgba(80,160,224,.12);color:#50a0e0;padding:2px 7px;border-radius:3px;font-size:10px;display:inline-block;margin:1px}
.badge-o{background:rgba(232,160,80,.12);color:#e0a050;padding:2px 7px;border-radius:3px;font-size:10px;display:inline-block;margin:1px}
.time-chip{background:rgba(255,255,255,.1);padding:1px 7px;border-radius:3px;font-size:11px;display:inline-block;margin:1px}
.times-list{display:flex;flex-wrap:wrap;gap:3px}
/* Photo cards grid */
.photo-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:16px}
.photo-card{background:#1e2318;border:1px solid rgba(255,255,255,.07);border-radius:6px;overflow:hidden;cursor:pointer;transition:border-color .15s,transform .1s}
.photo-card:hover{border-color:rgba(232,224,80,.4);transform:translateY(-2px)}
.photo-thumb{width:100%;aspect-ratio:4/3;object-fit:cover;display:block;background:#252b1c}
.photo-thumb-placeholder{width:100%;aspect-ratio:4/3;background:#252b1c;display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,.15);font-size:32px}
.photo-card-body{padding:10px 12px}
.photo-card-title{font-size:13px;font-weight:500;color:#fff;margin-bottom:4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.photo-card-meta{font-size:10px;color:rgba(255,255,255,.45);display:flex;justify-content:space-between}
/* Photo detail modal */
.modal-bg{position:fixed;inset:0;background:rgba(0,0,0,.75);z-index:100;display:none;align-items:flex-start;justify-content:center;padding:40px 20px;overflow-y:auto}
.modal-bg.open{display:flex}
.modal{background:#1e2318;border:1px solid rgba(255,255,255,.1);border-radius:8px;width:100%;max-width:820px;overflow:hidden}
.modal-header{display:flex;align-items:center;justify-content:space-between;padding:16px 22px;border-bottom:1px solid rgba(255,255,255,.08)}
.modal-title{font-family:'Bebas Neue',sans-serif;font-size:22px;letter-spacing:.12em;color:#e8e050}
.modal-close{background:none;border:1px solid rgba(255,255,255,.2);color:rgba(255,255,255,.6);padding:4px 10px;cursor:pointer;border-radius:3px;font-size:16px;font-family:'DM Mono',monospace}
.modal-close:hover{border-color:#fff;color:#fff}
.modal-img{width:100%;max-height:400px;object-fit:contain;background:#111}
.modal-body{padding:20px 22px;display:flex;flex-direction:column;gap:14px}
.detail-sec{border-radius:4px;overflow:hidden}
.detail-sec-header{font-size:8px;font-weight:500;letter-spacing:.2em;text-transform:uppercase;padding:6px 12px}
.detail-sec-photo {background:rgba(255,255,255,.04)} .detail-sec-photo .detail-sec-header{color:rgba(255,255,255,.4)}
.detail-sec-paper {background:rgba(96,208,128,.06)} .detail-sec-paper .detail-sec-header{background:rgba(96,208,128,.12);color:rgba(96,208,128,.9)}
.detail-sec-ct    {background:rgba(80,160,224,.06)} .detail-sec-ct .detail-sec-header{background:rgba(80,160,224,.12);color:rgba(80,160,224,.9)}
.detail-sec-neg   {background:rgba(232,160,80,.06)} .detail-sec-neg .detail-sec-header{background:rgba(232,160,80,.12);color:rgba(232,160,80,.9)}
.detail-sec-exp   {background:rgba(232,224,80,.06)} .detail-sec-exp .detail-sec-header{background:rgba(232,224,80,.12);color:rgba(232,224,80,.9)}
.detail-sec-dev   {background:rgba(160,80,224,.06)} .detail-sec-dev .detail-sec-header{background:rgba(160,80,224,.12);color:rgba(160,80,224,.9)}
.detail-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:10px;padding:12px}
.detail-kv{display:flex;flex-direction:column;gap:3px}
.detail-k{font-size:8px;letter-spacing:.15em;text-transform:uppercase;color:rgba(255,255,255,.3)}
.detail-v{font-size:12px;color:rgba(255,255,255,.8)}
.detail-actions{padding:6px 12px 12px;display:flex;gap:8px}
/* Options tab */
.opts-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:20px}
.opts-sec{background:#252b1c;border-radius:6px;border:1px solid rgba(255,255,255,.08);padding:18px}
.opts-sec h3{font-family:'Bebas Neue',sans-serif;font-size:16px;letter-spacing:.14em;color:#e8e050;margin-bottom:14px}
.opts-list{display:flex;flex-direction:column;gap:7px;margin-bottom:14px}
.opt-row{display:flex;align-items:center;gap:7px}
.opt-name{flex:1;font-size:12px;color:rgba(255,255,255,.8)}
.opts-add{display:flex;gap:8px}.opts-add input{flex:1}
.opts-recents{margin-top:14px}
.opts-recents-hdr{font-size:8px;letter-spacing:.18em;text-transform:uppercase;color:rgba(255,255,255,.35);margin-bottom:8px;padding-top:10px;border-top:1px solid rgba(255,255,255,.07)}
.opts-item{font-size:11px;color:rgba(255,255,255,.65);padding:4px 0;border-bottom:1px solid rgba(255,255,255,.04)}
.opts-item:last-child{border-bottom:none}
.opts-more{background:none;border:none;color:rgba(232,224,80,.6);font-size:10px;letter-spacing:.1em;cursor:pointer;padding:8px 0;width:100%;text-align:center;font-family:'DM Mono',monospace}.opts-more:hover{color:#e8e050}
/* Image upload */
.img-upload-area{border:2px dashed rgba(255,255,255,.18);border-radius:4px;padding:14px;text-align:center;cursor:pointer;transition:border-color .15s}.img-upload-area:hover{border-color:#e8e050}
.img-upload-area input[type=file]{display:none}
.img-preview{width:100%;max-height:160px;object-fit:contain;border-radius:3px;margin-top:8px;display:none}
/* Global error */
.global-error{background:rgba(200,50,50,.9);color:#fff;padding:12px 20px;border-radius:5px;margin-bottom:18px;font-size:12px;line-height:1.5;display:none}
@media(max-width:680px){.tab-btn{font-size:11px;padding:7px 10px}.form-grid{grid-template-columns:1fr}.opts-grid{grid-template-columns:1fr}.photo-grid{grid-template-columns:repeat(auto-fill,minmax(150px,1fr))}}
</style>
</head>
<body>
<div class="shell">
<div class="page-hdr">
  <a href="index.html">&#8592; KNOTWORK</a>
  <h1 class="page-title">DARKROOM</h1>
</div>
<div id="global-error" class="global-error"><strong>&#9888; API Error</strong><span id="global-error-msg"></span></div>

<div class="tabs">
  <button class="tab-btn active" onclick="switchTab('photo')">Photos</button>
  <button class="tab-btn" onclick="switchTab('support_paper')">Support Paper</button>
  <button class="tab-btn" onclick="switchTab('carbon_tissue')">Carbon Tissue</button>
  <button class="tab-btn" onclick="switchTab('negative')">Negatives</button>
  <button class="tab-btn" onclick="switchTab('options')">&#9881; Options</button>
</div>

<!-- PHOTOS -->
<div id="tab-photo" class="tab-pane">
  <div class="toolbar">
    <span class="section-label">Photo records</span>
    <button class="btn btn-accent" onclick="openPhotoForm()">+ New Photo</button>
  </div>

  <!-- PHOTO FORM (big sectioned) -->
  <div class="form-panel" id="form-photo">
    <div class="panel-header">
      <div class="panel-title" id="form-photo-title">New Photo</div>
    </div>

    <!-- ① PHOTO section -->
    <div class="fsec fsec-photo">
      <div class="fsec-label">Photo</div>
      <div class="form-grid">
        <div class="form-group span2"><label class="fld">Title</label><input type="text" id="ph-title" placeholder="e.g. Portrait Study, Landscape Test…"></div>
        <div class="form-group"><label class="fld">Photo Size</label><input type="text" id="ph-photo_size" placeholder="e.g. 8x10, 4x5"></div>
        <div class="form-group"><label class="fld">Date Sensitized</label><input type="date" id="ph-date_sensitized"></div>
        <div class="form-group"><label class="fld">Date Exposed</label><input type="date" id="ph-date_exposed"></div>
        <div class="form-group wide">
          <label class="fld">Image</label>
          <div class="img-upload-area" onclick="document.getElementById('ph-image-input').click()">
            <div id="ph-image-hint" style="color:rgba(255,255,255,.4);font-size:11px">Click to choose image (JPEG, PNG, WEBP)</div>
            <img id="ph-image-preview" class="img-preview">
            <input type="file" id="ph-image-input" accept="image/jpeg,image/png,image/webp" onchange="previewImage(this)">
          </div>
        </div>
        <div class="form-group wide"><label class="fld">Notes</label><textarea id="ph-notes" rows="2" placeholder="Results, observations…"></textarea></div>
      </div>
    </div>

    <!-- ② LAYERS -->
    <div class="fsec fsec-photo" id="ph-layers-container">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
        <div class="fsec-label" style="margin:0">Layers</div>
        <button class="btn btn-ghost btn-small" onclick="addLayer()">+ Add Layer</button>
      </div>
      <div id="ph-layers-list"></div>
    </div>

    <!-- ③ EXPOSURE section -->
    <div class="fsec fsec-exp">
      <div class="fsec-label">Exposure</div>
      <div class="form-grid">
        <div class="form-group">
          <label class="fld">Type</label>
          <div class="checkbox-row"><input type="checkbox" id="ph-test_strip" onchange="onPhTestStripChange()"><label for="ph-test_strip">Test Strip</label></div>
        </div>
        <div class="form-group wide">
          <label class="fld">Exposure Times (minutes)</label>
          <div class="exp-time-bar">
            <button class="add-row-btn" style="width:auto;padding:5px 14px" onclick="addPhTimeRow()">+ Add Interval</button>
            <button class="btn-repeat" id="ph-btn-repeat" style="display:none" onclick="repeatLastPhTime()">&#8635; Repeat Last</button>
          </div>
          <div class="multi-rows" id="ph-times-rows">
            <div class="multi-row"><input type="number" step="0.5" min="0" placeholder="minutes" class="ph-time-input"><button class="btn-icon" onclick="removeRow(this)">&#10005;</button></div>
          </div>
        </div>
      </div>
    </div>

    <!-- ④ DEVELOPMENT section -->
    <div class="fsec fsec-dev">
      <div class="fsec-label">Development</div>
      <div class="form-grid">
        <div class="form-group span2"><label class="fld">Paper Soak</label>
          <div style="display:flex;gap:8px">
            <input type="number" id="ph-paper_soak_time" step="1" min="0" placeholder="Time (min)" style="flex:1">
            <input type="number" id="ph-paper_soak_temp" step="0.5" placeholder="Temp (°C)" style="flex:1">
          </div>
        </div>
        <div class="form-group span2"><label class="fld">HOT Develop</label>
          <div style="display:flex;gap:8px">
            <input type="number" id="ph-hot_develop_time" step="1" min="0" placeholder="Time (sec)" style="flex:1">
            <input type="number" id="ph-hot_develop_temp" step="0.5" placeholder="Temp (°C)" style="flex:1">
          </div>
        </div>
        <div class="form-group span2"><label class="fld">COOL Develop</label>
          <div style="display:flex;gap:8px">
            <input type="number" id="ph-cool_develop_time" step="1" min="0" placeholder="Time (sec)" style="flex:1">
            <input type="number" id="ph-cool_develop_temp" step="0.5" placeholder="Temp (°C)" style="flex:1">
          </div>
        </div>
      </div>
    </div>

    <div class="form-footer">
      <button class="btn btn-accent" id="ph-save-btn" onclick="submitPhoto()">Save Photo</button>
      <button class="btn btn-ghost" onclick="closePhotoForm()">Cancel</button>
      <span class="form-msg" id="ph-msg"></span>
    </div>
  </div>

  <!-- Photo grid -->
  <div class="photo-grid" id="photo-grid"><div class="empty-state" style="grid-column:1/-1">Loading…</div></div>
</div>

<!-- SUPPORT PAPER -->
<div id="tab-support_paper" class="tab-pane" style="display:none">
  <div class="toolbar">
    <span class="section-label">Prepared support sheets with field marks</span>
    <button class="btn btn-accent" onclick="openForm('support_paper')">+ New Support Paper</button>
  </div>
  <div class="form-panel" id="form-support_paper">
    <div class="panel-header"><div class="panel-title" id="form-support_paper-title">New Support Paper</div></div>
    <div class="fsec fsec-photo">
      <div class="form-grid">
        <div class="form-group"><label class="fld">Base Paper *</label><select id="sp-paper_id"><option value="">-- select paper --</option></select></div>
        <div class="form-group"><label class="fld">ID / Mark</label><input type="text" id="sp-mark" placeholder="e.g. A1, B3" maxlength="20" style="text-transform:uppercase"></div>
        <div class="form-group wide"><label class="fld">Notes</label><textarea id="sp-notes" rows="2" placeholder="Sizing, preparation notes…"></textarea></div>
      </div>
    </div>
    <div class="form-footer">
      <button class="btn btn-accent" id="sp-save-btn" onclick="submitSupportPaper()">Save</button>
      <button class="btn btn-ghost" onclick="closeForm('support_paper')">Cancel</button>
      <span class="form-msg" id="sp-msg"></span>
    </div>
  </div>
  <div class="card">
    <table class="data-table" id="tbl-support_paper">
      <thead><tr><th>#</th><th>Mark</th><th>Base Paper</th><th>Weight</th><th>HP</th><th>Notes</th><th></th></tr></thead>
      <tbody><tr><td colspan="7" class="empty-state">Loading…</td></tr></tbody>
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
    <div class="panel-header"><div class="panel-title" id="form-carbon_tissue-title">New Carbon Tissue</div></div>
    <div class="fsec fsec-photo">
      <div class="form-grid">
        <div class="form-group"><label class="fld">Title / ID</label><input type="text" id="ct-title_id" placeholder="e.g. Portrait, Landscape…"></div>
        <div class="form-group"><label class="fld">Date Poured *</label><input type="date" id="ct-date_poured"></div>
        <div class="form-group"><label class="fld">Size</label><input type="text" id="ct-size" placeholder="e.g. 8x10"></div>
        <div class="form-group"><label class="fld">Chemistry Used</label><select id="ct-chemistry_id"><option value="">-- none --</option></select></div>
        <div class="form-group"><label class="fld">Amount Poured</label><input type="text" id="ct-amount_poured" placeholder="e.g. 45ml"></div>
        <div class="form-group wide"><label class="fld">Notes</label><textarea id="ct-notes" rows="2" placeholder="Batch conditions, pigment…"></textarea></div>
      </div>
    </div>
    <div class="form-footer">
      <button class="btn btn-accent" id="ct-save-btn" onclick="submitCarbonTissue()">Save</button>
      <button class="btn btn-ghost" onclick="closeForm('carbon_tissue')">Cancel</button>
      <span class="form-msg" id="ct-msg"></span>
    </div>
  </div>
  <div class="card">
    <table class="data-table" id="tbl-carbon_tissue">
      <thead><tr><th>#</th><th>Title</th><th>Date Poured</th><th>Size</th><th>Chemistry</th><th>Amount</th><th>Notes</th><th></th></tr></thead>
      <tbody><tr><td colspan="8" class="empty-state">Loading…</td></tr></tbody>
    </table>
  </div>
</div>

<!-- NEGATIVES -->
<div id="tab-negative" class="tab-pane" style="display:none">
  <div class="toolbar">
    <span class="section-label">Negatives</span>
    <button class="btn btn-accent" onclick="openForm('negative')">+ New Negative</button>
  </div>
  <div class="form-panel" id="form-negative">
    <div class="panel-header"><div class="panel-title" id="form-negative-title">New Negative</div></div>
    <div class="fsec fsec-photo">
      <div class="form-grid">
        <div class="form-group"><label class="fld">Title / ID</label><input type="text" id="n-title_id" placeholder="e.g. Portrait, Landscape…"></div>
        <div class="form-group"><label class="fld">Date Created *</label><input type="date" id="n-date_created"></div>
        <div class="form-group"><label class="fld">Type</label><select id="n-type_id"><option value="">-- select --</option></select></div>
        <div class="form-group wide"><label class="fld">Settings / Notes</label><textarea id="n-settings_notes" rows="2" placeholder="Print profile, resolution, ink…"></textarea></div>
      </div>
    </div>
    <div class="form-footer">
      <button class="btn btn-accent" id="n-save-btn" onclick="submitNegative()">Save</button>
      <button class="btn btn-ghost" onclick="closeForm('negative')">Cancel</button>
      <span class="form-msg" id="n-msg"></span>
    </div>
  </div>
  <div class="card">
    <table class="data-table" id="tbl-negative">
      <thead><tr><th>#</th><th>Title</th><th>Date</th><th>Type</th><th>Notes</th><th></th></tr></thead>
      <tbody><tr><td colspan="6" class="empty-state">Loading…</td></tr></tbody>
    </table>
  </div>
</div>

<!-- OPTIONS -->
<div id="tab-options" class="tab-pane" style="display:none">
  <div class="toolbar"><span class="section-label">Manage dropdown options &amp; recent records</span></div>
  <div class="opts-grid">
    <!-- Chemistry Types -->
    <div class="opts-sec">
      <h3>Chemistry Types</h3>
      <div class="opts-list" id="opt-chemistry_types"></div>
      <div class="opts-add">
        <input type="text" id="new-chemistry-type" placeholder="New type…" onkeydown="if(event.key==='Enter')addOption('chemistry_types')">
        <button class="btn btn-accent btn-small" onclick="addOption('chemistry_types')">Add</button>
      </div>
      <div class="opts-recents">
        <div class="opts-recents-hdr">Recent Chemistry</div>
        <div id="opts-chemistry-list"></div>
        <button class="opts-more" id="opts-chemistry-more" onclick="loadMoreChemistry()" style="display:none">Load more ↓</button>
      </div>
    </div>
    <!-- Negative Types -->
    <div class="opts-sec">
      <h3>Negative Types</h3>
      <div class="opts-list" id="opt-negative_types"></div>
      <div class="opts-add">
        <input type="text" id="new-negative-type" placeholder="New type…" onkeydown="if(event.key==='Enter')addOption('negative_types')">
        <button class="btn btn-accent btn-small" onclick="addOption('negative_types')">Add</button>
      </div>
      <div class="opts-recents">
        <div class="opts-recents-hdr">Recent Paper</div>
        <div id="opts-paper-list"></div>
        <button class="opts-more" id="opts-paper-more" onclick="loadMorePaper()" style="display:none">Load more ↓</button>
      </div>
    </div>
  </div>
</div>

<!-- PHOTO DETAIL MODAL -->
<div class="modal-bg" id="detail-modal" onclick="if(event.target===this)closeModal()">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title" id="modal-title">Photo Detail</div>
      <button class="modal-close" onclick="closeModal()">&#10005;</button>
    </div>
    <div id="modal-img-wrap"></div>
    <div class="modal-body" id="modal-body"></div>
    <div style="display:flex;gap:8px;padding:0 22px 18px">
      <button class="btn btn-edit btn-small" id="modal-edit-btn">Edit</button>
      <button class="btn btn-danger btn-small" id="modal-del-btn">Delete</button>
    </div>
  </div>
</div>

</div><!-- /shell -->
<script>
const TODAY = new Date().toISOString().split('T')[0];
const S = {
  chemistryTypes:[], negativeTypes:[], chemistry:[], paper:[],
  support_paper:[], carbon_tissue:[], negative:[], photo:[],
  chemistryPage:0, paperPage:0
};
const editId = { support_paper:null, carbon_tissue:null, negative:null, photo:null };
let layerCount = 0;

// ── API ──────────────────────────────────────────────────────
function showGlobalError(msg){ const el=document.getElementById('global-error'); document.getElementById('global-error-msg').textContent=msg; el.style.display='block'; }
async function api(res,{method='GET',id=null,body=null}={}){
  let url='darkroom-api.php?res='+res; if(id) url+='&id='+id;
  const init={method,headers:{'Content-Type':'application/json'}};
  if(body) init.body=JSON.stringify(body);
  const r=await fetch(url,init); let j;
  try{j=await r.json();}catch(e){throw new Error('Server '+r.status+' — not JSON');}
  if(!j.ok) throw new Error(j.error||'API error');
  return j.data;
}
async function uploadImage(photoId, file){
  const fd=new FormData(); fd.append('image',file);
  const r=await fetch('darkroom-api.php?res=photo_image&id='+photoId,{method:'POST',body:fd});
  const j=await r.json(); if(!j.ok) throw new Error(j.error||'Upload failed');
  return j.data.image_path;
}
function esc(s){ const d=document.createElement('div'); d.textContent=s??''; return d.innerHTML; }
function fmtYYMM(dateStr){ if(!dateStr) return ''; const p=dateStr.split('-'); return p.length>=3?p[1].slice(-2)+'.'+p[2].slice(-2):dateStr; }
function negMenuLabel(n){ const ym=n.date_created?n.date_created.slice(2).replace(/-/g,'.').slice(0,5):''; return n.title_id?(n.title_id+'.'+ym):'#'+n.id+' '+ym; }
function ctMenuLabel(ct){ const ym=ct.date_poured?ct.date_poured.slice(2).replace(/-/g,'.').slice(0,5):''; return ct.title_id?(ct.title_id+'.'+ym):'#'+ct.id+' '+ym; }

// ── Tab switching ─────────────────────────────────────────────
function switchTab(t){
  document.querySelectorAll('.tab-pane').forEach(p=>p.style.display='none');
  document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));
  document.getElementById('tab-'+t).style.display='';
  event.currentTarget.classList.add('active');
  if(t==='photo')       loadPhotos();
  if(t==='support_paper') loadSupportPaper();
  if(t==='carbon_tissue') loadCarbonTissue();
  if(t==='negative')    loadNegatives();
  if(t==='options')     renderOptions();
}

// ── Init ──────────────────────────────────────────────────────
async function init(){
  try{
    [S.chemistryTypes,S.negativeTypes,S.chemistry,S.paper,S.support_paper,S.carbon_tissue,S.negative,S.photo]=
      await Promise.all([
        api('chemistry_types'),api('negative_types'),api('chemistry'),api('paper'),
        api('support_paper'),api('carbon_tissue'),api('negative'),api('photo')
      ]);
    populateAllDropdowns();
    renderPhotos();
  }catch(ex){showGlobalError(ex.message);}
}

async function loadPhotos(){ try{S.photo=await api('photo');renderPhotos();}catch(ex){showGlobalError(ex.message);} }
async function loadSupportPaper(){ try{S.support_paper=await api('support_paper');renderSupportPaper();}catch(ex){showGlobalError(ex.message);} }
async function loadCarbonTissue(){ try{S.carbon_tissue=await api('carbon_tissue');renderCarbonTissue();}catch(ex){showGlobalError(ex.message);} }
async function loadNegatives(){ try{S.negative=await api('negative');renderNegative();}catch(ex){showGlobalError(ex.message);} }

// ── Dropdowns ─────────────────────────────────────────────────
function populateAllDropdowns(){
  populateSel('sp-paper_id', S.paper, p=>`${esc(p.manufacturer||'')} ${esc(p.label||'')}`.trim()||`#${p.id}`);
  populateSel('ct-chemistry_id', S.chemistry, c=>`#${c.id} ${esc(c.type_name||'')} ${c.date_created}`, true);
  populateSel('n-type_id', S.negativeTypes, t=>esc(t.name), true);
}
function populateSel(id, arr, labelFn, addNone=false){
  const sel=document.getElementById(id); if(!sel) return;
  const cur=sel.value;
  sel.innerHTML=(addNone?'<option value="">-- none --</option>':'<option value="">-- select --</option>');
  arr.forEach(r=>{ const o=document.createElement('option'); o.value=r.id; o.textContent=labelFn(r); sel.appendChild(o); });
  if(cur) sel.value=cur;
}
// Photo form layer dropdowns
function populateLayerDropdowns(layerId){
  populateSel('lsp-'+layerId, S.support_paper, sp=>`${esc(sp.mark)} (${esc(sp.paper_label||sp.manufacturer||'')})`, true);
  populateSel('lct-'+layerId, S.carbon_tissue, ct=>ctMenuLabel(ct), true);
  populateSel('lneg-'+layerId, S.negative, n=>negMenuLabel(n), true);
}

// ── Photo Form ────────────────────────────────────────────────
function openPhotoForm(data=null){
  editId.photo = data?data.id:null;
  document.getElementById('form-photo').classList.add('open');
  document.getElementById('form-photo-title').textContent = data?'Edit Photo':'New Photo';
  if(data?.editing) document.getElementById('form-photo').classList.add('editing');
  document.getElementById('ph-title').value = data?.title||'';
  document.getElementById('ph-photo_size').value = data?.photo_size||'';
  document.getElementById('ph-date_sensitized').value = data?.date_sensitized||TODAY;
  document.getElementById('ph-date_exposed').value = data?.date_exposed||TODAY;
  document.getElementById('ph-test_strip').checked = !!data?.test_strip;
  document.getElementById('ph-paper_soak_time').value = data?.paper_soak_time||'';
  document.getElementById('ph-paper_soak_temp').value = data?.paper_soak_temp||'';
  document.getElementById('ph-hot_develop_time').value = data?.hot_develop_time||'';
  document.getElementById('ph-hot_develop_temp').value = data?.hot_develop_temp||'';
  document.getElementById('ph-cool_develop_time').value = data?.cool_develop_time||'';
  document.getElementById('ph-cool_develop_temp').value = data?.cool_develop_temp||'';
  document.getElementById('ph-notes').value = data?.notes||'';
  onPhTestStripChange();
  // Reset image preview
  document.getElementById('ph-image-preview').style.display='none';
  document.getElementById('ph-image-hint').style.display='';
  if(data?.image_path){
    const img=document.getElementById('ph-image-preview');
    img.src=data.image_path; img.style.display='block';
    document.getElementById('ph-image-hint').style.display='none';
  }
  // Layers
  layerCount=0;
  document.getElementById('ph-layers-list').innerHTML='';
  if(data?.layers?.length){
    data.layers.forEach(l=>addLayer(l));
  } else {
    addLayer();
  }
  // Times
  const tr=document.getElementById('ph-times-rows');
  tr.innerHTML='';
  const times=(data?.times?.length)?data.times:[{duration_minutes:''}];
  times.forEach(t=>{ const row=document.createElement('div'); row.className='multi-row'; row.innerHTML=`<input type="number" step="0.5" min="0" placeholder="minutes" class="ph-time-input" value="${t.duration_minutes||''}"><button class="btn-icon" onclick="removeRow(this)">&#10005;</button>`; tr.appendChild(row); });
  document.getElementById('ph-msg').textContent='';
  document.getElementById('form-photo').scrollIntoView({behavior:'smooth',block:'start'});
}
function closePhotoForm(){
  document.getElementById('form-photo').classList.remove('open','editing');
  editId.photo=null;
}

function addLayer(data=null){
  layerCount++;
  const lid=layerCount;
  const block=document.createElement('div');
  block.className='layer-block'; block.id='layer-block-'+lid;
  block.innerHTML=`
    <div class="layer-header">Layer ${lid}
      <button class="btn-icon" onclick="removeLayer(${lid})">&#10005; Remove</button>
    </div>
    <div class="layer-body">
      <div class="layer-sub layer-sub-sp">
        <div class="layer-sub-label">Support Paper</div>
        <select id="lsp-${lid}" onchange="onLayerSpChange(${lid})"><option value="">-- none --</option></select>
        <div class="dyn-info" id="dyn-sp-${lid}"></div>
      </div>
      <div class="layer-sub layer-sub-ct">
        <div class="layer-sub-label">Carbon Tissue</div>
        <select id="lct-${lid}" onchange="onLayerCtChange(${lid})"><option value="">-- none --</option></select>
        <div class="dyn-info" id="dyn-ct-${lid}"></div>
      </div>
      <div class="layer-sub layer-sub-neg">
        <div class="layer-sub-label">Negative</div>
        <select id="lneg-${lid}" onchange="onLayerNegChange(${lid})"><option value="">-- none --</option></select>
        <div class="dyn-info" id="dyn-neg-${lid}"></div>
      </div>
    </div>`;
  document.getElementById('ph-layers-list').appendChild(block);
  populateLayerDropdowns(lid);
  if(data){
    if(data.support_paper_id) document.getElementById('lsp-'+lid).value=data.support_paper_id;
    if(data.carbon_tissue_id) document.getElementById('lct-'+lid).value=data.carbon_tissue_id;
    if(data.negative_id)      document.getElementById('lneg-'+lid).value=data.negative_id;
    if(data.support_paper_id) showDynSp(lid);
    if(data.carbon_tissue_id) showDynCt(lid);
    if(data.negative_id)      showDynNeg(lid);
  }
}
function removeLayer(lid){ const b=document.getElementById('layer-block-'+lid); if(b) b.remove(); }

// Dynamic info displays
function onLayerSpChange(lid){ showDynSp(lid); }
function onLayerCtChange(lid){ showDynCt(lid); }
function onLayerNegChange(lid){
  showDynNeg(lid);
  // auto-fill title from negative title_id
  const negId=parseInt(document.getElementById('lneg-'+lid).value);
  if(!negId) return;
  const neg=S.negative.find(n=>n.id===negId); if(!neg) return;
  const titleEl=document.getElementById('ph-title');
  if(neg.title_id && !titleEl.value){
    const ym=neg.date_created?neg.date_created.slice(2,7).replace('-','.'):'';
    titleEl.value=neg.title_id+(ym?'.'+ym:'');
  }
}
function showDynSp(lid){
  const id=parseInt(document.getElementById('lsp-'+lid).value);
  const el=document.getElementById('dyn-sp-'+lid); if(!el) return;
  if(!id){el.className='dyn-info';el.innerHTML='';return;}
  const sp=S.support_paper.find(x=>x.id===id); if(!sp){el.className='dyn-info';return;}
  el.className='dyn-info show';
  el.innerHTML=`<div class="dyn-row">${kv('Mark',sp.mark)}${kv('Paper',sp.paper_label||'')}${kv('Weight',sp.weight?sp.weight+' gsm':'')}${kv('HP',sp.hot_press?'Yes':'No')}${sp.notes?kv('Notes',sp.notes):''}</div>`;
}
function showDynCt(lid){
  const id=parseInt(document.getElementById('lct-'+lid).value);
  const el=document.getElementById('dyn-ct-'+lid); if(!el) return;
  if(!id){el.className='dyn-info';el.innerHTML='';return;}
  const ct=S.carbon_tissue.find(x=>x.id===id); if(!ct){el.className='dyn-info';return;}
  el.className='dyn-info show';
  el.innerHTML=`<div class="dyn-row">${kv('Title',ctMenuLabel(ct))}${kv('Size',ct.size||'')}${kv('Poured',ct.date_poured)}${kv('Chem',ct.chem_type||'')}${kv('Amount',ct.amount_poured||'')}${ct.notes?kv('Notes',ct.notes):''}</div>`;
}
function showDynNeg(lid){
  const id=parseInt(document.getElementById('lneg-'+lid).value);
  const el=document.getElementById('dyn-neg-'+lid); if(!el) return;
  if(!id){el.className='dyn-info';el.innerHTML='';return;}
  const n=S.negative.find(x=>x.id===id); if(!n){el.className='dyn-info';return;}
  el.className='dyn-info show';
  el.innerHTML=`<div class="dyn-row">${kv('Title',negMenuLabel(n))}${kv('Date',n.date_created)}${kv('Type',n.type_name||'')}${n.settings_notes?kv('Notes',n.settings_notes):''}</div>`;
}
function kv(k,v){ return `<div class="dyn-kv"><div class="dyn-k">${k}</div><div class="dyn-v">${esc(String(v??''))}</div></div>`; }

// Exposure times
function onPhTestStripChange(){
  document.getElementById('ph-btn-repeat').style.display=document.getElementById('ph-test_strip').checked?'':'none';
}
function addPhTimeRow(){
  const row=document.createElement('div'); row.className='multi-row';
  row.innerHTML=`<input type="number" step="0.5" min="0" placeholder="minutes" class="ph-time-input"><button class="btn-icon" onclick="removeRow(this)">&#10005;</button>`;
  document.getElementById('ph-times-rows').appendChild(row);
}
function repeatLastPhTime(){
  const inputs=document.querySelectorAll('.ph-time-input');
  const last=inputs[inputs.length-1]?.value;
  const row=document.createElement('div'); row.className='multi-row';
  row.innerHTML=`<input type="number" step="0.5" min="0" placeholder="minutes" class="ph-time-input" value="${esc(last||'')}"><button class="btn-icon" onclick="removeRow(this)">&#10005;</button>`;
  document.getElementById('ph-times-rows').appendChild(row);
}
function removeRow(btn){ btn.closest('.multi-row').remove(); }

// Image preview
function previewImage(input){
  const file=input.files[0]; if(!file) return;
  const img=document.getElementById('ph-image-preview');
  img.src=URL.createObjectURL(file); img.style.display='block';
  document.getElementById('ph-image-hint').style.display='none';
}

async function submitPhoto(){
  const msg=document.getElementById('ph-msg');
  msg.className='form-msg'; msg.textContent='Saving…';
  const layers=[];
  document.querySelectorAll('.layer-block').forEach(block=>{
    const id=block.id.replace('layer-block-','');
    const sp=parseInt(document.getElementById('lsp-'+id)?.value)||null;
    const ct=parseInt(document.getElementById('lct-'+id)?.value)||null;
    const neg=parseInt(document.getElementById('lneg-'+id)?.value)||null;
    if(sp||ct||neg) layers.push({support_paper_id:sp,carbon_tissue_id:ct,negative_id:neg});
  });
  const times=[...document.querySelectorAll('.ph-time-input')].map(i=>({duration_minutes:parseFloat(i.value)||null})).filter(t=>t.duration_minutes>0);
  const body={
    title:document.getElementById('ph-title').value||null,
    paper_id:null, // paper is per layer now via support_paper
    photo_size:document.getElementById('ph-photo_size').value||null,
    date_sensitized:document.getElementById('ph-date_sensitized').value||null,
    date_exposed:document.getElementById('ph-date_exposed').value||null,
    test_strip:document.getElementById('ph-test_strip').checked,
    paper_soak_time:parseInt(document.getElementById('ph-paper_soak_time').value)||null,
    paper_soak_temp:parseFloat(document.getElementById('ph-paper_soak_temp').value)||null,
    hot_develop_time:parseInt(document.getElementById('ph-hot_develop_time').value)||null,
    hot_develop_temp:parseFloat(document.getElementById('ph-hot_develop_temp').value)||null,
    cool_develop_time:parseInt(document.getElementById('ph-cool_develop_time').value)||null,
    cool_develop_temp:parseFloat(document.getElementById('ph-cool_develop_temp').value)||null,
    notes:document.getElementById('ph-notes').value||null,
    times, layers
  };
  try {
    let photoId;
    if(editId.photo){ await api('photo',{method:'PATCH',id:editId.photo,body}); photoId=editId.photo; }
    else { const r=await api('photo',{method:'POST',body}); photoId=r.id; }
    // Upload image if file selected
    const fileInput=document.getElementById('ph-image-input');
    if(fileInput.files[0]) await uploadImage(photoId, fileInput.files[0]);
    msg.className='form-msg ok'; msg.textContent='Saved!';
    closePhotoForm();
    await loadPhotos();
  } catch(ex){ msg.className='form-msg err'; msg.textContent=ex.message; }
}

// ── Photo Rendering ──────────────────────────────────────────
function renderPhotos(){
  const grid=document.getElementById('photo-grid');
  if(!S.photo.length){grid.innerHTML='<div class="empty-state" style="grid-column:1/-1">No photos yet — click + New Photo</div>';return;}
  grid.innerHTML=S.photo.map(p=>`
    <div class="photo-card" onclick="openDetailModal(${p.id})">
      ${p.image_path
        ?`<img class="photo-thumb" src="${esc(p.image_path)}" alt="${esc(p.title||'Photo')}" loading="lazy">`
        :`<div class="photo-thumb-placeholder">&#128247;</div>`}
      <div class="photo-card-body">
        <div class="photo-card-title">${esc(p.title||'Untitled #'+p.id)}</div>
        <div class="photo-card-meta"><span>${esc(p.date_exposed||'—')}</span><span>${esc(p.photo_size||'')}</span></div>
      </div>
    </div>`).join('');
}

// ── Detail Modal ─────────────────────────────────────────────
function openDetailModal(id){
  const p=S.photo.find(x=>x.id===id); if(!p) return;
  document.getElementById('modal-title').textContent=(p.title||'Photo #'+p.id);
  // Image
  const iw=document.getElementById('modal-img-wrap');
  iw.innerHTML=p.image_path?`<img src="${esc(p.image_path)}" class="modal-img" alt="photo">`:'';
  // Body
  const b=document.getElementById('modal-body');
  const dv=(k,v)=>`<div class="detail-kv"><div class="detail-k">${k}</div><div class="detail-v">${esc(String(v??'—'))}</div></div>`;
  const sec=(cls,label,content)=>`<div class="detail-sec detail-sec-${cls}"><div class="detail-sec-header">${label}</div><div class="detail-grid">${content}</div></div>`;
  let html='';
  html+=sec('photo','Photo',
    dv('Size',p.photo_size)+dv('Sensitized',p.date_sensitized)+dv('Exposed',p.date_exposed)+(p.notes?dv('Notes',p.notes):''));
  // Layers
  (p.layers||[]).forEach((l,i)=>{
    html+=`<div class="detail-sec" style="background:rgba(255,255,255,.03);border-radius:4px">
      <div class="detail-sec-header" style="background:rgba(255,255,255,.06);color:rgba(255,255,255,.5)">Layer ${i+1}</div>`;
    if(l.support_paper_id) html+=`<div class="detail-sec detail-sec-paper" style="margin:8px;border-radius:3px"><div class="detail-sec-header">Support Paper</div><div class="detail-grid">${dv('Mark',l.sp_mark)}${dv('Paper',l.sp_paper_label)}${l.sp_notes?dv('Notes',l.sp_notes):''}</div></div>`;
    if(l.carbon_tissue_id) html+=`<div class="detail-sec detail-sec-ct" style="margin:8px;border-radius:3px"><div class="detail-sec-header">Carbon Tissue</div><div class="detail-grid">${dv('Title',l.ct_title_id?ctMenuLabel({title_id:l.ct_title_id,date_poured:l.ct_date}):'—')}${dv('Size',l.ct_size)}${dv('Poured',l.ct_date)}${l.ct_amount?dv('Amount',l.ct_amount):''}</div></div>`;
    if(l.negative_id) html+=`<div class="detail-sec detail-sec-neg" style="margin:8px;border-radius:3px"><div class="detail-sec-header">Negative</div><div class="detail-grid">${dv('Title',l.neg_title_id?negMenuLabel({title_id:l.neg_title_id,date_created:l.neg_date}):'—')}${dv('Type',l.neg_type)}${dv('Date',l.neg_date)}${l.neg_notes?dv('Notes',l.neg_notes):''}</div></div>`;
    html+='</div>';
  });
  // Exposure times
  if(p.times?.length||p.test_strip){
    const times=(p.times||[]).map(t=>`<span class="time-chip">${t.duration_minutes}min</span>`).join(' ');
    html+=sec('exp','Exposure',dv('Test Strip',p.test_strip?'Yes':'No')+`<div class="detail-kv"><div class="detail-k">Times</div><div class="detail-v times-list">${times||'—'}</div></div>`);
  }
  // Development
  html+=sec('dev','Development',
    dv('Paper Soak',p.paper_soak_time?(p.paper_soak_time+'min'+(p.paper_soak_temp?' / '+p.paper_soak_temp+'°C':'')):'—')+
    dv('HOT Develop',p.hot_develop_time?(p.hot_develop_time+'s'+(p.hot_develop_temp?' / '+p.hot_develop_temp+'°C':'')):'—')+
    dv('COOL Develop',p.cool_develop_time?(p.cool_develop_time+'s'+(p.cool_develop_temp?' / '+p.cool_develop_temp+'°C':'')):'—'));
  b.innerHTML=html;
  document.getElementById('modal-edit-btn').onclick=()=>{closeModal();openPhotoForm({...p,editing:true});};
  document.getElementById('modal-del-btn').onclick=()=>deletePhoto(id);
  document.getElementById('detail-modal').classList.add('open');
}
function closeModal(){ document.getElementById('detail-modal').classList.remove('open'); }
async function deletePhoto(id){
  if(!confirm('Delete this photo?')) return;
  try{ await api('photo',{method:'DELETE',id}); S.photo=S.photo.filter(p=>p.id!==id); renderPhotos(); closeModal(); }
  catch(ex){ alert('Delete failed: '+ex.message); }
}

// ── Support Paper ────────────────────────────────────────────
function openForm(tab,data=null){
  document.getElementById('form-'+tab).classList.add('open','');
  if(data) document.getElementById('form-'+tab).classList.add('editing');
  else document.getElementById('form-'+tab).classList.remove('editing');
}
function closeForm(tab){ document.getElementById('form-'+tab).classList.remove('open','editing'); editId[tab]=null; }

async function submitSupportPaper(){
  const msg=document.getElementById('sp-msg'); msg.className='form-msg'; msg.textContent='Saving…';
  const pid=parseInt(document.getElementById('sp-paper_id').value);
  if(!pid){msg.className='form-msg err';msg.textContent='Select a base paper';return;}
  const body={paper_id:pid, mark:document.getElementById('sp-mark').value.toUpperCase(), notes:document.getElementById('sp-notes').value||null};
  try{
    if(editId.support_paper) await api('support_paper',{method:'PATCH',id:editId.support_paper,body});
    else await api('support_paper',{method:'POST',body});
    msg.className='form-msg ok'; msg.textContent='Saved!';
    closeForm('support_paper'); await loadSupportPaper();
  }catch(ex){msg.className='form-msg err';msg.textContent=ex.message;}
}
function renderSupportPaper(){
  const tb=document.querySelector('#tbl-support_paper tbody');
  if(!S.support_paper.length){tb.innerHTML='<tr><td colspan="7" class="empty-state">No support paper records yet</td></tr>';return;}
  tb.innerHTML=S.support_paper.map(r=>`<tr>
    <td style="color:rgba(255,255,255,.35)">${r.id}</td>
    <td><span class="badge-g" style="font-size:14px;font-weight:bold">${esc(r.mark||'--')}</span></td>
    <td>${esc(r.paper_label||'--')}</td>
    <td>${r.weight!=null?Number(r.weight).toFixed(1)+' gsm':'--'}</td>
    <td>${r.hot_press?'<span class="pill-yes">Yes</span>':'<span class="pill-no">No</span>'}</td>
    <td style="color:rgba(255,255,255,.5)">${esc(r.notes||'--')}</td>
    <td>${acts('support_paper',r.id)}</td></tr>`).join('');
}

// ── Carbon Tissue ────────────────────────────────────────────
async function submitCarbonTissue(){
  const msg=document.getElementById('ct-msg'); msg.className='form-msg'; msg.textContent='Saving…';
  if(!document.getElementById('ct-date_poured').value){msg.className='form-msg err';msg.textContent='Date required';return;}
  const body={title_id:document.getElementById('ct-title_id').value||null,date_poured:document.getElementById('ct-date_poured').value,size:document.getElementById('ct-size').value||null,chemistry_id:parseInt(document.getElementById('ct-chemistry_id').value)||null,amount_poured:document.getElementById('ct-amount_poured').value||null,notes:document.getElementById('ct-notes').value||null};
  try{
    if(editId.carbon_tissue) await api('carbon_tissue',{method:'PATCH',id:editId.carbon_tissue,body});
    else await api('carbon_tissue',{method:'POST',body});
    msg.className='form-msg ok'; msg.textContent='Saved!';
    closeForm('carbon_tissue'); S.carbon_tissue=await api('carbon_tissue'); renderCarbonTissue();
  }catch(ex){msg.className='form-msg err';msg.textContent=ex.message;}
}
function renderCarbonTissue(){
  const tb=document.querySelector('#tbl-carbon_tissue tbody');
  if(!S.carbon_tissue.length){tb.innerHTML='<tr><td colspan="8" class="empty-state">No carbon tissue records yet</td></tr>';return;}
  tb.innerHTML=S.carbon_tissue.map(r=>`<tr>
    <td style="color:rgba(255,255,255,.35)">${r.id}</td>
    <td>${r.title_id?`<span class="badge-o">${esc(ctMenuLabel(r))}</span>`:'--'}</td>
    <td>${esc(r.date_poured)}</td><td>${esc(r.size||'--')}</td>
    <td>${r.chemistry_id?`<span class="badge">#${r.chemistry_id} ${esc(r.chem_type||'')}</span>`:'--'}</td>
    <td>${esc(r.amount_poured||'--')}</td>
    <td style="color:rgba(255,255,255,.5)">${esc(r.notes||'--')}</td>
    <td>${acts('carbon_tissue',r.id)}</td></tr>`).join('');
}

// ── Negatives ─────────────────────────────────────────────────
async function submitNegative(){
  const msg=document.getElementById('n-msg'); msg.className='form-msg'; msg.textContent='Saving…';
  if(!document.getElementById('n-date_created').value){msg.className='form-msg err';msg.textContent='Date required';return;}
  const body={title_id:document.getElementById('n-title_id').value||null,date_created:document.getElementById('n-date_created').value,type_id:parseInt(document.getElementById('n-type_id').value)||null,settings_notes:document.getElementById('n-settings_notes').value||null};
  try{
    if(editId.negative) await api('negative',{method:'PATCH',id:editId.negative,body});
    else await api('negative',{method:'POST',body});
    msg.className='form-msg ok'; msg.textContent='Saved!';
    closeForm('negative'); S.negative=await api('negative'); renderNegative();
  }catch(ex){msg.className='form-msg err';msg.textContent=ex.message;}
}
function renderNegative(){
  const tb=document.querySelector('#tbl-negative tbody');
  if(!S.negative.length){tb.innerHTML='<tr><td colspan="6" class="empty-state">No negatives yet</td></tr>';return;}
  tb.innerHTML=S.negative.map(r=>`<tr>
    <td style="color:rgba(255,255,255,.35)">${r.id}</td>
    <td>${r.title_id?`<span class="badge-o">${esc(negMenuLabel(r))}</span>`:'--'}</td>
    <td>${esc(r.date_created)}</td>
    <td>${r.type_name?`<span class="badge">${esc(r.type_name)}</span>`:'--'}</td>
    <td style="color:rgba(255,255,255,.5);max-width:280px;white-space:pre-wrap">${esc(r.settings_notes||'--')}</td>
    <td>${acts('negative',r.id)}</td></tr>`).join('');
}

// ── Options Tab ───────────────────────────────────────────────
const OPTS_PAGE = 10;
function renderOptions(){
  renderOptList('chemistry_types',S.chemistryTypes);
  renderOptList('negative_types',S.negativeTypes);
  renderOptsRecent('opts-chemistry-list','opts-chemistry-more',S.chemistry,c=>`#${c.id} ${c.type_name||''} ${c.date_created}${c.percent_solution?' '+c.percent_solution+'%':''}`);
  renderOptsRecent('opts-paper-list','opts-paper-more',S.paper,p=>`#${p.id} ${p.manufacturer||''} ${p.label||''} ${p.weight?p.weight+'gsm':''}`);
}
function renderOptList(t,arr){
  const el=document.getElementById('opt-'+t); if(!el) return;
  el.innerHTML=arr.map(r=>`<div class="opt-row"><span class="opt-name">${esc(r.name)}</span><button class="btn btn-danger btn-small" onclick="deleteOption('${t}',${r.id})">Del</button></div>`).join('');
}
function renderOptsRecent(listId,moreId,arr,labelFn){
  const el=document.getElementById(listId); if(!el) return;
  const slice=arr.slice(0,OPTS_PAGE);
  el.innerHTML=slice.map(r=>`<div class="opts-item">${esc(labelFn(r))}</div>`).join('');
  const btn=document.getElementById(moreId);
  if(btn) btn.style.display=arr.length>OPTS_PAGE?'':'none';
}
function loadMoreChemistry(){
  const el=document.getElementById('opts-chemistry-list');
  el.innerHTML=S.chemistry.map(c=>`<div class="opts-item">#${c.id} ${esc(c.type_name||'')} ${c.date_created}${c.percent_solution?' '+c.percent_solution+'%':''}</div>`).join('');
  document.getElementById('opts-chemistry-more').style.display='none';
}
function loadMorePaper(){
  const el=document.getElementById('opts-paper-list');
  el.innerHTML=S.paper.map(p=>`<div class="opts-item">#${p.id} ${esc(p.manufacturer||'')} ${esc(p.label||'')} ${p.weight?p.weight+'gsm':''}</div>`).join('');
  document.getElementById('opts-paper-more').style.display='none';
}
async function addOption(t){
  const inp=document.getElementById(t==='chemistry_types'?'new-chemistry-type':'new-negative-type');
  const name=inp.value.trim(); if(!name) return;
  try{
    const r=await api(t,{method:'POST',body:{name}});
    if(t==='chemistry_types') S.chemistryTypes.push(r);
    else S.negativeTypes.push(r);
    inp.value=''; renderOptions();
  }catch(ex){alert(ex.message);}
}
async function deleteOption(t,id){
  if(!confirm('Delete?')) return;
  try{
    await api(t,{method:'DELETE',id});
    if(t==='chemistry_types') S.chemistryTypes=S.chemistryTypes.filter(x=>x.id!==id);
    else S.negativeTypes=S.negativeTypes.filter(x=>x.id!==id);
    renderOptions();
  }catch(ex){alert('Delete failed: '+ex.message);}
}

// ── Edit records ──────────────────────────────────────────────
async function editRecord(tab,id){
  let data; try{data=await api(tab,{id});}catch(ex){alert(ex.message);return;}
  editId[tab]=id;
  if(tab==='photo'){openPhotoForm({...data,editing:true});return;}
  const map={
    support_paper:()=>{
      document.getElementById('sp-paper_id').value=data.paper_id||'';
      document.getElementById('sp-mark').value=data.mark||'';
      document.getElementById('sp-notes').value=data.notes||'';
    },
    carbon_tissue:()=>{
      document.getElementById('ct-title_id').value=data.title_id||'';
      document.getElementById('ct-date_poured').value=data.date_poured||'';
      document.getElementById('ct-size').value=data.size||'';
      document.getElementById('ct-chemistry_id').value=data.chemistry_id||'';
      document.getElementById('ct-amount_poured').value=data.amount_poured||'';
      document.getElementById('ct-notes').value=data.notes||'';
    },
    negative:()=>{
      document.getElementById('n-title_id').value=data.title_id||'';
      document.getElementById('n-date_created').value=data.date_created||'';
      document.getElementById('n-type_id').value=data.type_id||'';
      document.getElementById('n-settings_notes').value=data.settings_notes||'';
    }
  };
  if(map[tab]) map[tab]();
  document.getElementById('form-'+tab+'-title').textContent='Edit '+tab.replace('_',' ');
  openForm(tab,data);
}
async function deleteRecord(tab,id,label){
  if(!confirm('Delete this '+label+'?')) return;
  try{
    await api(tab,{method:'DELETE',id});
    S[tab]=S[tab].filter(r=>r.id!==id);
    if(tab==='support_paper') renderSupportPaper();
    if(tab==='carbon_tissue') renderCarbonTissue();
    if(tab==='negative')      renderNegative();
  }catch(ex){alert('Delete failed: '+ex.message);}
}
function acts(tab,id){ return `<div class="td-actions"><button class="btn btn-edit btn-small" onclick="editRecord('${tab}',${id})">Edit</button><button class="btn btn-danger btn-small" onclick="deleteRecord('${tab}',${id},'${tab}')">Del</button></div>`; }

init();
</script>
</body>
</html>
