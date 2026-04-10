<?php
require __DIR__ . '/auth.php';
auth_require_page();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kaslo · To-Do Manager</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f5f5f5; color: #111; min-height: 100vh; }

#app { max-width: 900px; margin: 0 auto; padding: 24px 20px 80px; }

.page-header { display: flex; align-items: center; justify-content: space-between; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 24px; }
.page-header h1 { font-size: 14px; letter-spacing: 2px; text-transform: uppercase; }
.btn-back { font-size: 11px; color: #555; text-decoration: none; letter-spacing: 0.5px; text-transform: uppercase; border: 1px solid #ccc; padding: 4px 10px; }
.btn-back:hover { border-color: #000; color: #000; }

.section-label { font-size: 9px; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; color: #999; margin-bottom: 10px; }

/* ── STATUS BAR ── */
#statusBar { font-size: 11px; padding: 6px 10px; margin-bottom: 14px; border-radius: 2px; display: none; }
#statusBar.ok  { background: #e8f5e9; color: #2e7d32; display: block; }
#statusBar.err { background: #ffebee; color: #c62828; display: block; }

/* ── CREATE FORM ── */
.create-form { background: #fff; border: 1px solid #e0e0e0; border-top: 3px solid #000; padding: 18px 20px; margin-bottom: 28px; }
.form-row { display: flex; gap: 12px; margin-bottom: 12px; flex-wrap: wrap; }
.form-group { display: flex; flex-direction: column; gap: 4px; }
.form-group.grow { flex: 1; min-width: 180px; }
.form-group label { font-size: 8px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; color: #aaa; }
.form-group input[type=text],
.form-group input[type=date],
.form-group select,
.form-group textarea { border: none; border-bottom: 1px solid #ddd; padding: 5px 0; font-size: 14px; font-family: inherit; outline: none; background: transparent; color: #000; width: 100%; }
.form-group textarea { resize: vertical; min-height: 46px; border: 1px solid #ddd; padding: 5px 7px; }
.form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-bottom-color: #000; }

/* ── TAGS ── */
.tags-row { display: flex; gap: 6px; flex-wrap: wrap; align-items: center; margin-top: 2px; }
.tag-chip {
  display: inline-flex; align-items: center; gap: 4px;
  background: #fff; border: 1.5px solid #ddd;
  padding: 4px 10px; font-size: 11px; font-weight: 700;
  letter-spacing: 0.5px; cursor: pointer; border-radius: 2px;
  user-select: none; transition: all 0.1s;
}
.tag-chip:hover { border-color: #888; }
.tag-chip.active { background: #000; color: #fff; border-color: #000; }
.tag-chip.custom-active { background: #000; color: #fff; border-color: #000; }
.new-tag-wrap { display: flex; align-items: center; gap: 4px; margin-top: 2px; }
.new-tag-wrap input { border: none; border-bottom: 1px solid #ddd; font-size: 12px; font-family: inherit; outline: none; width: 80px; padding: 3px 0; }
.new-tag-wrap button { font-size: 11px; background: none; border: 1px solid #ccc; padding: 3px 8px; cursor: pointer; font-family: inherit; }
.new-tag-wrap button:hover { border-color: #000; }

/* ── PRIORITY ── */
.priority-pills { display: flex; gap: 6px; flex-wrap: wrap; margin-top: 2px; }
.pill { padding: 5px 12px; font-size: 11px; font-weight: 700; border: 1.5px solid #e0e0e0; cursor: pointer; border-radius: 2px; letter-spacing: 0.3px; transition: all 0.12s; user-select: none; }
.pill:hover { border-color: #aaa; }
.pill.active { border-color: transparent; }
.pill[data-p="1"].active { background: #c00;    color: #fff; border-color: #c00; }
.pill[data-p="2"].active { background: #e07000; color: #fff; border-color: #e07000; }
.pill[data-p="3"].active { background: #f0b400; color: #fff; border-color: #f0b400; }
.pill[data-p="4"].active { background: #555;    color: #fff; border-color: #555; }
.pill[data-p="5"].active { background: #aaa;    color: #fff; border-color: #aaa; }

/* ── RECURRENCE ── */
.recur-group { display: flex; align-items: flex-start; gap: 8px; flex-wrap: wrap; padding-top: 2px; }
.recur-type-btn { font-size: 11px; border: 1px solid #ddd; background: none; padding: 4px 10px; cursor: pointer; font-family: inherit; border-radius: 2px; color: #666; }
.recur-type-btn.active { background: #000; color: #fff; border-color: #000; }
#recurWeekdayWrap, #recurDayWrap { display: none; }
.dow-grid { display: flex; gap: 4px; }
.dow-btn { width: 30px; height: 30px; border: 1px solid #ddd; background: none; font-size: 10px; font-weight: 700; cursor: pointer; border-radius: 2px; font-family: inherit; }
.dow-btn.active { background: #000; color: #fff; border-color: #000; }

.form-submit-row { display: flex; align-items: center; gap: 12px; margin-top: 14px; }
.btn-add { background: #000; color: #fff; border: none; padding: 10px 28px; font-size: 13px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; cursor: pointer; }
.btn-add:hover { background: #333; }
.form-msg { font-size: 11px; color: #888; }

/* ── CUSTOM TAG MANAGER ── */
.tag-manager { background: #fff; border: 1px solid #e0e0e0; padding: 14px 18px; margin-bottom: 24px; }
.tag-list { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 10px; }
.tag-manage-chip {
  display: inline-flex; align-items: center; gap: 6px;
  background: #000; color: #fff;
  padding: 4px 10px; font-size: 11px; font-weight: 700; letter-spacing: 0.5px;
  border-radius: 2px;
}
.tag-manage-chip.tag-hidden { background: #ccc; color: #666; text-decoration: line-through; }
.tag-manage-chip .del-tag { cursor: pointer; opacity: 0.6; font-size: 12px; line-height: 1; }
.tag-manage-chip .del-tag:hover { opacity: 1; }
.add-tag-form { display: flex; gap: 8px; align-items: center; margin-top: 10px; }
.add-tag-form input { border: none; border-bottom: 1px solid #ddd; font-size: 13px; font-family: inherit; outline: none; width: 120px; padding: 4px 0; }
.add-tag-form button { font-size: 12px; background: none; border: 1px solid #ccc; padding: 4px 12px; cursor: pointer; font-family: inherit; }
.add-tag-form button:hover { border-color: #000; background: #000; color: #fff; }

/* ── PAGE TABS (Items / Calendars) ── */
.page-tabs { display: flex; gap: 0; border-bottom: 2px solid #000; margin-bottom: 24px; }
.page-tab { font-size: 12px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; padding: 8px 20px; border: none; background: none; cursor: pointer; color: #aaa; border-bottom: 3px solid transparent; margin-bottom: -2px; font-family: inherit; }
.page-tab.active { color: #000; border-bottom-color: #000; }
.tab-panel { display: none; }
.tab-panel.active { display: block; }

/* ── FILTER TABS (within Items tab) ── */
.filter-tabs { display: flex; gap: 0; margin-bottom: 14px; border-bottom: 1px solid #e0e0e0; flex-wrap: wrap; }
.filter-tab { font-size: 11px; font-weight: 600; letter-spacing: 0.5px; padding: 6px 14px; border: none; background: none; cursor: pointer; color: #aaa; border-bottom: 2px solid transparent; margin-bottom: -1px; font-family: inherit; }
.filter-tab.active { color: #000; border-bottom-color: #000; }

/* ── CAL FEED MANAGER ── */
.cal-feed-list { display: flex; flex-direction: column; gap: 0; margin-bottom: 20px; border: 1px solid #e0e0e0; }
.cal-feed-row { display: flex; align-items: center; gap: 10px; padding: 10px 14px; background: #fff; border-bottom: 1px solid #f0f0f0; }
.cal-feed-row:last-child { border-bottom: none; }
.cal-feed-row.disabled { opacity: 0.45; }
.cal-feed-toggle { width: 36px; height: 20px; border-radius: 10px; background: #ccc; border: none; cursor: pointer; position: relative; flex-shrink: 0; transition: background 0.15s; }
.cal-feed-toggle.on { background: #000; }
.cal-feed-toggle::after { content:''; position:absolute; width:16px; height:16px; border-radius:50%; background:#fff; top:2px; left:2px; transition: left 0.15s; }
.cal-feed-toggle.on::after { left:18px; }
.cal-feed-label { flex:1; font-size:13px; font-weight:600; min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.cal-feed-url { font-size:10px; color:#bbb; flex:2; min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.cal-color-select { border: 1.5px solid #ddd; padding: 3px 6px; font-size: 11px; font-family: inherit; border-radius: 2px; background: #fff; cursor: pointer; }
.cal-del-btn { background: none; border: 1px solid #ddd; color: #bbb; font-size: 11px; padding: 3px 8px; cursor: pointer; border-radius: 2px; font-family: inherit; }
.cal-del-btn:hover { border-color: #c00; color: #c00; }
.cal-swatch { width: 14px; height: 14px; border-radius: 2px; flex-shrink: 0; }

/* Add-feed form */
.add-feed-form { background: #fff; border: 1px solid #e0e0e0; border-top: 3px solid #000; padding: 16px 18px; }
.add-feed-form .form-row { flex-wrap: wrap; }

/* ── ITEM LIST ── */
#itemList { display: flex; flex-direction: column; }
.item-row { background: #fff; border: 1px solid #e8e8e8; border-top: none; display: flex; align-items: stretch; }
.item-row:first-child { border-top: 1px solid #e8e8e8; }
.item-row.done-row { opacity: 0.45; }
.item-row.editing { border-color: #000; border-width: 2px; z-index: 1; }

.item-stripe { width: 4px; flex-shrink: 0; }
.stripe-p1 { background: #c00; }
.stripe-p2 { background: #e07000; }
.stripe-p3 { background: #f0b400; }
.stripe-p4 { background: #555; }
.stripe-p5 { background: #ddd; }

.item-body { flex: 1; padding: 9px 12px; min-width: 0; }
.item-main { display: flex; align-items: center; gap: 8px; margin-bottom: 2px; }
.item-check { width: 16px; height: 16px; border: 1.5px solid #ccc; border-radius: 3px; flex-shrink: 0; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.12s; }
.item-check.done { background: #000; border-color: #000; }
.item-check.done::after { content: '✓'; color: #fff; font-size: 10px; font-weight: 700; }
.item-text { font-size: 13px; font-weight: 500; flex: 1; min-width: 0; }
.item-text.done { text-decoration: line-through; color: #aaa; }

.blk-tag { display: inline-block; background: #000; color: #fff; font-size: 9px; font-weight: 700; padding: 2px 6px; border-radius: 1px; margin-right: 4px; letter-spacing: 0.5px; vertical-align: middle; }
.priority-tag { font-size: 9px; font-weight: 700; letter-spacing: 0.8px; padding: 2px 6px; border-radius: 2px; white-space: nowrap; flex-shrink: 0; }
.tag-p1 { background: #fee;    color: #c00; }
.tag-p2 { background: #fff3e0; color: #e07000; }
.tag-p3 { background: #fffbe6; color: #c08000; }
.tag-p4 { background: #eee;    color: #555; }
.tag-p5 { background: #f4f4f4; color: #aaa; }

.item-meta { display: flex; gap: 10px; font-size: 10px; color: #aaa; margin-top: 2px; flex-wrap: wrap; }
.meta-due.overdue { color: #c00; font-weight: 700; }

.item-edit-panel { display: none; border-top: 1px solid #f0f0f0; padding: 12px 12px 14px; background: #fafafa; }
.item-edit-panel.open { display: block; }
.edit-row { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 10px; }
.edit-field { display: flex; flex-direction: column; gap: 3px; }
.edit-field.grow { flex: 1; min-width: 150px; }
.edit-field label { font-size: 8px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; color: #bbb; }
.edit-field input, .edit-field select { border: none; border-bottom: 1px solid #ddd; padding: 4px 0; font-size: 13px; font-family: inherit; outline: none; background: transparent; width: 100%; }
.edit-actions { display: flex; gap: 8px; margin-top: 10px; }
.btn-sm { font-size: 11px; padding: 5px 14px; border: 1px solid #000; background: none; cursor: pointer; font-family: inherit; font-weight: 600; }
.btn-sm.primary { background: #000; color: #fff; }
.btn-sm.danger  { border-color: #c00; color: #c00; }
.btn-sm.danger:hover { background: #c00; color: #fff; }

.item-actions { display: flex; flex-direction: column; align-items: center; justify-content: flex-start; gap: 2px; padding: 8px 8px 8px 4px; flex-shrink: 0; }
.act-btn { background: none; border: none; cursor: pointer; font-size: 14px; padding: 4px 6px; color: #ccc; line-height: 1; border-radius: 2px; }
.act-btn:hover { color: #000; background: #f0f0f0; }
.act-btn.del:hover { color: #c00; background: #fee; }

.drag-handle { display: flex; align-items: center; padding: 0 6px; cursor: grab; color: #ddd; font-size: 16px; flex-shrink: 0; }
.drag-handle:active { cursor: grabbing; }
.item-row.dragging { opacity: 0.4; border: 2px dashed #999; }
.item-row.drag-over { border-top: 2px solid #000; }

.empty-state { text-align: center; padding: 48px 20px; color: #ccc; font-size: 13px; }

.save-bar { position: fixed; bottom: 0; left: 0; right: 0; background: #000; color: #fff; display: none; align-items: center; justify-content: center; gap: 16px; padding: 12px; font-size: 13px; z-index: 100; }
.save-bar.visible { display: flex; }
.save-bar button { background: #fff; color: #000; border: none; padding: 7px 20px; font-weight: 700; font-size: 12px; letter-spacing: 0.5px; cursor: pointer; }
.save-bar button:hover { background: #eee; }
</style>
</head>
<body>
<div id="app">

  <div class="page-header">
    <h1>Kaslo · To-Do Manager</h1>
    <div style="display:flex;gap:10px;align-items:center">
      <a href="/kaslo-weather.php" class="btn-back">← Dashboard</a>
      <a href="/logout.php" style="font-size:11px;color:#aaa;text-decoration:none;letter-spacing:0.5px;text-transform:uppercase;border:1px solid #ddd;padding:4px 10px;">Log out</a>
    </div>
  </div>

  <div id="statusBar"></div>

  <!-- ── PAGE TABS ── -->
  <div class="page-tabs">
    <button class="page-tab active" data-page="items">Items</button>
    <button class="page-tab" data-page="calendars">Calendars</button>
  </div>

  <!-- ══ ITEMS TAB ══ -->
  <div class="tab-panel active" id="panel-items">

  <!-- ── CREATE FORM ── -->
  <div class="section-label">New Item</div>
  <div class="create-form">

    <!-- 1. Priority first -->
    <div class="form-row" style="align-items:flex-start;margin-bottom:16px">
      <div class="form-group">
        <label>Priority</label>
        <div class="priority-pills">
          <div class="pill" data-p="1">1 · Imp</div>
          <div class="pill" data-p="2">2</div>
          <div class="pill" data-p="3">3</div>
          <div class="pill" data-p="4">4</div>
          <div class="pill active" data-p="5">5 · Later</div>
        </div>
      </div>
    </div>

    <!-- 2. Item text (large) + due date -->
    <div class="form-row">
      <div class="form-group grow">
        <label>Item</label>
        <input type="text" id="newText" placeholder="What needs to be done?" maxlength="300" style="font-size:21px;padding-bottom:8px">
      </div>
      <div class="form-group">
        <label>Due Date</label>
        <input type="date" id="newDue">
      </div>
    </div>

    <!-- 3. Tags -->
    <div class="form-row" style="align-items:flex-start">
      <div class="form-group" style="flex:1">
        <label>Sections</label>
        <div class="tags-row" id="newTagPills"></div>
      </div>
    </div>

    <!-- 4. Recurrence -->
    <div class="form-row" style="align-items:flex-start">
      <div class="form-group" style="flex:1">
        <label>Recurrence</label>
        <div class="recur-group">
          <button class="recur-type-btn active" data-rt="none">None</button>
          <button class="recur-type-btn" data-rt="weekly">Weekly</button>
          <button class="recur-type-btn" data-rt="monthly">Monthly</button>
          <div id="recurWeekdayWrap">
            <div class="dow-grid">
              <button class="dow-btn" data-dow="0">Su</button>
              <button class="dow-btn" data-dow="1">Mo</button>
              <button class="dow-btn" data-dow="2">Tu</button>
              <button class="dow-btn" data-dow="3">We</button>
              <button class="dow-btn" data-dow="4">Th</button>
              <button class="dow-btn" data-dow="5">Fr</button>
              <button class="dow-btn" data-dow="6">Sa</button>
            </div>
          </div>
          <div id="recurDayWrap" style="display:flex;align-items:center;gap:8px">
            <span style="font-size:12px;color:#888">Day</span>
            <input type="number" id="recurDayInput" min="1" max="31" value="1"
              style="width:52px;border:none;border-bottom:1px solid #ddd;font-size:14px;font-family:inherit;outline:none;padding:3px 0">
            <span style="font-size:12px;color:#888">of each month</span>
          </div>
        </div>
      </div>
    </div>

    <!-- 5. Notes -->
    <div class="form-row">
      <div class="form-group grow">
        <label>Notes (optional)</label>
        <textarea id="newNotes" placeholder="Additional details…" rows="2"></textarea>
      </div>
    </div>

    <div class="form-submit-row">
      <button class="btn-add" id="addBtn">Add Item</button>
      <span class="form-msg" id="formMsg"></span>
    </div>
  </div>

  <!-- ── SECTION MANAGER (below the create form) ── -->
  <div class="section-label">Custom Sections</div>
  <div class="tag-manager">
    <div style="font-size:11px;color:#888;margin-bottom:6px">
      Sections appear as black labels on items.
      Items with a section <em>and</em> a recurrence appear in the 7-day calendar instead of the to-do list.
    </div>
    <div class="tag-list" id="tagList"></div>
    <div class="add-tag-form">
      <input type="text" id="newTagInput" placeholder="NEW SECTION" maxlength="20" style="text-transform:uppercase">
      <button onclick="addCustomTag()">+ Add Section</button>
    </div>
  </div>

  <!-- ── ITEM LIST ── -->
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px">
    <div class="section-label" style="margin-bottom:0">Items <span style="color:#ccc;font-weight:400" id="itemCount"></span></div>
  </div>

  <div class="filter-tabs">
    <button class="filter-tab active" data-filter="all">All</button>
    <button class="filter-tab" data-filter="active">Active</button>
    <button class="filter-tab" data-filter="1">P1</button>
    <button class="filter-tab" data-filter="2">P2</button>
    <button class="filter-tab" data-filter="3">P3</button>
    <button class="filter-tab" data-filter="recurring">Recurring</button>
  </div>

  <div id="itemList"></div>

  <!-- ── COMPLETED SECTION ── -->
  <div id="completedSection" style="display:none;margin-top:28px">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px">
      <div class="section-label" style="margin-bottom:0">Completed <span style="color:#ccc;font-weight:400" id="completedCount"></span></div>
      <button id="completedToggle" onclick="toggleCompletedList()" style="font-size:10px;font-weight:700;letter-spacing:1px;text-transform:uppercase;background:none;border:1px solid #ddd;padding:3px 10px;cursor:pointer;font-family:inherit;color:#aaa">Hide</button>
    </div>
    <div id="completedList"></div>
  </div>

  </div><!-- end #panel-items -->

  <!-- ══ CALENDARS TAB ══ -->
  <div class="tab-panel" id="panel-calendars">
    <div class="section-label">Calendar Feeds</div>
    <div class="cal-feed-list" id="calFeedList"></div>

    <div class="section-label" style="margin-top:20px">Add Feed</div>
    <div class="add-feed-form">
      <div class="form-row">
        <div class="form-group grow">
          <label>Name</label>
          <input type="text" id="newFeedLabel" placeholder="My Calendar" maxlength="40">
        </div>
        <div class="form-group">
          <label>Colour</label>
          <select id="newFeedColor">
            <option value="light">Light</option>
            <option value="medium" selected>Medium</option>
            <option value="dark">Dark</option>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group grow">
          <label>iCal / WebCal URL</label>
          <input type="text" id="newFeedUrl" placeholder="https://p162-caldav.icloud.com/published/2/…">
        </div>
      </div>
      <div class="form-submit-row">
        <button class="btn-add" onclick="addCalFeed()">+ Add Calendar</button>
        <span class="form-msg" id="calFeedMsg"></span>
      </div>
    </div>
  </div><!-- end #panel-calendars -->


<div class="save-bar" id="saveBar">
  <span>Unsaved changes</span>
  <button id="saveAllBtn">Save All</button>
</div>

<script>
// ── CONSTANTS ─────────────────────────────────────────────────────
const API = 'todo-api.php';
const TAGS_KEY = 'kaslo_custom_tags';

// Built-in tags (always present)
const BUILTIN_TAGS = ['FIRE', 'AMBO', 'HW'];

// Priority 1 (highest/most important) → 5 (lowest/don't forget)
const P_CONFIG = {
  1: { label:'1 · Important',  tagClass:'tag-p1', stripeClass:'stripe-p1' },
  2: { label:'2',              tagClass:'tag-p2', stripeClass:'stripe-p2' },
  3: { label:'3',              tagClass:'tag-p3', stripeClass:'stripe-p3' },
  4: { label:'4',              tagClass:'tag-p4', stripeClass:'stripe-p4' },
  5: { label:"5 · Don't Forget", tagClass:'tag-p5', stripeClass:'stripe-p5' },
};
function getPC(p) { return P_CONFIG[parseInt(p)] || P_CONFIG[5]; }

// ── STATE ─────────────────────────────────────────────────────────
let items          = [];
let dirty          = false;
let editingId      = null;
let currentFilter  = 'all';
let selectedPriority = 5; // default: lowest priority
let selectedRecurType = 'none';
let selectedDow    = null;
let selectedTags   = new Set();  // tags selected on the create form
let customTags     = JSON.parse(localStorage.getItem(TAGS_KEY) || '[]');
let dragSrcIdx     = null;

// ── TAG MANAGEMENT ────────────────────────────────────────────────
function allTags() {
  return [...BUILTIN_TAGS, ...customTags.filter(t => !BUILTIN_TAGS.includes(t))];
}

function saveCustomTags() {
  localStorage.setItem(TAGS_KEY, JSON.stringify(customTags));
}

const HIDDEN_SECTIONS_KEY = 'kaslo_hidden_sections';
function hiddenSections() {
  return new Set(JSON.parse(localStorage.getItem(HIDDEN_SECTIONS_KEY)||'[]'));
}
function toggleSection(tag) {
  const h = hiddenSections();
  if (h.has(tag)) h.delete(tag); else h.add(tag);
  localStorage.setItem(HIDDEN_SECTIONS_KEY, JSON.stringify([...h]));
  renderTagManager();
  renderNewTagPills();
}

function addCustomTag() {
  const inp = document.getElementById('newTagInput');
  const val = inp.value.trim().toUpperCase().replace(/[^A-Z0-9!_-]/g, '');
  if (!val || allTags().includes(val)) { inp.value=''; return; }
  customTags.push(val);
  saveCustomTags();
  inp.value = '';
  renderTagManager();
  renderNewTagPills();
}

function removeCustomTag(tag) {
  customTags = customTags.filter(t => t !== tag);
  saveCustomTags();
  renderTagManager();
  renderNewTagPills();
}

function renderTagManager() {
  const list = document.getElementById('tagList');
  list.innerHTML = '';
  const hidden = hiddenSections();
  allTags().forEach(tag => {
    const isHidden = hidden.has(tag);
    const chip = document.createElement('div');
    chip.className = 'tag-manage-chip' + (isHidden ? ' tag-hidden' : '');
    chip.innerHTML = escHtml(tag) +
      (BUILTIN_TAGS.includes(tag)
        ? ` <span class="del-tag" onclick="toggleSection('${escAttr(tag)}')" title="${isHidden?'Restore':'Hide'}">${isHidden?'↩':'✕'}</span>`
        : ` <span class="del-tag" onclick="removeCustomTag('${escAttr(tag)}')" title="Remove">✕</span>`);
    list.appendChild(chip);
  });
}

function renderNewTagPills() {
  const container = document.getElementById('newTagPills');
  container.innerHTML = '';
  allTags().forEach(tag => {
    const chip = document.createElement('div');
    chip.className = 'tag-chip' + (selectedTags.has(tag) ? ' active' : '');
    chip.textContent = tag;
    chip.onclick = () => {
      if (selectedTags.has(tag)) selectedTags.delete(tag);
      else selectedTags.add(tag);
      // If !IMP section selected, bump to priority 1
      if (tag === '!IMP' && selectedTags.has('!IMP')) setPriority(1);
      renderNewTagPills();
    };
    container.appendChild(chip);
  });
}

// ── PRIORITY ─────────────────────────────────────────────────────
function setPriority(p) {
  selectedPriority = parseInt(p) || 5;
  document.querySelectorAll('.priority-pills .pill').forEach(el =>
    el.classList.toggle('active', parseInt(el.dataset.p) === selectedPriority));
  renderNewTagPills();
}

// ── RECURRENCE ────────────────────────────────────────────────────
function setRecurType(rt) {
  selectedRecurType = rt;
  document.querySelectorAll('.recur-type-btn[data-rt]').forEach(el =>
    el.classList.toggle('active', el.dataset.rt === rt));
  document.getElementById('recurWeekdayWrap').style.display = rt==='weekly'  ? 'block' : 'none';
  document.getElementById('recurDayWrap').style.display     = rt==='monthly' ? 'flex'  : 'none';
}

// ── DATA ─────────────────────────────────────────────────────────
function status(msg, type='ok') {
  const bar = document.getElementById('statusBar');
  bar.textContent = msg;
  bar.className = type;
  clearTimeout(bar._t);
  bar._t = setTimeout(() => { bar.className = ''; bar.textContent = ''; }, 4000);
}

function purgeDoneItems() {
  const cutoff = Date.now() - 24*60*60*1000;
  const before = items.length;
  items = items.filter(item => {
    if (!item.done || !item.doneAt) return true;
    return new Date(item.doneAt).getTime() > cutoff;
  });
  if (items.length < before) markDirty();
}

async function loadItems() {
  try {
    const r = await fetch(API + '?t=' + Date.now(), {credentials:'include'});
    if (r.status === 401) { window.location.href = '/login.php?redirect=' + encodeURIComponent(window.location.pathname); return; }
    if (!r.ok) throw new Error('HTTP ' + r.status);
    const data = await r.json();
    if (data.error) throw new Error(data.error);
    items = data;
    dirty = false;
    purgeDoneItems(); // remove stale done items before rendering
    renderList();
    status('Loaded ' + items.length + ' items', 'ok');
  } catch(e) {
    console.error(e);
    status('Could not load: ' + e.message + ' — is todo-api.php deployed and todo.json writable?', 'err');
    document.getElementById('itemList').innerHTML =
      '<div class="empty-state" style="color:#c00">Error: ' + escHtml(e.message) + '</div>';
  }
}

async function saveAll() {
  try {
    const r = await fetch(API, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify(items),
    });
    const d = await r.json();
    if (r.status === 401) { status('Session expired — please log in again', 'err'); return; }
    if (!r.ok || d.error) throw new Error(d.error || 'HTTP ' + r.status);
    dirty = false;
    document.getElementById('saveBar').classList.remove('visible');
    status('Saved ' + (d.count ?? '?') + ' items ✓', 'ok');
  } catch(e) {
    status('Save failed: ' + e.message, 'err');
  }
}

function markDirty() {
  dirty = true;
  document.getElementById('saveBar').classList.add('visible');
}

function uid() { return 'i' + Date.now().toString(36) + Math.random().toString(36).slice(2,6); }

// ── RENDER ────────────────────────────────────────────────────────
// filteredItems only returns ACTIVE (non-done) items, applying the current filter
function filteredItems() {
  switch(currentFilter) {
    case 'active':    return items.filter(i => !i.done);
    case 'recurring': return items.filter(i => !i.done && (i.recurWeekday!=null || i.recurDay!=null));
    case 'all':       return items.filter(i => !i.done).sort((a,b)=>(parseInt(a.priority)||5)-(parseInt(b.priority)||5));
    default:          return items.filter(i => !i.done && parseInt(i.priority) === parseInt(currentFilter));
  }
}

function buildItemRow(item, isDone) {
  const realIdx = items.indexOf(item);
  const pc  = getPC(item.priority);
  const due = item.due ? formatDue(item.due) : null;
  const dueOverdue = item.due && item.due < todayStr() && !isDone;
  const DOW_NAMES = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
  let recurLabel = '';
  if (item.recurWeekday != null) recurLabel = '↻ Every ' + DOW_NAMES[item.recurWeekday];
  else if (item.recurDay != null) recurLabel = '↻ Day ' + item.recurDay + ' monthly';

  const tagHtml = (item.tags||[]).map(t =>
    '<span class="blk-tag">' + escHtml(t) + '</span>').join('');

  const row = document.createElement('div');
  row.className = 'item-row' + (isDone ? ' done-row' : '') + (editingId===item.id ? ' editing' : '');
  row.dataset.id  = item.id;
  row.dataset.idx = realIdx;
  if (!isDone) row.draggable = true;

  // Show doneAt timestamp for completed items
  const doneAtStr = isDone && item.doneAt
    ? new Date(item.doneAt).toLocaleString('en-CA',{month:'short',day:'numeric',hour:'2-digit',minute:'2-digit'})
    : null;

  row.innerHTML = `
    ${!isDone ? '<div class="drag-handle" title="Drag to reorder">⠿</div>' : '<div style="width:26px;flex-shrink:0"></div>'}
    <div class="item-stripe ${pc.stripeClass}"></div>
    <div class="item-body">
      <div class="item-main">
        <div class="item-check${isDone?' done':''}" onclick="toggleDone('${item.id}')"></div>
        <div class="item-text${isDone?' done':''}">${tagHtml}${escHtml(item.text)}</div>
        <span class="priority-tag ${pc.tagClass}">${pc.label}</span>
      </div>
      <div class="item-meta">
        ${due ? `<span class="meta-due${dueOverdue?' overdue':''}">📅 ${due}</span>` : ''}
        ${recurLabel ? `<span>${recurLabel}</span>` : ''}
        ${doneAtStr ? `<span style="color:#bbb">✓ ${doneAtStr}</span>` : ''}
        ${item.notes ? `<span style="color:#bbb;font-style:italic">${escHtml(item.notes.slice(0,60))}${item.notes.length>60?'…':''}</span>` : ''}
      </div>
      <div class="item-edit-panel${editingId===item.id?' open':''}" id="edit-${item.id}">
        ${buildEditPanel(item)}
      </div>
    </div>
    <div class="item-actions">
      <button class="act-btn" onclick="toggleEdit('${item.id}')" title="Edit">✎</button>
      <button class="act-btn del" onclick="deleteItem('${item.id}')" title="Delete">✕</button>
    </div>`;

  if (!isDone) setupDrag(row, realIdx);
  return row;
}

let completedVisible = true;
function toggleCompletedList() {
  completedVisible = !completedVisible;
  document.getElementById('completedList').style.display = completedVisible ? '' : 'none';
  document.getElementById('completedToggle').textContent = completedVisible ? 'Hide' : 'Show';
}

function renderList() {
  const list      = document.getElementById('itemList');
  const compList  = document.getElementById('completedList');
  const compSec   = document.getElementById('completedSection');

  const activeItems = filteredItems();
  const doneItems   = items.filter(i => i.done)
    .sort((a,b) => new Date(b.doneAt||0) - new Date(a.doneAt||0));

  const activeCount = items.filter(i => !i.done).length;
  document.getElementById('itemCount').textContent = '(' + activeItems.length + ' of ' + activeCount + ')';

  // ── Active list ──
  list.innerHTML = '';
  if (!activeItems.length) {
    list.innerHTML = '<div class="empty-state">No items here yet.</div>';
  } else {
    activeItems.forEach(item => list.appendChild(buildItemRow(item, false)));
  }

  // ── Completed list ──
  if (!doneItems.length) {
    compSec.style.display = 'none';
  } else {
    compSec.style.display = '';
    document.getElementById('completedCount').textContent = '(' + doneItems.length + ')';
    compList.innerHTML = '';
    compList.style.display = completedVisible ? '' : 'none';
    doneItems.forEach(item => compList.appendChild(buildItemRow(item, true)));
  }
}

function buildEditPanel(item) {
  const DOW_NAMES = ['Su','Mo','Tu','We','Th','Fr','Sa'];
  const dowBtns = DOW_NAMES.map((d,i) =>
    `<button class="dow-btn${item.recurWeekday===i?' active':''}"
      onclick="editSetDow('${item.id}',${i})" type="button">${d}</button>`).join('');

  // Tag checkboxes for edit panel
  const tagCheckboxes = allTags().map(t => {
    const checked = (item.tags||[]).includes(t);
    return `<label style="display:inline-flex;align-items:center;gap:4px;font-size:11px;cursor:pointer;margin-right:8px">
      <input type="checkbox" value="${escAttr(t)}" ${checked?'checked':''} onchange="editTagChange('${item.id}',this)">
      <span class="blk-tag" style="font-size:9px">${escHtml(t)}</span>
    </label>`;
  }).join('');

  return `
    <div class="edit-row">
      <div class="edit-field grow">
        <label>Text</label>
        <input type="text" id="etext-${item.id}" value="${escAttr(item.text)}" maxlength="300">
      </div>
      <div class="edit-field">
        <label>Due</label>
        <input type="date" id="edue-${item.id}" value="${item.due||''}">
      </div>
      <div class="edit-field">
        <label>Priority</label>
        <select id="epri-${item.id}" onchange="editPriChange('${item.id}',this.value)">
          ${[1,2,3,4,5].map(p =>
            `<option value="${p}"${parseInt(item.priority)===p?' selected':''}>${p}${p===1?' · Important':p===5?" · Don't Forget":''}</option>`).join('')}
        </select>
      </div>
    </div>
    <div class="edit-row">
      <div class="edit-field" style="flex:1">
        <label>Sections</label>
        <div style="display:flex;flex-wrap:wrap;gap:4px;padding-top:6px" id="etags-${item.id}">${tagCheckboxes}</div>
      </div>
    </div>
    <div class="edit-row">
      <div class="edit-field" style="flex:1">
        <label>Recurrence</label>
        <div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:4px">
          <button class="recur-type-btn${item.recurWeekday==null&&item.recurDay==null?' active':''}"
            onclick="editSetRecur('${item.id}','none')" type="button">None</button>
          <button class="recur-type-btn${item.recurWeekday!=null?' active':''}"
            onclick="editSetRecur('${item.id}','weekly')" type="button">Weekly</button>
          <button class="recur-type-btn${item.recurDay!=null?' active':''}"
            onclick="editSetRecur('${item.id}','monthly')" type="button">Monthly</button>
          <div id="edow-${item.id}" style="${item.recurWeekday!=null?'display:block':'display:none'}">
            <div class="dow-grid">${dowBtns}</div>
          </div>
          <div id="edom-${item.id}" style="${item.recurDay!=null?'display:flex':'display:none'};align-items:center;gap:6px">
            <span style="font-size:12px;color:#888">Day</span>
            <input type="number" id="eday-${item.id}" min="1" max="31" value="${item.recurDay??1}"
              style="width:48px;border:none;border-bottom:1px solid #ddd;font-size:13px;font-family:inherit;outline:none;padding:2px 0">
            <span style="font-size:12px;color:#888">monthly</span>
          </div>
        </div>
      </div>
    </div>
    <div class="edit-row">
      <div class="edit-field grow">
        <label>Notes</label>
        <input type="text" id="enotes-${item.id}" value="${escAttr(item.notes||'')}" maxlength="500">
      </div>
    </div>
    <div class="edit-actions">
      <button class="btn-sm primary" onclick="saveEdit('${item.id}')">Save</button>
      <button class="btn-sm" onclick="toggleEdit('${item.id}')">Cancel</button>
      <button class="btn-sm danger" onclick="deleteItem('${item.id}')">Delete</button>
    </div>`;
}

// ── ITEM ACTIONS ──────────────────────────────────────────────────
function addItem() {
  const text = document.getElementById('newText').value.trim();
  if (!text) { document.getElementById('newText').focus(); return; }
  const due   = document.getElementById('newDue').value || null;
  const notes = document.getElementById('newNotes').value.trim() || null;
  let recurWeekday = null, recurDay = null;
  if (selectedRecurType === 'weekly')  recurWeekday = selectedDow;
  if (selectedRecurType === 'monthly') recurDay = parseInt(document.getElementById('recurDayInput').value)||1;

  items.unshift({
    id: uid(), text, priority: selectedPriority,
    tags: [...selectedTags],
    due, recurWeekday, recurDay,
    done: false, created: todayStr(), notes,
  });

  // Reset form
  document.getElementById('newText').value  = '';
  document.getElementById('newDue').value   = '';
  document.getElementById('newNotes').value = '';
  selectedTags = new Set();
  setPriority(5);
  setRecurType('none');
  renderNewTagPills();
  showMsg('Added ✓');
  markDirty();
  renderList();
}

function toggleDone(id) {
  const item = items.find(i => i.id === id);
  if (!item) return;
  item.done = !item.done;
  item.doneAt = item.done ? new Date().toISOString() : null;
  // Auto-purge items done > 24 h ago on each toggle
  purgeDoneItems();
  markDirty();
  renderList();
}


function deleteItem(id) {
  if (!confirm('Remove this item?')) return;
  items = items.filter(i => i.id !== id);
  if (editingId === id) editingId = null;
  markDirty();
  renderList();
}

function toggleEdit(id) {
  editingId = editingId === id ? null : id;
  renderList();
  if (editingId) {
    const el = document.getElementById('etext-' + id);
    if (el) { el.focus(); el.select(); }
  }
}

function saveEdit(id) {
  const item = items.find(i => i.id === id);
  if (!item) return;
  item.text  = document.getElementById('etext-'+id)?.value.trim() || item.text;
  item.due   = document.getElementById('edue-'+id)?.value || null;
  item.priority = document.getElementById('epri-'+id)?.value || item.priority;
  item.notes = document.getElementById('enotes-'+id)?.value.trim() || null;
  // Collect checked tags
  const tagContainer = document.getElementById('etags-'+id);
  if (tagContainer) {
    item.tags = Array.from(tagContainer.querySelectorAll('input[type=checkbox]:checked'))
      .map(cb => cb.value);
  }
  // Monthly day
  const dayEl = document.getElementById('eday-'+id);
  if (dayEl && dayEl.closest('div').style.display !== 'none') {
    item.recurDay = parseInt(dayEl.value) || 1;
  }
  editingId = null;
  markDirty();
  renderList();
}

function editTagChange(id, checkbox) {
  // live update tags on item for consistent state when saveEdit is called
  const item = items.find(i => i.id === id);
  if (!item) return;
  const tag = checkbox.value;
  if (checkbox.checked) {
    if (!item.tags) item.tags = [];
    if (!item.tags.includes(tag)) item.tags.push(tag);
    // Auto priority 1 if !IMP section checked
    if (tag === '!IMP') {
      item.priority = 1;
      const priSel = document.getElementById('epri-'+id);
      if (priSel) priSel.value = '1';
    }
  } else {
    item.tags = (item.tags||[]).filter(t => t !== tag);
  }
}

function editPriChange(id, val) {
  const item = items.find(i => i.id === id);
  if (!item) return;
  item.priority = val;
}

function editSetRecur(id, type) {
  const item = items.find(i => i.id === id);
  if (!item) return;
  if (type === 'none')    { item.recurWeekday = null; item.recurDay = null; }
  if (type === 'weekly')  { item.recurDay = null; if (item.recurWeekday == null) item.recurWeekday = 1; }
  if (type === 'monthly') { item.recurWeekday = null; if (item.recurDay == null) item.recurDay = 1; }
  const panel = document.getElementById('edit-'+id);
  if (panel) panel.innerHTML = buildEditPanel(item);
}

function editSetDow(id, dow) {
  const item = items.find(i => i.id === id);
  if (!item) return;
  item.recurWeekday = dow;
  const panel = document.getElementById('edit-'+id);
  if (panel) panel.innerHTML = buildEditPanel(item);
}

// ── DRAG & DROP ───────────────────────────────────────────────────
function setupDrag(row, realIdx) {
  row.addEventListener('dragstart', e => {
    dragSrcIdx = realIdx; row.classList.add('dragging');
    e.dataTransfer.effectAllowed = 'move';
  });
  row.addEventListener('dragend', () => {
    row.classList.remove('dragging');
    document.querySelectorAll('.item-row').forEach(r => r.classList.remove('drag-over'));
  });
  row.addEventListener('dragover', e => {
    e.preventDefault(); e.dataTransfer.dropEffect = 'move';
    document.querySelectorAll('.item-row').forEach(r => r.classList.remove('drag-over'));
    row.classList.add('drag-over');
  });
  row.addEventListener('drop', e => {
    e.preventDefault();
    const destIdx = parseInt(row.dataset.idx);
    if (dragSrcIdx === null || dragSrcIdx === destIdx) return;
    const [moved] = items.splice(dragSrcIdx, 1);
    items.splice(destIdx, 0, moved);
    dragSrcIdx = null;
    markDirty(); renderList();
  });
}

// ── UTILS ─────────────────────────────────────────────────────────
function todayStr()  { return new Date().toLocaleDateString('en-CA'); } // local date string YYYY-MM-DD
function formatDue(d){ return new Date(d+'T12:00:00').toLocaleDateString('en-CA',{month:'short',day:'numeric',year:'numeric'}); }
function escHtml(s)  { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
function escAttr(s)  { return escHtml(s).replace(/"/g,'&quot;'); }
function showMsg(m, ms=2000) {
  const el = document.getElementById('formMsg');
  el.textContent = m;
  setTimeout(() => el.textContent = '', ms);
}

// ── CALENDAR FEEDS ───────────────────────────────────────────────
const CALS_KEY = 'kaslo_cals';
const DEFAULT_CALS = [
  { url: 'https://p162-caldav.icloud.com/published/2/MTI5MzgzNTk0MTI5MzgzNWY9cTYgK0OwIRz4UCfQYKvJY44bQNHC73gDwS5V1U5jUkV9ynvy7uipCHeNIfenut1Eq0LNCaulcS6IDwnCXzpP_iBTsC0Fc21d3SMFtSvB_1CnVBymPyAyPnzFyGkhsg', label: 'personal',  color: 'dark',   enabled: true },
  { url: 'https://p102-caldav.icloud.com/published/2/MTI5MzgzNTk0MTI5MzgzNWY9cTYgK0OwIRz4UCfQYKvnN6NvIE36DmOUbrBgiLcGN7ezhsYo-YXFxAw38AN_vpaiEFRiXefJROr9Az80VcU', label: 'personal2', color: 'medium', enabled: true },
  { url: 'https://p101-caldav.icloud.com/published/2/Mjc4Mjk1ODMxMjc4Mjk1OPiLHZnPp67Ltgtp3v229x8qT-uPdlC-Sg6bv_JZdLUiimpxJVvfu-OL9CBtnZ3CMevVIgwICabIi9WTyZIKqHA', label: 'personal3', color: 'light',  enabled: true },
];

function loadCalFeeds() {
  try {
    const s = JSON.parse(localStorage.getItem(CALS_KEY));
    if (Array.isArray(s) && s.length) return s;
  } catch(e) {}
  return JSON.parse(JSON.stringify(DEFAULT_CALS)); // deep copy
}

function saveCalFeeds(feeds) {
  localStorage.setItem(CALS_KEY, JSON.stringify(feeds));
}

function renderCalFeeds() {
  const feeds = loadCalFeeds();
  const list = document.getElementById('calFeedList');
  if (!list) return;
  list.innerHTML = '';
  const SWATCH = { light: '#aaa', medium: '#555', dark: '#111' };
  feeds.forEach((feed, i) => {
    const row = document.createElement('div');
    row.className = 'cal-feed-row' + (feed.enabled === false ? ' disabled' : '');
    row.innerHTML = `
      <div class="cal-swatch" style="background:${SWATCH[feed.color]||'#000'}"></div>
      <button class="cal-feed-toggle ${feed.enabled!==false?'on':''}"
        onclick="toggleCalFeed(${i})" title="${feed.enabled!==false?'Enabled — click to disable':'Disabled — click to enable'}">
      </button>
      <span class="cal-feed-label">${escHtml(feed.label)}</span>
      <span class="cal-feed-url" title="${escAttr(feed.url)}">${escHtml(feed.url.replace(/^https?:\/\//,'').slice(0,60))}…</span>
      <select class="cal-color-select" onchange="setCalColor(${i},this.value)" title="Colour">
        <option value="light"  ${feed.color==='light' ?'selected':''}>Light</option>
        <option value="medium" ${feed.color==='medium'?'selected':''}>Medium</option>
        <option value="dark"   ${feed.color==='dark'  ?'selected':''}>Dark</option>
      </select>
      <button class="cal-del-btn" onclick="deleteCalFeed(${i})">✕</button>`;
    list.appendChild(row);
  });
  if (!feeds.length) list.innerHTML = '<div style="padding:16px;color:#bbb;font-size:12px;text-align:center">No calendar feeds. Add one below.</div>';
}

function toggleCalFeed(i) {
  const feeds = loadCalFeeds();
  if (!feeds[i]) return;
  feeds[i].enabled = feeds[i].enabled === false ? true : false;
  saveCalFeeds(feeds); renderCalFeeds();
}

function setCalColor(i, color) {
  const feeds = loadCalFeeds();
  if (!feeds[i]) return;
  feeds[i].color = color;
  saveCalFeeds(feeds); renderCalFeeds();
}

function deleteCalFeed(i) {
  if (!confirm('Remove this calendar feed?')) return;
  const feeds = loadCalFeeds();
  feeds.splice(i, 1);
  saveCalFeeds(feeds); renderCalFeeds();
}

function addCalFeed() {
  const url   = document.getElementById('newFeedUrl').value.trim().replace(/^webcal:/,'https:');
  const label = document.getElementById('newFeedLabel').value.trim() || 'Calendar';
  const color = document.getElementById('newFeedColor').value || 'medium';
  const msg   = document.getElementById('calFeedMsg');
  if (!url || !url.startsWith('http')) { msg.textContent = 'Enter a valid https:// URL'; return; }
  const feeds = loadCalFeeds();
  if (feeds.some(f => f.url === url)) { msg.textContent = 'This feed is already added.'; return; }
  feeds.push({ url, label, color, enabled: true });
  saveCalFeeds(feeds);
  document.getElementById('newFeedUrl').value = '';
  document.getElementById('newFeedLabel').value = '';
  msg.textContent = 'Added ✓';
  setTimeout(() => msg.textContent = '', 2500);
  renderCalFeeds();
}

// ── PAGE TAB SWITCHING ────────────────────────────────────────────
function switchPage(page) {
  document.querySelectorAll('.page-tab').forEach(t => t.classList.toggle('active', t.dataset.page === page));
  document.querySelectorAll('.tab-panel').forEach(p => p.classList.toggle('active', p.id === 'panel-' + page));
  if (page === 'calendars') renderCalFeeds();
}

// ── EVENTS ────────────────────────────────────────────────────────
document.getElementById('addBtn').addEventListener('click', addItem);
document.getElementById('newText').addEventListener('keydown', e => { if(e.key==='Enter') addItem(); });
document.getElementById('newTagInput').addEventListener('keydown', e => { if(e.key==='Enter') addCustomTag(); });
document.getElementById('saveAllBtn').addEventListener('click', saveAll);

document.querySelectorAll('.priority-pills .pill').forEach(el =>
  el.addEventListener('click', () => setPriority(el.dataset.p)));
document.querySelectorAll('.recur-type-btn[data-rt]').forEach(el =>
  el.addEventListener('click', () => setRecurType(el.dataset.rt)));
document.querySelectorAll('.dow-btn[data-dow]').forEach(el =>
  el.addEventListener('click', () => {
    selectedDow = parseInt(el.dataset.dow);
    document.querySelectorAll('.dow-btn[data-dow]').forEach(b => b.classList.remove('active'));
    el.classList.add('active');
  }));
document.querySelectorAll('.filter-tab').forEach(tab =>
  tab.addEventListener('click', () => {
    currentFilter = tab.dataset.filter;
    document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
    tab.classList.add('active');
    renderList();
  }));

// ── INIT ──────────────────────────────────────────────────────────
document.querySelectorAll('.page-tab').forEach(t =>
  t.addEventListener('click', () => switchPage(t.dataset.page)));
renderTagManager();
renderNewTagPills();
loadItems();
</script>
</body>
</html>
