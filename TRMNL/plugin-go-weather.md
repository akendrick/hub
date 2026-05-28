# Plugin: go.weather
# Layout: 2/3 Go board (DGS game 2) + 1/3 weather stack
#   (current conditions → pressure trend → moon phase → solar dial)
#
# Polling URL:
#   https://knotwork.ca/kaslo-api.php?key=YOUR_DEVICE_KEY&game=2
#
# Settings: Strategy: Polling | Verb: GET | Remove bleed margin: Yes
# Interval: 15 minutes minimum (DGS rate limit)

## Markup (Full tab)

```html
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html,body{width:800px;height:480px;overflow:hidden;background:#fff;color:#000}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Arial,sans-serif;font-size:12px;display:flex;flex-direction:column}

.hdr{height:24px;display:flex;align-items:center;padding:0 10px;border-bottom:2px solid #000;flex-shrink:0;gap:10px}
.hdr-title{font-weight:800;font-size:12px;letter-spacing:.06em;text-transform:uppercase}
.hdr-vs{font-size:12px;font-weight:700}
.hdr-stone{display:inline-block;width:10px;height:10px;border-radius:50%;vertical-align:-1px;border:1px solid #444;flex-shrink:0}
.hdr-stone.b{background:#111;border-color:#111}
.hdr-stone.w{background:#fff;border-color:#555}
.hdr-meta{font-size:10px;color:#666}
.hdr-time{font-size:10px;color:#555;margin-left:auto}
.badge{background:#000;color:#fff;font-size:9px;font-weight:700;padding:1px 5px;border-radius:2px;flex-shrink:0}

.body{display:flex;flex:1;min-height:0}
.board-col{width:533px;flex-shrink:0;overflow:hidden;border-right:2px solid #000}

/* Weather sidebar */
.wx-col{flex:1;display:flex;flex-direction:column;overflow:hidden;padding:6px 10px 4px}
.wx-temp{font-size:54px;font-weight:200;line-height:1;letter-spacing:-.02em;flex-shrink:0}
.wx-temp sup{font-size:22px;vertical-align:.6em;font-weight:300}
.wx-cond{font-size:12px;font-weight:700;flex-shrink:0;margin-top:1px}
.wx-feels{font-size:10px;color:#555;flex-shrink:0;margin-top:1px}
.wx-hr{border:none;border-top:1px solid #ddd;margin:4px 0;flex-shrink:0}
.ws{font-size:10px;color:#555;line-height:1.55;flex-shrink:0}
.ws b{color:#000;font-weight:700}
.wx-indoor{font-size:9px;color:#aaa;flex-shrink:0;margin-top:1px}

.sec{flex-shrink:0;border-top:1px solid #e0e0e0;padding-top:4px;margin-top:5px}
.sec-lbl{font-size:8px;text-transform:uppercase;letter-spacing:.1em;color:#aaa;margin-bottom:3px}
.prow{font-size:11px;font-weight:700;margin-top:3px;display:flex;align-items:center;gap:6px}
.prow span{font-size:13px}

.moon-inner{display:flex;align-items:center;gap:10px}
.moon-name{font-size:10px;color:#555;font-style:italic;line-height:1.3}

.wx-solar{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:0;border-top:1px solid #e0e0e0;margin-top:5px;padding-top:4px}
.sun-times{font-size:11px;font-weight:600;color:#333;margin-top:3px;letter-spacing:.04em}
</style>

<div class="hdr">
  <span class="hdr-title">Go &middot; Weather</span>
  {% if IDX_0.go.opponent != "" %}
    <span class="hdr-stone {{ IDX_0.go.color | downcase }}"></span>
    <span class="hdr-vs">{{ IDX_0.go.opponent }}</span>
    <span class="hdr-meta">Mv {{ IDX_0.go.moves }}</span>
    {% if IDX_0.go.time_left != "" %}<span class="hdr-meta">{{ IDX_0.go.time_left }}</span>{% endif %}
  {% endif %}
  {% if IDX_0.go.my_turn_count > 0 %}<span class="badge">{{ IDX_0.go.my_turn_count }} to move</span>{% endif %}
  <span class="hdr-time">{{ trmnl.user.time | date: "%H:%M %a %-d" }}</span>
</div>

<div class="body">

  <div class="board-col">
    <svg id="bsvg" width="533" height="456" style="display:block"></svg>
  </div>

  <div class="wx-col">

    <div class="wx-temp">{{ IDX_0.wx.temp }}<sup>&deg;</sup></div>
    <div class="wx-cond">{{ IDX_0.fc.current_condition }}</div>
    <div class="wx-feels">Feels {{ IDX_0.wx.feels }}&deg; &nbsp;&bull;&nbsp; Hi {{ IDX_0.fc.days[0].hi }}&deg; / Lo {{ IDX_0.fc.days[0].lo }}&deg;</div>
    <hr class="wx-hr">
    <div class="ws">{{ IDX_0.wx.wdir }} <b>{{ IDX_0.wx.wind_kmh }}</b> km/h &nbsp;gust <b>{{ IDX_0.wx.gust_kmh }}</b> &nbsp;&bull;&nbsp; Rain <b>{{ IDX_0.wx.rain_day }}</b> mm</div>
    <div class="ws">Hum <b>{{ IDX_0.wx.humidity }}%</b> &nbsp;Dew <b>{{ IDX_0.wx.dew }}&deg;</b> &nbsp;UV <b>{{ IDX_0.wx.uvi }}</b> &nbsp;Sol <b>{{ IDX_0.wx.solar }}</b></div>
    <div class="wx-indoor">Indoor {{ IDX_0.wx.indoor_temp }}&deg; / {{ IDX_0.wx.indoor_hum }}%</div>

    <div class="sec">
      <div class="sec-lbl">Pressure &mdash; 3 day trend</div>
      <svg id="psvg" width="247" height="32" style="display:block"></svg>
      <div class="prow"><span id="pval">{{ IDX_0.fc.pressure_now }}</span> hPa &nbsp;{{ IDX_0.fc.pressure_trend }}</div>
    </div>

    <div class="sec">
      <div class="sec-lbl">Moon Phase</div>
      <div class="moon-inner">
        <svg id="moon-svg" width="50" height="50" style="display:block;flex-shrink:0"></svg>
        <div class="moon-name">{{ IDX_0.moon.name }}</div>
      </div>
    </div>

    <div class="wx-solar">
      <svg id="sun-svg" width="170" height="170" style="display:block"></svg>
      <div class="sun-times">&uarr;&thinsp;{{ IDX_0.sun.rise }} &emsp; &darr;&thinsp;{{ IDX_0.sun.set }}</div>
    </div>

  </div>

</div>

<script>
(function(){
  var NS='http://www.w3.org/2000/svg';
  function mk(tag,a){var e=document.createElementNS(NS,tag);for(var k in a)e.setAttribute(k,a[k]);return e;}

  /* ── Go board ── */
  var BK=JSON.parse('{{ IDX_0.go.board_black_json }}'||'[]');
  var WH=JSON.parse('{{ IDX_0.go.board_white_json }}'||'[]');
  var LC=parseInt('{{ IDX_0.go.last_col }}');
  var LR=parseInt('{{ IDX_0.go.last_row }}');
  var LCOL='{{ IDX_0.go.last_color }}';
  var W=533,H=456,PAD=16,BD=H-PAD*2,CELL=BD/18,OX=(W-BD)/2,OY=PAD,R=CELL/2*0.93;
  var bsvg=document.getElementById('bsvg');
  bsvg.appendChild(mk('rect',{x:OX-CELL*0.65,y:OY-CELL*0.65,width:BD+CELL*1.3,height:BD+CELL*1.3,fill:'#e8d5a0',rx:3}));
  for(var i=0;i<19;i++){
    bsvg.appendChild(mk('line',{x1:OX,y1:OY+i*CELL,x2:OX+BD,y2:OY+i*CELL,stroke:'#6b5a36','stroke-width':0.8}));
    bsvg.appendChild(mk('line',{x1:OX+i*CELL,y1:OY,x2:OX+i*CELL,y2:OY+BD,stroke:'#6b5a36','stroke-width':0.8}));
  }
  [[3,3],[3,9],[3,15],[9,3],[9,9],[9,15],[15,3],[15,9],[15,15]].forEach(function(s){
    bsvg.appendChild(mk('circle',{cx:OX+s[0]*CELL,cy:OY+s[1]*CELL,r:CELL*0.11,fill:'#4a3a20'}));
  });
  BK.forEach(function(s){bsvg.appendChild(mk('circle',{cx:OX+s[0]*CELL,cy:OY+s[1]*CELL,r:R,fill:'#111',stroke:'#000','stroke-width':0.5}));});
  WH.forEach(function(s){bsvg.appendChild(mk('circle',{cx:OX+s[0]*CELL,cy:OY+s[1]*CELL,r:R,fill:'#f0f0f0',stroke:'#333','stroke-width':1}));});
  if(LC>=0&&LR>=0) bsvg.appendChild(mk('circle',{cx:OX+LC*CELL,cy:OY+LR*CELL,r:R*0.28,fill:LCOL==='B'?'#eee':'#111'}));

  /* ── Pressure sparkline ── */
  var pressures=JSON.parse('{{ IDX_0.fc.pressure_history_json }}'||'[]');
  if(pressures.length>1){
    var mn=pressures.reduce(function(a,b){return Math.min(a,b);}),mx=pressures.reduce(function(a,b){return Math.max(a,b);});
    var rng=mx-mn||0.5,PW=247,PH=32;
    var psvg=document.getElementById('psvg');
    var pts=pressures.map(function(v,i){
      return ((i/(pressures.length-1))*(PW-4)+2).toFixed(1)+','+(PH-2-((v-mn)/rng)*(PH-6)).toFixed(1);
    }).join(' ');
    psvg.appendChild(mk('polyline',{points:pts,fill:'none',stroke:'#999','stroke-width':1.2,'stroke-linejoin':'round'}));
    var lx=PW-2,lv=pressures[pressures.length-1],ly=(PH-2-((lv-mn)/rng)*(PH-6));
    psvg.appendChild(mk('circle',{cx:lx.toFixed(1),cy:ly.toFixed(1),r:3,fill:'#000'}));
  }

  /* ── Moon phase ── */
  var phase=parseFloat('{{ IDX_0.moon.phase }}')||0;
  var msvg=document.getElementById('moon-svg');
  if(msvg){
    var mr=22,mcx=25,mcy=25;
    msvg.appendChild(mk('circle',{cx:mcx,cy:mcy,r:mr,fill:'#1a1a1a',stroke:'#777','stroke-width':1.5}));
    if(phase>0.98||(phase>=0&&phase<0.02)){
      msvg.appendChild(mk('circle',{cx:mcx,cy:mcy,r:mr,fill:'#e8e8e8'}));
    } else if(phase>=0.02){
      var waxing=(phase<=0.5),angle=phase*2*Math.PI,ellRx=mr*Math.cos(angle),absEll=Math.abs(ellRx).toFixed(2);
      var d,sw;
      if(waxing){sw=(ellRx>=0)?0:1;d='M '+mcx+','+(mcy-mr)+' A '+mr+','+mr+' 0 0 1 '+mcx+','+(mcy+mr)+' A '+absEll+','+mr+' 0 0 '+sw+' '+mcx+','+(mcy-mr)+'Z';}
      else      {sw=(ellRx>=0)?1:0;d='M '+mcx+','+(mcy-mr)+' A '+mr+','+mr+' 0 0 0 '+mcx+','+(mcy+mr)+' A '+absEll+','+mr+' 0 0 '+sw+' '+mcx+','+(mcy-mr)+'Z';}
      msvg.appendChild(mk('path',{d:d,fill:'#e8e8e8'}));
    }
  }

  /* ── Solar dial ── */
  var CX=85,CY=85,SR=80;
  function toMin(t){var p=(t||'').split(':');return p.length>=2?parseInt(p[0],10)*60+parseInt(p[1],10):0;}
  function minToAngle(m){return 270-(m/1440)*360;}
  function toXY(r,deg){var rad=deg*Math.PI/180;return[CX+r*Math.cos(rad),CY-r*Math.sin(rad)];}
  var sr='{{ IDX_0.sun.rise }}',ss='{{ IDX_0.sun.set }}';
  var ssvg=document.getElementById('sun-svg');
  if(ssvg&&sr&&sr.indexOf(':')>=0&&ss&&ss.indexOf(':')>=0){
    var rA=minToAngle(toMin(sr)),sA=minToAngle(toMin(ss));
    var ri=toXY(SR,rA),si=toXY(SR,sA),span=((rA-sA)+360)%360;
    ssvg.appendChild(mk('circle',{cx:CX,cy:CY,r:SR,fill:'#111'}));
    ssvg.appendChild(mk('path',{d:'M '+CX+','+CY+' L '+ri[0].toFixed(1)+','+ri[1].toFixed(1)+' A '+SR+' '+SR+' 0 '+(span>180?1:0)+' 1 '+si[0].toFixed(1)+' '+si[1].toFixed(1)+' Z',fill:'#aaa'}));
    ssvg.appendChild(mk('circle',{cx:CX,cy:CY,r:SR,fill:'none',stroke:'#444','stroke-width':2}));
    ssvg.appendChild(mk('line',{x1:CX,y1:CY-SR+2,x2:CX,y2:CY-SR+14,stroke:'#777','stroke-width':2.5}));
    ssvg.appendChild(mk('line',{x1:CX,y1:CY+SR-14,x2:CX,y2:CY+SR-2,stroke:'#555','stroke-width':2}));
    var now=new Date(),nA=minToAngle(now.getHours()*60+now.getMinutes()),n1=toXY(SR*0.52,nA),n2=toXY(SR,nA);
    ssvg.appendChild(mk('line',{x1:n1[0].toFixed(1),y1:n1[1].toFixed(1),x2:n2[0].toFixed(1),y2:n2[1].toFixed(1),stroke:'#fff','stroke-width':4,'stroke-linecap':'round'}));
  }
})();
</script>
```
