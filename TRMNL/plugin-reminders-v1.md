# Plugin: Kaslo Reminders (v1 — iPhone Webhook via TRMNL Companion)
#
# Strategy: WEBHOOK  (not Polling)
# Verb: POST
# Remove bleed margin: Yes
#
# Variables confirmed from TRMNL Companion:
#   r.title     — reminder title
#   r.due_date  — ISO 8601 string e.g. "2026-05-26T01:30:00.000Z"
#   r.priority  — 0=none, 1=high, 5=medium, 9=low  (Apple EventKit)
#   r.notes     — notes field (often empty)
#   r.list      — NOT sent by Companion (blank)
#
# Classification (done in JS, Liquid can't do date math):
#   URGENT     — priority=1  OR  due within 3 days / overdue
#   IMPORTANT  — priority=5  OR  due within 7 days (not already urgent)
#   REST       — everything else
#
# Layout:
#   LEFT  50% — URGENT (full height, large font)
#   RIGHT 50% — top half: IMPORTANT / bottom half: EVERYTHING ELSE
#
# IMPORTANT: Paste ONLY the HTML below (starting with <style>) into the
# TRMNL "Markup" tab. Do NOT paste this header or the ``` fences.

## Markup tab

```html
<style>
*{box-sizing:border-box;margin:0;padding:0}
html,body{width:100vw;height:100vh;overflow:hidden;background:#fff;color:#000}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;display:flex;flex-direction:column}

.hdr{height:46px;display:flex;justify-content:space-between;align-items:center;padding:0 14px;border-bottom:3px solid #000;flex-shrink:0}
.hdr-t{font-weight:800;font-size:15px;letter-spacing:.05em}
.hdr-r{font-size:12px;color:#666}

.body{display:flex;flex:1;min-height:0}

.lcol{width:50%;display:flex;flex-direction:column;border-right:3px solid #000;overflow:hidden}
.rcol{width:50%;display:flex;flex-direction:column;overflow:hidden}
.rsec{flex:1;display:flex;flex-direction:column;min-height:0;overflow:hidden}
.rsec+.rsec{border-top:2px solid #000}

.hd{font-size:18px;font-weight:900;text-transform:uppercase;letter-spacing:.15em;padding:5px 13px;flex-shrink:0;background:#111;color:#fff}

.ul,.il,.rl{display:flex;flex-direction:column;flex:1;min-height:0;overflow:hidden}

.ui{flex:1 1 auto;min-height:32px;padding:4px 5px;display:flex;flex-direction:column;justify-content:center}
.ui.z{background:#f2f2f2}
.ut{font-size:46px;font-weight:800;line-height:1}
.um{font-size:14px;color:white;background-color:darkgray;font-weight:500;margin-top:3px}

.ii{flex:1 1 auto;min-height:36px;padding:5px 13px;display:flex;flex-direction:column;justify-content:center}
.ii.z{background:#f2f2f2}
.it::first-line{font-size:36px;font-weight:600;line-height:1.25}
.it{font-size:20px;font-weight:300}
.im{font-size:11px;color:#888;margin-top:2px}

.ri{flex:1 1 auto;min-height:28px;padding:4px 13px;display:flex;flex-direction:column;justify-content:center}
.ri.z{background:#f2f2f2}
.rt::first-line{font-size:36px;font-weight:700}
.rt{font-size:30px;font-weight:500;line-height:1.2}
.rm{font-size:10px;color:darkgray;margin-top:1px}

.empty{font-size:14px;color:#bbb;font-style:italic;padding:14px 13px}
.more{font-size:11px;color:#aaa;padding:3px 13px;flex-shrink:0}
</style>

<!-- Raw reminder data — hidden, consumed by JS below -->
{% for r in reminders %}
<div class="rd"
  data-title="{{ r.title }}"
  data-due="{{ r.due_date }}"
  data-pri="{{ r.priority }}"
  data-notes="{{ r.notes }}"
  style="display:none"></div>
{% endfor %}

<div class="hdr">
  <div class="hdr-t">REMINDERS &middot; KASLO</div>
  <div class="hdr-r"><span id="hdr-count">–</span> open &middot; {{ trmnl.user.time | date: "%H:%M %a %-d" }}</div>
</div>

<div class="body">
  <div class="lcol">
    <div class="hd">Urgent</div>
    <div class="ul" id="ul"></div>
  </div>
  <div class="rcol">
    <div class="rsec">
      <div class="hd">Important</div>
      <div class="il" id="il"></div>
    </div>
    <div class="rsec">
      <div class="hd">Everything Else</div>
      <div class="rl" id="rl"></div>
    </div>
  </div>
</div>

<script>
(function(){
  // ── Read reminder data from hidden DOM elements ──────────────────────────
  var items = [];
  document.querySelectorAll('.rd').forEach(function(el){
    items.push({
      title : el.getAttribute('data-title') || '',
      due   : el.getAttribute('data-due')   || '',
      pri   : parseInt(el.getAttribute('data-pri')) || 0,
      notes : el.getAttribute('data-notes') || ''
    });
  });

  // ── Date helpers ──────────────────────────────────────────────────────────
  var today = new Date();
  today.setHours(0,0,0,0);

  function daysUntil(isoStr){
    if(!isoStr) return null;
    var d = new Date(isoStr);
    if(isNaN(d)) return null;
    d.setHours(0,0,0,0);
    return Math.round((d - today) / 86400000);
  }

  function fmtDue(isoStr){
    var days = daysUntil(isoStr);
    if(days === null) return '';
    if(days < 0)  return Math.abs(days) + 'd overdue';
    if(days === 0) return 'Today';
    if(days === 1) return 'Tomorrow';
    var d = new Date(isoStr);
    var mo = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    return mo[d.getMonth()] + ' ' + d.getDate();
  }

  // ── Classify ──────────────────────────────────────────────────────────────
  var urgent=[], important=[], rest=[];
  items.forEach(function(item){
    var days = daysUntil(item.due);
    if(item.pri === 1 || (days !== null && days <= 3)){
      urgent.push(item);
    } else if(item.pri === 5 || (days !== null && days <= 7)){
      important.push(item);
    } else {
      rest.push(item);
    }
  });

  // ── Sort: soonest first, then alpha ───────────────────────────────────────
  function byDue(a,b){
    var da=daysUntil(a.due), db=daysUntil(b.due);
    if(da!==null && db!==null) return da-db;
    if(da!==null) return -1;
    if(db!==null) return  1;
    return a.title.localeCompare(b.title);
  }
  urgent.sort(byDue);
  important.sort(byDue);
  rest.sort(byDue);

  // ── Render helpers ────────────────────────────────────────────────────────
  function esc(s){ return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

  function renderList(arr, rowCls, txtCls, metaCls, max, emptyMsg){
    if(!arr.length) return '<div class="empty">' + emptyMsg + '</div>';
    var html = '';
    var cap  = Math.min(arr.length, max);
    for(var i=0; i<cap; i++){
      var item = arr[i];
      var z    = i%2===1 ? ' z' : '';
      var meta = fmtDue(item.due);
      html += '<div class="'+rowCls+z+'">';
      html += '<div class="'+txtCls+'">'+esc(item.title)+'</div>';
      if(meta) html += '<div class="'+metaCls+'">'+esc(meta)+'</div>';
      html += '</div>';
    }
    if(arr.length > cap){
      html += '<div class="more">+' + (arr.length-cap) + ' more</div>';
    }
    return html;
  }

  // ── Inject ────────────────────────────────────────────────────────────────
  document.getElementById('ul').innerHTML =
    renderList(urgent,    'ui','ut','um', 4, 'Nothing urgent — good work');
  document.getElementById('il').innerHTML =
    renderList(important, 'ii','it','im', 6, 'Nothing here');
  document.getElementById('rl').innerHTML =
    renderList(rest,      'ri','rt','rm', 8, 'All clear');

  document.getElementById('hdr-count').textContent = items.length;
})();
</script>
```
