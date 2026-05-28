# Plugin: Reminders (v3 — two-column, priority-first, larger fonts + zebra)
#
# Strategy: WEBHOOK  (TRMNL Companion → Apple Reminders)
# Verb: POST  |  Remove bleed margin: Yes
#
# Changes from v2:
#   • Removed hardcoded width:800px / height:480px — fixes right-column clipping
#     on TRMNL's renderer (same root cause as DGS v3 → v4 fix)
#   • Font sizes roughly doubled throughout
#   • Zebra striping on every second item in both columns
#   • Column split is 50/50 (percentage, not fixed px)
#
# Layout
#   LEFT  50% — first 6 items in priority order, emphasized style
#   RIGHT 50% — remaining items, compact two-line style
#
# IMPORTANT: paste ONLY the HTML block (from <style> to </script>) into the
# TRMNL "Markup" tab.

## Markup tab

```html
<style>
*{box-sizing:border-box;margin:0;padding:0}
html,body{width:100%;height:100%;overflow:hidden;background:#fff;color:#000}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;display:flex;flex-direction:column}

/* ── Header ── */
.hdr{height:38px;flex-shrink:0;display:flex;align-items:center;justify-content:space-between;padding:0 14px;border-bottom:2px solid #000}
.hdr-t{font-weight:800;font-size:15px;letter-spacing:.06em}
.badge{display:inline-block;background:#111;color:#fff;font-size:10px;font-weight:700;padding:2px 7px;border-radius:2px;margin-left:7px;vertical-align:1px}
.hdr-r{font-size:12px;color:#777}

/* ── Two-column body ── */
.body{display:flex;flex:1;min-height:0;overflow:hidden}

/* Left: emphasized items — 50% width */
.col1{flex:0 0 50%;display:flex;flex-direction:column;overflow:hidden;border-right:1px solid #ddd;padding:4px 0 0}

/* Right: compact items — remaining space */
.col2{flex:1;display:flex;flex-direction:column;overflow:hidden;padding:4px 0 0}

/* ── Section divider — only appears between group transitions ── */
.sep{display:flex;align-items:center;gap:7px;padding:5px 14px 3px;flex-shrink:0}
.sep-ln{flex:1;height:1px;background:#e4e4e4}
.sep-t{font-size:9px;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:#bbb;white-space:nowrap}

/* ── Emphasized item (col1 — first 6) ── */
.ibig{padding:6px 14px 5px;border-bottom:1px solid #f0f0f0;flex-shrink:0}
.ibig:last-child{border-bottom:none}
/* Zebra stripe — every second item */
.ibig.zb{background:#f7f7f7}
/* Primary line: large and dark */
.ib-t{font-size:32px;font-weight:700;color:#111;line-height:1.15;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
/* Secondary line: lighter and smaller */
.ib-m{font-size:18px;color:#aaa;margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
/* Urgent variant */
.ibig.urg .ib-t{font-weight:800;color:#000}
.ibig.urg .ib-m{color:#c44}

/* ── Compact item (col2 — remaining) ── */
.ism{padding:5px 14px 4px;border-bottom:1px solid #f5f5f5;flex-shrink:0}
.ism:last-child{border-bottom:none}
/* Zebra stripe */
.ism.zb{background:#f7f7f7}
.is-t{font-size:22px;font-weight:600;color:#333;line-height:1.2;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.is-m{font-size:14px;color:#bbb;margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}

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
// 2. LAYOUT — stamp explicit pixel column widths from actual viewport.
//    Percentage flex-basis (50%) can silently collapse to 0 in some
//    headless renderers if the parent width isn't explicitly resolved.
//    Stamping real px values avoids that entirely.
// ════════════════════════════════════════════════════════════════════
var VW = window.innerWidth || 800;
var col1W = Math.floor(VW / 2);
var col2W = VW - col1W;
var c1el = document.getElementById('col1');
var c2el = document.getElementById('col2');
if(c1el){ c1el.style.flex='0 0 '+col1W+'px'; c1el.style.width=col1W+'px'; }
if(c2el){ c2el.style.flex='0 0 '+col2W+'px'; c2el.style.width=col2W+'px'; }

// ════════════════════════════════════════════════════════════════════
// 3. Date helpers
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
// 4. Classify and sort
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
// 5. Header count
// ════════════════════════════════════════════════════════════════════
if(ALL.length){
  var b=document.getElementById('hdr-badge');
  b.textContent=ALL.length; b.style.display='';
}

// ════════════════════════════════════════════════════════════════════
// 6. Render helpers
// ════════════════════════════════════════════════════════════════════
function esc(s){
  return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

var GLABEL={U:'Urgent',I:'Important',R:'Other'};

// Renders an array of items with group-transition dividers.
// big=true → emphasized col1 style; big=false → compact col2 style.
// Zebra stripe (.zb) applied to every second item (0-indexed even = no stripe).
function render(arr, big){
  if(!arr.length) return '';
  var html='', lastG=null, itemIdx=0;
  for(var i=0;i<arr.length;i++){
    var item=arr[i];
    // Divider only on group change — and never before the very first item
    if(item.g!==lastG && lastG!==null){
      html+='<div class="sep">'
        +'<span class="sep-ln"></span>'
        +'<span class="sep-t">'+GLABEL[item.g]+'</span>'
        +'<span class="sep-ln"></span>'
        +'</div>';
      itemIdx=0; // reset zebra counter at each section
    }
    lastG=item.g;
    var zb=itemIdx%2===1?' zb':'';
    itemIdx++;

    if(big){
      html+='<div class="ibig'+(item.g==='U'?' urg':'')+zb+'">'
        +'<div class="ib-t">'+esc(item.t)+'</div>'
        +(item.m?'<div class="ib-m">'+esc(item.m)+'</div>':'')
        +'</div>';
    } else {
      html+='<div class="ism'+zb+'">'
        +'<div class="is-t">'+esc(item.t)+'</div>'
        +(item.m?'<div class="is-m">'+esc(item.m)+'</div>':'')
        +'</div>';
    }
  }
  return html;
}

// ════════════════════════════════════════════════════════════════════
// 7. Split and inject
//    Col1 = first 6 items (priority order: urgent → important → rest)
//    Col2 = everything beyond the first 6
// ════════════════════════════════════════════════════════════════════
var c1=document.getElementById('col1');
var c2=document.getElementById('col2');

if(ALL.length===0){
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

## What changed from v2

| v2 | v3 |
|---|---|
| `html,body{width:800px;height:480px}` | `html,body{width:100%;height:100%}` — fixes right-column clipping |
| `.col1{flex:0 0 400px}` | `.col1{flex:0 0 50%}` — percentage split survives any viewport size |
| `.ib-t` 16px | `.ib-t` 32px |
| `.ib-m` 10px | `.ib-m` 18px |
| `.is-t` 13px | `.is-t` 22px |
| `.is-m` 9px | `.is-m` 14px |
| No zebra | `.zb{background:#f7f7f7}` on every second item, resets at section dividers |

## What the columns show

```
┌──────────────────────────────────┬──────────────────────────────────┐
│ Photo Supports                   │ Get batteries                     │
│ 1d overdue                       │ Jun 2                             │
│▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒│▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒│
│ E-Paper connectors               │ Bring sunscreen                   │ ← zebra
│ 1d overdue                       │ Jun 4                             │
│────────── IMPORTANT ─────────── │ Snow tires                        │
│ Buy underwear                    │ Tomato seeds                      │
│ Jun 4                            │▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒│
│▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒│ …                                │
│ Fix tomato trellis               │                                   │
│ —                                │                                   │
│────────── OTHER ────────────────│                                   │
│ Sort car park                    │                                   │
│ —                                │                                   │
└──────────────────────────────────┴──────────────────────────────────┘
```

Zebra counter resets at each section divider so the stripe rhythm stays
visually clean within each priority group.
