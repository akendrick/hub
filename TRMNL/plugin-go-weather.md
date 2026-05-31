# Plugin: go.weather
# Layout: 2/3 Go board (DGS game 2) + 1/3 weather stack
# Based on: plugin-dgs-v3.md layout architecture
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
html,body{width:800px;height:480px;overflow:hidden;background:#fff;color:#000;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif}
body{display:grid;grid-template-rows:24px 456px}

.hdr{display:flex;align-items:center;padding:0 10px;border-bottom:2px solid #000;gap:8px;overflow:hidden}
.hdr-t{font-weight:800;font-size:12px;letter-spacing:.06em;text-transform:uppercase;flex-shrink:0}
.hdr-vs{font-size:12px;font-weight:700}
.hdr-stone{display:inline-block;width:10px;height:10px;border-radius:50%;vertical-align:-1px;border:1px solid #444;flex-shrink:0}
.hdr-stone.b{background:#111;border-color:#111}
.hdr-stone.w{background:#fff;border-color:#555}
.hdr-meta{font-size:10px;color:#666}
.badge{background:#000;color:#fff;font-size:9px;font-weight:700;padding:1px 5px;border-radius:2px;flex-shrink:0}
.hdr-r{font-size:10px;color:#555;margin-left:auto}

.content{display:flex;flex-direction:row;width:800px;height:456px;overflow:hidden}
.board-panel{flex:0 0 534px;width:534px;height:456px;overflow:hidden;border-right:2px solid #000;background:#D4A740;display:flex;align-items:flex-start;justify-content:flex-start}
#bw{width:444px;height:444px;flex-shrink:0}

/* Weather panel */
.wx-panel{flex:0 0 266px;width:266px;height:456px;overflow:hidden;display:flex;flex-direction:column;padding:6px 10px 4px}
.wx-indoor{font-size:12px;color:#555;flex-shrink:0;margin-bottom:2px}
.wx-indoor b{color:#000}
.wx-temp{font-size:68px;font-weight:200;line-height:1;flex-shrink:0}
.wx-temp sup{font-size:24px;vertical-align:.6em;font-weight:300}
.wx-cond{font-size:15px;font-weight:700;flex-shrink:0;margin-top:2px}
.wx-sub{font-size:12px;color:#555;flex-shrink:0;margin-top:2px}
.wx-hr{border:none;border-top:1px solid #ddd;margin:5px 0;flex-shrink:0}
.ws{font-size:12px;color:#555;line-height:1.55;flex-shrink:0}
.ws b{color:#000;font-weight:700}
.sec{flex-shrink:0;border-top:1px solid #e0e0e0;padding-top:4px;margin-top:5px}
.sec-lbl{font-size:8px;text-transform:uppercase;letter-spacing:.1em;color:#aaa;margin-bottom:3px}
.prow{font-size:12px;font-weight:700;margin-top:2px}
.moon-row{display:flex;align-items:center;gap:10px}
.moon-name{font-size:12px;color:#555;font-style:italic}
.solar-sec{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:0;border-top:1px solid #e0e0e0;margin-top:5px;padding-top:4px}
.sun-times{font-size:12px;font-weight:600;color:#333;margin-top:3px}
</style>

<div class="hdr">
  <span class="hdr-t">Go &middot; Weather</span>
  {% if go_opponent != "" %}
    <span class="hdr-stone {{ go_color }}"></span>
    <span class="hdr-vs">{{ go_opponent }}</span>
    <span class="hdr-meta">Mv {{ go_moves }}</span>
    {% if go_time_left != "" %}<span class="hdr-meta">{{ go_time_left }}</span>{% endif %}
  {% endif %}
  {% if go_my_turn_count > 0 %}<span class="badge">{{ go_my_turn_count }} to move</span>{% endif %}
  <span class="hdr-r">{{ trmnl.user.time | date: "%H:%M %a %-d" }}</span>
</div>

<div class="content">

  <div class="board-panel"><div id="bw"></div></div>

  <div class="wx-panel">
    <div class="wx-indoor">Indoor <b>{{ wx_indoor_temp }}&deg;</b> / <b>{{ wx_indoor_hum }}%</b></div>
    <div class="wx-temp">{{ wx_temp }}<sup>&deg;</sup></div>
    <div class="wx-cond">{{ fc_condition }}</div>
    <div class="wx-sub">Feels {{ wx_feels }}&deg; &nbsp;&bull;&nbsp; Hi {{ f0_hi }}&deg; / Lo {{ f0_lo }}&deg;</div>
    <hr class="wx-hr">
    <div class="ws">{{ wx_wdir }} <b>{{ wx_wind_kmh }}</b> km/h &nbsp;gust <b>{{ wx_gust_kmh }}</b> &nbsp;&bull;&nbsp; Rain <b>{{ wx_rain_day }}</b> mm</div>
    <div class="ws">Hum <b>{{ wx_humidity }}%</b> &nbsp;Dew <b>{{ wx_dew }}&deg;</b> &nbsp;UV <b>{{ wx_uvi }}</b> &nbsp;Sol <b>{{ wx_solar }}</b></div>
    <div class="sec">
      <div class="sec-lbl">Pressure — 3 day trend</div>
      <svg id="psvg" width="246" height="28" style="display:block"></svg>
      <div class="prow">{{ fc_pressure_now }} hPa &mdash; {{ fc_pressure_trend }}</div>
    </div>
    <div class="sec">
      <div class="sec-lbl">Moon Phase</div>
      <div class="moon-row">
        <svg id="moon-svg" width="90" height="90" style="display:block;flex-shrink:0"></svg>
        <div class="moon-name">{{ moon_name }}</div>
      </div>
    </div>
    <div class="solar-sec">
      <svg id="sun-svg" width="200" height="200" style="display:block"></svg>
      <div class="sun-times">&uarr;&thinsp;{{ sun_rise }} &emsp; &darr;&thinsp;{{ sun_set }}</div>
    </div>
  </div>

</div>

<script>
(function(){
var NS='http://www.w3.org/2000/svg';
function mkNS(tag,a){var e=document.createElementNS(NS,tag);for(var k in a)e.setAttribute(k,a[k]);return e;}

/* Board — innerHTML approach matching dgs-v3 */
var BK=JSON.parse('{{ go_board_black_json }}'||'[]');
var WH=JSON.parse('{{ go_board_white_json }}'||'[]');
var LC=parseInt('{{ go_last_col }}');
var LR=parseInt('{{ go_last_row }}');
var LCOL='{{ go_last_color }}';
var W=444,H=444,VW=420,VH=420,pad=14,gridPx=VW-pad*2,cell=gridPx/18,sr=Math.min(cell*0.47,11),ox=pad,oy=pad;
var S=[];
S.push('<rect x="'+(ox-pad*0.5).toFixed(1)+'" y="'+(oy-pad*0.5).toFixed(1)+'" width="'+(gridPx+pad).toFixed(1)+'" height="'+(gridPx+pad).toFixed(1)+'" fill="#D4A740" rx="2"/>');
for(var i=0;i<19;i++){var sw=(i===0||i===18)?'1.6':'0.6';var gx=(ox+i*cell).toFixed(1),gy=(oy+i*cell).toFixed(1);S.push('<line x1="'+gx+'" y1="'+oy+'" x2="'+gx+'" y2="'+(oy+gridPx).toFixed(1)+'" stroke="#3A2000" stroke-width="'+sw+'"/>');S.push('<line x1="'+ox+'" y1="'+gy+'" x2="'+(ox+gridPx).toFixed(1)+'" y2="'+gy+'" stroke="#3A2000" stroke-width="'+sw+'"/>');}
var hr=Math.max(1.2,cell*0.1);
[[3,3],[3,9],[3,15],[9,3],[9,9],[9,15],[15,3],[15,9],[15,15]].forEach(function(s){S.push('<circle cx="'+(ox+s[0]*cell).toFixed(1)+'" cy="'+(oy+s[1]*cell).toFixed(1)+'" r="'+hr.toFixed(1)+'" fill="#3A2000"/>');});
BK.forEach(function(s){var cx=(ox+s[0]*cell).toFixed(1),cy=(oy+s[1]*cell).toFixed(1);S.push('<circle cx="'+cx+'" cy="'+cy+'" r="'+sr.toFixed(1)+'" fill="#111" stroke="#000" stroke-width="0.4"/>');S.push('<ellipse cx="'+(ox+s[0]*cell-sr*0.28).toFixed(1)+'" cy="'+(oy+s[1]*cell-sr*0.28).toFixed(1)+'" rx="'+(sr*0.22).toFixed(1)+'" ry="'+(sr*0.14).toFixed(1)+'" fill="rgba(255,255,255,0.18)" transform="rotate(-30,'+cx+','+cy+')"/>');});
WH.forEach(function(s){S.push('<circle cx="'+(ox+s[0]*cell).toFixed(1)+'" cy="'+(oy+s[1]*cell).toFixed(1)+'" r="'+sr.toFixed(1)+'" fill="#f8f8f8" stroke="#444" stroke-width="1"/>');});
if(LC>=0&&LR>=0)S.push('<circle cx="'+(ox+LC*cell).toFixed(1)+'" cy="'+(oy+LR*cell).toFixed(1)+'" r="'+(sr*0.32).toFixed(1)+'" fill="'+(LCOL==='B'?'#fff':'#222')+'"/>');
document.getElementById('bw').innerHTML='<svg xmlns="http://www.w3.org/2000/svg" width="'+W+'" height="'+H+'" viewBox="0 0 '+VW+' '+VH+'" preserveAspectRatio="xMinYMin meet" style="display:block">'+S.join('')+'</svg>';

/* Pressure sparkline */
var pressures=JSON.parse('{{ fc_pressure_json }}'||'[]');
if(pressures.length>1){
  var mn=pressures.reduce(function(a,b){return Math.min(a,b);}),mx=pressures.reduce(function(a,b){return Math.max(a,b);});
  var rng=mx-mn||0.5,PW=246,PH=28;
  var psvg=document.getElementById('psvg');
  var pts=pressures.map(function(v,i){return((i/(pressures.length-1))*(PW-4)+2).toFixed(1)+','+(PH-2-((v-mn)/rng)*(PH-6)).toFixed(1);}).join(' ');
  psvg.appendChild(mkNS('polyline',{points:pts,fill:'none',stroke:'#888','stroke-width':1.5,'stroke-linejoin':'round'}));
  var lv=pressures[pressures.length-1],ly=(PH-2-((lv-mn)/rng)*(PH-6));
  psvg.appendChild(mkNS('circle',{cx:(PW-2).toFixed(1),cy:ly.toFixed(1),r:3,fill:'#000'}));
}

/* Moon phase */
var phase=parseFloat('{{ moon_phase }}')||0;
var msvg=document.getElementById('moon-svg');
if(msvg){
  var mr=40,mcx=45,mcy=45;
  msvg.appendChild(mkNS('circle',{cx:mcx,cy:mcy,r:mr,fill:'#1a1a1a',stroke:'#666','stroke-width':1.5}));
  if(phase>0.98||(phase>=0&&phase<0.02)){
    msvg.appendChild(mkNS('circle',{cx:mcx,cy:mcy,r:mr,fill:'#e8e8e8'}));
  } else if(phase>=0.02){
    var waxing=(phase<=0.5),angle=phase*2*Math.PI,ellRx=mr*Math.cos(angle),absEll=Math.abs(ellRx).toFixed(2);
    var d,sw;
    if(waxing){sw=(ellRx>=0)?0:1;d='M '+mcx+','+(mcy-mr)+' A '+mr+','+mr+' 0 0 1 '+mcx+','+(mcy+mr)+' A '+absEll+','+mr+' 0 0 '+sw+' '+mcx+','+(mcy-mr)+'Z';}
    else{sw=(ellRx>=0)?1:0;d='M '+mcx+','+(mcy-mr)+' A '+mr+','+mr+' 0 0 0 '+mcx+','+(mcy+mr)+' A '+absEll+','+mr+' 0 0 '+sw+' '+mcx+','+(mcy-mr)+'Z';}
    msvg.appendChild(mkNS('path',{d:d,fill:'#e8e8e8'}));
  }
}

/* Solar dial */
var ssvg=document.getElementById('sun-svg');
if(ssvg){
  var sr2='{{ sun_rise }}',ss='{{ sun_set }}';
  if(sr2&&sr2.indexOf(':')>=0&&ss&&ss.indexOf(':')>=0){
    var CX=100,CY=100,SR=92;
    function toMin(t){var p=t.split(':');return parseInt(p[0],10)*60+parseInt(p[1],10);}
    function mta(m){return 270-(m/1440)*360;}
    function txy(r,deg){var rad=deg*Math.PI/180;return[CX+r*Math.cos(rad),CY-r*Math.sin(rad)];}
    var rA=mta(toMin(sr2)),sA=mta(toMin(ss));
    var ri=txy(SR,rA),si=txy(SR,sA),span=((rA-sA)+360)%360;
    ssvg.appendChild(mkNS('circle',{cx:CX,cy:CY,r:SR,fill:'#111'}));
    ssvg.appendChild(mkNS('path',{d:'M '+CX+','+CY+' L '+ri[0].toFixed(1)+','+ri[1].toFixed(1)+' A '+SR+' '+SR+' 0 '+(span>180?1:0)+' 1 '+si[0].toFixed(1)+' '+si[1].toFixed(1)+' Z',fill:'#888'}));
    ssvg.appendChild(mkNS('circle',{cx:CX,cy:CY,r:SR,fill:'none',stroke:'#444','stroke-width':2}));
    ssvg.appendChild(mkNS('line',{x1:CX,y1:CY-SR+2,x2:CX,y2:CY-SR+SR*0.16,stroke:'#666','stroke-width':2.5}));
    var now=new Date(),nA=mta(now.getHours()*60+now.getMinutes()),n1=txy(SR*0.5,nA),n2=txy(SR,nA);
    ssvg.appendChild(mkNS('line',{x1:n1[0].toFixed(1),y1:n1[1].toFixed(1),x2:n2[0].toFixed(1),y2:n2[1].toFixed(1),stroke:'#fff','stroke-width':4,'stroke-linecap':'round'}));
  }
}
})();
</script>
```
