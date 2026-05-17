# Plugin: Kaslo Weather (v2 — liquid layout + sun wheel)
#
# Polling URLs (unchanged):
#   IDX_0 → EcoWitt live station
#   IDX_1 → Open-Meteo 7-day forecast
#
# Layout:
#   Header: title | outdoor+indoor temp | time
#   Left 42%:  big temp → condition → rain stats → 4-stat grid
#   Right 58%: [sun wheel + rise/set/daylen] | [7-day forecast]
#
# Sun wheel: SVG arc drawn by inline JS using srtime/sstime Liquid vars.
#   Dark circle = night, gray wedge = daylight, white tick = now.
#
# Settings: Strategy: Polling | Verb: GET | Remove bleed margin: Yes
#
# IMPORTANT: Paste ONLY the HTML below (starting with the ##{% comment %})
# into the TRMNL "Full" markup tab. Stop at the closing </script> tag.
# Do NOT include this markdown header or the ``` fences.

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

##{% comment %} ── WMO code → condition label ── ##{% endcomment %}
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
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:12px;display:flex;flex-direction:column}

/* ── Header ─────────────────────────────────────────────────────────── */
.hdr{display:flex;justify-content:space-between;align-items:center;padding:0 12px;height:32px;border-bottom:2px solid #000;flex-shrink:0}
.hdr-t{font-weight:800;font-size:14px;letter-spacing:.04em}
.hdr-m{font-size:12px;color:#555}
.hdr-r{font-size:11px;color:#666}

/* ── Two-column body ─────────────────────────────────────────────────── */
.body{display:grid;grid-template-columns:42% 1fr;flex:1;min-height:0}

/* ── LEFT column ─────────────────────────────────────────────────────── */
.col-left{display:flex;flex-direction:column;padding:10px 14px;overflow:hidden;border-right:2px solid #bbb}

.hero-row{display:flex;align-items:flex-end;gap:10px;margin-bottom:4px;flex-shrink:0}
.big-temp{font-size:96px;font-weight:200;line-height:1}
.big-temp sup{font-size:32px;vertical-align:.65em;font-weight:300}
.hero-right{padding-bottom:6px}
.wx-cond{font-size:22px;font-weight:700;line-height:1.2}
.wx-feels{font-size:16px;color:#555;margin-top:3px}
.hi-lo{font-size:16px;font-weight:600;margin-top:3px}

.precip-row{font-size:16px;color:#666;margin-bottom:8px;flex-shrink:0}

.stats{display:grid;grid-template-columns:1fr 1fr;border:1px solid #ddd;margin-top:auto;flex-shrink:0}
.st{padding:7px 10px;border-right:1px solid #ddd;border-bottom:1px solid #ddd}
.st:nth-child(2n){border-right:none}
.st:nth-child(3),.st:nth-child(4){border-bottom:none}
.st-lbl{font-size:8px;text-transform:uppercase;letter-spacing:.08em;color:#999;margin-bottom:2px}
.st-val{font-size:24px;font-weight:700;line-height:1.1}
.st-unit{font-size:13px;font-weight:400}
.st-sub{font-size:14px;color:#777;margin-top:1px}

/* ── RIGHT column ────────────────────────────────────────────────────── */
.col-right{display:flex;flex-direction:column;overflow:hidden}

/* Sun section — height driven by the 230px SVG + padding */
.sun-section{flex:0 0 auto;display:flex;flex-direction:row;align-items:center;justify-content:center;gap:20px;border-bottom:1px solid #ccc;padding:8px 14px}
.sun-info{display:flex;flex-direction:column;gap:10px}
.sun-row{font-size:18px;font-weight:700;white-space:nowrap}
.sun-lbl{font-size:9px;text-transform:uppercase;letter-spacing:.08em;color:#aaa;display:block;margin-bottom:2px}
.sun-daylen{font-size:14px;color:#888;margin-top:4px}

/* 7-day forecast */
.fc{display:grid;grid-template-columns:repeat(7,1fr);flex:1;min-height:0}
.fd{display:flex;flex-direction:column;align-items:center;justify-content:center;padding:6px 2px;border-right:1px solid #eee;gap:3px}
.fd:last-child{border-right:none}
.fd-name{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.04em}
.fd-cond{font-size:8px;color:#555;text-align:center;line-height:1.25}
.fd-pop{font-size:9px;color:#888}
.fd-hi{font-size:16px;font-weight:700}
.fd-lo{font-size:12px;color:#888}
.fd-mm{font-size:9px;color:#aaa}
</style>

<div class="hdr">
  <div class="hdr-t">IKASLO6 &middot; Kaslo BC</div>
  <div class="hdr-m">##{{ IDX_0.data.outdoor.temperature.value }}&deg;C &ensp;&bull;&ensp; indoor ##{{ IDX_0.data.indoor.temperature.value | split:"." | first }}&deg; / ##{{ IDX_0.data.indoor.humidity.value }}%</div>
  <div class="hdr-r">##{{ trmnl.user.time | date: "%H:%M &middot; %a %b %-d" }}</div>
</div>

<div class="body">

  <!-- ── LEFT: current conditions ──────────────────────────────────────── -->
  <div class="col-left">
    <div class="hero-row">
      <div class="big-temp">##{{ IDX_0.data.outdoor.temperature.value | split:"." | first }}<sup>&deg;</sup></div>
      <div class="hero-right">
        <div class="wx-cond">##{{ wxlbl }}</div>
        <div class="wx-feels">Feels ##{{ IDX_0.data.outdoor.feels_like.value | split:"." | first }}&deg;</div>
        <div class="hi-lo">Hi ##{{ IDX_1.daily.temperature_2m_max[0] | round }}&deg; &middot; Lo ##{{ IDX_1.daily.temperature_2m_min[0] | round }}&deg;</div>
      </div>
    </div>

    <div class="precip-row">
      Rain today <strong>##{{ IDX_0.data.rainfall.daily.value }}mm</strong>
      &ensp;&bull;&ensp; Rate <strong>##{{ IDX_0.data.rainfall.rain_rate.value }}mm/h</strong>
      &ensp;&bull;&ensp; Week <strong>##{{ IDX_0.data.rainfall.weekly.value }}mm</strong>
    </div>

    <div class="stats">
      <div class="st">
        <div class="st-lbl">Wind</div>
        <div class="st-val">##{{ IDX_0.data.wind.wind_speed.value | split:"." | first }}<span class="st-unit"> km/h</span></div>
        <div class="st-sub">##{{ wdir }} &bull; gust ##{{ IDX_0.data.wind.wind_gust.value | split:"." | first }}</div>
      </div>
      <div class="st">
        <div class="st-lbl">Pressure</div>
        <div class="st-val" style="font-size:15px">##{{ IDX_0.data.pressure.relative.value | split:"." | first }}<span class="st-unit"> hPa</span></div>
        <div class="st-sub">&nbsp;</div>
      </div>
      <div class="st">
        <div class="st-lbl">Humidity / Dew</div>
        <div class="st-val">##{{ IDX_0.data.outdoor.humidity.value }}<span class="st-unit">%</span></div>
        <div class="st-sub">Dew ##{{ IDX_0.data.outdoor.dew_point.value | split:"." | first }}&deg;</div>
      </div>
      <div class="st">
        <div class="st-lbl">UV / Solar</div>
        <div class="st-val">##{{ IDX_0.data.solar_and_uvi.uvi.value }}</div>
        <div class="st-sub">##{{ IDX_0.data.solar_and_uvi.solar.value | split:"." | first }} W/m&sup2;</div>
      </div>
    </div>
  </div>

  <!-- ── RIGHT: sun wheel + forecast ───────────────────────────────────── -->
  <div class="col-right">

    <div class="sun-section">
      <!-- SVG wheel: JS fills in the day wedge and now-tick at render time -->
      <svg id="sun-svg" viewBox="0 0 240 240" width="230" height="230" style="flex-shrink:0">
        <!-- Night: full dark circle -->
        <circle cx="120" cy="120" r="110" fill="#111"/>
        <!-- Day wedge: drawn by JS -->
        <path id="day-wedge" d="" fill="#aaa"/>
        <!-- Circle outline -->
        <circle cx="120" cy="120" r="110" fill="none" stroke="#444" stroke-width="2"/>
        <!-- Noon marker (top) -->
        <line x1="120" y1="12" x2="120" y2="30" stroke="#777" stroke-width="2.5"/>
        <!-- Midnight marker (bottom) -->
        <line x1="120" y1="210" x2="120" y2="228" stroke="#555" stroke-width="2"/>
        <!-- Current-time tick: default points to noon, JS updates to real time -->
        <line id="now-tick" x1="120" y1="64" x2="120" y2="10" stroke="#fff" stroke-width="4" stroke-linecap="round"/>
      </svg>

      <div class="sun-info">
        <div>
          <span class="sun-lbl">Sunrise</span>
          <span class="sun-row">&uarr; ##{{ srtime }}</span>
        </div>
        <div>
          <span class="sun-lbl">Sunset</span>
          <span class="sun-row">&darr; ##{{ sstime }}</span>
        </div>
        <div class="sun-daylen" id="daylen">&nbsp;</div>
      </div>
    </div>

    <!-- 7-day forecast -->
    <div class="fc">
      ##{% for i in (0..6) %}
        ##{% assign _fc = IDX_1.daily.weather_code[i] | plus: 0 %}
        ##{% if _fc == 0 %}##{% assign fc = "Clear" %}
        ##{% elsif _fc == 1 %}##{% assign fc = "Mostly Clr" %}
        ##{% elsif _fc == 2 %}##{% assign fc = "Pt Cloudy" %}
        ##{% elsif _fc == 3 %}##{% assign fc = "Overcast" %}
        ##{% elsif _fc == 45 or _fc == 48 %}##{% assign fc = "Fog" %}
        ##{% elsif _fc >= 51 and _fc <= 55 %}##{% assign fc = "Drizzle" %}
        ##{% elsif _fc >= 61 and _fc <= 65 %}##{% assign fc = "Rain" %}
        ##{% elsif _fc >= 71 and _fc <= 75 %}##{% assign fc = "Snow" %}
        ##{% elsif _fc >= 80 and _fc <= 82 %}##{% assign fc = "Showers" %}
        ##{% elsif _fc == 85 or _fc == 86  %}##{% assign fc = "Snw Shwrs" %}
        ##{% elsif _fc >= 95 %}##{% assign fc = "T-Storm" %}
        ##{% else %}##{% assign fc = "—" %}##{% endif %}
        <div class="fd">
          <div class="fd-name">##{{ IDX_1.daily.time[i] | date: "%a" }}</div>
          <div class="fd-cond">##{{ fc }}</div>
          ##{% assign _pop = IDX_1.daily.precipitation_probability_max[i] | plus: 0 %}
          ##{% if _pop > 10 %}<div class="fd-pop">##{{ _pop }}%</div>##{% endif %}
          <div class="fd-hi">##{{ IDX_1.daily.temperature_2m_max[i] | round }}&deg;</div>
          <div class="fd-lo">##{{ IDX_1.daily.temperature_2m_min[i] | round }}&deg;</div>
          ##{% assign _mm = IDX_1.daily.precipitation_sum[i] | plus: 0 %}
          ##{% if _mm > 0.1 %}<div class="fd-mm">##{{ IDX_1.daily.precipitation_sum[i] | round: 1 }}mm</div>##{% endif %}
        </div>
      ##{% endfor %}
    </div>

  </div>
</div>

<script>
(function(){
  var CX=120,CY=120,R=110;
  function toMin(t){var p=t.split(':');return parseInt(p[0],10)*60+parseInt(p[1],10);}
  function minToAngle(m){return 270-(m/1440)*360;}
  function toXY(r,deg){var rad=deg*Math.PI/180;return[CX+r*Math.cos(rad),CY-r*Math.sin(rad)];}

  var sr="##{{ srtime }}",ss="##{{ sstime }}";
  var srMin=toMin(sr),ssMin=toMin(ss);
  var rA=minToAngle(srMin),sA=minToAngle(ssMin);
  var ri=toXY(R,rA),si=toXY(R,sA);
  var span=((rA-sA)+360)%360;
  var large=span>180?1:0;
  document.getElementById('day-wedge').setAttribute('d',
    'M '+CX+','+CY+' L '+ri[0].toFixed(1)+','+ri[1].toFixed(1)+
    ' A '+R+' '+R+' 0 '+large+' 1 '+si[0].toFixed(1)+' '+si[1].toFixed(1)+' Z'
  );

  /* Daylight duration label */
  var dayMin=(ssMin-srMin+1440)%1440;
  document.getElementById('daylen').textContent=
    Math.floor(dayMin/60)+'h '+String(dayMin%60).padStart(2,'0')+'m daylight';

  /* Current-time tick: from 50% radius to edge */
  var now=new Date();
  var nowMin=now.getHours()*60+now.getMinutes();
  var nA=minToAngle(nowMin);
  var n1=toXY(55,nA),n2=toXY(R,nA);
  var tick=document.getElementById('now-tick');
  tick.setAttribute('x1',n1[0].toFixed(1));tick.setAttribute('y1',n1[1].toFixed(1));
  tick.setAttribute('x2',n2[0].toFixed(1));tick.setAttribute('y2',n2[1].toFixed(1));
})();
</script>
```
