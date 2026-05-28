# Plugin: Go Games · DGS (v2 — 1/3 game list + 2/3 live board)
#
# PHP backend: dgs-device-api.php
#   Config: /config/dgs-config.json
#   { "uid":"24738", "username":"Shrimphead", "password":"...", "device_key":"..." }
#   Password is optional — game list is public; SGF of ongoing games requires login.
#
# Polling URL:
#   https://knotwork.ca/dgs-device-api.php?key=YOUR_DEVICE_KEY
#
# Variables:
#   total, my_turn_count
#   g0_id … g9_id, g0_opp … g9_opp, g0_col … g9_col (B/W = your colour)
#   g0_moves … g9_moves, g0_yours … g9_yours (1=your move), g0_size … g9_size
#   board_gid, board_opp, board_col, board_moves, board_size, board_yours
#   board_sgf  — full SGF text of oldest game (requires DGS credentials in config)
#   board_error — "auth_required" when SGF couldn't be fetched
#
# Layout:
#   LEFT  1/3  — all running games list (oldest first)
#   RIGHT 2/3  — board for oldest game, rendered from SGF by inline JS
#
# Board renderer: pure JS + SVG, no external libraries.
#   Parses SGF, applies stone placement + captures, renders B&W board.
#   Sun position dot marks last move.
#   jGoBoard / WGo.js are external alternatives but e-ink B&W SVG is cleaner.
#
# Settings: Strategy: Polling | Verb: GET | Remove bleed margin: Yes
#   Polling interval: 15 min minimum (DGS request — correspondence server)
#
# IMPORTANT: Paste ONLY the HTML below (starting with <style>) into the
# TRMNL "Full" markup tab. Do NOT paste this header or the ``` fences.

## Markup (Full tab)

```html
<style>
*{box-sizing:border-box;margin:0;padding:0}
html,body{width:800px;height:480px;overflow:hidden;background:#fff;color:#000}
body{
  font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;
  display:grid;
  grid-template-rows:36px 1fr;
  height:480px;
}

/* ── Header ── */
.hdr{display:flex;justify-content:space-between;align-items:center;padding:0 14px;border-bottom:2px solid #000}
.hdr-t{font-weight:800;font-size:18px;letter-spacing:.04em}
.hdr-c{font-size:13px;color:#555}
.hdr-r{font-size:13px;color:#666}
.badge{display:inline-block;background:#000;color:#fff;font-size:11px;font-weight:700;padding:2px 7px;border-radius:2px;margin-left:6px;vertical-align:1px}

/* ── Content: game list | main board | two side boards ── */
.content{
  display:grid;
  grid-template-columns:220px 1fr 196px;
  height:444px;           /* 480 - 36px header */
  overflow:hidden;
}

/* ── LEFT: Game list ── */
.game-list{display:flex;flex-direction:column;border-right:2px solid #000;overflow:hidden;height:444px}
.list-hdr{
  font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.14em;
  color:#bbb;padding:4px 10px;border-bottom:1px solid #ddd;flex-shrink:0;
  display:flex;justify-content:space-between;
}
.games{display:flex;flex-direction:column;flex:1;min-height:0;overflow:hidden}

/* Game rows */
.gr{
  flex:1 1 auto;min-height:28px;
  display:flex;align-items:center;
  padding:2px 8px;gap:5px;
  border-bottom:1px solid #f2f2f2;overflow:hidden;
}
.gr:nth-child(even){background:#f7f7f7}
.gr.yours{background:#111;color:#fff}
.td{width:7px;height:7px;border-radius:50%;flex-shrink:0;background:#ccc}
.gr.yours .td{background:#fff}
.gr.active .td{background:#000}
.gr.yours.active .td{background:#fff}
.go{flex:1;font-size:13px;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;min-width:0}
.gr.yours .go{font-weight:700}
.gc{width:14px;height:14px;border-radius:50%;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:8px;font-weight:900}
.gc.b{background:#111;color:#fff}
.gc.w{background:#fff;color:#111;border:1.5px solid #555}
.gr.yours .gc.b{background:#eee;color:#111}
.gr.yours .gc.w{background:#333;color:#fff;border-color:#888}
.gm{font-size:10px;color:#999;flex-shrink:0;white-space:nowrap}
.gr.yours .gm{color:#aaa}
.gs{font-size:9px;font-weight:700;color:#ccc;flex-shrink:0}
.gr.yours .gs{color:#777}

/* ── CENTRE: Main board ── */
.board-main{
  display:flex;flex-direction:column;
  border-left:1px solid #e0e0e0;border-right:1px solid #e0e0e0;
  height:444px;overflow:hidden;
}
.bm-meta{
  font-size:11px;color:#888;flex-shrink:0;text-align:center;
  padding:3px 8px 2px;border-bottom:1px solid #eee;
  white-space:nowrap;overflow:hidden;text-overflow:ellipsis;
}
.bm-meta b{color:#111}
.bm-meta .your-move{color:#000;font-weight:800}

/* Board wrap — fills remaining height; SVG uses viewBox so it scales */
.bw{
  flex:1;min-height:0;
  display:flex;align-items:center;justify-content:center;
  overflow:hidden;background:#fff;
}
.bw svg{display:block;width:100%;height:100%}

/* ── RIGHT: Two side boards stacked ── */
.boards-side{
  display:grid;grid-template-rows:1fr 1fr;
  height:444px;overflow:hidden;
}
.bs-item{
  display:flex;flex-direction:column;
  overflow:hidden;
}
.bs-item+.bs-item{border-top:2px solid #ccc}
.bs-meta{
  font-size:10px;font-weight:700;color:#666;flex-shrink:0;
  padding:2px 6px;border-bottom:1px solid #eee;
  white-space:nowrap;overflow:hidden;text-overflow:ellipsis;
  display:flex;align-items:center;gap:4px;
}
.bs-meta .gc{width:12px;height:12px;font-size:7px}
.bs-meta .ym{color:#000;font-weight:900}
.no-board{font-size:11px;color:#bbb;font-style:italic;padding:10px 6px}
</style>

<!-- Hidden SGF data for all three boards -->
<pre id="sgf-0" style="display:none">{{ board_sgf }}</pre>
<pre id="sgf-1" style="display:none">{{ board1_sgf }}</pre>
<pre id="sgf-2" style="display:none">{{ board2_sgf }}</pre>

<div class="hdr">
  <div class="hdr-t">DGS &middot; Shrimphead</div>
  <div class="hdr-c">
    {{ total }} game{% if total != '1' %}s{% endif %}
    {% if my_turn_count != '0' %}<span class="badge">{{ my_turn_count }} to move</span>{% endif %}
  </div>
  <div class="hdr-r">{{ trmnl.user.time | date: "%H:%M %a %-d" }}</div>
</div>

<div class="content">

  <!-- ── Left: game list ── -->
  <div class="game-list">
    <div class="list-hdr"><span>Game</span><span>Mv</span></div>
    <div class="games">
      {% if g0_opp %}<div class="gr{% if g0_yours == '1' %} yours active{% endif %}"><div class="td"></div><div class="go">{{ g0_opp }}</div><div class="gc {% if g0_col == 'B' %}b{% else %}w{% endif %}">{{ g0_col }}</div><div class="gm">{{ g0_moves }}</div>{% if g0_size != '19' %}<div class="gs">{{ g0_size }}</div>{% endif %}</div>{% endif %}
      {% if g1_opp %}<div class="gr{% if g1_yours == '1' %} yours active{% endif %}"><div class="td"></div><div class="go">{{ g1_opp }}</div><div class="gc {% if g1_col == 'B' %}b{% else %}w{% endif %}">{{ g1_col }}</div><div class="gm">{{ g1_moves }}</div>{% if g1_size != '19' %}<div class="gs">{{ g1_size }}</div>{% endif %}</div>{% endif %}
      {% if g2_opp %}<div class="gr{% if g2_yours == '1' %} yours active{% endif %}"><div class="td"></div><div class="go">{{ g2_opp }}</div><div class="gc {% if g2_col == 'B' %}b{% else %}w{% endif %}">{{ g2_col }}</div><div class="gm">{{ g2_moves }}</div>{% if g2_size != '19' %}<div class="gs">{{ g2_size }}</div>{% endif %}</div>{% endif %}
      {% if g3_opp %}<div class="gr{% if g3_yours == '1' %} yours active{% endif %}"><div class="td"></div><div class="go">{{ g3_opp }}</div><div class="gc {% if g3_col == 'B' %}b{% else %}w{% endif %}">{{ g3_col }}</div><div class="gm">{{ g3_moves }}</div>{% if g3_size != '19' %}<div class="gs">{{ g3_size }}</div>{% endif %}</div>{% endif %}
      {% if g4_opp %}<div class="gr{% if g4_yours == '1' %} yours active{% endif %}"><div class="td"></div><div class="go">{{ g4_opp }}</div><div class="gc {% if g4_col == 'B' %}b{% else %}w{% endif %}">{{ g4_col }}</div><div class="gm">{{ g4_moves }}</div>{% if g4_size != '19' %}<div class="gs">{{ g4_size }}</div>{% endif %}</div>{% endif %}
      {% if g5_opp %}<div class="gr{% if g5_yours == '1' %} yours active{% endif %}"><div class="td"></div><div class="go">{{ g5_opp }}</div><div class="gc {% if g5_col == 'B' %}b{% else %}w{% endif %}">{{ g5_col }}</div><div class="gm">{{ g5_moves }}</div>{% if g5_size != '19' %}<div class="gs">{{ g5_size }}</div>{% endif %}</div>{% endif %}
      {% if g6_opp %}<div class="gr{% if g6_yours == '1' %} yours active{% endif %}"><div class="td"></div><div class="go">{{ g6_opp }}</div><div class="gc {% if g6_col == 'B' %}b{% else %}w{% endif %}">{{ g6_col }}</div><div class="gm">{{ g6_moves }}</div>{% if g6_size != '19' %}<div class="gs">{{ g6_size }}</div>{% endif %}</div>{% endif %}
      {% if g7_opp %}<div class="gr{% if g7_yours == '1' %} yours active{% endif %}"><div class="td"></div><div class="go">{{ g7_opp }}</div><div class="gc {% if g7_col == 'B' %}b{% else %}w{% endif %}">{{ g7_col }}</div><div class="gm">{{ g7_moves }}</div>{% if g7_size != '19' %}<div class="gs">{{ g7_size }}</div>{% endif %}</div>{% endif %}
      {% if g8_opp %}<div class="gr{% if g8_yours == '1' %} yours active{% endif %}"><div class="td"></div><div class="go">{{ g8_opp }}</div><div class="gc {% if g8_col == 'B' %}b{% else %}w{% endif %}">{{ g8_col }}</div><div class="gm">{{ g8_moves }}</div>{% if g8_size != '19' %}<div class="gs">{{ g8_size }}</div>{% endif %}</div>{% endif %}
      {% if g9_opp %}<div class="gr{% if g9_yours == '1' %} yours active{% endif %}"><div class="td"></div><div class="go">{{ g9_opp }}</div><div class="gc {% if g9_col == 'B' %}b{% else %}w{% endif %}">{{ g9_col }}</div><div class="gm">{{ g9_moves }}</div>{% if g9_size != '19' %}<div class="gs">{{ g9_size }}</div>{% endif %}</div>{% endif %}
    </div>
  </div>

  <!-- ── Centre: main board (oldest game) ── -->
  <div class="board-main">
    <div class="bm-meta">
      {% if board_yours == '1' %}<span class="your-move">&#9654; Your move</span>{% else %}Waiting{% endif %}
      &nbsp;vs&nbsp;<b>{{ board_opp }}</b>
      &nbsp;&bull;&nbsp;you play {% if board_col == 'B' %}&#9679;{% else %}&#9675;{% endif %}
      &nbsp;&bull;&nbsp;mv&nbsp;{{ board_moves }}
    </div>
    <div class="bw" id="bw0"></div>
  </div>

  <!-- ── Right: two smaller boards ── -->
  <div class="boards-side">

    <div class="bs-item">
      {% if board1_gid %}
      <div class="bs-meta">
        {% if board1_yours == '1' %}<span class="ym">&#9654;</span>{% endif %}
        <span class="gc {% if board1_col == 'B' %}b{% else %}w{% endif %}">{{ board1_col }}</span>
        {{ board1_opp }}&nbsp;<span style="color:#bbb;font-weight:400">{{ board1_moves }}</span>
      </div>
      {% endif %}
      <div class="bw" id="bw1"></div>
    </div>

    <div class="bs-item">
      {% if board2_gid %}
      <div class="bs-meta">
        {% if board2_yours == '1' %}<span class="ym">&#9654;</span>{% endif %}
        <span class="gc {% if board2_col == 'B' %}b{% else %}w{% endif %}">{{ board2_col }}</span>
        {{ board2_opp }}&nbsp;<span style="color:#bbb;font-weight:400">{{ board2_moves }}</span>
      </div>
      {% endif %}
      <div class="bw" id="bw2"></div>
    </div>

  </div>

</div>

<script>
(function(){

// ── Shared SGF parser + SVG board renderer ──────────────────────────────────
// Uses SVG viewBox so the board scales to fill its container via CSS width/height:100%.
// No offsetWidth/offsetHeight needed — no timing dependency on layout.

function parseSGF(sgf) {
  var sz = 19;
  var szm = sgf.match(/SZ\[(\d+)\]/);
  if (szm) sz = Math.max(2, Math.min(25, parseInt(szm[1])));

  var board = new Array(sz * sz).fill(0);
  function idx(x,y){ return y*sz+x; }
  function ob(x,y){ return x>=0&&x<sz&&y>=0&&y<sz; }
  function pc(s){
    if(!s||s.length<2) return null;
    var x=s.charCodeAt(0)-97, y=s.charCodeAt(1)-97;
    return ob(x,y)?[x,y]:null;
  }
  function getGrp(x,y){
    var col=board[idx(x,y)]; if(!col) return{s:[],l:0};
    var s=[],vis={},l=0;
    function dfs(cx,cy){
      var k=cx+','+cy; if(vis[k]) return; vis[k]=1; s.push([cx,cy]);
      var ns=[[cx-1,cy],[cx+1,cy],[cx,cy-1],[cx,cy+1]];
      for(var i=0;i<4;i++){
        var nx=ns[i][0],ny=ns[i][1]; if(!ob(nx,ny)) continue;
        var nc=board[idx(nx,ny)];
        if(nc===0) l++; else if(nc===col) dfs(nx,ny);
      }
    }
    dfs(x,y); return{s:s,l:l};
  }

  // Pre-placed stones (AB/AW — handicap)
  var fixRe=/A([BW])((?:\[[a-z]{2}\])+)/g, fm;
  while((fm=fixRe.exec(sgf))!==null){
    var fc=fm[1]==='B'?1:2;
    (fm[2].match(/\[[a-z]{2}\]/g)||[]).forEach(function(c){
      var pt=pc(c.slice(1,3)); if(pt) board[idx(pt[0],pt[1])]=fc;
    });
  }

  // Strip variation branches, keep main line
  var depth=0, stripped='';
  for(var ci=0;ci<sgf.length;ci++){
    var ch=sgf[ci];
    if(ch==='('){if(depth++>0) continue;}
    else if(ch===')'){if(--depth>0) continue;}
    if(depth>=0) stripped+=ch;
  }

  var moveRe=/;(B|W)\[([a-z]{0,2})\]/g, mm;
  var lx=-1, ly=-1, lc=0;
  while((mm=moveRe.exec(stripped))!==null){
    var col=mm[1]==='B'?1:2, pt=pc(mm[2]);
    if(!pt) continue;
    var x=pt[0],y=pt[1];
    board[idx(x,y)]=col; lx=x; ly=y; lc=col;
    var opp=col===1?2:1;
    [[x-1,y],[x+1,y],[x,y-1],[x,y+1]].forEach(function(n){
      if(!ob(n[0],n[1])) return;
      if(board[idx(n[0],n[1])]===opp){
        var gi=getGrp(n[0],n[1]);
        if(gi.l===0) gi.s.forEach(function(s){board[idx(s[0],s[1])]=0;});
      }
    });
    var sg=getGrp(x,y); if(sg.l===0) sg.s.forEach(function(s){board[idx(s[0],s[1])]=0;});
  }
  return {sz:sz, board:board, lx:lx, ly:ly, lc:lc};
}

function renderBoard(containerId, sgf, compact) {
  var wrap = document.getElementById(containerId);
  if (!wrap || !sgf || sgf.indexOf('GM[') < 0) return;

  var p = parseSGF(sgf);
  var sz=p.sz, board=p.board, lx=p.lx, ly=p.ly, lc=p.lc;
  function idx(x,y){return y*sz+x;}

  // Fixed coordinate space — CSS scales the SVG to fill the container
  var VW = compact ? 210 : 420;
  var VH = compact ? 210 : 420;
  var pad      = compact ? 5  : 14;
  var labelPad = compact ? 0  : 17;  // no labels on compact boards
  var gridPx   = VW - pad*2 - labelPad;
  var cell     = gridPx / (sz - 1);
  var sr       = Math.min(cell * 0.47, compact ? 6 : 11);
  var ox       = pad + labelPad;
  var oy       = pad;

  var S = [];

  // Board background
  S.push('<rect x="'+(ox-pad*0.4)+'" y="'+(oy-pad*0.4)+'"'
    +' width="'+(gridPx+pad*0.8)+'" height="'+(gridPx+pad*0.8)+'"'
    +' fill="#D4A740" rx="2"/>');

  // Coordinate labels (main board only)
  if (!compact) {
    var FILES='ABCDEFGHJKLMNOPQRST';
    for(var ci=0;ci<sz;ci++){
      var lbx=(ox+ci*cell).toFixed(1);
      S.push('<text x="'+lbx+'" y="'+(oy+gridPx+labelPad*0.82).toFixed(1)+'"'
        +' text-anchor="middle" font-size="9" font-family="monospace" fill="#7A4A00">'+FILES[ci]+'</text>');
      S.push('<text x="'+(ox-labelPad*0.48).toFixed(1)+'" y="'+(oy+ci*cell+3).toFixed(1)+'"'
        +' text-anchor="middle" font-size="9" font-family="monospace" fill="#7A4A00">'+(sz-ci)+'</text>');
    }
  }

  // Grid lines
  for(var gi=0;gi<sz;gi++){
    var gx=(ox+gi*cell).toFixed(1), gy=(oy+gi*cell).toFixed(1);
    var sw=(gi===0||gi===sz-1)?'1.6':'0.6';
    S.push('<line x1="'+gx+'" y1="'+oy+'" x2="'+gx+'" y2="'+(oy+gridPx).toFixed(1)+'" stroke="#3A2000" stroke-width="'+sw+'"/>');
    S.push('<line x1="'+ox+'" y1="'+gy+'" x2="'+(ox+gridPx).toFixed(1)+'" y2="'+gy+'" stroke="#3A2000" stroke-width="'+sw+'"/>');
  }

  // Star points
  var H19=[3,9,15],H13=[3,6,9],H9=[2,4,6];
  var hoshi=sz===19?H19:sz===13?H13:sz===9?H9:[];
  var hr=Math.max(1.2,cell*0.1);
  hoshi.forEach(function(xi){hoshi.forEach(function(yi){
    S.push('<circle cx="'+(ox+xi*cell).toFixed(1)+'" cy="'+(oy+yi*cell).toFixed(1)+'" r="'+hr.toFixed(1)+'" fill="#3A2000"/>');
  });});

  // Stones
  for(var sy2=0;sy2<sz;sy2++){
    for(var sx2=0;sx2<sz;sx2++){
      var sc=board[idx(sx2,sy2)]; if(!sc) continue;
      var scx=(ox+sx2*cell).toFixed(1), scy=(oy+sy2*cell).toFixed(1);
      if(sc===1){
        S.push('<circle cx="'+scx+'" cy="'+scy+'" r="'+sr.toFixed(1)+'" fill="#111" stroke="#000" stroke-width="0.4"/>');
        if(!compact) S.push('<ellipse cx="'+(ox+sx2*cell-sr*0.28).toFixed(1)+'" cy="'+(oy+sy2*cell-sr*0.28).toFixed(1)+'"'
          +' rx="'+(sr*0.22).toFixed(1)+'" ry="'+(sr*0.14).toFixed(1)+'" fill="rgba(255,255,255,0.18)"'
          +' transform="rotate(-30,'+scx+','+scy+')"/>');
      } else {
        S.push('<circle cx="'+scx+'" cy="'+scy+'" r="'+sr.toFixed(1)+'" fill="#f8f8f8" stroke="#444" stroke-width="1"/>');
      }
    }
  }

  // Last-move marker
  if(lx>=0){
    var mf=lc===1?'#fff':'#222';
    S.push('<circle cx="'+(ox+lx*cell).toFixed(1)+'" cy="'+(oy+ly*cell).toFixed(1)+'"'
      +' r="'+(sr*0.32).toFixed(1)+'" fill="'+mf+'"/>');
  }

  wrap.innerHTML='<svg xmlns="http://www.w3.org/2000/svg"'
    +' viewBox="0 0 '+VW+' '+VH+'"'
    +' preserveAspectRatio="xMidYMid meet"'
    +' style="width:100%;height:100%;display:block">'
    +S.join('')+'</svg>';
}

// ── Render all three boards ──────────────────────────────────────────────────
var s0 = (document.getElementById('sgf-0')||{}).textContent||'';
var s1 = (document.getElementById('sgf-1')||{}).textContent||'';
var s2 = (document.getElementById('sgf-2')||{}).textContent||'';
renderBoard('bw0', s0.trim(), false);   // main board — full labels
renderBoard('bw1', s1.trim(), true);    // side board 1 — compact
renderBoard('bw2', s2.trim(), true);    // side board 2 — compact

})();
</script>
```

---

## Config file

Create `/config/dgs-config.json` on the server:

```json
{
  "uid":        "24738",
  "username":   "Shrimphead",
  "password":   "YOUR_DGS_PASSWORD",
  "device_key": "YOUR_TRMNL_DEVICE_KEY"
}
```

Without `password`, the game list is still fetched (public data), but the board
will show "Add DGS password to config to display board" since SGF of ongoing
games requires a DGS login session.

Without `device_key`, the endpoint is accessible to anyone who knows the URL
(fine for a home display; add the key for security).

---

## Notes

**Rate limit** — poll at 15 min minimum. DGS is a correspondence server; games
move a few times per day. The PHP caches for 5 minutes regardless.

**Game list source** — uses `quick_status.php` (public DGS API) for fast
pipe-delimited game data. Falls back to HTML-parse of `show_games.php` if the
quick_status returns nothing.

**Board renderer** — pure inline JS + SVG. No jQuery, no jGoBoard, no CDN.
Parses full SGF (main line only, ignores variation branches), applies stone
captures, renders grid + coordinate labels + star points + stones with a
last-move marker. Optimized for e-ink: high-contrast B&W.

**SGF coordinate system** — `aa` = top-left (column A, row 19). `sa` = top-right
for 19×19. Row labels count down from sz at top to 1 at bottom, matching the
standard Go board orientation.

**Oldest game** — the PHP sorts all running games by game ID ascending; lowest
ID = game created first. This is the one displayed on the board.
