// To-Do list — done-set, rendering, two-column split, API fetch

/* ── TO-DO LIST (reads from todo.php) ── */
const TODO_API = window.location.origin + '/todo-api.php';
let todoRawItems = []; // all raw items — used by loadCalendar for todo→cal injection

// Priority 1(critical)→5(someday): bg, text colour, left-stripe colour, short label
const PRIORITY_STYLE = {
  1: { bg:'#fee',    color:'#b00',    stripe:'#b00',    label:'P1' },
  2: { bg:'#fff3e0', color:'#d06000', stripe:'#d06000', label:'P2' },
  3: { bg:'#f0f0f0', color:'#555',    stripe:'#888',    label:'P3' },
  4: { bg:'#f5f5f5', color:'#999',    stripe:'#bbb',    label:'P4' },
  5: { bg:'#fafafa', color:'#bbb',    stripe:'#ddd',    label:'P5' },
};
function getPriorityStyle(p) {
  return PRIORITY_STYLE[parseInt(p)] || PRIORITY_STYLE[3];
}

const DONE_KEY   = 'kaslo_done_ids';
const DONE_AT_KEY = 'kaslo_done_at';   // id→timestamp map for 24h auto-purge

// Load done set, purging any entries older than 24 h
function loadDoneSet() {
  const raw   = JSON.parse(localStorage.getItem(DONE_KEY)   || '[]');
  const atMap = JSON.parse(localStorage.getItem(DONE_AT_KEY)|| '{}');
  const now   = Date.now();
  const keep  = raw.filter(id => {
    const t = atMap[id];
    return t && (now - t) < 24*60*60*1000; // keep if < 24 h old
  });
  // Clean up stale atMap entries
  const keepSet = new Set(keep);
  Object.keys(atMap).forEach(id => { if (!keepSet.has(id)) delete atMap[id]; });
  localStorage.setItem(DONE_KEY,    JSON.stringify(keep));
  localStorage.setItem(DONE_AT_KEY, JSON.stringify(atMap));
  return new Set(keep);
}

let doneSet = loadDoneSet();

function saveDone(id, nowDone) {
  const atMap = JSON.parse(localStorage.getItem(DONE_AT_KEY)||'{}');
  if (nowDone) { atMap[id] = Date.now(); }
  else         { delete atMap[id]; }
  localStorage.setItem(DONE_AT_KEY, JSON.stringify(atMap));
  localStorage.setItem(DONE_KEY,    JSON.stringify([...doneSet]));
}

function fmtDue(due) {
  if(!due) return '';
  // Compare as date strings to avoid any timezone/DST offset shifting the day
  const todayStr = new Date().toLocaleDateString('en-CA'); // 'YYYY-MM-DD' in local time
  if(due === todayStr) {
    return ' <span style="font-size:9px;color:#e07000">Today</span>';
  }
  const todayD = new Date(todayStr + 'T12:00:00');
  const dueD   = new Date(due      + 'T12:00:00');
  const diff = Math.round((dueD - todayD) / 86400000);
  const label = diff < 0  ? Math.abs(diff) + 'd overdue'
              : diff === 1 ? 'Tomorrow'
              : dueD.toLocaleDateString('en-CA',{month:'short',day:'numeric'});
  const color = diff < 0 ? '#c00' : diff === 1 ? '#e07000' : '#aaa';
  return ' <span style="font-size:9px;color:'+color+'">' + label + '</span>';
}

function makeTodoRow(item) {
  const done = doneSet.has(item.id) || item.done;
  const tags = Array.isArray(item.tags) && item.tags.length > 0 ? item.tags : null;
  const tagParts = tags
    ? tags.map(t => `<span style="background:#222;color:#fff;font-size:7px;font-weight:700;padding:1px 4px;border-radius:1px;letter-spacing:0.5px">${escHtml(t)}</span>`).join(' ')
    : '';
  const duePart = item.due ? fmtDue(item.due) : '';
  const metaHtml = (tagParts || duePart) ? `<div class="todo-item-meta">${tagParts}${tagParts&&duePart?' ':''}${duePart}</div>` : '';
  const notesHtml = item.notes ? `<div class="todo-item-meta">${escHtml(item.notes)}</div>` : '';
  const row = document.createElement('div');
  row.className = `todo-item${done ? ' done-item' : ''}`;
  if (done) row.style.opacity = '0.4';
  row.innerHTML = `<div class="todo-item-text${done?' todo-text done':''}">${escHtml(item.text)}</div>${metaHtml}${notesHtml}`;
  return row;
}

function renderTodoItems(listItems){
  const colUrgent = document.getElementById('todoColUrgent');
  const colRest   = document.getElementById('todoColRest');
  if (!colUrgent || !colRest) return; // guard

  colUrgent.innerHTML = '';
  colRest.innerHTML   = '';

  if (!listItems.length) {
    colUrgent.innerHTML = '<div style="font-size:10px;color:#ccc;font-style:italic;padding:4px 0">All clear ✓</div>';
    return;
  }

  // Left column: priority 1–2 (Important/Urgent) OR due within 3 days (including overdue)
  // Right column: everything else, sorted by priority then text
  const todayStr = new Date().toLocaleDateString('en-CA');
  const todayD   = new Date(todayStr + 'T12:00:00');
  const in3Days  = new Date(todayD); in3Days.setDate(todayD.getDate() + 3);

  function isUrgentLeft(item) {
    const pri = parseInt(item.priority) || 5;
    if (pri <= 2) return true;
    if (item.due) {
      const dueD = new Date(item.due + 'T12:00:00');
      if (dueD <= in3Days) return true; // due today, tomorrow, day after, or overdue
    }
    return false;
  }

  const urgent = [], rest = [];
  listItems.forEach(item => {
    (isUrgentLeft(item) ? urgent : rest).push(item);
  });

  // Sort each: priority asc, then text asc
  const byPriTxt = (a,b) => {
    const pa = parseInt(a.priority)||5, pb = parseInt(b.priority)||5;
    if (pa !== pb) return pa - pb;
    return String(a.text).localeCompare(b.text);
  };
  urgent.sort((a,b) => {
    // Within urgent: overdue first, then due-soonest, then by priority
    const da = a.due ? new Date(a.due+'T12:00:00') : null;
    const db = b.due ? new Date(b.due+'T12:00:00') : null;
    if (da && db) return da - db;
    if (da && !db) return -1;
    if (!da && db) return 1;
    return byPriTxt(a,b);
  });
  rest.sort(byPriTxt);

  if (urgent.length === 0) {
    colUrgent.innerHTML = '<div style="font-size:9px;color:#ccc;font-style:italic;padding:4px 0">Nothing urgent</div>';
  } else {
    urgent.forEach(item => colUrgent.appendChild(makeTodoRow(item)));
  }
  rest.forEach(item => colRest.appendChild(makeTodoRow(item)));

  // Mirror to mobile (flatten all items by priority for mobile single-col)
  const mobileList = document.getElementById('mobileTodoList');
  if (mobileList) {
    mobileList.innerHTML = '';
    [...urgent, ...rest].forEach(item => mobileList.appendChild(makeTodoRow(item)));
  }
}
// escHtml() is defined in utils.js

function toggleDone(id, el){
  const nowDone = !doneSet.has(id);
  if (nowDone) doneSet.add(id); else doneSet.delete(id);
  saveDone(id, nowDone);
  const row = el.closest('.todo-item');
  el.classList.toggle('done');
  el.nextElementSibling.classList.toggle('done');
  row.style.opacity = nowDone ? '0.4' : '';
}

async function loadTodos(){
  try {
    // Try ?action=raw first (returns full unfiltered list for calendar injection)
    let allItems = null;
    const r = await fetch(TODO_API + '?t=' + Date.now(), {credentials:'include'});
    if (r.status === 401) throw new Error('session_expired');
    if (!r.ok) throw new Error('todo-api HTTP ' + r.status);
    const data = await r.json();
    if (!Array.isArray(data)) throw new Error('Unexpected response from todo-api');
    allItems = data;

    todoRawItems = allItems; // store ALL items for calendar injection

    // For the todo panel: filter out done, and recurring items (they go to calendar)
    const todayLocal = new Date().toLocaleDateString('en-CA'); // YYYY-MM-DD local
    const calDateSet = new Set(forecastDates);
    const displayItems = allItems.filter(item => {
      if (item.done) return false;
      if (item.recurWeekday != null && item.recurWeekday !== '') return false; // in calendar
      if (item.recurDay     != null && item.recurDay     !== '') return false; // in calendar
      if (item.due && calDateSet.has(item.due)) return false; // due-in-window → calendar
      return true;
    });

    renderTodoItems(displayItems);
  } catch(e){
    console.error('loadTodos failed:', e.message);
    const expired = e.message === 'session_expired';
    document.getElementById('todoList').innerHTML = expired
      ? '<div style="font-size:9px;color:#c00;font-style:italic"><a href="/login.php" style="color:#c00">Session expired — log in</a></div>'
      : '<div style="font-size:9px;color:#ccc;font-style:italic">List unavailable</div>';
  }
}

// loadTodos is called after forecast+calendar in the init block below
// Calendar refreshes always (public feeds); todos only when authed
setInterval(async()=>{ await loadCalendar(); }, 10*60*1000); // calendar every 10 min
if (IS_AUTHED) setInterval(async()=>{ doneSet = loadDoneSet(); await loadTodos(); }, 5*60*1000); // todos every 5 min
