# Plugin: Kaslo Weather (v4 — flat top-level vars, no IDX_0, no loops)
#
# Polling URL (one only):
#   https://knotwork.ca/kaslo-api.php?key=kw_40e818911c1980bcd56dc4aff37a820f811990a130eb771fd9e21a536edc55ea
#
# Layout:
#   TOP 2/3  — three equal panels: WEATHER | MOON PHASE | SOLAR CLOCK
#   BOTTOM 1/3 — 5-day forecast strip with weather + calendar events
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
  grid-template-rows:36px 1fr 263px;
  height:100vh;
}

/* ── HEADER ── */
.hdr{display:flex;justify-content:space-between;align-items:center;padding:0 14px;border-bottom:2px solid #000}
.hdr-t{font-weight:800;font-size:20px;letter-spacing:.04em}
.hdr-m{font-size:17px;color:#555}
.hdr-r{font-size:15px;color:#666}

/* ── MAIN: three equal panels ── */
.main{display:grid;grid-template-columns:1fr 1fr 1fr;min-height:0;overflow:hidden}
.c-wx,.c-moon,.c-sun{
  display:flex;flex-direction:column;justify-content:center;
  padding:8px 12px;overflow:hidden;min-width:0;
}
.c-wx  {border-right:2px solid #bbb}
.c-moon{border-right:2px solid #bbb;align-items:center;background:#CCC}
.c-sun {align-items:center;background:#efefef}
.panel-lbl{font-size:26px;font-weight:700;text-transform:uppercase;letter-spacing:.14em;color:black;margin-bottom:5px;flex-shrink:0}

/* ── WEATHER PANEL ── */
.temp{font-size:150px;font-weight:200;line-height:1;letter-spacing:-.02em;flex-shrink:0}
.temp sup{font-size:48px;vertical-align:.6em;font-weight:300}
.cond{font-size:38px;font-weight:700;margin-top:3px;flex-shrink:0}
.sub{font-size:22px;color:#555;margin-top:5px;flex-shrink:0}
.stat{font-size:18px;color:#555;margin-top:5px;line-height:1.35;flex-shrink:0}
.stat strong{color:#000;font-weight:700}

/* ── MOON PANEL ── */
.moon-wrap{flex-shrink:0}
.moon-name{font-size:26px;font-weight:800;text-align:center;margin-top:9px;flex-shrink:0}
.moon-sub{font-size:16px;color:#888;text-align:center;margin-top:3px;flex-shrink:0}

/* ── SOLAR PANEL ── */
.sun-wrap{flex-shrink:0}
.sun-times{display:flex;gap:20px;margin-top:9px;flex-shrink:0}
.sun-item{text-align:center}
.sun-lbl{font-size:11px;text-transform:uppercase;letter-spacing:.1em;color:#aaa;display:block}
.sun-val{font-size:29px;font-weight:700}
.sun-daylen{font-size:18px;color:#888;margin-top:5px;flex-shrink:0}

/* ── FORECAST + CALENDAR BAR ── */
.fc-bar{
  display:grid;grid-template-columns:repeat(5,1fr);
  border-top:2px solid #000;overflow:hidden;
}
.fd{
  display:flex;flex-direction:column;
  padding:4px 5px 3px;
  border-right:1px solid #ddd;overflow:hidden;
}
.fd:last-child{border-right:none}

/* Day name */
.fd-dow{font-size:26px;font-weight:900;text-transform:uppercase;letter-spacing:.02em;line-height:1;text-align:center;flex-shrink:0}

/* Weather icon — centred */
.fd-icon{width:106px;height:106px;display:block;margin:2px auto 0;flex-shrink:0;overflow:visible}

/* Precipitation below icon */
.fd-pop{font-size:17px;color:#555;text-align:center;flex-shrink:0;line-height:1.2;margin-top:1px}

/* Hi / Lo temps — large and dominant */
.fd-temps{text-align:center;flex-shrink:0;margin-top:1px}
.fd-hi{font-size:56px;font-weight:700;line-height:1}
.fd-lo{font-size:34px;color:#888;line-height:1}

/* Temperature range bar */
.fd-graph{width:100%;height:14px;display:block;flex-shrink:0;margin:3px 0}

/* Calendar events — no times */
.fd-cal{border-top:1px solid #ddd;margin-top:3px;padding-top:3px;flex:1;overflow:hidden;display:flex;flex-direction:column;gap:3px}
.fd-hol{font-size:13px;font-weight:800;text-transform:uppercase;background:#222;color:#fff;padding:1px 5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;align-self:flex-start;flex-shrink:0}
.fd-ev-n{font-size:17px;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.fd-aev{font-size:16px;font-weight:500;color:#333;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;font-style:italic}
.fd-more{font-size:13px;color:#aaa}
</style>

<div class="hdr">
  <div class="hdr-t">IKASLO6 &middot; Kaslo BC</div>
  <div class="hdr-m">{{ temp_full }}&deg;C &ensp;&bull;&ensp; indoor {{ indoor_temp }}&deg; / {{ indoor_hum }}%</div>
  <div class="hdr-r">{{ trmnl.user.time | date: "%H:%M &middot; %a %b %-d" }}</div>
</div>

<div class="main">

  <!-- ── PANEL 1: Current Weather ── -->
  <div class="c-wx">
    <div class="panel-lbl">Weather</div>
    <div class="temp">{{ temp }}<sup>&deg;</sup></div>
    <div class="cond">{{ condition }}</div>
    <div class="sub">Feels {{ feels }}&deg; &nbsp;&bull;&nbsp; Hi {{ hi }}&deg; / Lo {{ lo }}&deg;</div>
    <div class="stat">Rain <strong>{{ rain_day }}mm</strong> &bull; Rate <strong>{{ rain_rate }}mm/h</strong> &bull; Week <strong>{{ rain_week }}mm</strong></div>
    <div class="stat">Wind <strong>{{ wdir }} {{ wind_kmh }}km/h</strong> &bull; Gust <strong>{{ gust_kmh }}</strong> &bull; <strong>{{ pressure }}hPa</strong></div>
    <div class="stat">Humid <strong>{{ humidity }}%</strong> &bull; Dew <strong>{{ dew }}&deg;</strong> &bull; UV <strong>{{ uvi }}</strong> &bull; Solar <strong>{{ solar }}W</strong></div>
  </div>

  <!-- ── PANEL 2: Moon Phase ── -->
  <div class="c-moon">
    <div class="panel-lbl">Moon Phase</div>
    <svg id="moon-svg" class="moon-wrap" viewBox="0 0 200 200" width="202" height="202"></svg>
    <div class="moon-name" id="moon-phase-name"></div>
    <div class="moon-sub" id="moon-phase-sub"></div>
  </div>

  <!-- ── PANEL 3: Solar Clock ── -->
  <div class="c-sun">
    <div class="panel-lbl">Solar Clock</div>
    <svg id="sun-svg" class="sun-wrap" viewBox="-12 -12 284 284" width="245" height="245">
      <defs><clipPath id="sol-clip"><circle cx="130" cy="130" r="120"/></clipPath></defs>
      <!-- Night base -->
      <circle cx="130" cy="130" r="120" fill="#111"/>
      <!-- Twilight + day sectors, clipped to circle -->
      <g clip-path="url(#sol-clip)">
        <path id="astro-arc"  d="" fill="#444"/>
        <path id="naut-arc"   d="" fill="#777"/>
        <path id="civil-arc"  d="" fill="#bbb"/>
        <path id="day-arc"    d="" fill="#fff"/>
      </g>
      <!-- Circle border + hour markers -->
      <circle cx="130" cy="130" r="120" fill="none" stroke="#888" stroke-width="2"/>
      <line x1="130" y1="10" x2="130" y2="28" stroke="#555" stroke-width="2.5" stroke-linecap="round"/>
      <line x1="130" y1="232" x2="130" y2="250" stroke="#555" stroke-width="2.5" stroke-linecap="round"/>
      <!-- Sun position dot (moved by JS) -->
      <circle id="sun-dot" cx="130" cy="10" r="15" fill="#fff" stroke="#000" stroke-width="3"/>
    </svg>
    <div class="sun-times">
      <div class="sun-item"><span class="sun-lbl">Sunrise</span><span class="sun-val">&uarr;&thinsp;{{ sunrise }}</span></div>
      <div class="sun-item"><span class="sun-lbl">Sunset</span><span class="sun-val">&darr;&thinsp;{{ sunset }}</span></div>
    </div>
    <div class="sun-daylen">{{ daylight }}</div>
  </div>

</div>

<!-- Local time for sun wheel JS (avoids UTC timezone issue) -->
<span id="now-hm" style="display:none">{{ trmnl.user.time | date: "%H:%M" }}</span>

<!-- ── 5-Day Forecast + Calendar ── -->
<div class="fc-bar">

  <div class="fd" data-cond="{{ f0_condition }}">
    <div class="fd-dow">{{ f0_dow }}</div>
    <svg class="fd-icon" id="fi0" viewBox="0 0 54 54"></svg>
    {% if f0_pop_str %}<div class="fd-pop">{{ f0_pop_str }}</div>{% endif %}
    <div class="fd-temps"><span class="fd-hi">{{ f0_hi }}&deg;</span> <span class="fd-lo">{{ f0_lo }}&deg;</span></div>
    <svg class="fd-graph" viewBox="0 0 100 14" preserveAspectRatio="none"></svg>
    <div class="fd-cal">
      {% if f0_hol %}<div class="fd-hol">{{ f0_hol }}</div>{% endif %}
      {% if f0_tev0n %}<div class="fd-ev-n">{{ f0_tev0n }}</div>{% endif %}
      {% if f0_tev1n %}<div class="fd-ev-n">{{ f0_tev1n }}</div>{% endif %}
      {% if f0_tmore %}<div class="fd-more">{{ f0_tmore }}</div>{% endif %}
      {% if f0_aev0 %}<div class="fd-aev">{{ f0_aev0 }}</div>{% endif %}
      {% if f0_aev1 %}<div class="fd-aev">{{ f0_aev1 }}</div>{% endif %}
      {% if f0_amore %}<div class="fd-more">{{ f0_amore }}</div>{% endif %}
    </div>
  </div>

  <div class="fd" data-cond="{{ f1_condition }}">
    <div class="fd-dow">{{ f1_dow }}</div>
    <svg class="fd-icon" id="fi1" viewBox="0 0 54 54"></svg>
    {% if f1_pop_str %}<div class="fd-pop">{{ f1_pop_str }}</div>{% endif %}
    <div class="fd-temps"><span class="fd-hi">{{ f1_hi }}&deg;</span> <span class="fd-lo">{{ f1_lo }}&deg;</span></div>
    <svg class="fd-graph" viewBox="0 0 100 14" preserveAspectRatio="none"></svg>
    <div class="fd-cal">
      {% if f1_hol %}<div class="fd-hol">{{ f1_hol }}</div>{% endif %}
      {% if f1_tev0n %}<div class="fd-ev-n">{{ f1_tev0n }}</div>{% endif %}
      {% if f1_tev1n %}<div class="fd-ev-n">{{ f1_tev1n }}</div>{% endif %}
      {% if f1_tmore %}<div class="fd-more">{{ f1_tmore }}</div>{% endif %}
      {% if f1_aev0 %}<div class="fd-aev">{{ f1_aev0 }}</div>{% endif %}
      {% if f1_aev1 %}<div class="fd-aev">{{ f1_aev1 }}</div>{% endif %}
      {% if f1_amore %}<div class="fd-more">{{ f1_amore }}</div>{% endif %}
    </div>
  </div>

  <div class="fd" data-cond="{{ f2_condition }}">
    <div class="fd-dow">{{ f2_dow }}</div>
    <svg class="fd-icon" id="fi2" viewBox="0 0 54 54"></svg>
    {% if f2_pop_str %}<div class="fd-pop">{{ f2_pop_str }}</div>{% endif %}
    <div class="fd-temps"><span class="fd-hi">{{ f2_hi }}&deg;</span> <span class="fd-lo">{{ f2_lo }}&deg;</span></div>
    <svg class="fd-graph" viewBox="0 0 100 14" preserveAspectRatio="none"></svg>
    <div class="fd-cal">
      {% if f2_hol %}<div class="fd-hol">{{ f2_hol }}</div>{% endif %}
      {% if f2_tev0n %}<div class="fd-ev-n">{{ f2_tev0n }}</div>{% endif %}
      {% if f2_tev1n %}<div class="fd-ev-n">{{ f2_tev1n }}</div>{% endif %}
      {% if f2_tmore %}<div class="fd-more">{{ f2_tmore }}</div>{% endif %}
      {% if f2_aev0 %}<div class="fd-aev">{{ f2_aev0 }}</div>{% endif %}
      {% if f2_aev1 %}<div class="fd-aev">{{ f2_aev1 }}</div>{% endif %}
      {% if f2_amore %}<div class="fd-more">{{ f2_amore }}</div>{% endif %}
    </div>
  </div>

  <div class="fd" data-cond="{{ f3_condition }}">
    <div class="fd-dow">{{ f3_dow }}</div>
    <svg class="fd-icon" id="fi3" viewBox="0 0 54 54"></svg>
    {% if f3_pop_str %}<div class="fd-pop">{{ f3_pop_str }}</div>{% endif %}
    <div class="fd-temps"><span class="fd-hi">{{ f3_hi }}&deg;</span> <span class="fd-lo">{{ f3_lo }}&deg;</span></div>
    <svg class="fd-graph" viewBox="0 0 100 14" preserveAspectRatio="none"></svg>
    <div class="fd-cal">
      {% if f3_hol %}<div class="fd-hol">{{ f3_hol }}</div>{% endif %}
      {% if f3_tev0n %}<div class="fd-ev-n">{{ f3_tev0n }}</div>{% endif %}
      {% if f3_tev1n %}<div class="fd-ev-n">{{ f3_tev1n }}</div>{% endif %}
      {% if f3_tmore %}<div class="fd-more">{{ f3_tmore }}</div>{% endif %}
      {% if f3_aev0 %}<div class="fd-aev">{{ f3_aev0 }}</div>{% endif %}
      {% if f3_aev1 %}<div class="fd-aev">{{ f3_aev1 }}</div>{% endif %}
      {% if f3_amore %}<div class="fd-more">{{ f3_amore }}</div>{% endif %}
    </div>
  </div>

  <div class="fd" data-cond="{{ f4_condition }}">
    <div class="fd-dow">{{ f4_dow }}</div>
    <svg class="fd-icon" id="fi4" viewBox="0 0 54 54"></svg>
    {% if f4_pop_str %}<div class="fd-pop">{{ f4_pop_str }}</div>{% endif %}
    <div class="fd-temps"><span class="fd-hi">{{ f4_hi }}&deg;</span> <span class="fd-lo">{{ f4_lo }}&deg;</span></div>
    <svg class="fd-graph" viewBox="0 0 100 14" preserveAspectRatio="none"></svg>
    <div class="fd-cal">
      {% if f4_hol %}<div class="fd-hol">{{ f4_hol }}</div>{% endif %}
      {% if f4_tev0n %}<div class="fd-ev-n">{{ f4_tev0n }}</div>{% endif %}
      {% if f4_tev1n %}<div class="fd-ev-n">{{ f4_tev1n }}</div>{% endif %}
      {% if f4_tmore %}<div class="fd-more">{{ f4_tmore }}</div>{% endif %}
      {% if f4_aev0 %}<div class="fd-aev">{{ f4_aev0 }}</div>{% endif %}
      {% if f4_aev1 %}<div class="fd-aev">{{ f4_aev1 }}</div>{% endif %}
      {% if f4_amore %}<div class="fd-more">{{ f4_amore }}</div>{% endif %}
    </div>
  </div>

</div>

<script>
(function(){

  // ── Solar clock ────────────────────────────────────────────────────────────
  // Layers drawn largest→smallest; each paints over previous, revealing
  // the twilight rings at the edges. Noon at top, midnight at bottom.
  //   Black  = night     White = full daylight
  //   #444   = astronomical twilight (±90 min)
  //   #777   = nautical twilight     (±55 min)
  //   #bbb   = civil twilight        (±25 min)
  (function(){
    var CX=130,CY=130,R=120;
    function toMin(t){var p=t.split(':');return +p[0]*60+ +p[1];}
    function ang(m){return 270-(m/1440)*360;}
    function rad(d){return d*Math.PI/180;}
    function pt(r,d){return[CX+r*Math.cos(rad(d)),CY-r*Math.sin(rad(d))];}
    // Draws a filled sector from center, going through noon (top).
    // sweep=0 is CCW in SVG (y-down) = clockwise in math coords = through noon.
    function sector(s,e){
      var sa=ang(s),ea=ang(e),p1=pt(R,sa),p2=pt(R,ea);
      var span=((sa-ea)+360)%360, la=span>180?1:0;
      // sweep=1 selects the circle centered at (CX,CY); sweep=0 picks wrong circle
      return 'M'+CX+','+CY+
             ' L'+p1[0].toFixed(1)+','+p1[1].toFixed(1)+
             ' A'+R+' '+R+' 0 '+la+' 1 '+p2[0].toFixed(1)+' '+p2[1].toFixed(1)+' Z';
    }
    var sr='{{ sunrise }}',ss='{{ sunset }}';
    if(sr&&ss&&sr.indexOf(':')>=0){
      var srM=toMin(sr),ssM=toMin(ss);
      document.getElementById('astro-arc').setAttribute('d', sector(srM-90, ssM+90));
      document.getElementById('naut-arc').setAttribute('d',  sector(srM-55, ssM+55));
      document.getElementById('civil-arc').setAttribute('d', sector(srM-25, ssM+25));
      document.getElementById('day-arc').setAttribute('d',   sector(srM,    ssM));
      // Sun dot — positioned at current local time on perimeter
      var nowHM=(document.getElementById('now-hm')||{}).textContent||'';
      var nowM=nowHM.indexOf(':')>=0?toMin(nowHM.trim()):720;
      var sp=pt(R,ang(nowM));
      var dot=document.getElementById('sun-dot');
      dot.setAttribute('cx',sp[0].toFixed(1));
      dot.setAttribute('cy',sp[1].toFixed(1));
    }
  })();

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
      'Day '+Math.round(age)+' of 29  ·  '+
      Math.round(50*(1-Math.cos(age/SYN*2*Math.PI)))+'% lit';
    var cx=100,cy=100,r=90;
    var ms={
      'New Moon':
        '<circle cx="'+cx+'" cy="'+cy+'" r="'+r+'" fill="#222" stroke="#777" stroke-width="3"/>',
      'Full Moon':
        '<circle cx="'+cx+'" cy="'+cy+'" r="'+r+'" fill="#fff" stroke="#333" stroke-width="3"/>',
      'First Quarter':
        '<circle cx="'+cx+'" cy="'+cy+'" r="'+r+'" fill="#222"/>'+
        '<path d="M'+cx+','+(cy-r)+' A'+r+','+r+' 0 0,1 '+cx+','+(cy+r)+' Z" fill="#fff"/>',
      'Last Quarter':
        '<circle cx="'+cx+'" cy="'+cy+'" r="'+r+'" fill="#222"/>'+
        '<path d="M'+cx+','+(cy-r)+' A'+r+','+r+' 0 0,0 '+cx+','+(cy+r)+' Z" fill="#fff"/>',
      'Waxing Crescent':
        '<circle cx="'+cx+'" cy="'+cy+'" r="'+r+'" fill="#222"/>'+
        '<path d="M'+cx+','+(cy-r)+' A'+r+','+r+' 0 0,1 '+cx+','+(cy+r)+
        ' A'+(r*.38)+','+r+' 0 0,0 '+cx+','+(cy-r)+' Z" fill="#fff"/>',
      'Waning Crescent':
        '<circle cx="'+cx+'" cy="'+cy+'" r="'+r+'" fill="#222"/>'+
        '<path d="M'+cx+','+(cy-r)+' A'+r+','+r+' 0 0,0 '+cx+','+(cy+r)+
        ' A'+(r*.38)+','+r+' 0 0,1 '+cx+','+(cy-r)+' Z" fill="#fff"/>',
      'Waxing Gibbous':
        '<circle cx="'+cx+'" cy="'+cy+'" r="'+r+'" fill="#fff" stroke="#333" stroke-width="2"/>'+
        '<path d="M'+cx+','+(cy-r)+' A'+r+','+r+' 0 0,0 '+cx+','+(cy+r)+
        ' A'+(r*.5)+','+r+' 0 0,1 '+cx+','+(cy-r)+' Z" fill="#222"/>',
      'Waning Gibbous':
        '<circle cx="'+cx+'" cy="'+cy+'" r="'+r+'" fill="#fff" stroke="#333" stroke-width="2"/>'+
        '<path d="M'+cx+','+(cy-r)+' A'+r+','+r+' 0 0,1 '+cx+','+(cy+r)+
        ' A'+(r*.5)+','+r+' 0 0,0 '+cx+','+(cy-r)+' Z" fill="#222"/>'
    };
    var msvg=document.getElementById('moon-svg');
    if(msvg) msvg.innerHTML=ms[names[idx]]||ms['New Moon'];
  })();

  // ── Forecast icons ─────────────────────────────────────────────────────────
  var IC={
    'Clear':
      '<circle cx="27" cy="27" r="9" fill="#000"/>'+
      '<line x1="27" y1="4"  x2="27" y2="12" stroke="#000" stroke-width="2.5" stroke-linecap="round"/>'+
      '<line x1="27" y1="42" x2="27" y2="50" stroke="#000" stroke-width="2.5" stroke-linecap="round"/>'+
      '<line x1="4"  y1="27" x2="12" y2="27" stroke="#000" stroke-width="2.5" stroke-linecap="round"/>'+
      '<line x1="42" y1="27" x2="50" y2="27" stroke="#000" stroke-width="2.5" stroke-linecap="round"/>'+
      '<line x1="11" y1="11" x2="17" y2="17" stroke="#000" stroke-width="2.5" stroke-linecap="round"/>'+
      '<line x1="37" y1="37" x2="43" y2="43" stroke="#000" stroke-width="2.5" stroke-linecap="round"/>'+
      '<line x1="43" y1="11" x2="37" y2="17" stroke="#000" stroke-width="2.5" stroke-linecap="round"/>'+
      '<line x1="11" y1="43" x2="17" y2="37" stroke="#000" stroke-width="2.5" stroke-linecap="round"/>',
    'Mostly Clear':
      '<circle cx="20" cy="19" r="8" fill="#000"/>'+
      '<line x1="20" y1="5"  x2="20" y2="11" stroke="#000" stroke-width="2" stroke-linecap="round"/>'+
      '<line x1="5"  y1="19" x2="11" y2="19" stroke="#000" stroke-width="2" stroke-linecap="round"/>'+
      '<line x1="9"  y1="9"  x2="14" y2="13" stroke="#000" stroke-width="2" stroke-linecap="round"/>'+
      '<line x1="31" y1="9"  x2="26" y2="13" stroke="#000" stroke-width="2" stroke-linecap="round"/>'+
      '<path d="M18 32 Q13 32 13 27 Q13 22 18 21 Q18 15 24 14 Q30 13 33 18 Q38 18 40 22 Q45 22 45 27 Q45 32 40 32 Z" fill="#000"/>',
    'Partly Cloudy':
      '<circle cx="18" cy="18" r="8" fill="#000"/>'+
      '<line x1="18" y1="4"  x2="18" y2="10" stroke="#000" stroke-width="2" stroke-linecap="round"/>'+
      '<line x1="4"  y1="18" x2="10" y2="18" stroke="#000" stroke-width="2" stroke-linecap="round"/>'+
      '<line x1="8"  y1="8"  x2="13" y2="13" stroke="#000" stroke-width="2" stroke-linecap="round"/>'+
      '<line x1="28" y1="8"  x2="23" y2="13" stroke="#000" stroke-width="2" stroke-linecap="round"/>'+
      '<path d="M15 38 Q10 38 10 33 Q10 28 16 27 Q16 21 22 20 Q28 19 31 24 Q36 24 38 28 Q44 28 44 33 Q44 38 38 38 Z" fill="#000"/>',
    'Overcast':
      '<path d="M8 34 Q3 34 3 28 Q3 22 9 21 Q9 12 18 10 Q28 8 32 17 Q38 17 41 22 Q48 22 48 28 Q48 34 41 34 Z" fill="#000"/>'+
      '<path d="M14 44 Q10 44 10 40 Q10 36 15 35 Q15 30 21 29 Q27 28 30 33 Q35 33 37 37 Q42 37 42 41 Q42 45 37 45 Z" fill="#555"/>',
    'Fog':
      '<line x1="7"  y1="14" x2="47" y2="14" stroke="#000" stroke-width="4"   stroke-linecap="round"/>'+
      '<line x1="4"  y1="23" x2="50" y2="23" stroke="#888" stroke-width="3"   stroke-linecap="round"/>'+
      '<line x1="7"  y1="32" x2="47" y2="32" stroke="#000" stroke-width="4"   stroke-linecap="round"/>'+
      '<line x1="11" y1="41" x2="43" y2="41" stroke="#bbb" stroke-width="2.5" stroke-linecap="round"/>'+
      '<line x1="15" y1="49" x2="39" y2="49" stroke="#ddd" stroke-width="2"   stroke-linecap="round"/>',
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
      '<line x1="20" y1="30" x2="20" y2="46" stroke="#444" stroke-width="2"   stroke-linecap="round"/>'+
      '<line x1="34" y1="30" x2="34" y2="46" stroke="#444" stroke-width="2"   stroke-linecap="round"/>'+
      '<line x1="13" y1="35" x2="27" y2="41" stroke="#444" stroke-width="2"   stroke-linecap="round"/>'+
      '<line x1="27" y1="35" x2="13" y2="41" stroke="#444" stroke-width="2"   stroke-linecap="round"/>',
    'Thunderstorm':
      '<path d="M10 23 Q6 23 6 17 Q6 11 12 10 Q12 2 21 0 Q30 -2 34 7 Q40 7 42 12 Q48 12 48 18 Q48 24 42 24 Z" fill="#000"/>'+
      '<polygon points="30,27 22,42 28,42 22,54 35,36 29,36" fill="#333"/>'
  };
  document.querySelectorAll('.fc-bar .fd').forEach(function(fd,i){
    var cond=(fd.getAttribute('data-cond')||'').trim();
    var svg=document.getElementById('fi'+i);
    if(svg) svg.innerHTML=IC[cond]||IC['Overcast'];
  });

  // ── Temperature range graph ────────────────────────────────────────────────
  (function(){
    var fds=[].slice.call(document.querySelectorAll('.fc-bar .fd'));
    var his=fds.map(function(fd){
      var el=fd.querySelector('.fd-hi');
      return el?parseFloat(el.textContent)||0:0;
    });
    var los=fds.map(function(fd){
      var el=fd.querySelector('.fd-lo');
      return el?parseFloat(el.textContent)||0:0;
    });
    var mn=Math.min.apply(null,los)-2;
    var mx=Math.max.apply(null,his)+2;
    var rng=mx-mn||1;
    fds.forEach(function(fd,i){
      var svg=fd.querySelector('.fd-graph');
      if(!svg)return;
      var lx=((los[i]-mn)/rng*92+4).toFixed(1);
      var hx=((his[i]-mn)/rng*92+4).toFixed(1);
      var w=(+hx-+lx).toFixed(1);
      svg.innerHTML='<rect x="2" y="4" width="96" height="6" fill="#ddd" rx="3"/>'+
                    '<rect x="'+lx+'" y="2" width="'+w+'" height="10" fill="#444" rx="3"/>';
    });
  })();

})();
</script>
```
