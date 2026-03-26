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
.opts-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:20px}
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
.opt-edit-input{background:rgba(0,0,0,.3);border:1px solid #e8e050;border-radius:3px;color:#fff;font-family:'DM Mono',monospace;font-size:12px;padding:4px 8px;flex:1}
.opt-row{position:relative;border-radius:4px;transition:background .1s}
.opt-row:hover{background:rgba(255,255,255,.04)}
.opt-actions{display:flex;gap:4px;opacity:0;transform:translateX(6px);transition:opacity .15s,transform .15s;pointer-events:none}
.opt-row:hover .opt-actions{opacity:1;transform:none;pointer-events:auto}
@media(max-width:680px){.opt-actions{opacity:1!important;transform:none!important;pointer-events:auto!important}}
.badge-pt{background:rgba(160,80,224,.12);color:#a050e0;padding:2px 7px;border-radius:3px;font-size:10px;display:inline-block;margin:1px}
.badge-layers{background:rgba(80,160,224,.12);color:#50a0e0;padding:2px 7px;border-radius:3px;font-size:10px;display:inline-block;margin:1px}
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
<div id="global-error" class="global-error"><strong>&#9888; API Error</strong><span id="global-error-msg"></span><button onclick="this.parentElement.style.display='none'" style="margin-left:12px;background:none;border:1px solid rgba(255,255,255,.5);color:#fff;border-radius:3px;padding:2px 8px;cursor:pointer;font-size:11px">dismiss</button></div>

<div class="tabs">
  <button class="tab-btn active" onclick="switchTab('photo')">Photos</button>
  <button class="tab-btn" onclick="switchTab('layers')">Layers</button>
  <button class="tab-btn" onclick="switchTab('chemistry')">Chemistry</button>
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
        <div class="form-group"><label class="fld">Process Type *</label>
          <select id="ph-photo_type_id" onchange="onPhTypeChange()">
            <option value="">— select process —</option>
          </select>
        </div>
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

    <!-- ② EXPOSURE section (always shown) -->
    <div class="fsec fsec-exp">
      <div class="fsec-label">Exposure</div>
      <div class="form-grid">
        <div class="form-group">
          <label class="fld">Test Strip</label>
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

    <!-- ③ LAYERS — shown for carbon / has_layers types -->
    <div class="fsec fsec-photo" id="ph-layers-container" style="display:none">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
        <div class="fsec-label" style="margin:0">Layers</div>
        <button class="btn btn-ghost btn-small" onclick="addLayer()">+ Add Layer</button>
      </div>
      <div id="ph-layers-list"></div>
    </div>

    <!-- ③b SIMPLE LAYER — shown for non-layered types -->
    <div class="fsec fsec-paper" id="ph-simple-layer-container" style="display:none">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
        <div class="fsec-label" style="margin:0">Support Paper / Layer</div>
        <button class="btn btn-ghost btn-small" onclick="addSimpleLayer()">+ Add Layer</button>
      </div>
      <div id="ph-simple-layers-list"></div>
    </div>

    <!-- ④ DEVELOPMENT — Carbon mode -->
    <div class="fsec fsec-dev" id="ph-dev-carbon" style="display:none">
      <div class="fsec-label">Development — Carbon</div>
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

    <!-- ④b DEVELOPMENT — Simple mode -->
    <div class="fsec fsec-dev" id="ph-dev-simple" style="display:none">
      <div class="fsec-label">Development</div>
      <div class="form-grid">
        <div class="form-group"><label class="fld">Time (sec)</label><input type="number" id="ph-develop_time" step="1" min="0" placeholder="e.g. 120"></div>
        <div class="form-group"><label class="fld">Temp (°C)</label><input type="number" id="ph-develop_temp" step="0.5" placeholder="e.g. 20"></div>
        <div class="form-group wide"><label class="fld">Notes</label><textarea id="ph-develop_notes" rows="2" placeholder="Developer dilution, observations…"></textarea></div>
      </div>
    </div>

    <!-- ⑤ FINISHING -->
    <div class="fsec fsec-photo" id="ph-finishing-container" style="display:none">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
        <div class="fsec-label" style="margin:0">Finishing</div>
        <button class="btn btn-ghost btn-small" onclick="addFinishingRow()">+ Add Step</button>
      </div>
      <div id="ph-finishing-list"></div>
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

<!-- LAYERS -->
<div id="tab-layers" class="tab-pane" style="display:none">
  <div class="opts-grid">

    <!-- ── Support Paper ─────────────────────────────── -->
    <div class="opts-sec" style="grid-column:1/-1">
      <div class="toolbar" style="margin-bottom:14px">
        <h3 style="margin:0">Support Paper</h3>
        <button class="btn btn-accent btn-small" onclick="openForm('support_paper')" style="font-size:15px;padding:4px 12px">＋ New Support Paper</button>
      </div>
      <div class="form-panel" id="form-support_paper" style="margin-bottom:14px">
        <div class="panel-header"><div class="panel-title" id="form-support_paper-title">New Support Paper</div></div>
        <div class="fsec fsec-photo">
          <div class="form-grid">
            <div class="form-group"><label class="fld">Base Paper *</label><select id="sp-paper_id"><option value="">-- select paper --</option></select></div>
            <div class="form-group"><label class="fld">ID / Mark</label><input type="text" id="sp-mark" placeholder="e.g. A1, B3" maxlength="20" style="text-transform:uppercase"></div>
            <div class="form-group"><label class="fld">Treatment Chemistry</label><select id="sp-treatment_chemistry_id"><option value="">-- none --</option></select></div>
            <div class="form-group wide"><label class="fld">Notes</label><textarea id="sp-notes" rows="2" placeholder="Sizing, preparation notes…"></textarea></div>
          </div>
        </div>
        <div class="form-footer">
          <button class="btn btn-accent" id="sp-save-btn" onclick="submitSupportPaper()">Save</button>
          <button class="btn btn-ghost" onclick="closeForm('support_paper')">Cancel</button>
          <span class="form-msg" id="sp-msg"></span>
        </div>
      </div>
      <table class="data-table" id="tbl-support_paper">
        <thead><tr><th>#</th><th>Mark</th><th>Base Paper</th><th>Weight</th><th>HP</th><th>Treatment</th><th>Notes</th><th></th></tr></thead>
        <tbody><tr><td colspan="8" class="empty-state">Loading…</td></tr></tbody>
      </table>
    </div>

    <!-- ── Carbon Tissue ─────────────────────────────── -->
    <div class="opts-sec" style="grid-column:1/-1">
      <div class="toolbar" style="margin-bottom:14px">
        <h3 style="margin:0">Carbon Tissue</h3>
        <button class="btn btn-accent btn-small" onclick="openForm('carbon_tissue')" style="font-size:15px;padding:4px 12px">＋ New Carbon Tissue</button>
      </div>
      <div class="form-panel" id="form-carbon_tissue" style="margin-bottom:14px">
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
      <table class="data-table" id="tbl-carbon_tissue">
        <thead><tr><th>#</th><th>Title</th><th>Date Poured</th><th>Size</th><th>Chemistry</th><th>Amount</th><th>Notes</th><th></th></tr></thead>
        <tbody><tr><td colspan="8" class="empty-state">Loading…</td></tr></tbody>
      </table>
    </div>

    <!-- ── Negatives ──────────────────────────────────── -->
    <div class="opts-sec" style="grid-column:1/-1">
      <div class="toolbar" style="margin-bottom:14px">
        <h3 style="margin:0">Negatives</h3>
        <button class="btn btn-accent btn-small" onclick="openForm('negative')" style="font-size:15px;padding:4px 12px">＋ New Negative</button>
      </div>
      <div class="form-panel" id="form-negative" style="margin-bottom:14px">
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
      <table class="data-table" id="tbl-negative">
        <thead><tr><th>#</th><th>Title</th><th>Date</th><th>Type</th><th>Notes</th><th></th></tr></thead>
        <tbody><tr><td colspan="6" class="empty-state">Loading…</td></tr></tbody>
      </table>
    </div>

  </div>
</div>

<!-- CHEMISTRY -->
<div id="tab-chemistry" class="tab-pane" style="display:none">
  <div class="opts-grid">
    <div class="opts-sec" style="grid-column:1/-1">
      <div class="toolbar" style="margin-bottom:14px">
        <h3 style="margin:0">Chemistry</h3>
        <button class="btn btn-accent btn-small" onclick="openForm('chemistry')" style="font-size:15px;padding:4px 12px">＋ New Chemistry</button>
      </div>
      <div class="form-panel" id="form-chemistry" style="margin-bottom:14px">
        <div class="panel-header"><div class="panel-title" id="form-chemistry-title">New Chemistry</div></div>
        <div class="fsec fsec-photo">
          <div class="form-grid">
            <div class="form-group span2"><label class="fld">Label</label><input type="text" id="chem-label" placeholder="e.g. IndiaInkSample, GelSize-A…"></div>
            <div class="form-group"><label class="fld">Date Created *</label><input type="date" id="chem-date_created"></div>
            <div class="form-group"><label class="fld">Type</label><select id="chem-type_id"><option value="">-- select --</option></select></div>
            <div class="form-group"><label class="fld">% Solution</label><input type="number" id="chem-percent_solution" step="0.1" min="0" max="100" placeholder="e.g. 10.0"></div>
            <div class="form-group"><label class="fld">Created From</label>
              <select id="chem-created_from_id"><option value="">-- none --</option></select>
            </div>
            <div class="form-group wide"><label class="fld">Notes</label><textarea id="chem-notes" rows="2" placeholder="Batch notes…"></textarea></div>
          </div>
        </div>
        <div class="form-footer">
          <button class="btn btn-accent" id="chem-save-btn" onclick="submitChemistry()">Save</button>
          <button class="btn btn-ghost" onclick="closeForm('chemistry')">Cancel</button>
          <span class="form-msg" id="chem-msg"></span>
        </div>
      </div>
      <table class="data-table" id="tbl-chemistry">
        <thead><tr><th>#</th><th>Label</th><th>Type</th><th>Date</th><th>%</th><th>Created From</th><th>Notes</th><th></th></tr></thead>
        <tbody><tr><td colspan="7" class="empty-state">Loading…</td></tr></tbody>
      </table>
      <button class="opts-more" id="opts-chemistry-more" onclick="loadMoreChemistry()" style="display:none">Load more ↓</button>
    </div>
  </div>
</div>

<!-- OPTIONS -->
<div id="tab-options" class="tab-pane" style="display:none">
  <div class="toolbar"><span class="section-label">Manage dropdown options &amp; records</span></div>
  <div class="opts-grid">

    <!-- ── Paper (full width) ────────────────────────── -->
    <div class="opts-sec" style="grid-column:1/-1">
      <div class="toolbar" style="margin-bottom:14px">
        <h3 style="margin:0">Paper</h3>
        <button class="btn btn-accent btn-small" onclick="openForm('paper')" style="font-size:15px;padding:4px 12px">＋ New Paper</button>
      </div>
      <div class="form-panel" id="form-paper" style="margin-bottom:14px">
        <div class="panel-header"><div class="panel-title" id="form-paper-title">New Paper</div></div>
        <div class="fsec fsec-photo">
          <div class="form-grid">
            <div class="form-group"><label class="fld">Manufacturer</label><input type="text" id="p-manufacturer" placeholder="e.g. Fabriano"></div>
            <div class="form-group"><label class="fld">Label / Name</label><input type="text" id="p-label" placeholder="e.g. Artistico"></div>
            <div class="form-group"><label class="fld">Weight (gsm)</label><input type="number" id="p-weight" step="0.5" min="0" placeholder="e.g. 300"></div>
            <div class="form-group"><label class="fld">Hot Press?</label>
              <div class="checkbox-row" style="padding:4px 0"><input type="checkbox" id="p-hot_press"><label for="p-hot_press">Hot Press</label></div>
            </div>
            <div class="form-group wide"><label class="fld">Notes</label><textarea id="p-notes" rows="2" placeholder="Sizing, surface notes…"></textarea></div>
          </div>
        </div>
        <div class="form-footer">
          <button class="btn btn-accent" id="p-save-btn" onclick="submitPaper()">Save</button>
          <button class="btn btn-ghost" onclick="closeForm('paper')">Cancel</button>
          <span class="form-msg" id="p-msg"></span>
        </div>
      </div>
      <table class="data-table" id="tbl-paper">
        <thead><tr><th>#</th><th>Manufacturer</th><th>Label</th><th>Weight</th><th>HP</th><th>Notes</th><th></th></tr></thead>
        <tbody><tr><td colspan="7" class="empty-state">Loading…</td></tr></tbody>
      </table>
      <button class="opts-more" id="opts-paper-more" onclick="loadMorePaper()" style="display:none">Load more ↓</button>
    </div>

    <!-- ── Negative Types (top-left) ─────────────────── -->
    <div class="opts-sec">
      <div class="toolbar" style="margin-bottom:14px">
        <h3 style="margin:0">Negative Types</h3>
        <button class="btn btn-accent btn-small" onclick="focusNewOptInput('negative_types')" title="Add negative type" style="font-size:16px;padding:4px 10px">＋</button>
      </div>
      <div class="opts-list" id="opt-negative_types"></div>
      <div class="opts-add" style="margin-top:10px">
        <input type="text" id="new-negative-type" placeholder="New type name…" onkeydown="if(event.key==='Enter')addOption('negative_types')">
        <button class="btn btn-accent btn-small" onclick="addOption('negative_types')">Add</button>
      </div>
    </div>

    <!-- ── Chemistry Types (top-right) ──────────────── -->
    <div class="opts-sec">
      <div class="toolbar" style="margin-bottom:14px">
        <h3 style="margin:0">Chemistry Types</h3>
        <button class="btn btn-accent btn-small" onclick="focusNewOptInput('chemistry_types')" title="Add chemistry type" style="font-size:16px;padding:4px 10px">＋</button>
      </div>
      <div class="opts-list" id="opt-chemistry_types"></div>
      <div class="opts-add" style="margin-top:10px">
        <input type="text" id="new-chemistry-type" placeholder="New type name…" onkeydown="if(event.key==='Enter')addOption('chemistry_types')">
        <button class="btn btn-accent btn-small" onclick="addOption('chemistry_types')">Add</button>
      </div>
    </div>

    <!-- ── Process Types (bottom-left) ──────────────── -->
    <div class="opts-sec">
      <div class="toolbar" style="margin-bottom:14px">
        <h3 style="margin:0">Process Types</h3>
        <button class="btn btn-accent btn-small" onclick="focusNewOptInput('photo_types')" title="Add process type" style="font-size:16px;padding:4px 10px">＋</button>
      </div>
      <div class="opts-list" id="opt-photo_types"></div>
      <div class="opts-add" style="margin-top:10px;display:grid;grid-template-columns:1fr auto auto auto;gap:8px;align-items:center">
        <input type="text" id="new-photo-type" placeholder="New process name…" onkeydown="if(event.key==='Enter')addOption('photo_types')">
        <select id="new-pt-dev_mode" style="background:rgba(0,0,0,.3);border:1px solid rgba(255,255,255,.15);border-radius:3px;color:#fff;font-family:'DM Mono',monospace;font-size:11px;padding:4px 6px">
          <option value="simple">Simple</option>
          <option value="carbon">Carbon</option>
        </select>
        <div class="checkbox-row" style="padding:0;white-space:nowrap">
          <input type="checkbox" id="new-pt-has_layers"><label for="new-pt-has_layers" style="font-size:11px">Layers</label>
        </div>
        <button class="btn btn-accent btn-small" onclick="addOption('photo_types')">Add</button>
      </div>
    </div>

    <!-- ── Finishing Types (bottom-right) ───────────── -->
    <div class="opts-sec">
      <div class="toolbar" style="margin-bottom:14px">
        <h3 style="margin:0">Finishing Types</h3>
        <button class="btn btn-accent btn-small" onclick="focusNewOptInput('finishing_types')" title="Add finishing type" style="font-size:16px;padding:4px 10px">＋</button>
      </div>
      <div class="opts-list" id="opt-finishing_types"></div>
      <div class="opts-add" style="margin-top:10px">
        <input type="text" id="new-finishing-type" placeholder="New type name…" onkeydown="if(event.key==='Enter')addOption('finishing_types')">
        <button class="btn btn-accent btn-small" onclick="addOption('finishing_types')">Add</button>
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
  photoTypes:[], chemistryTypes:[], negativeTypes:[], finishingTypes:[], chemistry:[], paper:[],
  support_paper:[], carbon_tissue:[], negative:[], photo:[],
  chemistryShowAll:false, paperShowAll:false
};
const editId = { photo_types:null, support_paper:null, carbon_tissue:null, negative:null, photo:null, chemistry:null, paper:null };
let layerCount = 0;

// ── API ──────────────────────────────────────────────────────
function showGlobalError(msg,res=''){
  const el=document.getElementById('global-error');
  const label = res ? ` [${res}] ` : ' ';
  document.getElementById('global-error-msg').textContent = label+msg;
  el.style.display='block';
}
async function api(res,{method='GET',id=null,body=null,timeout=12000}={}){
  let url='darkroom-api.php?res='+res; if(id) url+='&id='+id;
  const ctrl=new AbortController();
  const timer=setTimeout(()=>ctrl.abort(),timeout);
  const init={method,headers:{'Content-Type':'application/json'},signal:ctrl.signal};
  if(body) init.body=JSON.stringify(body);
  let r;
  try{ r=await fetch(url,init); }
  catch(e){ clearTimeout(timer); throw new Error(e.name==='AbortError'?`Request timed out (${res})`:e.message); }
  clearTimeout(timer);
  let j;
  try{j=await r.json();}catch(e){throw new Error('Server '+r.status+' — not JSON');}
  if(!j.ok) throw new Error(j.error||'API error');
  return j.data;
}
async function uploadImage(photoId, file){
  const fd=new FormData(); fd.append('image',file);
  const r=await fetch('darkroom-api.php?res=photo_image&id='+photoId,{method:'POST',body:fd});
  const j=await r.json(); if(!j.ok) throw new Error(j.error||'Upload failed');
  // Update local photo state so grid re-renders without a full reload
  const p=S.photo.find(x=>x.id===photoId);
  if(p){ p.image_path=j.data.image_path; p.thumb_path=j.data.thumb_path; }
  return j.data.image_path;
}
function esc(s){ const d=document.createElement('div'); d.textContent=s??''; return d.innerHTML; }
function fmtYYMM(dateStr){ if(!dateStr) return ''; const p=dateStr.split('-'); return p.length>=3?p[1].slice(-2)+'.'+p[2].slice(-2):dateStr; }
function negMenuLabel(n){ const ym=n.date_created?n.date_created.slice(2).replace(/-/g,'.').slice(0,5):''; return n.title_id?(n.title_id+'.'+ym):'#'+n.id+' '+ym; }
function ctMenuLabel(ct){ const ym=ct.date_poured?ct.date_poured.slice(2).replace(/-/g,'.').slice(0,5):''; return ct.title_id?(ct.title_id+'.'+ym):'#'+ct.id+' '+ym; }
function chemLabel(c){
  if(!c) return '';
  const type = c.type_name||'Chemistry';
  if(c.label) return `${c.label} (${type})`;
  // Fallback for legacy records without a label
  const d = c.date_created ? new Date(c.date_created+'T12:00:00') : null;
  const mon = d ? d.toLocaleString('en-CA',{month:'short'}) : '';
  const day = d ? d.getDate() : '';
  return `${type} ${mon} ${day}`.trim();
}

// ── Tab switching ─────────────────────────────────────────────
function switchTab(t){
  document.querySelectorAll('.tab-pane').forEach(p=>p.style.display='none');
  document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));
  document.getElementById('tab-'+t).style.display='';
  event.currentTarget.classList.add('active');
  if(t==='photo')     loadPhotos();
  if(t==='layers')    loadLayers();
  if(t==='chemistry') loadChemistryTab();
  if(t==='options')   loadOptions();
}

// ── Init ──────────────────────────────────────────────────────
async function init(){
  try{
    // Only load what the Photos tab needs on first render
    [S.photoTypes,S.chemistryTypes,S.negativeTypes,S.photo]=
      await Promise.all([
        api('photo_types'),api('chemistry_types'),api('negative_types'),api('photo')
      ]);
    populateSel('ph-photo_type_id', S.photoTypes, pt=>esc(pt.name), false);
    populateSel('n-type_id', S.negativeTypes, t=>esc(t.name), true);
    renderPhotos();
    // Defer secondary data
    api('chemistry').then(d=>{ S.chemistry=d; populateChemSelects(); }).catch(ex=>showGlobalError(ex.message,'chemistry'));
    api('paper').then(d=>{ S.paper=d; }).catch(ex=>showGlobalError(ex.message,'paper'));
    api('carbon_tissue').then(d=>{ S.carbon_tissue=d; }).catch(ex=>showGlobalError(ex.message,'carbon_tissue'));
    api('negative').then(d=>{ S.negative=d; }).catch(ex=>showGlobalError(ex.message,'negative'));
  }catch(ex){showGlobalError(ex.message);}
}

async function loadLayers(){
  try{
    [S.carbon_tissue,S.negative] = await Promise.all([api('carbon_tissue'),api('negative')]);
    S.support_paper = await api('support_paper');
    populateSel('sp-paper_id', S.paper, p=>`${esc(p.manufacturer||'')} ${esc(p.label||'')}`.trim()||`#${p.id}`);
    populateSel('sp-treatment_chemistry_id', S.chemistry, c=>esc(chemLabel(c)), true);
    populateLayerDropdownsAll();
    renderSupportPaper();
    renderCarbonTissue();
    renderNegative();
  }catch(ex){showGlobalError(ex.message,'layers');}
}

async function loadChemistryTab(){
  try{
    [S.chemistry,S.chemistryTypes] = await Promise.all([api('chemistry'),api('chemistry_types')]);
    populateSel('chem-type_id', S.chemistryTypes, t=>esc(t.name), true);
    populateChemSelects();
    renderChemistry();
  }catch(ex){showGlobalError(ex.message,'chemistry');}
}

async function loadOptions(){
  try{
    // Fetch paper and photo_types (not yet loaded); chemistry/negative types already in S from init
    const [pt, p] = await Promise.all([api('photo_types'), api('paper')]);
    S.photoTypes = pt; S.paper = p;
    // Render immediately with what we have — finishing_types may still be loading
    renderOptions();
    // Fetch finishing_types independently so a failure doesn't block the rest
    if(!S.finishingTypes.length){
      api('finishing_types')
        .then(d=>{ S.finishingTypes=d; renderOptList('finishing_types', S.finishingTypes); })
        .catch(ex=>showGlobalError(ex.message,'finishing_types'));
    }
  }catch(ex){showGlobalError(ex.message,'options');}
}

async function loadPhotos(){ try{S.photo=await api('photo');renderPhotos();}catch(ex){showGlobalError(ex.message,'photo');} }
async function loadSupportPaper(){ try{S.support_paper=await api('support_paper');renderSupportPaper();populateLayerDropdownsAll();}catch(ex){showGlobalError(ex.message,'support_paper');} }
async function loadCarbonTissue(){ try{S.carbon_tissue=await api('carbon_tissue');renderCarbonTissue();}catch(ex){showGlobalError(ex.message,'carbon_tissue');} }
async function loadNegatives(){ try{S.negative=await api('negative');renderNegative();}catch(ex){showGlobalError(ex.message,'negative');} }

// ── Dropdowns ─────────────────────────────────────────────────
function populateSel(id, arr, labelFn, addNone=false){
  const sel=document.getElementById(id); if(!sel) return;
  const cur=sel.value;
  sel.innerHTML=(addNone?'<option value="">-- none --</option>':'<option value="">-- select --</option>');
  arr.forEach(r=>{ const o=document.createElement('option'); o.value=r.id; o.textContent=labelFn(r); sel.appendChild(o); });
  if(cur) sel.value=cur;
}
function populateChemSelects(){
  // All chemistry dropdowns throughout the form
  ['chem-created_from_id','sp-treatment_chemistry_id','ct-chemistry_id'].forEach(id=>{
    populateSel(id, S.chemistry, c=>esc(chemLabel(c)), true);
  });
}
// Photo form layer dropdowns
function populateLayerDropdowns(layerId){
  populateSel('lsp-'+layerId, S.support_paper, sp=>`${esc(sp.mark)} (${esc(sp.paper_label||sp.manufacturer||'')})`, true);
  populateSel('lct-'+layerId, S.carbon_tissue, ct=>ctMenuLabel(ct), true);
  populateSel('lneg-'+layerId, S.negative, n=>negMenuLabel(n), true);
}
// Refresh support_paper into all already-rendered layer selects (called after lazy-load)
function populateLayerDropdownsAll(){
  document.querySelectorAll('[id^="lsp-"]').forEach(sel=>{
    const lid=sel.id.replace('lsp-',''); const cur=sel.value;
    populateSel('lsp-'+lid, S.support_paper, sp=>`${esc(sp.mark)} (${esc(sp.paper_label||sp.manufacturer||'')})`, true);
    if(cur) sel.value=cur;
  });
  document.querySelectorAll('[id^="slsp-"]').forEach(sel=>{
    const lid=sel.id.replace('slsp-',''); const cur=sel.value;
    populateSel('slsp-'+lid, S.support_paper, sp=>`${esc(sp.mark)} (${esc(sp.paper_label||sp.manufacturer||'')})`, true);
    if(cur) sel.value=cur;
  });
}

// ── Photo Form ────────────────────────────────────────────────
async function openPhotoForm(data=null){
  // Ensure all data needed by the photo form is loaded (deferred from init)
  const toLoad=[];
  if(!S.support_paper.length) toLoad.push(api('support_paper').then(d=>S.support_paper=d));
  if(!S.chemistry.length)     toLoad.push(api('chemistry').then(d=>{ S.chemistry=d; populateChemSelects(); }));
  if(!S.carbon_tissue.length) toLoad.push(api('carbon_tissue').then(d=>S.carbon_tissue=d));
  if(!S.negative.length)      toLoad.push(api('negative').then(d=>S.negative=d));
  if(!S.paper.length)         toLoad.push(api('paper').then(d=>S.paper=d));
  if(toLoad.length){
    try{ await Promise.all(toLoad); }catch(ex){ showGlobalError(ex.message); return; }
  }
  editId.photo = data?data.id:null;
  document.getElementById('form-photo').classList.add('open');
  document.getElementById('form-photo-title').textContent = data?'Edit Photo':'New Photo';
  if(data?.editing) document.getElementById('form-photo').classList.add('editing');
  else document.getElementById('form-photo').classList.remove('editing');

  // Basic fields
  document.getElementById('ph-title').value = data?.title||'';
  document.getElementById('ph-photo_size').value = data?.photo_size||'';
  document.getElementById('ph-date_sensitized').value = data?.date_sensitized||TODAY;
  document.getElementById('ph-date_exposed').value = data?.date_exposed||TODAY;
  document.getElementById('ph-notes').value = data?.notes||'';
  document.getElementById('ph-test_strip').checked = !!data?.test_strip;

  // Photo type
  populateSel('ph-photo_type_id', S.photoTypes, pt=>esc(pt.name), false);
  document.getElementById('ph-photo_type_id').value = data?.photo_type_id||'';

  // Dev fields
  document.getElementById('ph-paper_soak_time').value = data?.paper_soak_time||'';
  document.getElementById('ph-paper_soak_temp').value = data?.paper_soak_temp||'';
  document.getElementById('ph-hot_develop_time').value = data?.hot_develop_time||'';
  document.getElementById('ph-hot_develop_temp').value = data?.hot_develop_temp||'';
  document.getElementById('ph-cool_develop_time').value = data?.cool_develop_time||'';
  document.getElementById('ph-cool_develop_temp').value = data?.cool_develop_temp||'';
  document.getElementById('ph-develop_time').value = data?.develop_time||'';
  document.getElementById('ph-develop_temp').value = data?.develop_temp||'';
  document.getElementById('ph-develop_notes').value = data?.develop_notes||'';

  // Image preview
  document.getElementById('ph-image-preview').style.display='none';
  document.getElementById('ph-image-hint').style.display='';
  if(data?.image_path){
    const img=document.getElementById('ph-image-preview');
    img.src=data.image_path; img.style.display='block';
    document.getElementById('ph-image-hint').style.display='none';
  }

  // Trigger section visibility based on type
  onPhTypeChange();
  onPhTestStripChange();

  // Exposure times
  const tr=document.getElementById('ph-times-rows');
  tr.innerHTML='';
  const times=(data?.times?.length)?data.times:[{duration_minutes:''}];
  times.forEach(t=>{ const row=document.createElement('div'); row.className='multi-row'; row.innerHTML=`<input type="number" step="0.5" min="0" placeholder="minutes" class="ph-time-input" value="${t.duration_minutes||''}"><button class="btn-icon" onclick="removeRow(this)">&#10005;</button>`; tr.appendChild(row); });

  // Multi-layer list (carbon/has_layers types)
  layerCount=0;
  document.getElementById('ph-layers-list').innerHTML='';
  if(data?.layers?.length){
    data.layers.forEach(l=>addLayer(l));
  } else if(!data) {
    addLayer();
  }

  // Simple layers (non-layered types)
  simpleLayerCount=0;
  document.getElementById('ph-simple-layers-list').innerHTML='';
  if(data?.layers?.length){
    data.layers.forEach(l=>addSimpleLayer(l));
  } else {
    addSimpleLayer();
  }

  // Finishing steps
  finishingCount=0;
  document.getElementById('ph-finishing-list').innerHTML='';
  if(data?.finishing?.length){
    data.finishing.forEach(f=>addFinishingRow(f.label||'', f.chemistry_id||'', f.step_time||'', f.step_temp||'', f.notes||''));
  }

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

// Photo type change → show/hide sections
function onPhTypeChange(){
  const tid = parseInt(document.getElementById('ph-photo_type_id').value)||null;
  const pt = tid ? S.photoTypes.find(t=>t.id===tid) : null;
  const hasLayers = pt ? !!pt.has_layers : false;
  const isCarbon  = pt ? (pt.dev_mode==='carbon') : false;
  const hasType   = !!pt;
  document.getElementById('ph-layers-container').style.display      = hasLayers ? '' : 'none';
  document.getElementById('ph-simple-layer-container').style.display = (!hasLayers && hasType) ? '' : 'none';
  document.getElementById('ph-dev-carbon').style.display             = isCarbon  ? '' : 'none';
  document.getElementById('ph-dev-simple').style.display             = (!isCarbon && hasType) ? '' : 'none';
  document.getElementById('ph-finishing-container').style.display    = hasType   ? '' : 'none';
}

// Simple single-layer adder (non-carbon types)
let simpleLayerCount = 0;
function addSimpleLayer(data=null){
  simpleLayerCount++;
  const lid = 'sl'+simpleLayerCount;
  const row = document.createElement('div');
  row.className = 'layer-block'; row.id = 'simple-layer-block-'+lid;
  row.innerHTML = `
    <div class="layer-header">Layer ${simpleLayerCount}
      <button class="btn-icon" onclick="this.closest('.layer-block').remove()">&#10005; Remove</button>
    </div>
    <div class="layer-body">
      <div class="layer-sub layer-sub-sp">
        <div class="layer-sub-label">Support Paper</div>
        <select id="slsp-${lid}" onchange="showSimpleDynSp('${lid}')">
          <option value="">-- none --</option>
        </select>
        <div class="dyn-info" id="dyn-slsp-${lid}"></div>
      </div>
      <div class="layer-sub layer-sub-neg">
        <div class="layer-sub-label">Negative</div>
        <select id="slneg-${lid}"><option value="">-- none --</option></select>
      </div>
    </div>`;
  document.getElementById('ph-simple-layers-list').appendChild(row);
  populateSel('slsp-'+lid, S.support_paper, sp=>`${esc(sp.mark)} (${esc(sp.paper_label||sp.manufacturer||'')})`, true);
  populateSel('slneg-'+lid, S.negative, n=>negMenuLabel(n), true);
  if(data?.support_paper_id){ document.getElementById('slsp-'+lid).value=data.support_paper_id; showSimpleDynSp(lid); }
  if(data?.negative_id) document.getElementById('slneg-'+lid).value=data.negative_id;
}
function showSimpleDynSp(lid){
  const id=parseInt(document.getElementById('slsp-'+lid).value);
  const el=document.getElementById('dyn-slsp-'+lid); if(!el) return;
  if(!id){el.className='dyn-info';el.innerHTML='';return;}
  const sp=S.support_paper.find(x=>x.id===id); if(!sp){el.className='dyn-info';return;}
  el.className='dyn-info show';
  el.innerHTML=`<div class="dyn-row">${kv('Mark',sp.mark)}${kv('Paper',sp.paper_label||'')}${kv('Weight',sp.weight?sp.weight+' gsm':'')}${kv('HP',sp.hot_press?'Yes':'No')}</div>`;
}

// Finishing step adder
let finishingCount = 0;
const FINISHING_PRESETS = ['Clearing Bath','Fixative','Toner','Bleach','Stop Bath','Wash','Hypo Clear'];
function addFinishingRow(label='', chemId='', time='', temp='', notes=''){
  finishingCount++;
  const fid = finishingCount;
  const container = document.getElementById('ph-finishing-list');
  const row = document.createElement('div');
  row.className = 'layer-block'; row.id = 'finishing-block-'+fid;
  const presetHtml = FINISHING_PRESETS.map(p=>`<button type="button" class="btn btn-ghost btn-small" style="font-size:10px;padding:3px 8px" onclick="document.getElementById('flabel-${fid}').value='${p}'">${p}</button>`).join('');
  row.innerHTML = `
    <div class="layer-header">Finishing Step ${fid}
      <button class="btn-icon" onclick="this.closest('.layer-block').remove()">&#10005; Remove</button>
    </div>
    <div class="layer-body">
      <div style="display:flex;gap:5px;flex-wrap:wrap;margin-bottom:8px">${presetHtml}</div>
      <div class="form-grid">
        <div class="form-group span2"><label class="fld">Label</label><input type="text" id="flabel-${fid}" value="${esc(label)}" placeholder="e.g. Clearing Bath, Fixative…"></div>
        <div class="form-group"><label class="fld">Chemistry</label>
          <select id="fchem-${fid}"><option value="">-- none --</option></select>
        </div>
        <div class="form-group"><label class="fld">Time (sec)</label><input type="number" id="ftime-${fid}" value="${esc(time)}" placeholder="sec" min="0"></div>
        <div class="form-group"><label class="fld">Temp (°C)</label><input type="number" id="ftemp-${fid}" value="${esc(temp)}" step="0.5" placeholder="°C"></div>
        <div class="form-group wide"><label class="fld">Notes</label><textarea id="fnotes-${fid}" rows="2" placeholder="Notes…">${esc(notes)}</textarea></div>
      </div>
    </div>`;
  container.appendChild(row);
  populateSel('fchem-'+fid, S.chemistry, c=>esc(chemLabel(c)), true);
  if(chemId) document.getElementById('fchem-'+fid).value = chemId;
}

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

  const photoTypeId = parseInt(document.getElementById('ph-photo_type_id').value)||null;
  const pt = photoTypeId ? S.photoTypes.find(t=>t.id===photoTypeId) : null;
  const hasLayers = pt ? !!pt.has_layers : false;

  // Collect layers (multi-layer or simple)
  const layers=[];
  if(hasLayers){
    document.querySelectorAll('#ph-layers-list .layer-block').forEach(block=>{
      const id=block.id.replace('layer-block-','');
      const sp=parseInt(document.getElementById('lsp-'+id)?.value)||null;
      const ct=parseInt(document.getElementById('lct-'+id)?.value)||null;
      const neg=parseInt(document.getElementById('lneg-'+id)?.value)||null;
      if(sp||ct||neg) layers.push({support_paper_id:sp,carbon_tissue_id:ct,negative_id:neg});
    });
  } else {
    document.querySelectorAll('#ph-simple-layers-list .layer-block').forEach(block=>{
      const lid=block.id.replace('simple-layer-block-','');
      const sp=parseInt(document.getElementById('slsp-'+lid)?.value)||null;
      const neg=parseInt(document.getElementById('slneg-'+lid)?.value)||null;
      if(sp||neg) layers.push({support_paper_id:sp,carbon_tissue_id:null,negative_id:neg});
    });
  }

  // Collect finishing steps
  const finishing=[];
  document.querySelectorAll('#ph-finishing-list .layer-block').forEach(block=>{
    const fid=block.id.replace('finishing-block-','');
    finishing.push({
      label:     document.getElementById('flabel-'+fid)?.value||null,
      chemistry_id: parseInt(document.getElementById('fchem-'+fid)?.value)||null,
      step_time: parseInt(document.getElementById('ftime-'+fid)?.value)||null,
      step_temp: parseFloat(document.getElementById('ftemp-'+fid)?.value)||null,
      notes:     document.getElementById('fnotes-'+fid)?.value||null,
    });
  });

  const times=[...document.querySelectorAll('.ph-time-input')].map(i=>({duration_minutes:parseFloat(i.value)||null})).filter(t=>t.duration_minutes>0);
  const isCarbon = pt?.dev_mode==='carbon';

  const body={
    photo_type_id: photoTypeId,
    title:         document.getElementById('ph-title').value||null,
    paper_id:      null,
    photo_size:    document.getElementById('ph-photo_size').value||null,
    date_sensitized: document.getElementById('ph-date_sensitized').value||null,
    date_exposed:    document.getElementById('ph-date_exposed').value||null,
    test_strip:    document.getElementById('ph-test_strip').checked,
    paper_soak_time:  isCarbon ? (parseInt(document.getElementById('ph-paper_soak_time').value)||null)  : null,
    paper_soak_temp:  isCarbon ? (parseFloat(document.getElementById('ph-paper_soak_temp').value)||null) : null,
    hot_develop_time: isCarbon ? (parseInt(document.getElementById('ph-hot_develop_time').value)||null)  : null,
    hot_develop_temp: isCarbon ? (parseFloat(document.getElementById('ph-hot_develop_temp').value)||null): null,
    cool_develop_time:isCarbon ? (parseInt(document.getElementById('ph-cool_develop_time').value)||null) : null,
    cool_develop_temp:isCarbon ? (parseFloat(document.getElementById('ph-cool_develop_temp').value)||null):null,
    develop_time:  !isCarbon ? (parseInt(document.getElementById('ph-develop_time').value)||null)  : null,
    develop_temp:  !isCarbon ? (parseFloat(document.getElementById('ph-develop_temp').value)||null) : null,
    develop_notes: !isCarbon ? (document.getElementById('ph-develop_notes').value||null) : null,
    notes:         document.getElementById('ph-notes').value||null,
    times, layers, finishing
  };
  try {
    let photoId;
    if(editId.photo){ await api('photo',{method:'PATCH',id:editId.photo,body}); photoId=editId.photo; }
    else { const r=await api('photo',{method:'POST',body}); photoId=r.id; }
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
      ${p.thumb_path
        ?`<img class="photo-thumb" src="${esc(p.thumb_path)}" alt="${esc(p.title||'Photo')}" loading="lazy">`
        :p.image_path
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
  // Clear form fields when opening fresh (not editing)
  if(!data && tab==='photo_types'){
    document.getElementById('pt-name').value='';
    document.getElementById('pt-dev_mode').value='simple';
    document.getElementById('pt-has_layers').checked=false;
    document.getElementById('pt-msg').textContent='';
    document.getElementById('form-photo_types-title').textContent='New Process Type';
  }
  if(!data && tab==='chemistry'){
    document.getElementById('chem-label').value='';
    document.getElementById('chem-date_created').value=TODAY;
    document.getElementById('chem-type_id').value='';
    document.getElementById('chem-percent_solution').value='';
    document.getElementById('chem-created_from_id').value='';
    document.getElementById('chem-notes').value='';
    document.getElementById('chem-msg').textContent='';
    document.getElementById('form-chemistry-title').textContent='New Chemistry';
  }
  if(!data && tab==='paper'){
    document.getElementById('p-manufacturer').value='';
    document.getElementById('p-label').value='';
    document.getElementById('p-weight').value='';
    document.getElementById('p-hot_press').checked=false;
    document.getElementById('p-notes').value='';
    document.getElementById('p-msg').textContent='';
    document.getElementById('form-paper-title').textContent='New Paper';
  }
  if(!data && tab==='support_paper'){
    document.getElementById('sp-paper_id').value='';
    document.getElementById('sp-mark').value='';
    document.getElementById('sp-treatment_chemistry_id').value='';
    document.getElementById('sp-notes').value='';
    document.getElementById('sp-msg').textContent='';
    document.getElementById('form-support_paper-title').textContent='New Support Paper';
  }
  document.getElementById('form-'+tab).classList.add('open');
  if(data) document.getElementById('form-'+tab).classList.add('editing');
  else document.getElementById('form-'+tab).classList.remove('editing');
  document.getElementById('form-'+tab).scrollIntoView({behavior:'smooth',block:'start'});
}
function closeForm(tab){ document.getElementById('form-'+tab).classList.remove('open','editing'); editId[tab]=null; }

async function submitSupportPaper(){
  const msg=document.getElementById('sp-msg'); msg.className='form-msg'; msg.textContent='Saving…';
  const pid=parseInt(document.getElementById('sp-paper_id').value);
  if(!pid){msg.className='form-msg err';msg.textContent='Select a base paper';return;}
  const body={paper_id:pid, mark:document.getElementById('sp-mark').value.toUpperCase(), treatment_chemistry_id:parseInt(document.getElementById('sp-treatment_chemistry_id').value)||null, notes:document.getElementById('sp-notes').value||null};
  try{
    if(editId.support_paper) await api('support_paper',{method:'PATCH',id:editId.support_paper,body});
    else await api('support_paper',{method:'POST',body});
    msg.className='form-msg ok'; msg.textContent='Saved!';
    closeForm('support_paper'); await loadSupportPaper();
  }catch(ex){msg.className='form-msg err';msg.textContent=ex.message;}
}
function renderSupportPaper(){
  const tb=document.querySelector('#tbl-support_paper tbody');
  if(!S.support_paper.length){tb.innerHTML='<tr><td colspan="8" class="empty-state">No support paper records yet</td></tr>';return;}
  tb.innerHTML=S.support_paper.map(r=>`<tr>
    <td style="color:rgba(255,255,255,.35)">${r.id}</td>
    <td><span class="badge-g" style="font-size:14px;font-weight:bold">${esc(r.mark||'--')}</span></td>
    <td>${esc(r.paper_label||'--')}</td>
    <td>${r.weight!=null?Number(r.weight).toFixed(1)+' gsm':'--'}</td>
    <td>${r.hot_press?'<span class="pill-yes">Yes</span>':'<span class="pill-no">No</span>'}</td>
    <td>${r.treatment_label?`<span class="badge">${esc(r.treatment_label)}</span>`:'--'}</td>
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

// ── Photo Types ───────────────────────────────────────────────
function renderPhotoTypesList(){
  const el=document.getElementById('opt-photo_types'); if(!el) return;
  if(!S.photoTypes.length){ el.innerHTML='<div style="color:rgba(255,255,255,.3);font-size:11px;padding:6px 0">No process types yet</div>'; return; }
  el.innerHTML=S.photoTypes.map(r=>`
    <div class="opt-row" id="opt-row-photo_types-${r.id}">
      <span class="opt-name">${esc(r.name)}
        <span class="badge-pt" style="margin-left:6px">${r.dev_mode==='carbon'?'Carbon':'Simple'}</span>
        ${r.has_layers?'<span class="badge-layers">Layers</span>':''}
      </span>
      <div class="opt-actions">
        <button class="btn btn-edit btn-small" onclick="startEditProcessType(${r.id},'${esc(r.name).replace(/'/g,"\\'")}','${r.dev_mode}',${r.has_layers?1:0})">Edit</button>
        <button class="btn btn-danger btn-small" onclick="deleteOption('photo_types',${r.id})">Del</button>
      </div>
    </div>`).join('');
}
function startEditProcessType(id, name, devMode, hasLayers){
  const row=document.getElementById(`opt-row-photo_types-${id}`); if(!row) return;
  row.innerHTML=`
    <div style="display:grid;grid-template-columns:1fr auto auto auto auto;gap:7px;align-items:center;width:100%">
      <input type="text" class="opt-edit-input" id="opt-edit-pt-name-${id}" value="${esc(name)}">
      <select id="opt-edit-pt-dev-${id}" style="background:rgba(0,0,0,.3);border:1px solid rgba(255,255,255,.15);border-radius:3px;color:#fff;font-family:'DM Mono',monospace;font-size:11px;padding:4px 6px">
        <option value="simple"${devMode==='simple'?' selected':''}>Simple</option>
        <option value="carbon"${devMode==='carbon'?' selected':''}>Carbon</option>
      </select>
      <div class="checkbox-row" style="padding:0;white-space:nowrap">
        <input type="checkbox" id="opt-edit-pt-layers-${id}"${hasLayers?' checked':''}>
        <label for="opt-edit-pt-layers-${id}" style="font-size:11px">Layers</label>
      </div>
      <div class="opt-actions" style="opacity:1;transform:none;pointer-events:auto">
        <button class="btn btn-accent btn-small" onclick="saveEditProcessType(${id})">Save</button>
        <button class="btn btn-ghost btn-small" onclick="renderPhotoTypesList()">Cancel</button>
      </div>
    </div>`;
  setTimeout(()=>document.getElementById(`opt-edit-pt-name-${id}`)?.focus(),50);
}
async function saveEditProcessType(id){
  const name=document.getElementById(`opt-edit-pt-name-${id}`)?.value.trim();
  if(!name){alert('Name required');return;}
  const dev_mode=document.getElementById(`opt-edit-pt-dev-${id}`)?.value;
  const has_layers=document.getElementById(`opt-edit-pt-layers-${id}`)?.checked;
  try{
    await api('photo_types',{method:'PATCH',id,body:{name,dev_mode,has_layers}});
    const x=S.photoTypes.find(r=>r.id===id);
    if(x){x.name=name;x.dev_mode=dev_mode;x.has_layers=has_layers?1:0;}
    renderPhotoTypesList();
    populateSel('ph-photo_type_id',S.photoTypes,pt=>esc(pt.name),false);
  }catch(ex){alert('Save failed: '+ex.message);}
}
async function submitPhotoType(){
  const msg=document.getElementById('pt-msg'); msg.className='form-msg'; msg.textContent='Saving…';
  const name=document.getElementById('pt-name').value.trim();
  if(!name){msg.className='form-msg err';msg.textContent='Name required';return;}
  const body={
    name,
    dev_mode: document.getElementById('pt-dev_mode').value,
    has_layers: document.getElementById('pt-has_layers').checked
  };
  try{
    if(editId.photo_types) await api('photo_types',{method:'PATCH',id:editId.photo_types,body});
    else await api('photo_types',{method:'POST',body});
    msg.className='form-msg ok'; msg.textContent='Saved!';
    closeForm('photo_types');
    S.photoTypes=await api('photo_types');
    renderPhotoTypesList();
    // refresh photo form type picker if present
    const sel=document.getElementById('ph-photo_type_id');
    if(sel) populateSel('ph-photo_type_id', S.photoTypes, pt=>esc(pt.name), false);
  }catch(ex){msg.className='form-msg err';msg.textContent=ex.message;}
}

// ── Options Tab ───────────────────────────────────────────────
const OPTS_PAGE = 10;
function renderOptions(){
  renderOptList('chemistry_types', S.chemistryTypes);
  renderOptList('negative_types',  S.negativeTypes);
  renderOptList('finishing_types', S.finishingTypes);
  renderPhotoTypesList();
  renderPaper();
}
function renderOptList(t,arr){
  const el=document.getElementById('opt-'+t); if(!el) return;
  el.innerHTML=arr.map(r=>`
    <div class="opt-row" id="opt-row-${t}-${r.id}">
      <span class="opt-name">${esc(r.name)}</span>
      <div class="opt-actions">
        <button class="btn btn-edit btn-small" onclick="startEditOption('${t}',${r.id},'${esc(r.name).replace(/'/g,"\\'")}')">Edit</button>
        <button class="btn btn-danger btn-small" onclick="deleteOption('${t}',${r.id})">Del</button>
      </div>
    </div>`).join('');
}
function focusNewOptInput(t){
  const map={chemistry_types:'new-chemistry-type',negative_types:'new-negative-type',finishing_types:'new-finishing-type',photo_types:'new-photo-type'};
  const inp=document.getElementById(map[t]||t);
  if(inp){ inp.focus(); inp.scrollIntoView({behavior:'smooth',block:'nearest'}); }
}
function startEditOption(t, id, currentName){
  const row=document.getElementById(`opt-row-${t}-${id}`); if(!row) return;
  const arrMap={chemistry_types:'S.chemistryTypes',negative_types:'S.negativeTypes',finishing_types:'S.finishingTypes'};
  const arrRef=arrMap[t]||'[]';
  row.innerHTML=`
    <input type="text" class="opt-edit-input" id="opt-edit-inp-${id}" value="${esc(currentName)}">
    <div class="opt-actions" style="opacity:1;transform:none;pointer-events:auto">
      <button class="btn btn-accent btn-small" onclick="saveEditOption('${t}',${id})">Save</button>
      <button class="btn btn-ghost btn-small" onclick="renderOptList('${t}',${arrRef})">Cancel</button>
    </div>`;
  setTimeout(()=>document.getElementById(`opt-edit-inp-${id}`)?.focus(),50);
}
async function saveEditOption(t, id){
  const inp=document.getElementById(`opt-edit-inp-${id}`); if(!inp) return;
  const name=inp.value.trim(); if(!name){alert('Name required');return;}
  try{
    await api(t,{method:'PATCH',id,body:{name}});
    if(t==='chemistry_types'){const x=S.chemistryTypes.find(r=>r.id===id);if(x)x.name=name;}
    else if(t==='negative_types'){const x=S.negativeTypes.find(r=>r.id===id);if(x)x.name=name;}
    else if(t==='finishing_types'){const x=S.finishingTypes.find(r=>r.id===id);if(x)x.name=name;}
    const arr=t==='chemistry_types'?S.chemistryTypes:t==='negative_types'?S.negativeTypes:S.finishingTypes;
    renderOptList(t, arr);
    populateSel('chem-type_id', S.chemistryTypes, t2=>esc(t2.name), true);
    populateSel('n-type_id', S.negativeTypes, t2=>esc(t2.name), true);
  }catch(ex){alert('Save failed: '+ex.message);}
}
function renderOptsRecent(listId,moreId,arr,labelFn){
  const el=document.getElementById(listId); if(!el) return;
  const slice=arr.slice(0,OPTS_PAGE);
  el.innerHTML=slice.map(r=>`<div class="opts-item">${esc(labelFn(r))}</div>`).join('');
  const btn=document.getElementById(moreId);
  if(btn) btn.style.display=arr.length>OPTS_PAGE?'':'none';
}

// Chemistry table render
function renderChemistry(){
  const tb=document.querySelector('#tbl-chemistry tbody'); if(!tb) return;
  const rows = S.chemistryShowAll ? S.chemistry : S.chemistry.slice(0,OPTS_PAGE);
  if(!rows.length){tb.innerHTML='<tr><td colspan="8" class="empty-state">No chemistry records yet</td></tr>';
    document.getElementById('opts-chemistry-more').style.display='none'; return;}
  tb.innerHTML=rows.map(r=>{
    const fromLabel = r.created_from_ids
      ? (()=>{ const parent=S.chemistry.find(c=>String(c.id)===String(r.created_from_ids)); return parent?`<span class="badge-g">${esc(chemLabel(parent))}</span>`:`<span class="badge-g">#${esc(r.created_from_ids)}</span>`; })()
      : '--';
    return `<tr>
    <td style="color:rgba(255,255,255,.35)">${r.id}</td>
    <td><strong>${esc(r.label||'—')}</strong></td>
    <td>${esc(r.type_name||'—')}</td>
    <td>${esc(r.date_created)}</td>
    <td>${r.percent_solution!=null?r.percent_solution+'%':'--'}</td>
    <td>${fromLabel}</td>
    <td style="color:rgba(255,255,255,.5);max-width:180px">${esc(r.notes||'--')}</td>
    <td>${acts('chemistry',r.id)}</td></tr>`;
  }).join('');
  const moreBtn=document.getElementById('opts-chemistry-more');
  if(moreBtn) moreBtn.style.display=(!S.chemistryShowAll&&S.chemistry.length>OPTS_PAGE)?'':'none';
}
function loadMoreChemistry(){ S.chemistryShowAll=true; renderChemistry(); }

// Paper table render
function renderPaper(){
  const tb=document.querySelector('#tbl-paper tbody'); if(!tb) return;
  const rows = S.paperShowAll ? S.paper : S.paper.slice(0,OPTS_PAGE);
  if(!rows.length){tb.innerHTML='<tr><td colspan="7" class="empty-state">No paper records yet</td></tr>';
    document.getElementById('opts-paper-more').style.display='none'; return;}
  tb.innerHTML=rows.map(r=>`<tr>
    <td style="color:rgba(255,255,255,.35)">${r.id}</td>
    <td>${esc(r.manufacturer||'--')}</td>
    <td>${esc(r.label||'--')}</td>
    <td>${r.weight?r.weight+' gsm':'--'}</td>
    <td>${r.hot_press?'<span class="pill-yes">HP</span>':'<span class="pill-no">CP</span>'}</td>
    <td style="color:rgba(255,255,255,.5);max-width:200px">${esc(r.notes||'--')}</td>
    <td>${acts('paper',r.id)}</td></tr>`).join('');
  const moreBtn=document.getElementById('opts-paper-more');
  if(moreBtn) moreBtn.style.display=(!S.paperShowAll&&S.paper.length>OPTS_PAGE)?'':'none';
}
function loadMorePaper(){ S.paperShowAll=true; renderPaper(); }
async function submitPaper(){
  const msg=document.getElementById('p-msg'); msg.className='form-msg'; msg.textContent='Saving…';
  const body={
    manufacturer: document.getElementById('p-manufacturer').value||null,
    label:        document.getElementById('p-label').value||null,
    weight:       parseFloat(document.getElementById('p-weight').value)||null,
    hot_press:    document.getElementById('p-hot_press').checked,
    notes:        document.getElementById('p-notes').value||null
  };
  try{
    if(editId.paper) await api('paper',{method:'PATCH',id:editId.paper,body});
    else await api('paper',{method:'POST',body});
    msg.className='form-msg ok'; msg.textContent='Saved!';
    closeForm('paper');
    S.paper=await api('paper');
    renderPaper();
    // refresh base paper dropdown in support paper form
    populateSel('sp-paper_id', S.paper, p=>`${esc(p.manufacturer||'')} ${esc(p.label||'')}`.trim()||`#${p.id}`);
  }catch(ex){msg.className='form-msg err';msg.textContent=ex.message;}
}

// Chemistry form
async function submitChemistry(){
  const msg=document.getElementById('chem-msg'); msg.className='form-msg'; msg.textContent='Saving…';
  const dateVal=document.getElementById('chem-date_created').value;
  if(!dateVal){msg.className='form-msg err';msg.textContent='Date required';return;}
  const body={
    label:        document.getElementById('chem-label').value.trim()||null,
    date_created: dateVal,
    type_id:      parseInt(document.getElementById('chem-type_id').value)||null,
    percent_solution: parseFloat(document.getElementById('chem-percent_solution').value)||null,
    created_from_id:  parseInt(document.getElementById('chem-created_from_id').value)||null,
    notes:        document.getElementById('chem-notes').value||null
  };
  try{
    if(editId.chemistry) await api('chemistry',{method:'PATCH',id:editId.chemistry,body});
    else await api('chemistry',{method:'POST',body});
    msg.className='form-msg ok'; msg.textContent='Saved!';
    closeForm('chemistry');
    S.chemistry=await api('chemistry');
    renderChemistry();
    populateChemSelects();
  }catch(ex){msg.className='form-msg err';msg.textContent=ex.message;}
}

async function addOption(t){
  let name, body;
  if(t==='photo_types'){
    const inp=document.getElementById('new-photo-type');
    name=inp.value.trim(); if(!name) return;
    body={
      name,
      dev_mode: document.getElementById('new-pt-dev_mode').value,
      has_layers: document.getElementById('new-pt-has_layers').checked
    };
    try{
      const r=await api(t,{method:'POST',body});
      S.photoTypes.push({...body,id:r.id,has_layers:body.has_layers?1:0});
      inp.value=''; document.getElementById('new-pt-has_layers').checked=false;
      renderPhotoTypesList();
      populateSel('ph-photo_type_id',S.photoTypes,pt=>esc(pt.name),false);
    }catch(ex){alert(ex.message);}
    return;
  }
  const inpId = t==='chemistry_types'?'new-chemistry-type':t==='negative_types'?'new-negative-type':'new-finishing-type';
  const inp=document.getElementById(inpId);
  name=inp.value.trim(); if(!name) return;
  try{
    const r=await api(t,{method:'POST',body:{name}});
    if(t==='chemistry_types') S.chemistryTypes.push(r);
    else if(t==='negative_types') S.negativeTypes.push(r);
    else S.finishingTypes.push(r);
    inp.value=''; renderOptions();
  }catch(ex){alert(ex.message);}
}
async function deleteOption(t,id){
  if(!confirm('Delete?')) return;
  try{
    await api(t,{method:'DELETE',id});
    if(t==='chemistry_types')  S.chemistryTypes=S.chemistryTypes.filter(x=>x.id!==id);
    else if(t==='negative_types')  S.negativeTypes=S.negativeTypes.filter(x=>x.id!==id);
    else if(t==='finishing_types') S.finishingTypes=S.finishingTypes.filter(x=>x.id!==id);
    else if(t==='photo_types'){ S.photoTypes=S.photoTypes.filter(x=>x.id!==id); renderPhotoTypesList(); return; }
    renderOptions();
  }catch(ex){alert('Delete failed: '+ex.message);}
}

// ── Edit records ──────────────────────────────────────────────
async function editRecord(tab,id){
  let data; try{data=await api(tab,{id});}catch(ex){alert(ex.message);return;}
  editId[tab]=id;
  if(tab==='photo'){openPhotoForm({...data,editing:true});return;}
  const map={
    photo_types:()=>{
      document.getElementById('pt-name').value=data.name||'';
      document.getElementById('pt-dev_mode').value=data.dev_mode||'simple';
      document.getElementById('pt-has_layers').checked=!!data.has_layers;
    },
    chemistry:()=>{
      document.getElementById('chem-label').value=data.label||'';
      document.getElementById('chem-date_created').value=data.date_created||'';
      document.getElementById('chem-type_id').value=data.type_id||'';
      document.getElementById('chem-percent_solution').value=data.percent_solution||'';
      document.getElementById('chem-created_from_id').value=data.created_from_ids||'';
      document.getElementById('chem-notes').value=data.notes||'';
    },
    paper:()=>{
      document.getElementById('p-manufacturer').value=data.manufacturer||'';
      document.getElementById('p-label').value=data.label||'';
      document.getElementById('p-weight').value=data.weight||'';
      document.getElementById('p-hot_press').checked=!!data.hot_press;
      document.getElementById('p-notes').value=data.notes||'';
    },
    support_paper:()=>{
      document.getElementById('sp-paper_id').value=data.paper_id||'';
      document.getElementById('sp-mark').value=data.mark||'';
      document.getElementById('sp-treatment_chemistry_id').value=data.treatment_chemistry_id||'';
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
  document.getElementById('form-'+tab+'-title').textContent='Edit '+tab.replace(/_/g,' ');
  openForm(tab,data);
}
async function deleteRecord(tab,id,label){
  if(!confirm('Delete this '+label+'?')) return;
  try{
    await api(tab,{method:'DELETE',id});
    S[tab]=S[tab]?.filter(r=>r.id!==id);
    if(tab==='chemistry')     renderChemistry();
    if(tab==='paper')         renderPaper();
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