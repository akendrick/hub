# Plugin: Kaslo Weather (v4 — flat top-level vars, no IDX_0, no loops)
#
# Polling URL (one only):
#   https://knotwork.ca/weather-device-api.php?key=kw_40e818911c1980bcd56dc4aff37a820f811990a130eb771fd9e21a536edc55ea
#
# TRMNL exposes all JSON keys at top level — no IDX_0 prefix.
# All values are flat scalars. Forecast is f0_*/f1_*/f2_*/f3_*/f4_* (5 days).
#
# Settings: Strategy: Polling | Verb: GET | Remove bleed margin: Yes
#
# IMPORTANT: Paste ONLY the HTML below (starting with <style>) into the
# TRMNL "Full" markup tab. Do NOT paste this header or the ``` fences.

## Markup (Full tab)

```html
<style>
*{box-sizing:border-box;margin:0;padding:0}
html,body{width:100%;height:100%;overflow:hidden;background:#fff;color:#000}
body{
  font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;
  display:grid;
  grid-template-rows:28px 1fr 140px;
  height:100vh;
}

/* ── HEADER ── */
.hdr{display:flex;justify-content:space-between;align-items:center;padding:0 14px;border-bottom:2px solid #000}
.hdr-t{font-weight:800;font-size:14px;letter-spacing:.04em}
.hdr-m{font-size:12px;color:#555}
.hdr-r{font-size:11px;color:#666}

/* ── MAIN (middle row) ── */
.main{display:flex;flex-direction:row;min-height:0;overflow:hidden}

/* ── LEFT — current conditions ── */
.col-left{flex:0 0 50%;display:flex;flex-direction:column;padding:6px 14px;overflow:hidden;border-right:2px solid #bbb}
.big-temp{font-size:155px;font-weight:200;line-height:1;letter-spacing:-.02em;flex-shrink:0}
.big-temp sup{font-size:50px;vertical-align:.6em;font-weight:300}
.wx-cond{font-size:26px;font-weight:700;margin-top:2px;flex-shrink:0}
.wx-sub{font-size:17px;color:#555;margin-top:3px;flex-shrink:0}
.stat-row{font-size:15px;color:#555;margin-top:4px;flex-shrink:0;line-height:1.4}
.stat-row strong{color:#000;font-weight:700}

/* ── RIGHT — sun wheel + lunar ── */
.col-right{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:6px;overflow:hidden;padding:6px}
.sun-labels{display:flex;flex-direction:column;align-items:center;gap:4px}
.sun-times{display:flex;gap:24px}
.sun-item{text-align:center}
.sun-lbl{font-size:9px;text-transform:uppercase;letter-spacing:.1em;color:#aaa;display:block}
.sun-val{font-size:21px;font-weight:700}
.sun-daylen{font-size:14px;color:#888}
.lunar{display:flex;align-items:center;gap:8px;padding:4px 0}
.lunar-name{font-size:14px;font-weight:700;color:#333}
.lunar-sub{font-size:11px;color:#999}

/* ── FORECAST BAR (bottom row) ── */
.fc-bar{display:grid;grid-template-columns:repeat(5,1fr);border-top:2px solid #000;min-height:0;overflow:hidden}
.fd{display:flex;flex-direction:column;align-items:center;justify-content:center;padding:4px 2px;border-right:1px solid #ddd;gap:1px}
.fd:last-child{border-right:none}
.fd-name{font-size:22px;font-weight:900;text-transform:uppercase;letter-spacing:.03em;line-height:1}
.fd-icon{width:48px;height:48px;flex-shrink:0;overflow:visible}
.fd-hi{font-size:20px;font-weight:700;line-height:1}
.fd-lo{font-size:14px;color:#888;line-height:1}
.fd-pop{font-size:10px;color:#888}
.fd-mm{font-size:10px;color:#aaa}
</style>

<div class="hdr">
  <div class="hdr-t">IKASLO6 &middot; Kaslo BC</div>
  <div class="hdr-m">{{ temp_full }}&deg;C &ensp;&bull;&ensp; indoor {{ indoor_temp }}&deg; / {{ indoor_hum }}%</div>
  <div class="hdr-r">{{ trmnl.user.time | date: "%H:%M &middot; %a %b %-d" }}</div>
</div>

<div class="main">
  <div class="col-left">
    <div class="big-temp">{{ temp }}<sup>&deg;</sup></div>
    <div class="wx-cond">{{ condition }}</div>
    <div class="wx-sub">Feels {{ feels }}&deg; &nbsp;&bull;&nbsp; Hi {{ hi }}&deg; &nbsp;/&nbsp; Lo {{ lo }}&deg;</div>
    <div class="stat-row">Rain <strong>{{ rain_day }}mm</strong> &ensp;&bull;&ensp; Rate <strong>{{ rain_rate }}mm/h</strong> &ensp;&bull;&ensp; Week <strong>{{ rain_week }}mm</strong></div>
    <div class="stat-row">Wind <strong>{{ wdir }} {{ wind_kmh }} km/h</strong> &ensp;&bull;&ensp; Gust <strong>{{ gust_kmh }}</strong> &ensp;&bull;&ensp; Pressure <strong>{{ pressure }} hPa</strong></div>
    <div class="stat-row">Humidity <strong>{{ humidity }}%</strong> &ensp;&bull;&ensp; Dew <strong>{{ dew }}&deg;</strong> &ensp;&bull;&ensp; UV <strong>{{ uvi }}</strong> &ensp;&bull;&ensp; Solar <strong>{{ solar }} W/m&sup2;</strong></div>
  </div>

  <div class="col-right">
    <svg id="sun-svg" viewBox="0 0 260 260" width="190" height="190" style="flex-shrink:0">
      <circle cx="130" cy="130" r="125" fill="#111"/>
      <path id="day-wedge" d="" fill="#aaa"/>
      <circle cx="130" cy="130" r="125" fill="none" stroke="#444" stroke-width="2"/>
      <line x1="130" y1="7"   x2="130" y2="26"  stroke="#888" stroke-width="3"/>
      <line x1="130" y1="234" x2="130" y2="253" stroke="#555" stroke-width="2"/>
      <line id="now-tick" x1="130" y1="67" x2="130" y2="5" stroke="#fff" stroke-width="4.5" stroke-linecap="round"/>
    </svg>
    <div class="sun-labels">
      <div class="sun-times">
        <div class="sun-item"><span class="sun-lbl">Sunrise</span><span class="sun-val">&uarr; {{ sunrise }}</span></div>
        <div class="sun-item"><span class="sun-lbl">Sunset</span><span class="sun-val">&darr; {{ sunset }}</span></div>
      </div>
      <div class="sun-daylen">{{ daylight }}</div>
    </div>
    <div class="lunar">
      <svg id="moon-svg" viewBox="0 0 40 40" width="34" height="34" style="flex-shrink:0"></svg>
      <div>
        <div class="lunar-name" id="moon-phase-name"></div>
        <div class="lunar-sub" id="moon-phase-sub"></div>
      </div>
    </div>
  </div>
</div>

<div class="fc-bar">
  <div class="fd" data-cond="{{ f0_condition }}">
    <div class="fd-name">{{ f0_dow }}</div>
    <svg class="fd-icon" id="fi0" viewBox="0 0 54 54"></svg>
    <div class="fd-hi">{{ f0_hi }}&deg;</div>
    <div class="fd-lo">{{ f0_lo }}&deg;</div>
    {% if f0_pop_str %}<div class="fd-pop">{{ f0_pop_str }}</div>{% endif %}
  </div>
  <div class="fd" data-cond="{{ f1_condition }}">
    <div class="fd-name">{{ f1_dow }}</div>
    <svg class="fd-icon" id="fi1" viewBox="0 0 54 54"></svg>
    <div class="fd-hi">{{ f1_hi }}&deg;</div>
    <div class="fd-lo">{{ f1_lo }}&deg;</div>
    {% if f1_pop_str %}<div class="fd-pop">{{ f1_pop_str }}</div>{% endif %}
  </div>
  <div class="fd" data-cond="{{ f2_condition }}">
    <div class="fd-name">{{ f2_dow }}</div>
    <svg class="fd-icon" id="fi2" viewBox="0 0 54 54"></svg>
    <div class="fd-hi">{{ f2_hi }}&deg;</div>
    <div class="fd-lo">{{ f2_lo }}&deg;</div>
    {% if f2_pop_str %}<div class="fd-pop">{{ f2_pop_str }}</div>{% endif %}
  </div>
  <div class="fd" data-cond="{{ f3_condition }}">
    <div class="fd-name">{{ f3_dow }}</div>
    <svg class="fd-icon" id="fi3" viewBox="0 0 54 54"></svg>
    <div class="fd-hi">{{ f3_hi }}&deg;</div>
    <div class="fd-lo">{{ f3_lo }}&deg;</div>
    {% if f3_pop_str %}<div class="fd-pop">{{ f3_pop_str }}</div>{% endif %}
  </div>
  <div class="fd" data-cond="{{ f4_condition }}">
    <div class="fd-name">{{ f4_dow }}</div>
    <svg class="fd-icon" id="fi4" viewBox="0 0 54 54"></svg>
    <div class="fd-hi">{{ f4_hi }}&deg;</div>
    <div class="fd-lo">{{ f4_lo }}&deg;</div>
    {% if f4_pop_str %}<div class="fd-pop">{{ f4_pop_str }}</div>{% endif %}
  </div>
</div>

<script>
(function(){
  // ── Sun wheel ──────────────────────────────────────────────────────────────
  var CX=130,CY=130,R=125;
  function toMin(t){var p=t.split(':');return+p[0]*60+ +p[1];}
  function ang(m){return 270-(m/1440)*360;}
  function xy(r,d){var a=d*Math.PI/180;return[CX+r*Math.cos(a),CY-r*Math.sin(a)];}
  var sr='{{ sunrise }}',ss='{{ sunset }}';
  if(sr&&ss&&sr.indexOf(':')>=0){
    var rA=ang(toMin(sr)),sA=ang(toMin(ss));
    var ri=xy(R,rA),si=xy(R,sA),span=((rA-sA)+360)%360;
    document.getElementById('day-wedge').setAttribute('d',
      'M '+CX+','+CY+' L '+ri[0].toFixed(1)+','+ri[1].toFixed(1)+
      ' A '+R+' '+R+' 0 '+(span>180?1:0)+' 1 '+si[0].toFixed(1)+' '+si[1].toFixed(1)+' Z');
    var now=new Date(),nA=ang(now.getHours()*60+now.getMinutes());
    var n1=xy(63,nA),n2=xy(R,nA),tk=document.getElementById('now-tick');
    tk.setAttribute('x1',n1[0].toFixed(1));tk.setAttribute('y1',n1[1].toFixed(1));
    tk.setAttribute('x2',n2[0].toFixed(1));tk.setAttribute('y2',n2[1].toFixed(1));
  }

  // ── Lunar phase ────────────────────────────────────────────────────────────
  (function(){
    var SYN=29.53058867;
    function jd(d){
      var y=d.getUTCFullYear(),m=d.getUTCMonth()+1,dy=d.getUTCDate();
      var h=d.getUTCHours()/24+d.getUTCMinutes()/1440;
      if(m<=2){y--;m+=12;}
      var A=Math.floor(y/100);
      return Math.floor(365.25*(y+4716))+Math.floor(30.6001*(m+1))+dy+h+2-A+Math.floor(A/4)-1524.5;
    }
    var age=((jd(new Date())-2451549.76)%SYN+SYN)%SYN;
    var names=['New Moon','Waxing Crescent','First Quarter','Waxing Gibbous',
               'Full Moon','Waning Gibbous','Last Quarter','Waning Crescent'];
    var bounds=[1.85,7.38,9.22,14.77,16.61,22.15,23.99,29.53];
    var idx=bounds.length-1;
    for(var i=0;i<bounds.length;i++){if(age<bounds[i]){idx=i;break;}}
    document.getElementById('moon-phase-name').textContent=names[idx];
    document.getElementById('moon-phase-sub').textContent=
      'Day '+Math.round(age)+' · '+Math.round(50*(1-Math.cos(age/SYN*2*Math.PI)))+'% lit';

    // Moon icon — 8 phase SVGs (viewBox 0 0 40 40, cx=20 cy=20 r=15)
    var cx=20,cy=20,r=15;
    var moonSVGs={
      'New Moon':'<circle cx="'+cx+'" cy="'+cy+'" r="'+r+'" fill="#333" stroke="#999" stroke-width="1.5"/>',
      'Full Moon':'<circle cx="'+cx+'" cy="'+cy+'" r="'+r+'" fill="#fff" stroke="#333" stroke-width="1.5"/>',
      'First Quarter':
        '<circle cx="'+cx+'" cy="'+cy+'" r="'+r+'" fill="#333"/>'+
        '<path d="M'+cx+','+(cy-r)+' A'+r+','+r+' 0 0,1 '+cx+','+(cy+r)+' Z" fill="#fff"/>',
      'Last Quarter':
        '<circle cx="'+cx+'" cy="'+cy+'" r="'+r+'" fill="#333"/>'+
        '<path d="M'+cx+','+(cy-r)+' A'+r+','+r+' 0 0,0 '+cx+','+(cy+r)+' Z" fill="#fff"/>',
      'Waxing Crescent':
        '<circle cx="'+cx+'" cy="'+cy+'" r="'+r+'" fill="#333"/>'+
        '<path d="M'+cx+','+(cy-r)+' A'+r+','+r+' 0 0,1 '+cx+','+(cy+r)+' A'+(r*0.4)+','+r+' 0 0,0 '+cx+','+(cy-r)+' Z" fill="#fff"/>',
      'Waning Crescent':
        '<circle cx="'+cx+'" cy="'+cy+'" r="'+r+'" fill="#333"/>'+
        '<path d="M'+cx+','+(cy-r)+' A'+r+','+r+' 0 0,0 '+cx+','+(cy+r)+' A'+(r*0.4)+','+r+' 0 0,1 '+cx+','+(cy-r)+' Z" fill="#fff"/>',
      'Waxing Gibbous':
        '<circle cx="'+cx+'" cy="'+cy+'" r="'+r+'" fill="#fff" stroke="#333" stroke-width="1"/>'+
        '<path d="M'+cx+','+(cy-r)+' A'+r+','+r+' 0 0,0 '+cx+','+(cy+r)+' A'+(r*0.5)+','+r+' 0 0,1 '+cx+','+(cy-r)+' Z" fill="#333"/>',
      'Waning Gibbous':
        '<circle cx="'+cx+'" cy="'+cy+'" r="'+r+'" fill="#fff" stroke="#333" stroke-width="1"/>'+
        '<path d="M'+cx+','+(cy-r)+' A'+r+','+r+' 0 0,1 '+cx+','+(cy+r)+' A'+(r*0.5)+','+r+' 0 0,0 '+cx+','+(cy-r)+' Z" fill="#333"/>'
    };
    var msvg=document.getElementById('moon-svg');
    if(msvg) msvg.innerHTML=moonSVGs[names[idx]]||moonSVGs['New Moon'];
  })();

  // ── Forecast icons ─────────────────────────────────────────────────────────
  var IC={
    'Clear':
      '<circle cx="27" cy="27" r="9" fill="#000"/>'+
      '<line x1="27" y1="4" x2="27" y2="12" stroke="#000" stroke-width="2.5" stroke-linecap="round"/>'+
      '<line x1="27" y1="42" x2="27" y2="50" stroke="#000" stroke-width="2.5" stroke-linecap="round"/>'+
      '<line x1="4" y1="27" x2="12" y2="27" stroke="#000" stroke-width="2.5" stroke-linecap="round"/>'+
      '<line x1="42" y1="27" x2="50" y2="27" stroke="#000" stroke-width="2.5" stroke-linecap="round"/>'+
      '<line x1="11" y1="11" x2="17" y2="17" stroke="#000" stroke-width="2.5" stroke-linecap="round"/>'+
      '<line x1="37" y1="37" x2="43" y2="43" stroke="#000" stroke-width="2.5" stroke-linecap="round"/>'+
      '<line x1="43" y1="11" x2="37" y2="17" stroke="#000" stroke-width="2.5" stroke-linecap="round"/>'+
      '<line x1="11" y1="43" x2="17" y2="37" stroke="#000" stroke-width="2.5" stroke-linecap="round"/>',
    'Mostly Clear':
      '<circle cx="20" cy="19" r="8" fill="#000"/>'+
      '<line x1="20" y1="5" x2="20" y2="11" stroke="#000" stroke-width="2" stroke-linecap="round"/>'+
      '<line x1="5" y1="19" x2="11" y2="19" stroke="#000" stroke-width="2" stroke-linecap="round"/>'+
      '<line x1="9" y1="9" x2="14" y2="13" stroke="#000" stroke-width="2" stroke-linecap="round"/>'+
      '<line x1="31" y1="9" x2="26" y2="13" stroke="#000" stroke-width="2" stroke-linecap="round"/>'+
      '<path d="M18 32 Q13 32 13 27 Q13 22 18 21 Q18 15 24 14 Q30 13 33 18 Q38 18 40 22 Q45 22 45 27 Q45 32 40 32 Z" fill="#000"/>',
    'Partly Cloudy':
      '<circle cx="18" cy="18" r="8" fill="#000"/>'+
      '<line x1="18" y1="4" x2="18" y2="10" stroke="#000" stroke-width="2" stroke-linecap="round"/>'+
      '<line x1="4" y1="18" x2="10" y2="18" stroke="#000" stroke-width="2" stroke-linecap="round"/>'+
      '<line x1="8" y1="8" x2="13" y2="13" stroke="#000" stroke-width="2" stroke-linecap="round"/>'+
      '<line x1="28" y1="8" x2="23" y2="13" stroke="#000" stroke-width="2" stroke-linecap="round"/>'+
      '<path d="M15 38 Q10 38 10 33 Q10 28 16 27 Q16 21 22 20 Q28 19 31 24 Q36 24 38 28 Q44 28 44 33 Q44 38 38 38 Z" fill="#000"/>',
    'Overcast':
      '<path d="M8 34 Q3 34 3 28 Q3 22 9 21 Q9 12 18 10 Q28 8 32 17 Q38 17 41 22 Q48 22 48 28 Q48 34 41 34 Z" fill="#000"/>'+
      '<path d="M14 44 Q10 44 10 40 Q10 36 15 35 Q15 30 21 29 Q27 28 30 33 Q35 33 37 37 Q42 37 42 41 Q42 45 37 45 Z" fill="#555"/>',
    'Fog':
      '<line x1="7" y1="14" x2="47" y2="14" stroke="#000" stroke-width="4" stroke-linecap="round"/>'+
      '<line x1="4" y1="23" x2="50" y2="23" stroke="#888" stroke-width="3" stroke-linecap="round"/>'+
      '<line x1="7" y1="32" x2="47" y2="32" stroke="#000" stroke-width="4" stroke-linecap="round"/>'+
      '<line x1="11" y1="41" x2="43" y2="41" stroke="#bbb" stroke-width="2.5" stroke-linecap="round"/>'+
      '<line x1="15" y1="49" x2="39" y2="49" stroke="#ddd" stroke-width="2" stroke-linecap="round"/>',
    'Drizzle':
      '<path d="M10 26 Q6 26 6 20 Q6 14 12 13 Q12 5 21 3 Q30 1 34 10 Q40 10 42 15 Q48 15 48 21 Q48 27 42 27 Z" fill="#000"/>'+
      '<line x1="18" y1="33" x2="16" y2="43" stroke="#666" stroke-width="2.5" stroke-linecap="round"/>'+
      '<line x1="27" y1="33" x2="25" y2="43" stroke="#666" stroke-width="2.5" stroke-linecap="round"/>'+
      '<line x1="36" y1="33" x2="34" y2="43" stroke="#666" stroke-width="2.5" stroke-linecap="round"/>',
    'Rain':
      '<path d="M10 25 Q6 25 6 19 Q6 13 12 12 Q12 4 21 2 Q30 0 34 9 Q40 9 42 14 Q48 14 48 20 Q48 26 42 26 Z" fill="#000"/>'+
      '<line x1="16" y1="32" x2="12" y2="46" stroke="#444" stroke-width="3" stroke-linecap="round"/>'+
      '<line x1="27" y1="32" x2="23" y2="46" stroke="#444" stroke-width="3" stroke-linecap="round"/>'+
      '<line x1="38" y1="32" x2="34" y2="46" stroke="#444" stroke-width="3" stroke-linecap="round"/>',
    'Showers':
      '<path d="M10 23 Q6 23 6 17 Q6 11 12 10 Q12 2 21 0 Q30 -2 34 7 Q40 7 42 12 Q48 12 48 18 Q48 24 42 24 Z" fill="#000"/>'+
      '<line x1="16" y1="30" x2="13" y2="40" stroke="#444" stroke-width="2.5" stroke-linecap="round"/>'+
      '<line x1="27" y1="30" x2="24" y2="40" stroke="#444" stroke-width="2.5" stroke-linecap="round"/>'+
      '<line x1="38" y1="30" x2="35" y2="40" stroke="#444" stroke-width="2.5" stroke-linecap="round"/>'+
      '<line x1="20" y1="44" x2="18" y2="52" stroke="#888" stroke-width="2" stroke-linecap="round"/>'+
      '<line x1="34" y1="44" x2="32" y2="52" stroke="#888" stroke-width="2" stroke-linecap="round"/>',
    'Snow':
      '<path d="M10 23 Q6 23 6 17 Q6 11 12 10 Q12 2 21 0 Q30 -2 34 7 Q40 7 42 12 Q48 12 48 18 Q48 24 42 24 Z" fill="#000"/>'+
      '<line x1="27" y1="28" x2="27" y2="52" stroke="#444" stroke-width="2.5" stroke-linecap="round"/>'+
      '<line x1="15" y1="34" x2="39" y2="46" stroke="#444" stroke-width="2.5" stroke-linecap="round"/>'+
      '<line x1="39" y1="34" x2="15" y2="46" stroke="#444" stroke-width="2.5" stroke-linecap="round"/>'+
      '<circle cx="27" cy="40" r="2" fill="#fff"/>'+
      '<circle cx="19" cy="36" r="2" fill="#fff"/>'+
      '<circle cx="35" cy="36" r="2" fill="#fff"/>',
    'Snow Showers':
      '<path d="M10 23 Q6 23 6 17 Q6 11 12 10 Q12 2 21 0 Q30 -2 34 7 Q40 7 42 12 Q48 12 48 18 Q48 24 42 24 Z" fill="#000"/>'+
      '<line x1="20" y1="30" x2="20" y2="46" stroke="#444" stroke-width="2" stroke-linecap="round"/>'+
      '<line x1="34" y1="30" x2="34" y2="46" stroke="#444" stroke-width="2" stroke-linecap="round"/>'+
      '<line x1="13" y1="35" x2="27" y2="41" stroke="#444" stroke-width="2" stroke-linecap="round"/>'+
      '<line x1="27" y1="35" x2="13" y2="41" stroke="#444" stroke-width="2" stroke-linecap="round"/>',
    'Thunderstorm':
      '<path d="M10 23 Q6 23 6 17 Q6 11 12 10 Q12 2 21 0 Q30 -2 34 7 Q40 7 42 12 Q48 12 48 18 Q48 24 42 24 Z" fill="#000"/>'+
      '<polygon points="30,27 22,42 28,42 22,54 35,36 29,36" fill="#333"/>'
  };

  document.querySelectorAll('.fc-bar .fd').forEach(function(fd,i){
    var cond=(fd.getAttribute('data-cond')||'').trim();
    var svg=document.getElementById('fi'+i);
    if(svg) svg.innerHTML=IC[cond]||IC['Overcast'];
  });
})();
</script>
```
