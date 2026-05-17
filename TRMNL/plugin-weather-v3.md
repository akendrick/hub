# Plugin: Kaslo Weather (v3 — large temp stacked, big dial, 3-day bar)
#
# Layout:
#   Header (32px): title | outdoor+indoor | time
#   Main (flex row, flex:1):
#     Left 48%: big temp → condition → feels/hilo → precip → wind → pressure/hum → uv
#     Right 52%: centered 260px sun dial + rise/set/daylen labels
#   Forecast bar (150px, full width): 3 days — large and clear
#
# Settings: Strategy: Polling | Verb: GET | Remove bleed margin: Yes
#
# IMPORTANT: Paste ONLY the HTML below (starting with ##{% comment %})
# into the TRMNL "Full" markup tab. Stop at </script>. No markdown fences.

## Markup (Full tab)

```html
##{% comment %} ── Wind direction ── ##{% endcomment %}
##{% assign _wd = IDX_0.data.wind.wind_direction.value | plus: 0 %}
##{% if _wd < 23 or _wd >= 338 %}##{% assign wdir = "N" %}
##{% elsif _wd < 68  %}##{% assign wdir = "NE" %}
##{% elsif _wd < 113 %}##{% assign wdir = "E"  %}
##{% elsif _wd < 158 %}##{% assign wdir = "SE" %}
##{% elsif _wd < 203 %}##{% assign wdir = "S"  %}
##{% elsif _wd < 248 %}##{% assign wdir = "SW" %}
##{% elsif _wd < 293 %}##{% assign wdir = "W"  %}
##{% else             %}##{% assign wdir = "NW" %}##{% endif %}

##{% comment %} ── WMO → label ── ##{% endcomment %}
##{% assign _wc = IDX_1.current.weather_code | plus: 0 %}
##{% if _wc == 0 %}##{% assign wxlbl = "Clear" %}
##{% elsif _wc == 1 %}##{% assign wxlbl = "Mostly Clear" %}
##{% elsif _wc == 2 %}##{% assign wxlbl = "Partly Cloudy" %}
##{% elsif _wc == 3 %}##{% assign wxlbl = "Overcast" %}
##{% elsif _wc == 45 or _wc == 48 %}##{% assign wxlbl = "Fog" %}
##{% elsif _wc >= 51 and _wc <= 55 %}##{% assign wxlbl = "Drizzle" %}
##{% elsif _wc >= 61 and _wc <= 65 %}##{% assign wxlbl = "Rain" %}
##{% elsif _wc >= 71 and _wc <= 75 %}##{% assign wxlbl = "Snow" %}
##{% elsif _wc >= 80 and _wc <= 82 %}##{% assign wxlbl = "Rain Showers" %}
##{% elsif _wc == 85 or _wc == 86  %}##{% assign wxlbl = "Snow Showers" %}
##{% elsif _wc >= 95 %}##{% assign wxlbl = "Thunderstorm" %}
##{% else %}##{% assign wxlbl = "—" %}##{% endif %}

##{% assign srtime = IDX_1.daily.sunrise[0] | split: "T" | last %}
##{% assign sstime = IDX_1.daily.sunset[0]  | split: "T" | last %}

<style>
*{box-sizing:border-box;margin:0;padding:0}
html,body{width:100vw;height:100vh;overflow:hidden;background:#fff;color:#000}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;display:flex;flex-direction:column}

/* ── Header ───────────────────────────────────────────────────────── */
.hdr{display:flex;justify-content:space-between;align-items:center;padding:0 14px;height:32px;border-bottom:2px solid #000;flex-shrink:0}
.hdr-t{font-weight:800;font-size:14px;letter-spacing:.04em}
.hdr-m{font-size:12px;color:#555}
.hdr-r{font-size:11px;color:#666}

/* ── Main row ─────────────────────────────────────────────────────── */
.main{display:flex;flex-direction:row;flex:1;min-height:0}

/* ── LEFT: stacked conditions ─────────────────────────────────────── */
.col-left{flex:0 0 48%;display:flex;flex-direction:column;padding:8px 16px 8px 14px;overflow:hidden;border-right:2px solid #bbb}

.big-temp{font-size:140px;font-weight:200;line-height:1;letter-spacing:-.02em;flex-shrink:0}
.big-temp sup{font-size:44px;vertical-align:.6em;font-weight:300}
.wx-cond{font-size:22px;font-weight:700;margin-top:4px;flex-shrink:0}
.wx-sub{font-size:15px;color:#555;margin-top:3px;flex-shrink:0}
.stat-row{font-size:13px;color:#555;margin-top:5px;flex-shrink:0;line-height:1.5}
.stat-row strong{color:#000;font-weight:700}

/* ── RIGHT: sun dial ──────────────────────────────────────────────── */
.col-right{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:12px;overflow:hidden;padding:8px}

.sun-labels{display:flex;flex-direction:column;align-items:center;gap:6px}
.sun-times{display:flex;gap:24px}
.sun-item{text-align:center}
.sun-lbl{font-size:9px;text-transform:uppercase;letter-spacing:.1em;color:#aaa;display:block}
.sun-val{font-size:18px;font-weight:700}
.sun-daylen{font-size:13px;color:#888}

/* ── 3-day forecast bar ───────────────────────────────────────────── */
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
  <div class="hdr-m">##{{ IDX_0.data.outdoor.temperature.value }}&deg;C &ensp;&bull;&ensp; indoor ##{{ IDX_0.data.indoor.temperature.value | split:"." | first }}&deg; / ##{{ IDX_0.data.indoor.humidity.value }}%</div>
  <div class="hdr-r">##{{ trmnl.user.time | date: "%H:%M &middot; %a %b %-d" }}</div>
</div>

<div class="main">

  <!-- ── LEFT column ─────────────────────────────────────────────── -->
  <div class="col-left">
    <div class="big-temp">##{{ IDX_0.data.outdoor.temperature.value | split:"." | first }}<sup>&deg;</sup></div>
    <div class="wx-cond">##{{ wxlbl }}</div>
    <div class="wx-sub">Feels ##{{ IDX_0.data.outdoor.feels_like.value | split:"." | first }}&deg; &nbsp;&bull;&nbsp; Hi ##{{ IDX_1.daily.temperature_2m_max[0] | round }}&deg; &nbsp;/&nbsp; Lo ##{{ IDX_1.daily.temperature_2m_min[0] | round }}&deg;</div>

    <div class="stat-row">
      Rain today <strong>##{{ IDX_0.data.rainfall.daily.value }}mm</strong>
      &ensp;&bull;&ensp; Rate <strong>##{{ IDX_0.data.rainfall.rain_rate.value }}mm/h</strong>
      &ensp;&bull;&ensp; Week <strong>##{{ IDX_0.data.rainfall.weekly.value }}mm</strong>
    </div>
    <div class="stat-row">
      Wind <strong>##{{ wdir }} ##{{ IDX_0.data.wind.wind_speed.value | split:"." | first }} km/h</strong>
      &ensp;&bull;&ensp; Gust <strong>##{{ IDX_0.data.wind.wind_gust.value | split:"." | first }}</strong>
      &ensp;&bull;&ensp; Pressure <strong>##{{ IDX_0.data.pressure.relative.value | split:"." | first }} hPa</strong>
    </div>
    <div class="stat-row">
      Humidity <strong>##{{ IDX_0.data.outdoor.humidity.value }}%</strong>
      &ensp;&bull;&ensp; Dew <strong>##{{ IDX_0.data.outdoor.dew_point.value | split:"." | first }}&deg;</strong>
      &ensp;&bull;&ensp; UV <strong>##{{ IDX_0.data.solar_and_uvi.uvi.value }}</strong>
      &ensp;&bull;&ensp; Solar <strong>##{{ IDX_0.data.solar_and_uvi.solar.value | split:"." | first }} W/m&sup2;</strong>
    </div>
  </div>

  <!-- ── RIGHT column: sun dial ──────────────────────────────────── -->
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
          <span class="sun-val">&uarr; ##{{ srtime }}</span>
        </div>
        <div class="sun-item">
          <span class="sun-lbl">Sunset</span>
          <span class="sun-val">&darr; ##{{ sstime }}</span>
        </div>
      </div>
      <div class="sun-daylen" id="daylen">&nbsp;</div>
    </div>
  </div>

</div>

<!-- ── 3-day forecast bar ──────────────────────────────────────────── -->
<div class="fc-bar">
  ##{% for i in (0..2) %}
    ##{% assign _fc = IDX_1.daily.weather_code[i] | plus: 0 %}
    ##{% if _fc == 0 %}##{% assign fc = "Clear" %}
    ##{% elsif _fc == 1 %}##{% assign fc = "Mostly Clear" %}
    ##{% elsif _fc == 2 %}##{% assign fc = "Partly Cloudy" %}
    ##{% elsif _fc == 3 %}##{% assign fc = "Overcast" %}
    ##{% elsif _fc == 45 or _fc == 48 %}##{% assign fc = "Fog" %}
    ##{% elsif _fc >= 51 and _fc <= 55 %}##{% assign fc = "Drizzle" %}
    ##{% elsif _fc >= 61 and _fc <= 65 %}##{% assign fc = "Rain" %}
    ##{% elsif _fc >= 71 and _fc <= 75 %}##{% assign fc = "Snow" %}
    ##{% elsif _fc >= 80 and _fc <= 82 %}##{% assign fc = "Showers" %}
    ##{% elsif _fc == 85 or _fc == 86  %}##{% assign fc = "Snow Showers" %}
    ##{% elsif _fc >= 95 %}##{% assign fc = "T-Storm" %}
    ##{% else %}##{% assign fc = "—" %}##{% endif %}
    <div class="fd">
      <div class="fd-name">##{{ IDX_1.daily.time[i] | date: "%a" }}</div>
      <div class="fd-cond">##{{ fc }}</div>
      ##{% assign _pop = IDX_1.daily.precipitation_probability_max[i] | plus: 0 %}
      ##{% if _pop > 10 %}<div class="fd-pop">##{{ _pop }}% chance</div>##{% endif %}
      <div class="fd-hi">##{{ IDX_1.daily.temperature_2m_max[i] | round }}&deg;</div>
      <div class="fd-lo">##{{ IDX_1.daily.temperature_2m_min[i] | round }}&deg;</div>
      ##{% assign _mm = IDX_1.daily.precipitation_sum[i] | plus: 0 %}
      ##{% if _mm > 0.1 %}<div class="fd-mm">##{{ IDX_1.daily.precipitation_sum[i] | round: 1 }}mm</div>##{% endif %}
    </div>
  ##{% endfor %}
</div>

<script>
(function(){
  var CX=130,CY=130,R=125;
  function toMin(t){var p=t.split(':');return parseInt(p[0],10)*60+parseInt(p[1],10);}
  function minToAngle(m){return 270-(m/1440)*360;}
  function toXY(r,deg){var rad=deg*Math.PI/180;return[CX+r*Math.cos(rad),CY-r*Math.sin(rad)];}

  var sr="##{{ srtime }}",ss="##{{ sstime }}";
  var srMin=toMin(sr),ssMin=toMin(ss);
  var rA=minToAngle(srMin),sA=minToAngle(ssMin);
  var ri=toXY(R,rA),si=toXY(R,sA);
  var span=((rA-sA)+360)%360;
  document.getElementById('day-wedge').setAttribute('d',
    'M '+CX+','+CY+' L '+ri[0].toFixed(1)+','+ri[1].toFixed(1)+
    ' A '+R+' '+R+' 0 '+(span>180?1:0)+' 1 '+si[0].toFixed(1)+' '+si[1].toFixed(1)+' Z'
  );

  var dayMin=(ssMin-srMin+1440)%1440;
  document.getElementById('daylen').textContent=
    Math.floor(dayMin/60)+'h '+String(dayMin%60).padStart(2,'0')+'m daylight';

  var now=new Date();
  var nA=minToAngle(now.getHours()*60+now.getMinutes());
  var n1=toXY(63,nA),n2=toXY(R,nA);
  var t=document.getElementById('now-tick');
  t.setAttribute('x1',n1[0].toFixed(1));t.setAttribute('y1',n1[1].toFixed(1));
  t.setAttribute('x2',n2[0].toFixed(1));t.setAttribute('y2',n2[1].toFixed(1));
})();
</script>
```
