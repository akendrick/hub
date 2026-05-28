# Plugin: Reminders (v2 — two-column, priority-first)
#
# Strategy: WEBHOOK  (TRMNL Companion → Apple Reminders)
# Verb: POST  |  Remove bleed margin: Yes
#
# Layout
#   LEFT  400 px — first 6 items in priority order, emphasized style
#                  (large bold title + small light meta line)
#                  Groups separated by a ruled divider only when they change.
#                  If <6 important/urgent items, rest items fill the gap.
#   RIGHT 400 px — remaining items, compact two-line style, as many as fit.
#
# Section dividers appear BETWEEN group transitions only (never before the
# first item in a column).  A group that produces no visible items in that
# column shows no divider at all.
#
# IMPORTANT: paste ONLY the HTML block (from <style> to </script>) into the
# TRMNL "Markup" tab.

## Markup tab

```html
<style>
*{box-sizing:border-box;margin:0;padding:0}
html,body{width:800px;height:480px;overflow:hidden;background:#fff;color:#000}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;display:flex;flex-direction:column}

/* ── Header ── */
.hdr{height:38px;flex-shrink:0;display:flex;align-items:center;justify-content:space-between;padding:0 14px;border-bottom:2px solid #000}
.hdr-t{font-weight:800;font-size:15px;letter-spacing:.06em}
.badge{display:inline-block;background:#111;color:#fff;font-size:10px;font-weight:700;padding:2px 7px;border-radius:2px;margin-left:7px;vertical-align:1px}
.hdr-r{font-size:12px;color:#777}

/* ── Two-column body ── */
.body{display:flex;flex:1;min-height:0;overflow:hidden}

/* Left: emphasized items */
.col1{flex:0 0 400px;display:flex;flex-direction:column;overflow:hidden;border-right:1px solid #ddd;padding:4px 0 0}

/* Right: compact items */
.col2{flex:1;display:flex;flex-direction:column;overflow:hidden;padding:4px 0 0}

/* ── Section divider — only appears between group transitions ── */
.sep{display:flex;align-items:center;gap:7px;padding:6px 14px 4px;flex-shrink:0}
.sep-ln{flex:1;height:1px;background:#e4e4e4}
.sep-t{font-size:8px;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:#bbb;white-space:nowrap}

/* ── Emphasized item (col1 — first 6) ── */
.ibig{padding:6px 14px 5px;border-bottom:1px solid #f0f0f0;flex-shrink:0}
.ibig:last-child{border-bottom:none}
/* Primary line: large and dark */
.ib-t{font-size:16px;font-weight:700;color:#111;line-height:1.25;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
/* Secondary line: much smaller and lighter */
.ib-m{font-size:10px;color:#aaa;margin-top:3px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
/* Urgent variant — slightly heavier */
.ibig.urg .ib-t{font-weight:800;color:#000}
.ibig.urg .ib-m{color:#c44}

/* ── Compact item (col2 — remaining) ── */
.ism{padding:5px 14px 4px;border-bottom:1px solid #f5f5f5;flex-shrink:0}
.ism:last-child{border-bottom:none}
.is-t{font-size:13px;font-weight:600;color:#333;line-height:1.25;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.is-m{font-size:9px;color:#bbb;margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}

/* ── All-clear full-width ── */
.all-clear{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:6px}
.ac-big{font-size:36px;font-weight:200;color:#ddd;line-height:1}
.ac-sub{font-size:13px;color:#ccc}
</style>

<!-- Reminder data from TRMNL Companion webhook -->
{% for r in reminders %}
<div class="rd" data-t="{{ r.title | escape }}" data-d="{{ r.due_date }}" data-p="{{ r.priority }}" style="display:none"></div>
{% endfor %}

<div class="hdr">
  <div class="hdr-t">REMINDERS<span id="hdr-badge" class="badge" style="display:none">0</span></div>
  <div class="hdr-r">{{ trmnl.user.time | date: "%H:%M %a %-d" }}</div>
</div>

<div class="body">
  <div class="col1" id="col1"></div>
  <div class="col2" id="col2"></div>
</div>

<script>
(function(){

// ════════════════════════════════════════════════════════════════════
// 1. Read reminder data from hidden DOM elements
// ════════════════════════════════════════════════════════════════════
var els=document.querySelectorAll('.rd');
var raw=[];
for(var i=0;i<els.length;i++){
  raw.push({
    t: els[i].getAttribute('data-t')||'',
    d: els[i].getAttribute('data-d')||'',
    p: parseInt(els[i].getAttribute('data-p'))||0
  });
}

// ════════════════════════════════════════════════════════════════════
// 2. Date helpers
// ════════════════════════════════════════════════════════════════════
var today=new Date(); today.setHours(0,0,0,0);
function daysUntil(s){
  if(!s) return null;
  var d=new Date(s); if(isNaN(d)) return null;
  d.setHours(0,0,0,0);
  return Math.round((d-today)/86400000);
}
function fmtDue(s){
  var n=daysUntil(s); if(n===null) return '';
  if(n<0)  return Math.abs(n)+'d overdue';
  if(n===0) return 'Today';
  if(n===1) return 'Tomorrow';
  var d=new Date(s);
  var M=['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
  return M[d.getMonth()]+' '+d.getDate();
}

// ════════════════════════════════════════════════════════════════════
// 3. Classify and sort
//    U = urgent   (priority 1 OR due ≤ 3 days / overdue)
//    I = important (priority 5 OR due ≤ 7 days)
//    R = rest
// ════════════════════════════════════════════════════════════════════
var U=[],I=[],R=[];
raw.forEach(function(x){
  var n=daysUntil(x.d), g;
  if(x.p===1||(n!==null&&n<=3)) g='U';
  else if(x.p===5||(n!==null&&n<=7)) g='I';
  else g='R';
  var obj={t:x.t, m:fmtDue(x.d), g:g, n:n};
  if(g==='U') U.push(obj);
  else if(g==='I') I.push(obj);
  else R.push(obj);
});

function cmp(a,b){
  if(a.n!==null&&b.n!==null) return a.n-b.n;
  if(a.n!==null) return -1;
  if(b.n!==null) return  1;
  return a.t.localeCompare(b.t);
}
U.sort(cmp); I.sort(cmp); R.sort(cmp);

var ALL=U.concat(I).concat(R);

// ════════════════════════════════════════════════════════════════════
// 4. Header count
// ════════════════════════════════════════════════════════════════════
if(ALL.length){
  var b=document.getElementById('hdr-badge');
  b.textContent=ALL.length; b.style.display='';
}

// ════════════════════════════════════════════════════════════════════
// 5. Render helpers
// ════════════════════════════════════════════════════════════════════
function esc(s){
  return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

var GLABEL={U:'Urgent',I:'Important',R:'Other'};

// Renders an array of items with group-transition dividers.
// big=true → emphasized col1 style; big=false → compact col2 style.
function render(arr, big){
  if(!arr.length) return '';
  var html='', lastG=null;
  for(var i=0;i<arr.length;i++){
    var item=arr[i];
    // Divider only on group change — and never before the very first item
    if(item.g!==lastG && lastG!==null){
      html+='<div class="sep">'
        +'<span class="sep-ln"></span>'
        +'<span class="sep-t">'+GLABEL[item.g]+'</span>'
        +'<span class="sep-ln"></span>'
        +'</div>';
    }
    lastG=item.g;

    if(big){
      html+='<div class="ibig'+(item.g==='U'?' urg':'')+'">'
        +'<div class="ib-t">'+esc(item.t)+'</div>'
        +(item.m?'<div class="ib-m">'+esc(item.m)+'</div>':'')
        +'</div>';
    } else {
      html+='<div class="ism">'
        +'<div class="is-t">'+esc(item.t)+'</div>'
        +(item.m?'<div class="is-m">'+esc(item.m)+'</div>':'')
        +'</div>';
    }
  }
  return html;
}

// ════════════════════════════════════════════════════════════════════
// 6. Split and inject
//    Col1 = first 6 items (priority order: urgent → important → rest)
//    Col2 = everything beyond the first 6
// ════════════════════════════════════════════════════════════════════
var c1=document.getElementById('col1');
var c2=document.getElementById('col2');

if(ALL.length===0){
  // Replace the entire two-column body with a centred all-clear
  document.querySelector('.body').innerHTML=
    '<div class="all-clear"><div class="ac-big">✓</div><div class="ac-sub">All clear — nothing open</div></div>';
} else {
  c1.innerHTML=render(ALL.slice(0,6), true);
  c2.innerHTML=render(ALL.slice(6),   false);
}

})();
</script>
```

---

## What the columns show

```
┌──────────────────────────────────┬──────────────────────────────────┐
│ Item 1 (big, bold title)         │ Item 7  (compact)                 │
│ meta — Today · TAG               │ meta                              │
│ Item 2 (big)                     │ Item 8  (compact)                 │
│ meta                             │ Item 9                            │
│ ─────── IMPORTANT ───────        │ ─────── OTHER ───────             │
│ Item 3 (big, from Important)     │ Item 10                           │
│ meta                             │ Item 11                           │
│ Item 4 (big)                     │ …as many as fit (~10–11 items)    │
│ Item 5 (big)                     │                                   │
│ ─────── OTHER ───────            │                                   │
│ Item 6 (big, rest fills gap)     │                                   │
└──────────────────────────────────┴──────────────────────────────────┘
```

**Rules:**
- Items 1–6 always use the big style regardless of which group they come from
- Dividers appear only at group transitions, never before the first item
- If a group produces no items visible in that column, its label never appears
- If there are ≤ 6 items total, col2 is blank (no placeholder text)
- If there are 0 items, the two columns are replaced by a full-width "All clear" message

**Urgent items** (priority 1 or due ≤ 3 days) get slightly heavier weight and a red meta line so they stand out even when mixed into the big-style list.
