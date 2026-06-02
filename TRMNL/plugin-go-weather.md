# Plugin: go.weather
# Layout: 2/3 Go board + 1/3 weather sidebar
#
# Polling URL:
#   https://knotwork.ca/kaslo-api.php?key=YOUR_DEVICE_KEY&game=2
#
# Settings: Strategy: Polling | Verb: GET | Remove bleed margin: Yes
# Interval: 15 minutes minimum (DGS rate limit)

## Markup (Full tab)

```html
<style>
*{box-sizing:border-box;margin:0;padding:0}
html,body{width:100%;height:100%;overflow:hidden;background:#fff;color:#000;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif}
body{display:flex;flex-direction:column}
.hdr{height:36px;flex-shrink:0;display:flex;align-items:center;padding:0 14px;border-bottom:2px solid #000;gap:8px;overflow:hidden}
.hdr-t{font-weight:800;font-size:16px;letter-spacing:.02em}
.hdr-vs{font-size:14px;font-weight:700}
.hdr-stone{display:inline-flex;align-items:center;justify-content:center;width:14px;height:14px;border-radius:50%;flex-shrink:0}
.hdr-stone.b{background:#111}
.hdr-stone.w{background:#fff;border:1.5px solid #555}
.hdr-meta{font-size:11px;color:#666}
.badge{background:#000;color:#fff;font-size:10px;font-weight:700;padding:2px 7px;border-radius:2px;flex-shrink:0;margin-left:4px}
.hdr-r{font-size:12px;color:#777;margin-left:auto}
.content{display:flex;flex-direction:row;flex:1;min-height:0;overflow:hidden}
.main-panel{flex-shrink:0;overflow:hidden;border-right:2px solid #000;background:#D4A740}
.bw{overflow:hidden;background:#D4A740}
.side-panel{flex:1;min-width:0;overflow:hidden;display:flex;flex-direction:column}
.wx-inner{flex:1;overflow:hidden;display:flex;flex-direction:column;padding:4px 8px}
.wx-indoor{font-size:20px;color:#555;flex-shrink:0;background:#e0e0e0;padding:3px 6px;margin-bottom:3px}
.wx-indoor b{color:#000}
.wx-temp{font-size:64px;font-weight:200;line-height:1;flex-shrink:0}
.wx-temp sup{font-size:22px;vertical-align:.6em;font-weight:300}
.wx-cond{font-size:17px;font-weight:700;flex-shrink:0;margin-top:1px}
.wx-sub{font-size:13px;color:#555;flex-shrink:0;margin-top:1px}
.wx-hr{border:none;border-top:1px solid #ddd;margin:3px 0;flex-shrink:0}
.ws{font-size:13px;color:#555;line-height:1.45;flex-shrink:0}
.ws b{color:#000;font-weight:700}
.celestial{flex:1;min-height:0;display:flex;flex-direction:column;border-top:1px solid #e0e0e0;margin-top:3px;padding-top:3px;overflow:hidden}
.moon-box{flex-shrink:0;display:flex;flex-direction:column;align-items:center;gap:3px;background:#E8E8E8;border-radius:6px;padding:6px;margin:0 4px 4px}
.moon-nm{font-size:12px;color:#444;font-style:italic;text-align:center}
.dial-wrap{flex:1;min-height:0;display:flex;flex-direction:column;align-items:center;justify-content:center;overflow:hidden}
.sun-t{font-size:13px;font-weight:700;color:#333;text-align:center;flex-shrink:0;padding:4px 0}
</style>

<span id="tt" style="display:none">{{ trmnl.user.time | date: "%H:%M" }}</span>

<div class="hdr">
  <div class="hdr-t">Go &middot; Weather</div>
  <div>
    {% if go_opponent != "" %}
      <span class="hdr-stone {{ go_color }}"></span>
      <span class="hdr-vs">{{ go_opponent }}</span>
      <span class="hdr-meta">Mv {{ go_moves }}</span>
      {% if go_time_left != "" %}<span class="hdr-meta">{{ go_time_left }}</span>{% endif %}
    {% endif %}
    {% if go_my_turn_count > 0 %}<span class="badge">{{ go_my_turn_count }} to move</span>{% endif %}
  </div>
  <div class="hdr-r">{{ trmnl.user.time | date: "%H:%M %a %-d" }}</div>
</div>

<div class="content">
  <div class="main-panel" id="main-panel">
    <div class="bw" id="bw-main"></div>
  </div>
  <div class="side-panel" id="side-panel">
    <div class="wx-inner">
      <div class="wx-indoor">Indoor <b>{{ wx_indoor_temp }}&deg;</b> / <b>{{ wx_indoor_hum }}%</b></div>
      <div class="wx-temp">{{ wx_temp }}<sup>&deg;</sup></div>
      <div class="wx-cond">{{ fc_condition }}</div>
      <div class="wx-sub">Feels {{ wx_feels }}&deg; &bull; Hi {{ f0_hi }}&deg; / Lo {{ f0_lo }}&deg;</div>
      <hr class="wx-hr">
      <div class="ws">{{ wx_wdir }} <b>{{ wx_wind_kmh }}</b> km/h gust <b>{{ wx_gust_kmh }}</b> &bull; Rain <b>{{ wx_rain_day }}</b> mm</div>
      <div class="ws">Hum <b>{{ wx_humidity }}%</b> Dew <b>{{ wx_dew }}&deg;</b> UV <b>{{ wx_uvi }}</b> Sol <b>{{ wx_solar }}</b></div>
      <div class="celestial" id="celestial">
        <div class="moon-box" id="moon-top" style="display:none"></div>
        <div class="dial-wrap">
          <svg id="dial" style="display:block;flex-shrink:0"></svg>
          <div class="sun-t">&uarr; {{ sun_rise }} &nbsp;&nbsp; &darr; {{ sun_set }}</div>
        </div>
        <div class="moon-box" id="moon-bot" style="display:none"></div>
      </div>
    </div>
  </div>
</div>

<script>
(function(){
/* ── Board (identical to go.todo — known working) ── */
var HDR_H=36;
var VW=window.innerWidth||800;
var VH=window.innerHeight||480;
var cntH=VH-HDR_H;
var mainW=Math.floor(VW*0.67);
var sideW=VW-mainW-2;
var brdSz=Math.min(mainW,cntH);
var mpEl=document.getElementById('main-panel');
if(mpEl){mpEl.style.width=mainW+'px';mpEl.style.flex='0 0 '+mainW+'px';mpEl.style.height=cntH+'px';}
var bwEl=document.getElementById('bw-main');
if(bwEl){bwEl.style.width=brdSz+'px';bwEl.style.height=brdSz+'px';}
var spEl=document.getElementById('side-panel');
if(spEl){spEl.style.width=sideW+'px';}
var BK=JSON.parse('{{ go_board_black_json }}'||'[]');
var WH=JSON.parse('{{ go_board_white_json }}'||'[]');
var LC=parseInt('{{ go_last_col }}');
var LR=parseInt('{{ go_last_row }}');
var LCOL='{{ go_last_color }}';
(function(){
var VBsz=420,pad=14,lblPad=18,gridPx=VBsz-pad*2-lblPad,cell=gridPx/18;
var sr=Math.min(cell*0.47,11),ox=pad+lblPad,oy=pad;var S=[];
S.push('<rect x="'+(ox-pad*0.5).toFixed(1)+'" y="'+(oy-pad*0.5).toFixed(1)+'" width="'+(gridPx+pad).toFixed(1)+'" height="'+(gridPx+pad).toFixed(1)+'" fill="#D4A740" rx="2"/>');
var FILES='ABCDEFGHJKLMNOPQRST';
for(var ci=0;ci<19;ci++){S.push('<text x="'+(ox+ci*cell).toFixed(1)+'" y="'+(oy+gridPx+lblPad*0.78).toFixed(1)+'" text-anchor="middle" font-size="9" font-family="monospace" fill="#7A4A00">'+FILES[ci]+'</text>');S.push('<text x="'+(ox-lblPad*0.5).toFixed(1)+'" y="'+(oy+ci*cell+3.5).toFixed(1)+'" text-anchor="middle" font-size="9" font-family="monospace" fill="#7A4A00">'+(19-ci)+'</text>');}
for(var gi=0;gi<19;gi++){var sw=(gi===0||gi===18)?'1.6':'0.6';var gx=(ox+gi*cell).toFixed(1),gy=(oy+gi*cell).toFixed(1);S.push('<line x1="'+gx+'" y1="'+oy+'" x2="'+gx+'" y2="'+(oy+gridPx).toFixed(1)+'" stroke="#3A2000" stroke-width="'+sw+'"/>');S.push('<line x1="'+ox+'" y1="'+gy+'" x2="'+(ox+gridPx).toFixed(1)+'" y2="'+gy+'" stroke="#3A2000" stroke-width="'+sw+'"/>');}
var hr2=Math.max(1.2,cell*0.1);
[[3,3],[3,9],[3,15],[9,3],[9,9],[9,15],[15,3],[15,9],[15,15]].forEach(function(s){S.push('<circle cx="'+(ox+s[0]*cell).toFixed(1)+'" cy="'+(oy+s[1]*cell).toFixed(1)+'" r="'+hr2.toFixed(1)+'" fill="#3A2000"/>');});
BK.forEach(function(s){var cx=(ox+s[0]*cell).toFixed(1),cy=(oy+s[1]*cell).toFixed(1);S.push('<circle cx="'+cx+'" cy="'+cy+'" r="'+sr.toFixed(1)+'" fill="#111" stroke="#000" stroke-width="0.4"/>');S.push('<ellipse cx="'+(ox+s[0]*cell-sr*0.28).toFixed(1)+'" cy="'+(oy+s[1]*cell-sr*0.28).toFixed(1)+'" rx="'+(sr*0.22).toFixed(1)+'" ry="'+(sr*0.14).toFixed(1)+'" fill="rgba(255,255,255,0.18)" transform="rotate(-30,'+cx+','+cy+')"/>');});
WH.forEach(function(s){S.push('<circle cx="'+(ox+s[0]*cell).toFixed(1)+'" cy="'+(oy+s[1]*cell).toFixed(1)+'" r="'+sr.toFixed(1)+'" fill="#f8f8f8" stroke="#444" stroke-width="1"/>');});
if(LC>=0&&LR>=0)S.push('<circle cx="'+(ox+LC*cell).toFixed(1)+'" cy="'+(oy+LR*cell).toFixed(1)+'" r="'+(sr*0.32).toFixed(1)+'" fill="'+(LCOL==="B"?"#fff":"#222")+'"/>');
document.getElementById('bw-main').innerHTML='<svg xmlns="http://www.w3.org/2000/svg" width="'+brdSz+'" height="'+brdSz+'" viewBox="0 0 420 420" preserveAspectRatio="xMinYMin meet" style="display:block">'+S.join('')+'</svg>';
})();

/* ── Weather sidebar — try/catch so any error cannot kill the board ── */
try{
var NS='http://www.w3.org/2000/svg';
function mkN(tag,a){var e=document.createElementNS(NS,tag);for(var k in a)e.setAttribute(k,a[k]);return e;}


/* Moon position */
var phase=parseFloat('{{ moon_phase }}')||0;
var ts=((document.getElementById('tt')||{}).textContent||'12:00').trim();
var tp2=ts.split(':'),curH2=parseInt(tp2[0])||12,curDec=curH2+(parseInt(tp2[1])||0)/60;
var moonRise=(6+phase*24)%24,moonSet=(moonRise+12)%24;
var moonUp=moonRise<moonSet?(curDec>=moonRise&&curDec<moonSet):(curDec>=moonRise||curDec<moonSet);
var MN='{{ moon_name }}';
var MSZ=70,MH=MSZ+24;

function makeMoon(){
  var mr=MSZ/2-4,mc=MSZ/2;
  var parts=['<circle cx="'+mc+'" cy="'+mc+'" r="'+mr+'" fill="#555" stroke="#888" stroke-width="1.5"/>'];
  if(phase>0.98||phase<0.02){parts.push('<circle cx="'+mc+'" cy="'+mc+'" r="'+mr+'" fill="#FFF"/>');}
  else{var w=phase<=0.5,a=phase*2*Math.PI,ex=mr*Math.cos(a),ae=Math.abs(ex).toFixed(2),ms2,md;
    if(w){ms2=(ex>=0)?0:1;md='M '+mc+','+(mc-mr)+' A '+mr+','+mr+' 0 0 1 '+mc+','+(mc+mr)+' A '+ae+','+mr+' 0 0 '+ms2+' '+mc+','+(mc-mr)+'Z';}
    else{ms2=(ex>=0)?1:0;md='M '+mc+','+(mc-mr)+' A '+mr+','+mr+' 0 0 0 '+mc+','+(mc+mr)+' A '+ae+','+mr+' 0 0 '+ms2+' '+mc+','+(mc-mr)+'Z';}
    parts.push('<path d="'+md+'" fill="#FFF"/>');}
  return '<svg xmlns="http://www.w3.org/2000/svg" width="'+MSZ+'" height="'+MSZ+'" style="display:block">'+parts.join('')+'</svg><div class="moon-nm">'+MN+'</div>';
}

/* Calculate dial size BEFORE touching DOM */
var ABOVE_H=280,SUN_T_H=28,PADDING=10;
var celestialH=Math.max(80,cntH-ABOVE_H);
var availDial=celestialH-SUN_T_H-PADDING-(moonUp?MH+8:0);
var dialSz=Math.min(sideW-12,Math.max(70,availDial));

/* Show moon */
var mt=document.getElementById('moon-top'),mb=document.getElementById('moon-bot');
if(moonUp&&mt){mt.innerHTML=makeMoon();mt.style.display='flex';mt.style.height=MH+'px';}
else if(mb){mb.innerHTML=makeMoon();mb.style.display='flex';mb.style.height=MH+'px';}

/* Solar dial */
var dial=document.getElementById('dial');
if(dial){
  var dW=sideW-12;
  dial.setAttribute('width',dW);dial.setAttribute('height',dialSz);dial.style.flexShrink='0';
  var sr3='{{ sun_rise }}',ss3='{{ sun_set }}';
  if(sr3&&sr3.indexOf(':')>=0){
    var R=Math.min(dW,dialSz)/2-6,CX=dW/2,CY=dialSz/2;
    var toMin=function(t){var p=t.split(':');return parseInt(p[0])*60+parseInt(p[1]);};
    var mta=function(m){return 270-m/1440*360;};
    var txy=function(r,d){var rad=d*Math.PI/180;return[CX+r*Math.cos(rad),CY-r*Math.sin(rad)];};
    var rA=mta(toMin(sr3)),sA=mta(toMin(ss3));
    var ri=txy(R,rA),si=txy(R,sA),sp=((rA-sA)+360)%360;
    dial.appendChild(mkN('circle',{cx:CX,cy:CY,r:R,fill:'#E8E8E8'}));
    dial.appendChild(mkN('path',{d:'M '+CX.toFixed(1)+','+CY.toFixed(1)+' L '+ri[0].toFixed(1)+','+ri[1].toFixed(1)+' A '+R+' '+R+' 0 '+(sp>180?1:0)+' 1 '+si[0].toFixed(1)+' '+si[1].toFixed(1)+' Z',fill:'#FFF'}));
    dial.appendChild(mkN('circle',{cx:CX,cy:CY,r:R,fill:'none',stroke:'#999','stroke-width':'2.5'}));
  }
}
}catch(e){}
})();
</script>
```
