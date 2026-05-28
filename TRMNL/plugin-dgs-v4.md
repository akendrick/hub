# Plugin: Go Games · DGS (v4 — viewport-adaptive layout)
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
# Layout (adapts to actual renderer viewport — no hardcoded 800×480)
#   Header  36 px
#   LEFT  ~67% width — main board (largest square that fits the height)
#   RIGHT ~33% width — two compact boards with stats, then remaining game list
#
# Rotation: JS selects which games to show as boards based on
#   Math.floor(Date.now() / 1800000)  — changes every 30 minutes.
#   Main board = slot t%N, side1 = (t+1)%N, side2 = (t+2)%N
#   where N = number of games that have a valid SGF (up to 6).
#
# Settings: Strategy: Polling | Verb: GET | Remove bleed margin: Yes
#   Polling interval: 15 min minimum (DGS request)
#
# KEY FIX in v4: ALL pixel dimensions are computed in JS from window.innerWidth /
# window.innerHeight rather than hardcoded. This means the layout works correctly
# regardless of the actual viewport the TRMNL renderer provides.
#
# IMPORTANT: paste ONLY the HTML below (starting with <style>) into the
# TRMNL "Full" markup tab. Do NOT paste this header or the ``` fences.

## Markup (Full tab)

```html
<style>
*{box-sizing:border-box;margin:0;padding:0}
html,body{width:100%;height:100%;overflow:hidden;background:#fff;color:#000;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif}
body{display:flex;flex-direction:column}

/* ── Header ── */
.hdr{height:36px;flex-shrink:0;display:flex;align-items:center;justify-content:space-between;padding:0 14px;border-bottom:2px solid #000;overflow:hidden}
.hdr-t{font-weight:800;font-size:18px;letter-spacing:.02em}
.hdr-c{font-size:13px;color:#555}
.badge{background:#000;color:#fff;font-size:11px;font-weight:700;padding:2px 8px;border-radius:2px;margin-left:7px}
.hdr-r{font-size:13px;color:#777}

/* ── Content row — fills remaining height ── */
.content{display:flex;flex-direction:row;flex:1;min-height:0;overflow:hidden}

/* ── Main panel: left ~67% — JS sets explicit width + board size ── */
.main-panel{flex-shrink:0;overflow:hidden;border-right:2px solid #000}
.bw{overflow:hidden;background:#D4A740}

/* ── Side panel: right ~33% ── */
.side-panel{flex:1;min-width:0;overflow:hidden;display:flex;flex-direction:column}

/* Main game info strip at top of side panel */
.mg-info{flex:0 0 50px;height:50px;border-bottom:2px solid #000;padding:5px 8px;display:flex;flex-direction:column;justify-content:center;gap:3px;overflow:hidden}
.mg-r1{display:flex;align-items:center;gap:6px;font-size:13px;font-weight:700;white-space:nowrap;overflow:hidden}
.mg-r1 .yt{color:#000;font-weight:900;font-size:12px}
.mg-r1 .wt{color:#aaa;font-size:12px}
.sd{display:inline-flex;align-items:center;justify-content:center;width:15px;height:15px;border-radius:50%;flex-shrink:0}
.sd.b{background:#111}
.sd.w{background:#fff;border:1.5px solid #555}
.mg-r2{font-size:10px;color:#888;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.mg-r2 .urg{color:#c00;font-weight:700}

/* Side board items — JS stamps explicit px height onto each */
.bs-item{flex-shrink:0;overflow:hidden;display:flex;flex-direction:column;border-bottom:1px solid #ccc}
.bs-bw{flex-shrink:0;overflow:hidden}
.bs-stats{flex:0 0 26px;height:26px;border-top:1px solid #eee;padding:3px 7px;font-size:10px;color:#666;display:flex;align-items:center;gap:5px;white-space:nowrap;overflow:hidden}
.bs-stats .yt{color:#000;font-weight:900;font-size:11px;flex-shrink:0}
.bs-stats b{color:#000;font-weight:700}
.sds{display:inline-block;width:10px;height:10px;border-radius:50%;flex-shrink:0;vertical-align:middle}
.sds.b{background:#111}
.sds.w{background:#fff;border:1px solid #666}
.bs-stats .urg{color:#c00;font-weight:700}

/* Game list — claims whatever height remains */
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

/* No-board placeholder — JS sets height */
.no-board{display:flex;align-items:center;justify-content:center;font-size:11px;color:#ccc;font-style:italic}
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

  <!-- Left: main board -->
  <div class="main-panel" id="main-panel">
    <div class="bw" id="bw-main"></div>
  </div>

  <!-- Right: main game info + two compact boards + game list -->
  <div class="side-panel">

    <div class="mg-info">
      <div class="mg-r1" id="mg-r1"><span class="wt">—</span></div>
      <div class="mg-r2" id="mg-r2"></div>
    </div>

    <div class="bs-item" id="bs-item-1">
      <div class="bs-bw" id="bw-s1"></div>
      <div class="bs-stats" id="bs-stats-1"></div>
    </div>

    <div class="bs-item" id="bs-item-2">
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
// 3. 30-minute rotation — pick which SGF slots to show as boards
// ════════════════════════════════════════════════════════════════════
var validIdx=[];
for(var vi=0;vi<SGF.length;vi++) if(SGF[vi]) validIdx.push(vi);
var vl=validIdx.length;
var t=Math.floor(Date.now()/1800000);
// bIdx[0]=main board, bIdx[1]=side board 1, bIdx[2]=side board 2
// -1 means nothing to render in that slot
var bIdx=[-1,-1,-1];
if(vl>0) bIdx[0]=validIdx[t%vl];
if(vl>1) bIdx[1]=validIdx[(t+1)%vl];
if(vl>2) bIdx[2]=validIdx[(t+2)%vl];

// ════════════════════════════════════════════════════════════════════
// 4. LAYOUT — compute all pixel dimensions from actual viewport size.
//    This is the key fix: no hardcoded 800×480 assumptions anywhere.
//    TRMNL's headless renderer sets the viewport; we adapt to it.
// ════════════════════════════════════════════════════════════════════
var HDR_H   = 36;
var VW      = window.innerWidth  || 800;
var VH      = window.innerHeight || 480;
var cntH    = VH - HDR_H;           // content area height

// Main panel: ~67% of width. Board is the largest square that fits.
var mainW   = Math.floor(VW * 0.67);
var sideW   = VW - mainW - 2;       // −2 for the 2px border
var brdSz   = Math.min(mainW, cntH);

// Side board sizing: two boards share the height below the mg-info strip.
// Each board gets 43% of that space; the game list gets the remainder.
var MGINFO_H = 50;
var STATS_H  = 26;
var sideAvail = cntH - MGINFO_H;
var bsItmH   = Math.floor(sideAvail * 0.43);
var bsBwH    = bsItmH - STATS_H;

// Apply explicit pixel widths/heights to DOM elements.
// This stamps real numbers onto elements so overflow:hidden clips correctly
// even if the renderer's CSS flex engine has quirks.
var mpEl = document.getElementById('main-panel');
if(mpEl){ mpEl.style.width=mainW+'px'; mpEl.style.flex='0 0 '+mainW+'px'; }

var bwEl = document.getElementById('bw-main');
if(bwEl){ bwEl.style.width=brdSz+'px'; bwEl.style.height=brdSz+'px'; }

['bs-item-1','bs-item-2'].forEach(function(id){
  var el=document.getElementById(id);
  if(el){ el.style.height=bsItmH+'px'; el.style.flex='0 0 '+bsItmH+'px'; }
});
['bw-s1','bw-s2'].forEach(function(id){
  var el=document.getElementById(id);
  if(el){ el.style.height=bsBwH+'px'; el.style.flex='0 0 '+bsBwH+'px'; }
});

// ════════════════════════════════════════════════════════════════════
// 5. SGF parser — main line only; returns board state + capture counts
// ════════════════════════════════════════════════════════════════════
function parseSGF(sgf){
  var sz=19;
  var szm=sgf.match(/SZ\[(\d+)\]/); if(szm) sz=Math.max(2,Math.min(25,parseInt(szm[1])));
  var board=new Array(sz*sz).fill(0);
  var bcap=0,wcap=0;
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
// 6. Board renderer — injects SVG into container element.
//    Uses brdSz (main) or sideW/bsBwH (compact) computed in section 4.
// ════════════════════════════════════════════════════════════════════
function renderBoard(containerId,p,compact){
  var wrap=document.getElementById(containerId);
  if(!wrap||!p) return;
  var sz=p.sz,board=p.board,lx=p.lx,ly=p.ly,lc=p.lc;
  function idx(x,y){return y*sz+x;}

  // Pixel dimensions come from the layout computation above.
  var W=compact?sideW:brdSz;
  var H=compact?bsBwH:brdSz;

  // SVG viewBox is always square; board fills it with padding for labels.
  var VBsz=compact?210:420;
  var pad=compact?5:14;
  var lblPad=compact?0:18;
  var gridPx=VBsz-pad*2-lblPad;
  var cell=gridPx/(sz-1);
  var sr=Math.min(cell*0.47,compact?6:11);
  var ox=pad+lblPad,oy=pad;
  var S=[];

  // Board background
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

  // SVG: explicit px width/height so the renderer doesn't need to resolve percentages.
  // preserveAspectRatio: main board anchors top-left; compact boards centre.
  var par=compact?'xMidYMid meet':'xMinYMin meet';
  wrap.innerHTML='<svg xmlns="http://www.w3.org/2000/svg"'
    +' width="'+W+'" height="'+H+'"'
    +' viewBox="0 0 '+VBsz+' '+VBsz+'"'
    +' preserveAspectRatio="'+par+'"'
    +' style="display:block">'
    +S.join('')+'</svg>';
}

// ════════════════════════════════════════════════════════════════════
// 7. Parse + render all three boards
// ════════════════════════════════════════════════════════════════════
var parsed=[null,null,null];
[0,1,2].forEach(function(pi){
  if(bIdx[pi]<0) return;
  var p=parseSGF(SGF[bIdx[pi]]);
  parsed[pi]=p;
  var ids=['bw-main','bw-s1','bw-s2'];
  renderBoard(ids[pi],p,pi>0);
});

// Placeholders for empty side slots
if(bIdx[1]<0){
  var e1=document.getElementById('bw-s1');
  if(e1){e1.innerHTML='<div class="no-board" style="height:'+bsBwH+'px">—</div>';}
}
if(bIdx[2]<0){
  var e2=document.getElementById('bw-s2');
  if(e2){e2.innerHTML='<div class="no-board" style="height:'+bsBwH+'px">—</div>';}
}

// ════════════════════════════════════════════════════════════════════
// 8. Urgency helper — highlight times containing "0d" (< 24 h)
// ════════════════════════════════════════════════════════════════════
function urgSpan(tm){
  if(!tm) return '';
  return tm.toLowerCase().indexOf('0d')>=0
    ?'<span class="urg">'+tm+'</span>':tm;
}

// ════════════════════════════════════════════════════════════════════
// 9. Main game stats — displayed in side panel mg-info strip
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
// 10. Side board stats
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

// ════════════════════════════════════════════════════════════════════
// 11. Game list — all games NOT currently shown as boards
// ════════════════════════════════════════════════════════════════════
var shown={};
bIdx.forEach(function(bi){if(bi>=0) shown[bi]=1;});
var listEl=document.getElementById('game-list');
if(listEl){
  var rows='';
  GAMES.forEach(function(g){
    if(shown[g.i]) return;
    rows+='<div class="gl-row'+(g.yt?' yt-row':'')+'">'
      +'<span class="sds '+(g.col==='B'?'b':'w')+'"></span>'
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

## What changed from v3

| v3 (broken) | v4 (fixed) |
|---|---|
| `html,body{width:800px;height:480px}` | `html,body{width:100%;height:100%}` |
| `body{display:grid;grid-template-rows:36px 1fr}` | `body{display:flex;flex-direction:column}` |
| `.content{height:444px;width:800px}` | `.content{flex:1;min-height:0}` (fills remaining height) |
| `.main-panel{flex:0 0 534px}` hardcoded | `.main-panel` width stamped by JS |
| SVG `width="444" height="444"` hardcoded | SVG dims computed from `window.innerWidth`/`innerHeight` |
| `.bs-item{height:170px}` hardcoded | heights computed from actual `cntH` and stamped by JS |

The root cause was that TRMNL's headless renderer provides a viewport that is not
exactly 800×480 CSS pixels (likely due to DPI scaling or other renderer settings).
Our hardcoded `width:800px` overflowed the actual viewport; `overflow:hidden` then
clipped the entire side panel off the right edge.

The v4 fix: **CSS defines structure only** (flex ratios, header height); **JS measures
the actual viewport** via `window.innerWidth`/`window.innerHeight` and stamps explicit
pixel values onto every container and SVG element before rendering any boards.

---

## Deploy checklist

1. **Paste markup** into TRMNL → Edit Markup → Full tab (replace all of v3)
2. No changes needed to `dgs-device-api.php` — same API, same SGF variables

---

## Layout at a glance (at 800×480)

```
JS computes:
  mainW  = floor(800 × 0.67) = 536 px
  sideW  = 800 − 536 − 2    = 262 px
  brdSz  = min(536, 444)     = 444 px  (square main board)
  bsItmH = floor(394 × 0.43) = 169 px  (per side board section)
  bsBwH  = 169 − 26          = 143 px  (board within section)

┌──────────────────────────────────────────────────────────────────────────────┐
│ DGS · Shrimphead            4 games  [2 to move]           14:32 Mon 25      │ 36px
├────────────────────────────────────────────┬───────────────────────────────│
│                                            │  ▶ McDarsh  ●                  │ 50px
│                                            │  mv68 · F: 30d · #1498436      │
│                                            ├───────────────────────────────┤
│                                            │  ┌──────────────────────────┐  │
│          MAIN BOARD 444×444                │  │  compact board 262×143   │  │ 169px
│          (full height)                     │  └──────────────────────────┘  │
│                                            │  ○ nikonor  mv108 · 30d        │ 26px
│                                            ├───────────────────────────────┤
│                                            │  ┌──────────────────────────┐  │
│                                            │  │  compact board 262×143   │  │ 169px
│                                            │  └──────────────────────────┘  │
│                                            │  ● WallSocket  mv13 · 2d 12h   │ 26px
│                                            ├───────────────────────────────┤
│                                            │  OTHER GAMES                   │
│                                            │  ● lobird  mv17  29d 11h       │ ~30px
└────────────────────────────────────────────┴───────────────────────────────┘
         536 px                                        262 px
```
