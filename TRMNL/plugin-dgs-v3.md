# Plugin: Go Games · DGS (v3 — 2/3 main board + 1/3 side panel)
#
# PHP backend : dgs-device-api.php
# Config      : /config/dgs-config.json
#   { "uid":"24738", "username":"Shrimphead", "password":"...", "device_key":"..." }
#
# Polling URL : https://knotwork.ca/dgs-device-api.php?key=YOUR_DEVICE_KEY
#
# Variables used
#   total, my_turn_count
#   g0_id … g9_id   g0_opp … g9_opp   g0_col … g9_col   (B/W = your colour)
#   g0_moves … g9_moves   g0_yours … g9_yours   g0_size … g9_size   g0_time … g9_time
#   g0_sgf … g5_sgf  — full SGF for first 6 games (requires DGS credentials)
#
# Layout (800 × 480 px)
#   Header  36 px
#   LEFT  ~534 px  — main board (full size) + stats bar
#   RIGHT  266 px  — two compact boards with stats, then remaining game list
#
# Rotation: JS selects which games to show as boards based on
#   Math.floor(Date.now() / 1800000)  — changes every 30 minutes.
#   Main board = slot t%N, side1 = (t+1)%N, side2 = (t+2)%N
#   where N = number of games that have a valid SGF (up to 6).
#
# Settings: Strategy: Polling | Verb: GET | Remove bleed margin: Yes
#   Polling interval: 15 min minimum (DGS request)
#
# IMPORTANT: paste ONLY the HTML below (starting with <style>) into the
# TRMNL "Full" markup tab. Do NOT paste this header or the ``` fences.

## Markup (Full tab)

```html
<style>
*{box-sizing:border-box;margin:0;padding:0}
html,body{width:800px;height:480px;overflow:hidden;background:#fff;color:#000;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif}
body{display:grid;grid-template-rows:36px 1fr}

/* ── Header ─────────────────────────────────────────────────────────── */
.hdr{display:flex;align-items:center;justify-content:space-between;padding:0 14px;border-bottom:2px solid #000}
.hdr-t{font-weight:800;font-size:18px;letter-spacing:.02em}
.hdr-c{font-size:13px;color:#555}
.badge{background:#000;color:#fff;font-size:11px;font-weight:700;padding:2px 8px;border-radius:2px;margin-left:7px}
.hdr-r{font-size:13px;color:#777}

/* ── Content: explicit flex — NO grid, NO 1fr (avoids viewport-width mis-calc) ── */
.content{display:flex;flex-direction:row;height:444px;width:800px;overflow:hidden}

/* ── LEFT: main board — explicit 534 px, board gets full 444 px height ──────── */
.main-panel{flex:0 0 534px;width:534px;height:444px;overflow:hidden;border-right:2px solid #000}

/* Board: square 444×444 — fills the full content height so nothing is wasted ── */
/* (534px panel - 444px board = 90px gap right of board; wood background fills it) */
.bw{width:444px;height:444px;overflow:hidden;background:#D4A740}

/* ── RIGHT: side panel — explicit 266 px, flex column ────────────────────────── */
.side-panel{flex:0 0 266px;width:266px;height:444px;overflow:hidden;display:flex;flex-direction:column}

/* ── Main game info strip at the top of the side panel ─────────────────────── */
.mg-info{flex:0 0 50px;height:50px;border-bottom:2px solid #000;padding:5px 8px;display:flex;flex-direction:column;justify-content:center;gap:3px;overflow:hidden}
.mg-r1{display:flex;align-items:center;gap:6px;font-size:13px;font-weight:700;white-space:nowrap;overflow:hidden}
.mg-r1 .yt{color:#000;font-weight:900;font-size:12px}
.mg-r1 .wt{color:#aaa;font-size:12px}
.sd{display:inline-flex;align-items:center;justify-content:center;width:15px;height:15px;border-radius:50%;flex-shrink:0}
.sd.b{background:#111}
.sd.w{background:#fff;border:1.5px solid #555}
.mg-r2{font-size:10px;color:#888;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.mg-r2 .urg{color:#c00;font-weight:700}

/* ── Side board items — 170 px each (hardcoded, so overflow:hidden can't collapse them) */
.bs-item{flex:0 0 170px;height:170px;display:flex;flex-direction:column;overflow:hidden;border-bottom:1px solid #ccc}
/* Board: 170 − 26 stats = 144 px */
.bs-bw{flex:0 0 144px;height:144px;overflow:hidden}
.bs-stats{flex:0 0 26px;height:26px;border-top:1px solid #eee;padding:3px 7px;font-size:10px;color:#666;display:flex;align-items:center;gap:5px;white-space:nowrap;overflow:hidden}
.bs-stats .yt{color:#000;font-weight:900;font-size:11px;flex-shrink:0}
.bs-stats b{color:#000;font-weight:700}
.sds{display:inline-block;width:10px;height:10px;border-radius:50%;flex-shrink:0;vertical-align:middle}
.sds.b{background:#111}
.sds.w{background:#fff;border:1px solid #666}
.bs-stats .urg{color:#c00;font-weight:700}

/* ── Game list — claims whatever height remains (444−50−170−170 = 54 px) ─── */
.game-list{flex:1;min-height:0;overflow:hidden;display:flex;flex-direction:column}
.gl-hdr{font-size:8px;font-weight:700;text-transform:uppercase;letter-spacing:.14em;color:#bbb;padding:2px 8px;border-bottom:1px solid #eee;flex-shrink:0}
.gl-row{display:flex;align-items:center;gap:4px;padding:2px 8px;font-size:10px;border-bottom:1px solid #f4f4f4;flex-shrink:0;overflow:hidden}
.gl-row.yt-row{background:#111;color:#fff}
.gl-opp{flex:1;font-weight:700;font-size:11px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;min-width:0}
.gl-row.yt-row .gl-opp{color:#fff}
.gl-mv{font-size:9px;color:#aaa;flex-shrink:0}
.gl-row.yt-row .gl-mv{color:#888}
.gl-tm{font-size:9px;color:#888;flex-shrink:0}
.gl-row.yt-row .gl-tm{color:#aaa}
.gl-row.yt-row .urg{color:#f88}
.gl-empty{font-size:10px;color:#ccc;font-style:italic;padding:4px 8px}

/* no-board placeholder */
.no-board{width:100%;height:144px;display:flex;align-items:center;justify-content:center;font-size:11px;color:#ccc;font-style:italic}
</style>

<!-- SGF data — injected by Liquid, read by JS -->
<pre id="sgf-0" style="display:none">{{ g0_sgf }}</pre>
<pre id="sgf-1" style="display:none">{{ g1_sgf }}</pre>
<pre id="sgf-2" style="display:none">{{ g2_sgf }}</pre>
<pre id="sgf-3" style="display:none">{{ g3_sgf }}</pre>
<pre id="sgf-4" style="display:none">{{ g4_sgf }}</pre>
<pre id="sgf-5" style="display:none">{{ g5_sgf }}</pre>

<div class="hdr">
  <div class="hdr-t">DGS &middot; Shrimphead</div>
  <div class="hdr-c">
    {{ total }} game{% if total != '1' %}s{% endif %}{% if my_turn_count != '0' %}<span class="badge">{{ my_turn_count }} to move</span>{% endif %}
  </div>
  <div class="hdr-r">{{ trmnl.user.time | date: "%H:%M %a %-d" }}</div>
</div>

<div class="content">

  <!-- ── Left: main board (full 444px height) ── -->
  <div class="main-panel">
    <div class="bw" id="bw-main"></div>
  </div>

  <!-- ── Right: main game info + two compact boards + game list ── -->
  <div class="side-panel">

    <!-- Main game stats live here so the board gets full height -->
    <div class="mg-info">
      <div class="mg-r1" id="mg-r1"><span class="wt">—</span></div>
      <div class="mg-r2" id="mg-r2"></div>
    </div>

    <div class="bs-item">
      <div class="bs-bw" id="bw-s1"></div>
      <div class="bs-stats" id="bs-stats-1"></div>
    </div>

    <div class="bs-item">
      <div class="bs-bw" id="bw-s2"></div>
      <div class="bs-stats" id="bs-stats-2"></div>
    </div>

    <div class="game-list">
      <div class="gl-hdr">Other games</div>
      <div id="game-list"></div>
    </div>

  </div>

</div>

<script>
(function(){

// ════════════════════════════════════════════════════════════════════
// 1. GAMES array — populated by Liquid from flat JSON variables
// ════════════════════════════════════════════════════════════════════
var GAMES=[];
{% if g0_opp %}GAMES.push({i:0,id:"{{ g0_id }}",opp:"{{ g0_opp }}",col:"{{ g0_col }}",mv:{{ g0_moves }},yt:{{ g0_yours }},sz:{{ g0_size }},tm:"{{ g0_time }}"});{% endif %}
{% if g1_opp %}GAMES.push({i:1,id:"{{ g1_id }}",opp:"{{ g1_opp }}",col:"{{ g1_col }}",mv:{{ g1_moves }},yt:{{ g1_yours }},sz:{{ g1_size }},tm:"{{ g1_time }}"});{% endif %}
{% if g2_opp %}GAMES.push({i:2,id:"{{ g2_id }}",opp:"{{ g2_opp }}",col:"{{ g2_col }}",mv:{{ g2_moves }},yt:{{ g2_yours }},sz:{{ g2_size }},tm:"{{ g2_time }}"});{% endif %}
{% if g3_opp %}GAMES.push({i:3,id:"{{ g3_id }}",opp:"{{ g3_opp }}",col:"{{ g3_col }}",mv:{{ g3_moves }},yt:{{ g3_yours }},sz:{{ g3_size }},tm:"{{ g3_time }}"});{% endif %}
{% if g4_opp %}GAMES.push({i:4,id:"{{ g4_id }}",opp:"{{ g4_opp }}",col:"{{ g4_col }}",mv:{{ g4_moves }},yt:{{ g4_yours }},sz:{{ g4_size }},tm:"{{ g4_time }}"});{% endif %}
{% if g5_opp %}GAMES.push({i:5,id:"{{ g5_id }}",opp:"{{ g5_opp }}",col:"{{ g5_col }}",mv:{{ g5_moves }},yt:{{ g5_yours }},sz:{{ g5_size }},tm:"{{ g5_time }}"});{% endif %}
{% if g6_opp %}GAMES.push({i:6,id:"{{ g6_id }}",opp:"{{ g6_opp }}",col:"{{ g6_col }}",mv:{{ g6_moves }},yt:{{ g6_yours }},sz:{{ g6_size }},tm:"{{ g6_time }}"});{% endif %}
{% if g7_opp %}GAMES.push({i:7,id:"{{ g7_id }}",opp:"{{ g7_opp }}",col:"{{ g7_col }}",mv:{{ g7_moves }},yt:{{ g7_yours }},sz:{{ g7_size }},tm:"{{ g7_time }}"});{% endif %}
{% if g8_opp %}GAMES.push({i:8,id:"{{ g8_id }}",opp:"{{ g8_opp }}",col:"{{ g8_col }}",mv:{{ g8_moves }},yt:{{ g8_yours }},sz:{{ g8_size }},tm:"{{ g8_time }}"});{% endif %}
{% if g9_opp %}GAMES.push({i:9,id:"{{ g9_id }}",opp:"{{ g9_opp }}",col:"{{ g9_col }}",mv:{{ g9_moves }},yt:{{ g9_yours }},sz:{{ g9_size }},tm:"{{ g9_time }}"});{% endif %}

// ════════════════════════════════════════════════════════════════════
// 2. Collect SGF strings
// ════════════════════════════════════════════════════════════════════
var SGF=[];
for(var si=0;si<6;si++){
  var el=document.getElementById('sgf-'+si);
  var txt=el?(el.textContent||'').trim():'';
  SGF.push(txt.indexOf('GM[')>=0?txt:'');
}

// ════════════════════════════════════════════════════════════════════
// 3. 30-minute rotation — pick which SGF slots to show
// ════════════════════════════════════════════════════════════════════
var validIdx=[];
for(var vi=0;vi<SGF.length;vi++) if(SGF[vi]) validIdx.push(vi);
var vl=validIdx.length;
var t=Math.floor(Date.now()/1800000); // increments every 30 min
// bIdx[0]=main, bIdx[1]=side1, bIdx[2]=side2  (-1 = nothing to show)
var bIdx=[-1,-1,-1];
if(vl>0) bIdx[0]=validIdx[t%vl];
if(vl>1) bIdx[1]=validIdx[(t+1)%vl];
if(vl>2) bIdx[2]=validIdx[(t+2)%vl];

// ════════════════════════════════════════════════════════════════════
// 4. SGF parser — main line only; returns board state + capture counts
// ════════════════════════════════════════════════════════════════════
function parseSGF(sgf){
  var sz=19;
  var szm=sgf.match(/SZ\[(\d+)\]/); if(szm) sz=Math.max(2,Math.min(25,parseInt(szm[1])));
  var board=new Array(sz*sz).fill(0);
  var bcap=0,wcap=0; // stones captured BY black / BY white
  function idx(x,y){return y*sz+x;}
  function ob(x,y){return x>=0&&x<sz&&y>=0&&y<sz;}
  function pc(s){
    if(!s||s.length<2) return null;
    var x=s.charCodeAt(0)-97,y=s.charCodeAt(1)-97;
    return ob(x,y)?[x,y]:null;
  }
  function getGrp(x,y){
    var col=board[idx(x,y)]; if(!col) return{s:[],l:0};
    var s=[],vis={},l=0;
    function dfs(cx,cy){
      var k=cx+','+cy; if(vis[k]) return; vis[k]=1; s.push([cx,cy]);
      var ns=[[cx-1,cy],[cx+1,cy],[cx,cy-1],[cx,cy+1]];
      for(var ni=0;ni<4;ni++){
        var nx=ns[ni][0],ny=ns[ni][1]; if(!ob(nx,ny)) continue;
        var nc=board[idx(nx,ny)];
        if(nc===0) l++; else if(nc===col) dfs(nx,ny);
      }
    }
    dfs(x,y); return{s:s,l:l};
  }
  // Pre-placed stones (handicap: AB / AW)
  var fixRe=/A([BW])((?:\[[a-z]{2}\])+)/g,fm;
  while((fm=fixRe.exec(sgf))!==null){
    var fc=fm[1]==='B'?1:2;
    (fm[2].match(/\[[a-z]{2}\]/g)||[]).forEach(function(c){
      var pt=pc(c.slice(1,3)); if(pt) board[idx(pt[0],pt[1])]=fc;
    });
  }
  // Strip variation branches — keep main line only
  var depth=0,stripped='';
  for(var ci=0;ci<sgf.length;ci++){
    var ch=sgf[ci];
    if(ch==='('){if(depth++>0) continue;}
    else if(ch===')'){if(--depth>0) continue;}
    if(depth>=0) stripped+=ch;
  }
  // Replay moves
  var moveRe=/;(B|W)\[([a-z]{0,2})\]/g,mm;
  var lx=-1,ly=-1,lc=0;
  while((mm=moveRe.exec(stripped))!==null){
    var col=mm[1]==='B'?1:2,pt=pc(mm[2]);
    if(!pt) continue;
    var x=pt[0],y=pt[1];
    board[idx(x,y)]=col; lx=x; ly=y; lc=col;
    var opp=col===1?2:1;
    [[x-1,y],[x+1,y],[x,y-1],[x,y+1]].forEach(function(n){
      if(!ob(n[0],n[1])) return;
      if(board[idx(n[0],n[1])]===opp){
        var gi=getGrp(n[0],n[1]);
        if(gi.l===0){
          gi.s.forEach(function(s){board[idx(s[0],s[1])]=0;});
          if(col===1) bcap+=gi.s.length; else wcap+=gi.s.length;
        }
      }
    });
    // suicide (rare)
    var sg=getGrp(x,y);
    if(sg.l===0) sg.s.forEach(function(s){board[idx(s[0],s[1])]=0;});
  }
  return{sz:sz,board:board,lx:lx,ly:ly,lc:lc,bcap:bcap,wcap:wcap};
}

// ════════════════════════════════════════════════════════════════════
// 5. Board renderer — injects SVG into container element
//    compact=false  → 420×420 viewBox, coordinate labels, stone glints
//    compact=true   → 210×210 viewBox, no labels, no glints
// ════════════════════════════════════════════════════════════════════
function renderBoard(containerId,p,compact){
  var wrap=document.getElementById(containerId);
  if(!wrap||!p) return;
  var sz=p.sz,board=p.board,lx=p.lx,ly=p.ly,lc=p.lc;
  function idx(x,y){return y*sz+x;}

  // Hardcoded pixel dims — must match CSS exactly.
  // Side (.bs-bw): 266 wide × 144 tall (170px section − 26px stats).
  // Main (.bw):    444×444 square (board gets full 444px content height).
  var W=compact?266:444;
  var H=compact?144:444;

  var VW=compact?210:420,VH=compact?210:420;
  var pad=compact?5:14;
  var lblPad=compact?0:18;    // space for row/col labels (main board only)
  var gridPx=VW-pad*2-lblPad;
  var cell=gridPx/(sz-1);
  var sr=Math.min(cell*0.47,compact?6:11);
  var ox=pad+lblPad,oy=pad;
  var S=[];

  // Board background (warm wood tone)
  S.push('<rect x="'+(ox-pad*0.5).toFixed(1)+'" y="'+(oy-pad*0.5).toFixed(1)+'"'
    +' width="'+(gridPx+pad).toFixed(1)+'" height="'+(gridPx+pad).toFixed(1)+'"'
    +' fill="#D4A740" rx="2"/>');

  // Coordinate labels (main board only)
  if(!compact){
    var FILES='ABCDEFGHJKLMNOPQRST';
    for(var ci=0;ci<sz;ci++){
      S.push('<text x="'+(ox+ci*cell).toFixed(1)+'" y="'+(oy+gridPx+lblPad*0.78).toFixed(1)+'"'
        +' text-anchor="middle" font-size="9" font-family="monospace" fill="#7A4A00">'+FILES[ci]+'</text>');
      S.push('<text x="'+(ox-lblPad*0.5).toFixed(1)+'" y="'+(oy+ci*cell+3.5).toFixed(1)+'"'
        +' text-anchor="middle" font-size="9" font-family="monospace" fill="#7A4A00">'+(sz-ci)+'</text>');
    }
  }

  // Grid lines
  for(var gi=0;gi<sz;gi++){
    var gx=(ox+gi*cell).toFixed(1),gy=(oy+gi*cell).toFixed(1);
    var sw=(gi===0||gi===sz-1)?'1.6':'0.6';
    S.push('<line x1="'+gx+'" y1="'+oy+'" x2="'+gx+'" y2="'+(oy+gridPx).toFixed(1)+'" stroke="#3A2000" stroke-width="'+sw+'"/>');
    S.push('<line x1="'+ox+'" y1="'+gy+'" x2="'+(ox+gridPx).toFixed(1)+'" y2="'+gy+'" stroke="#3A2000" stroke-width="'+sw+'"/>');
  }

  // Star points (hoshi)
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
      var scx=(ox+sx2*cell).toFixed(1),scy=(oy+sy2*cell).toFixed(1);
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
    S.push('<circle cx="'+(ox+lx*cell).toFixed(1)+'" cy="'+(oy+ly*cell).toFixed(1)+'"'
      +' r="'+(sr*0.32).toFixed(1)+'" fill="'+(lc===1?'#fff':'#222')+'"/>');
  }

  // Main board: xMinYMin anchors top-left; compact: xMidYMid centres in the column.
  // SVG gets explicit pixel width/height — no CSS percentage resolution needed.
  var par=compact?'xMidYMid meet':'xMinYMin meet';
  wrap.innerHTML='<svg xmlns="http://www.w3.org/2000/svg"'
    +' width="'+W+'" height="'+H+'"'
    +' viewBox="0 0 '+VW+' '+VH+'"'
    +' preserveAspectRatio="'+par+'"'
    +' style="display:block">'
    +S.join('')+'</svg>';
}

// ════════════════════════════════════════════════════════════════════
// 6. Parse + render all three boards
// ════════════════════════════════════════════════════════════════════
var parsed=[null,null,null];
[0,1,2].forEach(function(pi){
  if(bIdx[pi]<0) return;
  var p=parseSGF(SGF[bIdx[pi]]);
  parsed[pi]=p;
  var ids=['bw-main','bw-s1','bw-s2'];
  renderBoard(ids[pi],p,pi>0);
});

// ════════════════════════════════════════════════════════════════════
// 7. Urgency helper — highlight times < 24 h (contain "0d")
// ════════════════════════════════════════════════════════════════════
function urgSpan(tm){
  if(!tm) return '';
  var urgent=tm.toLowerCase().indexOf('0d')>=0;
  return urgent?'<span class="urg">'+tm+'</span>':tm;
}

// ════════════════════════════════════════════════════════════════════
// 8. Main game stats — now in side panel .mg-info strip
// ════════════════════════════════════════════════════════════════════
var mg=bIdx[0]>=0?GAMES[bIdx[0]]:null;
var mp=parsed[0];
if(mg){
  var myChip='<span class="sd '+(mg.col==='B'?'b':'w')+'"></span>';
  var oppChip='<span class="sd '+(mg.col==='B'?'w':'b')+'"></span>';
  var myCap=mp?(mg.col==='B'?mp.bcap:mp.wcap):0;
  var oppCap=mp?(mg.col==='B'?mp.wcap:mp.bcap):0;

  var r1=(mg.yt?'<span class="yt">&#9654;</span> ':'')
    +'<b>'+mg.opp+'</b> '+myChip;
  document.getElementById('mg-r1').innerHTML=r1;

  var r2='mv'+mg.mv
    +(mg.sz!==19?' &bull; '+mg.sz+'&#215;'+mg.sz:'')
    +(mg.tm?' &bull; '+urgSpan(mg.tm):'')
    +(myCap+oppCap>0?' &bull; cap: '+myChip+myCap+' '+oppChip+oppCap:'')
    +' &bull; #'+mg.id;
  document.getElementById('mg-r2').innerHTML=r2;
} else {
  document.getElementById('mg-r1').innerHTML='<span class="wt">—</span>';
}

// ════════════════════════════════════════════════════════════════════
// 9. Side board stats
// ════════════════════════════════════════════════════════════════════
function setSideStats(elId,bi,pi){
  var el=document.getElementById(elId);
  if(!el) return;
  if(bi<0||!GAMES[bi]){el.innerHTML='<span style="color:#ddd">—</span>';return;}
  var g=GAMES[bi],p=parsed[pi];
  var cap=p?(g.col==='B'?p.bcap:p.wcap):0;
  var html=(g.yt?'<span class="yt">&#9654;</span> ':' ')
    +'<span class="sds '+(g.col==='B'?'b':'w')+'"></span>'
    +' <b>'+g.opp+'</b>'
    +' mv'+g.mv
    +(g.sz!==19?' '+g.sz+'&#215;'+g.sz:'')
    +(g.tm?' &bull; '+urgSpan(g.tm):'')
    +(cap>0?' cap:'+cap:'');
  el.innerHTML=html;
}
setSideStats('bs-stats-1',bIdx[1],1);
setSideStats('bs-stats-2',bIdx[2],2);

// Show placeholder if side slot has no valid SGF
if(bIdx[1]<0){var e1=document.getElementById('bw-s1');if(e1) e1.innerHTML='<div class="no-board">—</div>';}
if(bIdx[2]<0){var e2=document.getElementById('bw-s2');if(e2) e2.innerHTML='<div class="no-board">—</div>';}

// ════════════════════════════════════════════════════════════════════
// 10. Game list — all games NOT currently shown as boards
// ════════════════════════════════════════════════════════════════════
var shown={};
bIdx.forEach(function(bi){if(bi>=0) shown[bi]=1;});
var listEl=document.getElementById('game-list');
if(listEl){
  var rows='';
  GAMES.forEach(function(g){
    if(shown[g.i]) return;
    var stoneClass=g.col==='B'?'b':'w';
    rows+='<div class="gl-row'+(g.yt?' yt-row':'')+'">'
      +'<span class="sds '+stoneClass+'"></span>'
      +'<span class="gl-opp">'+g.opp+'</span>'
      +'<span class="gl-mv">mv'+g.mv+'</span>'
      +(g.tm?'<span class="gl-tm '+(g.tm.toLowerCase().indexOf('0d')>=0?'urg':'')+'">'+g.tm+'</span>':'')
      +(g.sz!==19?'<span class="gl-mv">'+g.sz+'&#215;'+g.sz+'</span>':'')
      +'</div>';
  });
  listEl.innerHTML=rows||'<div class="gl-empty">All games on boards</div>';
}

})();
</script>
```

---

## Deploy checklist

1. **Upload `dgs-device-api.php`** — now fetches `g0_sgf` … `g5_sgf`
2. **Flush cache** (once after upload):
   ```
   https://knotwork.ca/dgs-device-api.php?key=kw_40e818911c1980bcd56dc4aff37a820f811990a130eb771fd9e21a536edc55ea&flush=1
   ```
3. **Paste markup** into TRMNL → Edit Markup → Full tab

---

## Layout at a glance

```
┌────────────────────────────────────────────────────────────────────────────┐
│ DGS · Shrimphead          4 games  [2 to move]            14:32 Mon 25     │ 36px
├──────────────────────────────────────────┬─────────────────────────────────┤
│                                          │  ┌─────────────────────────┐    │
│                                          │  │   compact board 1       │    │ 146px
│             MAIN BOARD                   │  └─────────────────────────┘    │
│              (fills height)              │  Opponent ● mv47 · 1d 8h        │ 26px
│                                          ├─────────────────────────────────┤
│                                          │  ┌─────────────────────────┐    │
│                                          │  │   compact board 2       │    │ 146px
│                                          │  └─────────────────────────┘    │
│                                          │  ▶ Opponent ○ mv23 · 0d 3h      │ 26px
├──────────────────────────────────────────┼─────────────────────────────────┤
│  ▶ Your move  Opponent  ●                │  OTHER GAMES                    │
│  Move 112 · 3d 2h · Cap: ●3 ○1 · #9271  │  ○ Wang  mv88  2d ·  ● Shin…   │ 54px
└──────────────────────────────────────────┴─────────────────────────────────┘
        ~534 px                                      266 px
```

---

## Notes

**Rotation** — `Math.floor(Date.now() / 1800000)` is a counter that increments
every 30 minutes. Main board = slot `t % N`, sides = `(t+1) % N` and `(t+2) % N`
where N = how many of the first 6 games have valid SGFs.
So each TRMNL refresh shows the same assignment within the current 30-minute
window; the boards "turn over" automatically on the next window.

**Captures** — counted during SGF replay (DFS liberty check). Displayed as
`Cap: ●3 ○1` meaning black captured 3 white stones and white captured 1 black
stone. Shows only if at least one capture has occurred.

**Time urgency** — times containing `0d` (less than 24 h remaining) are
highlighted in red on both the main stats bar and the game list.

**Board renderer** — pure inline SVG, no external libraries. Parses main line
only (variation branches stripped). Coordinate labels (A–T, 1–19) on main board;
compact boards use all space for the grid.

**SGF limit** — the PHP fetches SGFs for the 6 oldest games. If you have more
than 6 running games, the extras always appear in the game list but never as boards.
Raise `min(6, …)` in the PHP loop to increase the cap (each extra SGF = one extra
HTTP request at poll time).
