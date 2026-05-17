# Plugin: Kaslo Weather (v4 — flat top-level vars, no IDX_0, no loops)
#
# Polling URL (one only):
#   https://knotwork.ca/weather-device-api.php?key=kw_40e818911c1980bcd56dc4aff37a820f811990a130eb771fd9e21a536edc55ea
#
# TRMNL exposes all JSON keys at top level — no IDX_0 prefix.
# All values are flat scalars. Forecast is f0_*/f1_*/f2_* (no array loops).
#
# Settings: Strategy: Polling | Verb: GET | Remove bleed margin: Yes
#
# IMPORTANT: Paste ONLY the HTML below (starting with <style>) into the
# TRMNL "Full" markup tab. Do NOT paste this header or the ``` fences.

## Markup (Full tab)

```html
<style>
*{box-sizing:border-box;margin:0;padding:0}
html,body{width:100vw;height:100vh;overflow:hidden;background:#fff;color:#000}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;display:flex;flex-direction:column}

.hdr{display:flex;justify-content:space-between;align-items:center;padding:0 14px;height:32px;border-bottom:2px solid #000;flex-shrink:0}
.hdr-t{font-weight:800;font-size:14px;letter-spacing:.04em}
.hdr-m{font-size:12px;color:#555}
.hdr-r{font-size:11px;color:#666}

.main{display:flex;flex-direction:row;flex:1;min-height:0}

.col-left{flex:0 0 48%;display:flex;flex-direction:column;padding:8px 16px 8px 14px;overflow:hidden;border-right:2px solid #bbb}
.big-temp{font-size:140px;font-weight:200;line-height:1;letter-spacing:-.02em;flex-shrink:0}
.big-temp sup{font-size:44px;vertical-align:.6em;font-weight:300}
.wx-cond{font-size:22px;font-weight:700;margin-top:4px;flex-shrink:0}
.wx-sub{font-size:15px;color:#555;margin-top:3px;flex-shrink:0}
.stat-row{font-size:13px;color:#555;margin-top:5px;flex-shrink:0;line-height:1.5}
.stat-row strong{color:#000;font-weight:700}

.col-right{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:12px;overflow:hidden;padding:8px}
.sun-labels{display:flex;flex-direction:column;align-items:center;gap:6px}
.sun-times{display:flex;gap:24px}
.sun-item{text-align:center}
.sun-lbl{font-size:9px;text-transform:uppercase;letter-spacing:.1em;color:#aaa;display:block}
.sun-val{font-size:18px;font-weight:700}
.sun-daylen{font-size:13px;color:#888}

.fc-bar{flex-shrink:0;height:150px;display:grid;grid-template-columns:repeat(3,1fr);border-top:2px solid #000}
.fd{display:flex;flex-direction:column;align-items:center;justify-content:center;padding:6px 4px;border-right:2px solid #ddd;gap:2px}
.fd:last-child{border-right:none}
.fd-name{font-size:14px;font-weight:800;text-transform:uppercase;letter-spacing:.05em}
.fd-cond{font-size:11px;color:#555;text-align:center}
.fd-pop{font-size:11px;color:#888}
.fd-hi{font-size:30px;font-weight:700;line-height:1}
.fd-lo{font-size:20px;color:#888;line-height:1}
.fd-mm{font-size:10px;color:#aaa}
</style>

<div class="hdr">
  <div class="hdr-t">IKASLO6 &middot; Kaslo BC</div>
  <div class="hdr-m">##{{ temp_full }}&deg;C &ensp;&bull;&ensp; indoor ##{{ indoor_temp }}&deg; / ##{{ indoor_hum }}%</div>
  <div class="hdr-r">##{{ trmnl.user.time | date: "%H:%M &middot; %a %b %-d" }}</div>
</div>

<div class="main">

  <div class="col-left">
    <div class="big-temp">##{{ temp }}<sup>&deg;</sup></div>
    <div class="wx-cond">##{{ condition }}</div>
    <div class="wx-sub">Feels ##{{ feels }}&deg; &nbsp;&bull;&nbsp; Hi ##{{ hi }}&deg; &nbsp;/&nbsp; Lo ##{{ lo }}&deg;</div>
    <div class="stat-row">
      Rain today <strong>##{{ rain_day }}mm</strong>
      &ensp;&bull;&ensp; Rate <strong>##{{ rain_rate }}mm/h</strong>
      &ensp;&bull;&ensp; Week <strong>##{{ rain_week }}mm</strong>
    </div>
    <div class="stat-row">
      Wind <strong>##{{ wdir }} ##{{ wind_kmh }} km/h</strong>
      &ensp;&bull;&ensp; Gust <strong>##{{ gust_kmh }}</strong>
      &ensp;&bull;&ensp; Pressure <strong>##{{ pressure }} hPa</strong>
    </div>
    <div class="stat-row">
      Humidity <strong>##{{ humidity }}%</strong>
      &ensp;&bull;&ensp; Dew <strong>##{{ dew }}&deg;</strong>
      &ensp;&bull;&ensp; UV <strong>##{{ uvi }}</strong>
      &ensp;&bull;&ensp; Solar <strong>##{{ solar }} W/m&sup2;</strong>
    </div>
  </div>

  <div class="col-right">
    <svg id="sun-svg" viewBox="0 0 260 260" width="240" height="240" style="flex-shrink:0">
      <circle cx="130" cy="130" r="125" fill="#111"/>
      <path id="day-wedge" d="" fill="#aaa"/>
      <circle cx="130" cy="130" r="125" fill="none" stroke="#444" stroke-width="2"/>
      <line x1="130" y1="7"   x2="130" y2="26"  stroke="#888" stroke-width="3"/>
      <line x1="130" y1="234" x2="130" y2="253" stroke="#555" stroke-width="2"/>
      <line id="now-tick" x1="130" y1="67" x2="130" y2="5" stroke="#fff" stroke-width="4.5" stroke-linecap="round"/>
    </svg>
    <div class="sun-labels">
      <div class="sun-times">
        <div class="sun-item">
          <span class="sun-lbl">Sunrise</span>
          <span class="sun-val">&uarr; ##{{ sunrise }}</span>
        </div>
        <div class="sun-item">
          <span class="sun-lbl">Sunset</span>
          <span class="sun-val">&darr; ##{{ sunset }}</span>
        </div>
      </div>
      <div class="sun-daylen">##{{ daylight }}</div>
    </div>
  </div>

</div>

<div class="fc-bar">
  <div class="fd">
    <div class="fd-name">##{{ f0_dow }}</div>
    <div class="fd-cond">##{{ f0_condition }}</div>
    ##{% if f0_pop_str != "" %}<div class="fd-pop">##{{ f0_pop_str }} chance</div>##{% endif %}
    <div class="fd-hi">##{{ f0_hi }}&deg;</div>
    <div class="fd-lo">##{{ f0_lo }}&deg;</div>
    ##{% if f0_mm_str != "" %}<div class="fd-mm">##{{ f0_mm_str }}</div>##{% endif %}
  </div>
  <div class="fd">
    <div class="fd-name">##{{ f1_dow }}</div>
    <div class="fd-cond">##{{ f1_condition }}</div>
    ##{% if f1_pop_str != "" %}<div class="fd-pop">##{{ f1_pop_str }} chance</div>##{% endif %}
    <div class="fd-hi">##{{ f1_hi }}&deg;</div>
    <div class="fd-lo">##{{ f1_lo }}&deg;</div>
    ##{% if f1_mm_str != "" %}<div class="fd-mm">##{{ f1_mm_str }}</div>##{% endif %}
  </div>
  <div class="fd">
    <div class="fd-name">##{{ f2_dow }}</div>
    <div class="fd-cond">##{{ f2_condition }}</div>
    ##{% if f2_pop_str != "" %}<div class="fd-pop">##{{ f2_pop_str }} chance</div>##{% endif %}
    <div class="fd-hi">##{{ f2_hi }}&deg;</div>
    <div class="fd-lo">##{{ f2_lo }}&deg;</div>
    ##{% if f2_mm_str != "" %}<div class="fd-mm">##{{ f2_mm_str }}</div>##{% endif %}
  </div>
</div>

<script>
(function(){
  var CX=130,CY=130,R=125;
  function toMin(t){var p=t.split(':');return parseInt(p[0],10)*60+parseInt(p[1],10);}
  function minToAngle(m){return 270-(m/1440)*360;}
  function toXY(r,deg){var rad=deg*Math.PI/180;return[CX+r*Math.cos(rad),CY-r*Math.sin(rad)];}
  var sr="##{{ sunrise }}",ss="##{{ sunset }}";
  if(!sr||!ss||sr.indexOf(':')<0||ss.indexOf(':')<0){return;}
  var srMin=toMin(sr),ssMin=toMin(ss);
  var rA=minToAngle(srMin),sA=minToAngle(ssMin);
  var ri=toXY(R,rA),si=toXY(R,sA);
  var span=((rA-sA)+360)%360;
  document.getElementById('day-wedge').setAttribute('d',
    'M '+CX+','+CY+' L '+ri[0].toFixed(1)+','+ri[1].toFixed(1)+
    ' A '+R+' '+R+' 0 '+(span>180?1:0)+' 1 '+si[0].toFixed(1)+' '+si[1].toFixed(1)+' Z'
  );
  var now=new Date();
  var nA=minToAngle(now.getHours()*60+now.getMinutes());
  var n1=toXY(63,nA),n2=toXY(R,nA);
  var t=document.getElementById('now-tick');
  t.setAttribute('x1',n1[0].toFixed(1));t.setAttribute('y1',n1[1].toFixed(1));
  t.setAttribute('x2',n2[0].toFixed(1));t.setAttribute('y2',n2[1].toFixed(1));
})();
</script>
```
