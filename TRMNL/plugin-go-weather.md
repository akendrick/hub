# Plugin: go.weather
# Based on: plugin-dgs-v3.md — window.innerWidth/Height for layout sizing
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
.wx-inner{flex:1;overflow:hidden;display:flex;flex-direction:column;padding:6px 10px 4px}
.wx-indoor{font-size:13px;color:#555;flex-shrink:0;margin-bottom:2px}
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
.moon-nm{font-size:12px;color:#555;font-style:italic}
.solar-sec{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:0;border-top:1px solid #e0e0e0;margin-top:5px;padding-top:4px}
.sun-times{font-size:12px;font-weight:600;color:#333;margin-top:3px}
</style>

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
      <div class="wx-sub">Feels {{ wx_feels }}&deg; &nbsp;&bull;&nbsp; Hi {{ f0_hi }}&deg; / Lo {{ f0_lo }}&deg;</div>
      <hr class="wx-hr">
      <div class="ws">{{ wx_wdir }} <b>{{ wx_wind_kmh }}</b> km/h &nbsp;gust <b>{{ wx_gust_kmh }}</b> &nbsp;&bull;&nbsp; Rain <b>{{ wx_rain_day }}</b> mm</div>
      <div class="ws">Hum <b>{{ wx_humidity }}%</b> &nbsp;Dew <b>{{ wx_dew }}&deg;</b> &nbsp;UV <b>{{ wx_uvi }}</b> &nbsp;Sol <b>{{ wx_solar }}</b></div>
      <div class="sec">
        <div class="sec-lbl">Pressure &mdash; 3 day trend</div>
        <svg id="psvg" height="28" style="display:block;width:100%"></svg>
        <div class="prow">{{ fc_pressure_now }} hPa &mdash; {{ fc_pressure_trend }}</div>
      </div>
      <div class="sec">
        <div class="sec-lbl">Moon Phase</div>
        <div class="moon-row"><svg id="moon-svg" width="90" height="90" style="display:block;flex-shrink:0"></svg><div class="moon-nm">{{ moon_name }}</div></div>
      </div>
      <div class="solar-sec">
        <svg id="sun-svg" style="display:block;width:100%;flex:1;min-height:0"></svg>
        <div class="sun-times">&uarr;&thinsp;{{ sun_rise }} &emsp; &darr;&thinsp;{{ sun_set }}</div>
      </div>
    </div>
  </div>
</div>

<script>
(function(){
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
var sr=Math.min(cell*0.47,11),ox=pad+lblPad,oy=pad;
var S=[];
S.push('<rect x="'+(ox-pad*0.5).toFixed(1)+'" y="'+(oy-pad*0.5).toFixed(1)+'" width="'+(gridPx+pad).toFixed(1)+'" height="'+(gridPx+pad).toFixed(1)+'" fill="#D4A740" rx="2"/>');
var FILES='ABCDEFGHJKLMNOPQRST';
for(var ci=0;ci<19;ci++){
  S.push('<text x="'+(ox+ci*cell).toFixed(1)+'" y="'+(oy+gridPx+lblPad*0.78).toFixed(1)+'" text-anchor="middle" font-size="9" font-family="monospace" fill="#7A4A00">'+FILES[ci]+'</text>');
  S.push('<text x="'+(ox-lblPad*0.5).toFixed(1)+'" y="'+(oy+ci*cell+3.5).toFixed(1)+'" text-anchor="middle" font-size="9" font-family="monospace" fill="#7A4A00">'+(19-ci)+'</text>');
}
for(var gi=0;gi<19;gi++){var sw=(gi===0||gi===18)?'1.6':'0.6';var gx=(ox+gi*cell).toFixed(1),gy=(oy+gi*cell).toFixed(1);S.push('<line x1="'+gx+'" y1="'+oy+'" x2="'+gx+'" y2="'+(oy+gridPx).toFixed(1)+'" stroke="#3A2000" stroke-width="'+sw+'"/>');S.push('<line x1="'+ox+'" y1="'+gy+'" x2="'+(ox+gridPx).toFixed(1)+'" y2="'+gy+'" stroke="#3A2000" stroke-width="'+sw+'"/>');}
var hr=Math.max(1.2,cell*0.1);
[[3,3],[3,9],[3,15],[9,3],[9,9],[9,15],[15,3],[15,9],[15,15]].forEach(function(s){S.push('<circle cx="'+(ox+s[0]*cell).toFixed(1)+'" cy="'+(oy+s[1]*cell).toFixed(1)+'" r="'+hr.toFixed(1)+'" fill="#3A2000"/>');});
BK.forEach(function(s){var cx=(ox+s[0]*cell).toFixed(1),cy=(oy+s[1]*cell).toFixed(1);S.push('<circle cx="'+cx+'" cy="'+cy+'" r="'+sr.toFixed(1)+'" fill="#111" stroke="#000" stroke-width="0.4"/>');S.push('<ellipse cx="'+(ox+s[0]*cell-sr*0.28).toFixed(1)+'" cy="'+(oy+s[1]*cell-sr*0.28).toFixed(1)+'" rx="'+(sr*0.22).toFixed(1)+'" ry="'+(sr*0.14).toFixed(1)+'" fill="rgba(255,255,255,0.18)" transform="rotate(-30,'+cx+','+cy+')"/>');});
WH.forEach(function(s){S.push('<circle cx="'+(ox+s[0]*cell).toFixed(1)+'" cy="'+(oy+s[1]*cell).toFixed(1)+'" r="'+sr.toFixed(1)+'" fill="#f8f8f8" stroke="#444" stroke-width="1"/>');});
if(LC>=0&&LR>=0)S.push('<circle cx="'+(ox+LC*cell).toFixed(1)+'" cy="'+(oy+LR*cell).toFixed(1)+'" r="'+(sr*0.32).toFixed(1)+'" fill="'+(LCOL==='B'?'#fff':'#222')+'"/>');
document.getElementById('bw-main').innerHTML='<svg xmlns="http://www.w3.org/2000/svg" width="'+brdSz+'" height="'+brdSz+'" viewBox="0 0 420 420" preserveAspectRatio="xMinYMin meet" style="display:block">'+S.join('')+'</svg>';
})();
var NS='http://www.w3.org/2000/svg';
function mkN(tag,a){var e=document.createElementNS(NS,tag);for(var k in a)e.setAttribute(k,a[k]);return e;}
var prs=JSON.parse('{{ fc_pressure_json }}'||'[]');
if(prs.length>1){var mn2=prs.reduce(function(a,b){return Math.min(a,b);}),mx2=prs.reduce(function(a,b){return Math.max(a,b);});var rng2=mx2-mn2||0.5,PH=28;var psv=document.getElementById('psvg');var PW=psv?psv.parentElement.clientWidth||sideW-20:sideW-20;psv.setAttribute('width',PW);var pts=prs.map(function(v,i){return((i/(prs.length-1))*(PW-4)+2).toFixed(1)+','+(PH-2-((v-mn2)/rng2)*(PH-6)).toFixed(1);}).join(' ');psv.appendChild(mkN('polyline',{points:pts,fill:'none',stroke:'#888','stroke-width':'1.5','stroke-linejoin':'round'}));var lv2=prs[prs.length-1],ly2=(PH-2-((lv2-mn2)/rng2)*(PH-6));psv.appendChild(mkN('circle',{cx:(PW-2).toFixed(1),cy:ly2.toFixed(1),r:'3',fill:'#000'}));}
var phase=parseFloat('{{ moon_phase }}')||0;var msv=document.getElementById('moon-svg');
if(msv){var mr=40,mcx=45,mcy=45;msv.appendChild(mkN('circle',{cx:mcx,cy:mcy,r:mr,fill:'#1a1a1a',stroke:'#666','stroke-width':'1.5'}));if(phase>0.98||(phase>=0&&phase<0.02)){msv.appendChild(mkN('circle',{cx:mcx,cy:mcy,r:mr,fill:'#e8e8e8'}));}else if(phase>=0.02){var wax=(phase<=0.5),ang=phase*2*Math.PI,eRx=mr*Math.cos(ang),aE=Math.abs(eRx).toFixed(2);var md,ms;if(wax){ms=(eRx>=0)?0:1;md='M '+mcx+','+(mcy-mr)+' A '+mr+','+mr+' 0 0 1 '+mcx+','+(mcy+mr)+' A '+aE+','+mr+' 0 0 '+ms+' '+mcx+','+(mcy-mr)+'Z';}else{ms=(eRx>=0)?1:0;md='M '+mcx+','+(mcy-mr)+' A '+mr+','+mr+' 0 0 0 '+mcx+','+(mcy+mr)+' A '+aE+','+mr+' 0 0 '+ms+' '+mcx+','+(mcy-mr)+'Z';}msv.appendChild(mkN('path',{d:md,fill:'#e8e8e8'}));}}
var ssv=document.getElementById('sun-svg');
if(ssv){var sr2='{{ sun_rise }}',ss='{{ sun_set }}';if(sr2&&sr2.indexOf(':')>=0&&ss&&ss.indexOf(':')>=0){var sw=ssv.parentElement.clientWidth||sideW-20,sh=ssv.clientHeight||120;var SR3=Math.min(Math.min(sw,sh)/2-4,90);var CX=sw/2,CY=sh/2;ssv.setAttribute('width',sw);ssv.setAttribute('height',sh);function toMin(t){var p=t.split(':');return parseInt(p[0],10)*60+parseInt(p[1],10);}function mta(m){return 270-(m/1440)*360;}function txy(r,deg){var rad=deg*Math.PI/180;return[CX+r*Math.cos(rad),CY-r*Math.sin(rad)];}var rA=mta(toMin(sr2)),sA=mta(toMin(ss));var ri=txy(SR3,rA),si=txy(SR3,sA),span3=((rA-sA)+360)%360;ssv.appendChild(mkN('circle',{cx:CX,cy:CY,r:SR3,fill:'#111'}));ssv.appendChild(mkN('path',{d:'M '+CX+','+CY+' L '+ri[0].toFixed(1)+','+ri[1].toFixed(1)+' A '+SR3+' '+SR3+' 0 '+(span3>180?1:0)+' 1 '+si[0].toFixed(1)+' '+si[1].toFixed(1)+' Z',fill:'#888'}));ssv.appendChild(mkN('circle',{cx:CX,cy:CY,r:SR3,fill:'none',stroke:'#444','stroke-width':'2'}));var now=new Date(),nA=mta(now.getHours()*60+now.getMinutes()),n1=txy(SR3*0.5,nA),n2=txy(SR3,nA);ssv.appendChild(mkN('line',{x1:n1[0].toFixed(1),y1:n1[1].toFixed(1),x2:n2[0].toFixed(1),y2:n2[1].toFixed(1),stroke:'#fff','stroke-width':'4','stroke-linecap':'round'}));}}
})();
</script>
```
