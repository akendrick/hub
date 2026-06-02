# Plugin: dashboard
# Layout: Full-screen dashboard  weather + todo + solar/moon (top 1/3) | 2-week calendar (bottom 2/3)
#
# Polling URL:
#   https://knotwork.ca/kaslo-api.php?key=YOUR_DEVICE_KEY
#   (no &game=  skips DGS board fetch)
#
# Settings: Strategy: Polling | Verb: GET | Remove bleed margin: Yes

## Markup (Full tab)

```html
<style>
*{box-sizing:border-box;margin:0;padding:0}
html,body{width:100%;height:100%;overflow:hidden;background:#fff;color:#000;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif}
body{display:flex;flex-direction:column}

/* Header */
.hdr{height:28px;flex-shrink:0;display:flex;justify-content:space-between;align-items:center;padding:0 12px;border-bottom:2px solid #000}
.hdr-t{font-weight:800;font-size:14px;letter-spacing:.04em}
.hdr-r{font-size:11px;color:#555}

/* Top strip: 3 equal columns, 130px */
.top{flex-shrink:0;display:grid;grid-template-columns:1fr 1fr 1fr;border-bottom:2px solid #000;overflow:hidden}
.top-col{overflow:hidden;display:flex;flex-direction:column}
.top-col+.top-col{border-left:1px solid #ccc}
.col-lbl{font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:#888;flex-shrink:0;padding:4px 7px 2px}

/* Left col: 2 sub-cols (weather | moon+sun) */
.col-left-inner{flex:1;display:grid;grid-template-columns:55% 45%;min-height:0;overflow:hidden}
.wx-sub-col{overflow:hidden;padding:0 6px 4px;display:flex;flex-direction:column}
.moon-sub-col{overflow:hidden;padding:0 6px 4px;display:flex;flex-direction:column;align-items:center;justify-content:center}
.w-temp{font-size:52px;font-weight:200;line-height:1;flex-shrink:0}
.w-temp sup{font-size:16px;vertical-align:.6em;font-weight:300}
.w-cond{font-size:13px;font-weight:700;flex-shrink:0;margin-top:1px}
.w-sub{font-size:10px;color:#555;flex-shrink:0;line-height:1.45}
.moon-icon-wrap{flex-shrink:0}
.moon-nm{font-size:9px;color:#444;font-style:italic;text-align:center;margin-top:3px;flex-shrink:0}
.sun-times-v{font-size:10px;font-weight:700;text-align:center;color:#333;margin-top:4px;flex-shrink:0;line-height:1.6}

/* Todo column */
.td-inner{flex:1;min-height:0;overflow:hidden;padding:0 7px 4px;display:flex;flex-direction:column}
.td-list{flex:1;overflow:hidden;display:flex;flex-direction:column;gap:1px}
.td-item{font-size:11px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;line-height:1.3}
.td-item:nth-child(odd){background:#f6f6f6}
.td-item:nth-child(even){background:#fff}
.td-item.u::before{content:" ";font-weight:900}
.td-item.r{font-weight:500;color:#444}
.td-item.r::before{content:" ";font-size:10px}

/* Right col: Indoor temp fills box */
.col-indoor-inner{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;overflow:hidden;padding:4px}
.indoor-big{font-size:68px;font-weight:200;line-height:1;text-align:center;color:#000}
.indoor-big sup{font-size:26px;vertical-align:.5em;font-weight:300}
.indoor-hum{font-size:14px;color:#555;text-align:center;margin-top:4px}

/* Forecast strip  horizontal icon+temps layout */
.fc-strip{flex-shrink:0;display:grid;grid-template-columns:repeat(7,1fr);border-bottom:none;overflow:hidden;background:#f9f9f9;min-height:88px}
.fc-col{display:flex;flex-direction:column;align-items:center;justify-content:center;padding:3px 2px;border-right:1px solid #ddd;overflow:hidden;gap:2px}
.fc-col:last-child{border-right:none}
.fc-col:nth-child(even){background:#ebebeb}
.fc-col:nth-child(odd){background:#f9f9f9}
.fc-dov{display:flex;flex-direction:row;align-items:baseline;gap:3px;flex-shrink:0}
.fc-dow{font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.02em}
.fc-date{font-size:20px;font-weight:900;line-height:1}
.fc-mid{display:flex;align-items:center;gap:4px;flex-shrink:0}
.fc-icon{width:32px;height:32px;flex-shrink:0;overflow:visible}
.fc-temps-v{display:flex;flex-direction:column;line-height:1}
.fc-hi{font-size:15px;font-weight:800;color:#000}
.fc-lo{font-size:12px;font-weight:400;color:#888}
.fc-pop{font-size:8px;color:#666;flex-shrink:0;text-align:center}
.fc-bar-wrap{width:75%;height:6px;flex-shrink:0}

/* Calendar  bottom 2/3 */
.cal{flex:1;min-height:0;display:flex;flex-direction:column;overflow:hidden;border-top:2px solid #000}
.cal-row{flex-shrink:0;display:grid;grid-template-columns:repeat(7,1fr);border-bottom:1px solid #aaa;overflow:hidden}
.cal-row:last-child{border-bottom:none}

/* Day cells */
.day{overflow:hidden;display:flex;flex-direction:column;border-right:1px solid #ddd;padding:2px 3px;background:#fff}
.day:last-child{border-right:none}
.day.wknd{background:#ededED}
.day.hol{background:#d5d5d5}
.day.today{background:#222;color:#fff}
.d-hdr{flex-shrink:0}
.d-month{font-size:8px;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:#777;line-height:1.2}
.day.today .d-month{color:#aaa}
.d-dayline{display:flex;align-items:baseline;gap:3px}
.d-dow{font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:.04em;opacity:.65}
.d-dom{font-size:20px;font-weight:900;line-height:1}
.d-evts{flex:1;min-height:0;overflow:hidden;display:flex;flex-direction:column}
.d-ev{font-size:11px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;line-height:1.35;color:#222}
.day.today .d-ev{color:#fff}
.d-ev .t{font-size:9px;color:#888;margin-right:1px}
.day.today .d-ev .t{color:#ccc}
.d-allday{flex-shrink:0;display:flex;flex-wrap:nowrap;overflow:hidden;gap:1px;margin-top:auto;padding-top:1px}
.d-hol{font-size:7px;font-weight:800;background:#555;color:#fff;padding:1px 2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;border-radius:1px}
.d-aev{font-size:7px;font-weight:800;background:#333;color:#fff;padding:1px 2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;border-radius:1px}
.day.today .d-hol,.day.today .d-aev{background:#000}
</style>



<div class="hdr">
  <div class="hdr-t">KASLO &middot; Dashboard</div>
  <div class="hdr-r">{{ trmnl.user.time | date: "%H:%M &middot; %a %b %-d" }}</div>
</div>

<div class="top">

  <!-- Left col: Weather (left) | Moon+Sun (right) -->
  <div class="top-col">
    <div class="col-lbl">Weather &middot; IKASLO6</div>
    <div class="col-left-inner">
      <div class="wx-sub-col">
        <div class="w-temp">{{ temp }}<sup>&deg;</sup></div>
        <div class="w-cond">{{ condition }}</div>
        <div class="w-sub">Feels {{ feels }}&deg; &bull; Hi {{ hi }}&deg;/Lo {{ lo }}&deg;</div>
        <div class="w-sub">{{ wdir }} {{ wind_kmh }}km/h &bull; {{ rain_day }}mm</div>
        <div class="w-sub">{{ humidity }}% &bull; {{ pressure }}hPa</div>
      </div>
      <div class="moon-sub-col">
        <svg id="msvg" width="50" height="50" class="moon-icon-wrap" style="display:block"></svg>
        <div class="moon-nm">{{ moon_name }}</div>
        <div class="sun-times-v">&uarr; {{ sunrise }}<br>&darr; {{ sunset }}</div>
      </div>
    </div>
  </div>

  <!-- Middle: Todo -->
  <div class="top-col">
    <div class="col-lbl">To Do &nbsp;({{ todo_total }} open)</div>
    <div class="td-inner">
      <div class="td-list">
        {% if todo_u0_text %}<div class="td-item u">{{ todo_u0_text }}</div>{% endif %}
        {% if todo_u1_text %}<div class="td-item u">{{ todo_u1_text }}</div>{% endif %}
        {% if todo_u2_text %}<div class="td-item u">{{ todo_u2_text }}</div>{% endif %}
        {% if todo_u3_text %}<div class="td-item u">{{ todo_u3_text }}</div>{% endif %}
        {% if todo_r0_text %}<div class="td-item r">{{ todo_r0_text }}</div>{% endif %}
        {% if todo_r1_text %}<div class="td-item r">{{ todo_r1_text }}</div>{% endif %}
        {% if todo_r2_text %}<div class="td-item r">{{ todo_r2_text }}</div>{% endif %}
        {% if todo_r3_text %}<div class="td-item r">{{ todo_r3_text }}</div>{% endif %}
        {% if todo_u4_text %}<div class="td-item u">{{ todo_u4_text }}</div>{% endif %}
        {% if todo_u5_text %}<div class="td-item u">{{ todo_u5_text }}</div>{% endif %}
        {% if todo_r4_text %}<div class="td-item r">{{ todo_r4_text }}</div>{% endif %}
        {% if todo_r5_text %}<div class="td-item r">{{ todo_r5_text }}</div>{% endif %}
        {% if todo_r6_text %}<div class="td-item r">{{ todo_r6_text }}</div>{% endif %}
        {% if todo_r7_text %}<div class="td-item r">{{ todo_r7_text }}</div>{% endif %}
      </div>
    </div>
  </div>

  <!-- Right: Indoor temp (left) + Solar dial (right) -->
  <div class="top-col" style="overflow:hidden;display:flex;flex-direction:column">
    <div style="font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:#888;padding:4px 8px 2px">Indoor &middot; Solar</div>
    <div style="flex:1;min-height:0;display:grid;grid-template-columns:1fr 1fr;overflow:hidden">
      <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;padding:4px">
        <div style="font-size:52px;font-weight:200;line-height:1;color:#000;text-align:center">{{ indoor_temp }}<sup style="font-size:22px;vertical-align:.5em;font-weight:300">&deg;</sup></div>
        <div style="font-size:12px;color:#555;margin-top:3px">{{ indoor_hum }}% hum</div>
      </div>
      <div style="border-left:1px solid #e0e0e0;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:4px">
        <svg id="sdial" width="110" height="62" style="display:block"></svg>
        <div style="font-size:9px;font-weight:700;color:#333;text-align:center;margin-top:2px">&uarr;{{ sunrise }} &darr;{{ sunset }}</div>
      </div>
    </div>
  </div>

</div>

<!-- 7-day Forecast Strip -->
<div class="fc-strip">
  <div class="fc-col" id="fc0" data-cond="{{ f0_condition }}" data-hi="{{ f0_hi }}" data-lo="{{ f0_lo }}">
    <div class="fc-dov"><span class="fc-dow">{{ cal_d0_dow }}</span><span class="fc-date">{{ cal_d0_dom }}</span></div>
    <div class="fc-mid"><svg class="fc-icon" id="fi0" viewBox="0 0 54 54"></svg><div class="fc-temps-v"><span class="fc-hi">{{ f0_hi }}&deg;</span><span class="fc-lo">{{ f0_lo }}&deg;</span></div></div>
    {% if f0_pop_str != "" %}<div class="fc-pop">{{ f0_pop_str }}{{ f0_mm_str | prepend: " " }}</div>{% endif %}
    <svg class="fc-bar-wrap" id="fb0" viewBox="0 0 100 6" preserveAspectRatio="none"></svg>
  </div>
  <div class="fc-col" id="fc1" data-cond="{{ f1_condition }}" data-hi="{{ f1_hi }}" data-lo="{{ f1_lo }}">
    <div class="fc-dov"><span class="fc-dow">{{ cal_d1_dow }}</span><span class="fc-date">{{ cal_d1_dom }}</span></div>
    <div class="fc-mid"><svg class="fc-icon" id="fi1" viewBox="0 0 54 54"></svg><div class="fc-temps-v"><span class="fc-hi">{{ f1_hi }}&deg;</span><span class="fc-lo">{{ f1_lo }}&deg;</span></div></div>
    {% if f1_pop_str != "" %}<div class="fc-pop">{{ f1_pop_str }}{{ f1_mm_str | prepend: " " }}</div>{% endif %}
    <svg class="fc-bar-wrap" id="fb1" viewBox="0 0 100 6" preserveAspectRatio="none"></svg>
  </div>
  <div class="fc-col" id="fc2" data-cond="{{ f2_condition }}" data-hi="{{ f2_hi }}" data-lo="{{ f2_lo }}">
    <div class="fc-dov"><span class="fc-dow">{{ cal_d2_dow }}</span><span class="fc-date">{{ cal_d2_dom }}</span></div>
    <div class="fc-mid"><svg class="fc-icon" id="fi2" viewBox="0 0 54 54"></svg><div class="fc-temps-v"><span class="fc-hi">{{ f2_hi }}&deg;</span><span class="fc-lo">{{ f2_lo }}&deg;</span></div></div>
    {% if f2_pop_str != "" %}<div class="fc-pop">{{ f2_pop_str }}{{ f2_mm_str | prepend: " " }}</div>{% endif %}
    <svg class="fc-bar-wrap" id="fb2" viewBox="0 0 100 6" preserveAspectRatio="none"></svg>
  </div>
  <div class="fc-col" id="fc3" data-cond="{{ f3_condition }}" data-hi="{{ f3_hi }}" data-lo="{{ f3_lo }}">
    <div class="fc-dov"><span class="fc-dow">{{ cal_d3_dow }}</span><span class="fc-date">{{ cal_d3_dom }}</span></div>
    <div class="fc-mid"><svg class="fc-icon" id="fi3" viewBox="0 0 54 54"></svg><div class="fc-temps-v"><span class="fc-hi">{{ f3_hi }}&deg;</span><span class="fc-lo">{{ f3_lo }}&deg;</span></div></div>
    {% if f3_pop_str != "" %}<div class="fc-pop">{{ f3_pop_str }}{{ f3_mm_str | prepend: " " }}</div>{% endif %}
    <svg class="fc-bar-wrap" id="fb3" viewBox="0 0 100 6" preserveAspectRatio="none"></svg>
  </div>
  <div class="fc-col" id="fc4" data-cond="{{ f4_condition }}" data-hi="{{ f4_hi }}" data-lo="{{ f4_lo }}">
    <div class="fc-dov"><span class="fc-dow">{{ cal_d4_dow }}</span><span class="fc-date">{{ cal_d4_dom }}</span></div>
    <div class="fc-mid"><svg class="fc-icon" id="fi4" viewBox="0 0 54 54"></svg><div class="fc-temps-v"><span class="fc-hi">{{ f4_hi }}&deg;</span><span class="fc-lo">{{ f4_lo }}&deg;</span></div></div>
    {% if f4_pop_str != "" %}<div class="fc-pop">{{ f4_pop_str }}{{ f4_mm_str | prepend: " " }}</div>{% endif %}
    <svg class="fc-bar-wrap" id="fb4" viewBox="0 0 100 6" preserveAspectRatio="none"></svg>
  </div>
  <div class="fc-col" id="fc5" data-cond="{{ f5_condition }}" data-hi="{{ f5_hi }}" data-lo="{{ f5_lo }}">
    <div class="fc-dov"><span class="fc-dow">{{ cal_d5_dow }}</span><span class="fc-date">{{ cal_d5_dom }}</span></div>
    <div class="fc-mid"><svg class="fc-icon" id="fi5" viewBox="0 0 54 54"></svg><div class="fc-temps-v"><span class="fc-hi">{{ f5_hi }}&deg;</span><span class="fc-lo">{{ f5_lo }}&deg;</span></div></div>
    {% if f5_pop_str != "" %}<div class="fc-pop">{{ f5_pop_str }}{{ f5_mm_str | prepend: " " }}</div>{% endif %}
    <svg class="fc-bar-wrap" id="fb5" viewBox="0 0 100 6" preserveAspectRatio="none"></svg>
  </div>
  <div class="fc-col" id="fc6" data-cond="{{ f6_condition }}" data-hi="{{ f6_hi }}" data-lo="{{ f6_lo }}">
    <div class="fc-dov"><span class="fc-dow">{{ cal_d6_dow }}</span><span class="fc-date">{{ cal_d6_dom }}</span></div>
    <div class="fc-mid"><svg class="fc-icon" id="fi6" viewBox="0 0 54 54"></svg><div class="fc-temps-v"><span class="fc-hi">{{ f6_hi }}&deg;</span><span class="fc-lo">{{ f6_lo }}&deg;</span></div></div>
    {% if f6_pop_str != "" %}<div class="fc-pop">{{ f6_pop_str }}{{ f6_mm_str | prepend: " " }}</div>{% endif %}
    <svg class="fc-bar-wrap" id="fb6" viewBox="0 0 100 6" preserveAspectRatio="none"></svg>
  </div>
</div>

<!-- Calendar: row 1 = today + 6 days (events only), row 2 = days 7-13 -->
<div class="cal">

  <div class="cal-row">
<div class="day {{ cal_d0_today }} {{ cal_d0_wknd }}{% if cal_d0_hol != "" %} hol{% endif %}">
  <div class="d-evts">
    {% if cal_d0_tev0n != "" %}<div class="d-ev"><span class="t">{{cal_d0_tev0t}}</span>{{cal_d0_tev0n}}</div>{% endif %}
    {% if cal_d0_tev1n != "" %}<div class="d-ev"><span class="t">{{cal_d0_tev1t}}</span>{{cal_d0_tev1n}}</div>{% endif %}
  </div>
  <div class="d-allday">
    {% if cal_d0_hol != "" %}<span class="d-hol">{{cal_d0_hol}}</span>{% endif %}
    {% if cal_d0_aev0 != "" %}<span class="d-aev">{{cal_d0_aev0}}</span>{% endif %}
    {% if cal_d0_aev1 != "" %}<span class="d-aev">{{cal_d0_aev1}}</span>{% endif %}
  </div>
</div>
<div class="day {{ cal_d1_today }} {{ cal_d1_wknd }}{% if cal_d1_hol != "" %} hol{% endif %}">
  <div class="d-evts">
    {% if cal_d1_tev0n != "" %}<div class="d-ev"><span class="t">{{cal_d1_tev0t}}</span>{{cal_d1_tev0n}}</div>{% endif %}
    {% if cal_d1_tev1n != "" %}<div class="d-ev"><span class="t">{{cal_d1_tev1t}}</span>{{cal_d1_tev1n}}</div>{% endif %}
  </div>
  <div class="d-allday">
    {% if cal_d1_hol != "" %}<span class="d-hol">{{cal_d1_hol}}</span>{% endif %}
    {% if cal_d1_aev0 != "" %}<span class="d-aev">{{cal_d1_aev0}}</span>{% endif %}
    {% if cal_d1_aev1 != "" %}<span class="d-aev">{{cal_d1_aev1}}</span>{% endif %}
  </div>
</div>
<div class="day {{ cal_d2_today }} {{ cal_d2_wknd }}{% if cal_d2_hol != "" %} hol{% endif %}">
  <div class="d-evts">
    {% if cal_d2_tev0n != "" %}<div class="d-ev"><span class="t">{{cal_d2_tev0t}}</span>{{cal_d2_tev0n}}</div>{% endif %}
    {% if cal_d2_tev1n != "" %}<div class="d-ev"><span class="t">{{cal_d2_tev1t}}</span>{{cal_d2_tev1n}}</div>{% endif %}
  </div>
  <div class="d-allday">
    {% if cal_d2_hol != "" %}<span class="d-hol">{{cal_d2_hol}}</span>{% endif %}
    {% if cal_d2_aev0 != "" %}<span class="d-aev">{{cal_d2_aev0}}</span>{% endif %}
    {% if cal_d2_aev1 != "" %}<span class="d-aev">{{cal_d2_aev1}}</span>{% endif %}
  </div>
</div>
<div class="day {{ cal_d3_today }} {{ cal_d3_wknd }}{% if cal_d3_hol != "" %} hol{% endif %}">
  <div class="d-evts">
    {% if cal_d3_tev0n != "" %}<div class="d-ev"><span class="t">{{cal_d3_tev0t}}</span>{{cal_d3_tev0n}}</div>{% endif %}
    {% if cal_d3_tev1n != "" %}<div class="d-ev"><span class="t">{{cal_d3_tev1t}}</span>{{cal_d3_tev1n}}</div>{% endif %}
  </div>
  <div class="d-allday">
    {% if cal_d3_hol != "" %}<span class="d-hol">{{cal_d3_hol}}</span>{% endif %}
    {% if cal_d3_aev0 != "" %}<span class="d-aev">{{cal_d3_aev0}}</span>{% endif %}
    {% if cal_d3_aev1 != "" %}<span class="d-aev">{{cal_d3_aev1}}</span>{% endif %}
  </div>
</div>
<div class="day {{ cal_d4_today }} {{ cal_d4_wknd }}{% if cal_d4_hol != "" %} hol{% endif %}">
  <div class="d-evts">
    {% if cal_d4_tev0n != "" %}<div class="d-ev"><span class="t">{{cal_d4_tev0t}}</span>{{cal_d4_tev0n}}</div>{% endif %}
    {% if cal_d4_tev1n != "" %}<div class="d-ev"><span class="t">{{cal_d4_tev1t}}</span>{{cal_d4_tev1n}}</div>{% endif %}
  </div>
  <div class="d-allday">
    {% if cal_d4_hol != "" %}<span class="d-hol">{{cal_d4_hol}}</span>{% endif %}
    {% if cal_d4_aev0 != "" %}<span class="d-aev">{{cal_d4_aev0}}</span>{% endif %}
    {% if cal_d4_aev1 != "" %}<span class="d-aev">{{cal_d4_aev1}}</span>{% endif %}
  </div>
</div>
<div class="day {{ cal_d5_today }} {{ cal_d5_wknd }}{% if cal_d5_hol != "" %} hol{% endif %}">
  <div class="d-evts">
    {% if cal_d5_tev0n != "" %}<div class="d-ev"><span class="t">{{cal_d5_tev0t}}</span>{{cal_d5_tev0n}}</div>{% endif %}
    {% if cal_d5_tev1n != "" %}<div class="d-ev"><span class="t">{{cal_d5_tev1t}}</span>{{cal_d5_tev1n}}</div>{% endif %}
  </div>
  <div class="d-allday">
    {% if cal_d5_hol != "" %}<span class="d-hol">{{cal_d5_hol}}</span>{% endif %}
    {% if cal_d5_aev0 != "" %}<span class="d-aev">{{cal_d5_aev0}}</span>{% endif %}
    {% if cal_d5_aev1 != "" %}<span class="d-aev">{{cal_d5_aev1}}</span>{% endif %}
  </div>
</div>
<div class="day {{ cal_d6_today }} {{ cal_d6_wknd }}{% if cal_d6_hol != "" %} hol{% endif %}">
  <div class="d-evts">
    {% if cal_d6_tev0n != "" %}<div class="d-ev"><span class="t">{{cal_d6_tev0t}}</span>{{cal_d6_tev0n}}</div>{% endif %}
    {% if cal_d6_tev1n != "" %}<div class="d-ev"><span class="t">{{cal_d6_tev1t}}</span>{{cal_d6_tev1n}}</div>{% endif %}
  </div>
  <div class="d-allday">
    {% if cal_d6_hol != "" %}<span class="d-hol">{{cal_d6_hol}}</span>{% endif %}
    {% if cal_d6_aev0 != "" %}<span class="d-aev">{{cal_d6_aev0}}</span>{% endif %}
    {% if cal_d6_aev1 != "" %}<span class="d-aev">{{cal_d6_aev1}}</span>{% endif %}
  </div>
</div>
  </div>

  <div class="cal-row">
<div class="day {{ cal_d7_today }} {{ cal_d7_wknd }}{% if cal_d7_hol != "" %} hol{% endif %}">
  <div class="d-hdr">
    {% if cal_d7_month_label != "" %}<div class="d-month">{{cal_d7_month_label}}</div>{% endif %}
    <div class="d-dayline"><span class="d-dow">{{cal_d7_dow}}</span><span class="d-dom">{{cal_d7_dom}}</span></div>
  </div>
  <div class="d-evts">
    {% if cal_d7_tev0n != "" %}<div class="d-ev"><span class="t">{{cal_d7_tev0t}}</span>{{cal_d7_tev0n}}</div>{% endif %}
    {% if cal_d7_tev1n != "" %}<div class="d-ev"><span class="t">{{cal_d7_tev1t}}</span>{{cal_d7_tev1n}}</div>{% endif %}
  </div>
  <div class="d-allday">
    {% if cal_d7_hol != "" %}<span class="d-hol">{{cal_d7_hol}}</span>{% endif %}
    {% if cal_d7_aev0 != "" %}<span class="d-aev">{{cal_d7_aev0}}</span>{% endif %}
    {% if cal_d7_aev1 != "" %}<span class="d-aev">{{cal_d7_aev1}}</span>{% endif %}
  </div>
</div>
<div class="day {{ cal_d8_today }} {{ cal_d8_wknd }}{% if cal_d8_hol != "" %} hol{% endif %}">
  <div class="d-hdr">
    {% if cal_d8_month_label != "" %}<div class="d-month">{{cal_d8_month_label}}</div>{% endif %}
    <div class="d-dayline"><span class="d-dow">{{cal_d8_dow}}</span><span class="d-dom">{{cal_d8_dom}}</span></div>
  </div>
  <div class="d-evts">
    {% if cal_d8_tev0n != "" %}<div class="d-ev"><span class="t">{{cal_d8_tev0t}}</span>{{cal_d8_tev0n}}</div>{% endif %}
    {% if cal_d8_tev1n != "" %}<div class="d-ev"><span class="t">{{cal_d8_tev1t}}</span>{{cal_d8_tev1n}}</div>{% endif %}
  </div>
  <div class="d-allday">
    {% if cal_d8_hol != "" %}<span class="d-hol">{{cal_d8_hol}}</span>{% endif %}
    {% if cal_d8_aev0 != "" %}<span class="d-aev">{{cal_d8_aev0}}</span>{% endif %}
    {% if cal_d8_aev1 != "" %}<span class="d-aev">{{cal_d8_aev1}}</span>{% endif %}
  </div>
</div>
<div class="day {{ cal_d9_today }} {{ cal_d9_wknd }}{% if cal_d9_hol != "" %} hol{% endif %}">
  <div class="d-hdr">
    {% if cal_d9_month_label != "" %}<div class="d-month">{{cal_d9_month_label}}</div>{% endif %}
    <div class="d-dayline"><span class="d-dow">{{cal_d9_dow}}</span><span class="d-dom">{{cal_d9_dom}}</span></div>
  </div>
  <div class="d-evts">
    {% if cal_d9_tev0n != "" %}<div class="d-ev"><span class="t">{{cal_d9_tev0t}}</span>{{cal_d9_tev0n}}</div>{% endif %}
    {% if cal_d9_tev1n != "" %}<div class="d-ev"><span class="t">{{cal_d9_tev1t}}</span>{{cal_d9_tev1n}}</div>{% endif %}
  </div>
  <div class="d-allday">
    {% if cal_d9_hol != "" %}<span class="d-hol">{{cal_d9_hol}}</span>{% endif %}
    {% if cal_d9_aev0 != "" %}<span class="d-aev">{{cal_d9_aev0}}</span>{% endif %}
    {% if cal_d9_aev1 != "" %}<span class="d-aev">{{cal_d9_aev1}}</span>{% endif %}
  </div>
</div>
<div class="day {{ cal_d10_today }} {{ cal_d10_wknd }}{% if cal_d10_hol != "" %} hol{% endif %}">
  <div class="d-hdr">
    {% if cal_d10_month_label != "" %}<div class="d-month">{{cal_d10_month_label}}</div>{% endif %}
    <div class="d-dayline"><span class="d-dow">{{cal_d10_dow}}</span><span class="d-dom">{{cal_d10_dom}}</span></div>
  </div>
  <div class="d-evts">
    {% if cal_d10_tev0n != "" %}<div class="d-ev"><span class="t">{{cal_d10_tev0t}}</span>{{cal_d10_tev0n}}</div>{% endif %}
    {% if cal_d10_tev1n != "" %}<div class="d-ev"><span class="t">{{cal_d10_tev1t}}</span>{{cal_d10_tev1n}}</div>{% endif %}
  </div>
  <div class="d-allday">
    {% if cal_d10_hol != "" %}<span class="d-hol">{{cal_d10_hol}}</span>{% endif %}
    {% if cal_d10_aev0 != "" %}<span class="d-aev">{{cal_d10_aev0}}</span>{% endif %}
    {% if cal_d10_aev1 != "" %}<span class="d-aev">{{cal_d10_aev1}}</span>{% endif %}
  </div>
</div>
<div class="day {{ cal_d11_today }} {{ cal_d11_wknd }}{% if cal_d11_hol != "" %} hol{% endif %}">
  <div class="d-hdr">
    {% if cal_d11_month_label != "" %}<div class="d-month">{{cal_d11_month_label}}</div>{% endif %}
    <div class="d-dayline"><span class="d-dow">{{cal_d11_dow}}</span><span class="d-dom">{{cal_d11_dom}}</span></div>
  </div>
  <div class="d-evts">
    {% if cal_d11_tev0n != "" %}<div class="d-ev"><span class="t">{{cal_d11_tev0t}}</span>{{cal_d11_tev0n}}</div>{% endif %}
    {% if cal_d11_tev1n != "" %}<div class="d-ev"><span class="t">{{cal_d11_tev1t}}</span>{{cal_d11_tev1n}}</div>{% endif %}
  </div>
  <div class="d-allday">
    {% if cal_d11_hol != "" %}<span class="d-hol">{{cal_d11_hol}}</span>{% endif %}
    {% if cal_d11_aev0 != "" %}<span class="d-aev">{{cal_d11_aev0}}</span>{% endif %}
    {% if cal_d11_aev1 != "" %}<span class="d-aev">{{cal_d11_aev1}}</span>{% endif %}
  </div>
</div>
<div class="day {{ cal_d12_today }} {{ cal_d12_wknd }}{% if cal_d12_hol != "" %} hol{% endif %}">
  <div class="d-hdr">
    {% if cal_d12_month_label != "" %}<div class="d-month">{{cal_d12_month_label}}</div>{% endif %}
    <div class="d-dayline"><span class="d-dow">{{cal_d12_dow}}</span><span class="d-dom">{{cal_d12_dom}}</span></div>
  </div>
  <div class="d-evts">
    {% if cal_d12_tev0n != "" %}<div class="d-ev"><span class="t">{{cal_d12_tev0t}}</span>{{cal_d12_tev0n}}</div>{% endif %}
    {% if cal_d12_tev1n != "" %}<div class="d-ev"><span class="t">{{cal_d12_tev1t}}</span>{{cal_d12_tev1n}}</div>{% endif %}
  </div>
  <div class="d-allday">
    {% if cal_d12_hol != "" %}<span class="d-hol">{{cal_d12_hol}}</span>{% endif %}
    {% if cal_d12_aev0 != "" %}<span class="d-aev">{{cal_d12_aev0}}</span>{% endif %}
    {% if cal_d12_aev1 != "" %}<span class="d-aev">{{cal_d12_aev1}}</span>{% endif %}
  </div>
</div>
<div class="day {{ cal_d13_today }} {{ cal_d13_wknd }}{% if cal_d13_hol != "" %} hol{% endif %}">
  <div class="d-hdr">
    {% if cal_d13_month_label != "" %}<div class="d-month">{{cal_d13_month_label}}</div>{% endif %}
    <div class="d-dayline"><span class="d-dow">{{cal_d13_dow}}</span><span class="d-dom">{{cal_d13_dom}}</span></div>
  </div>
  <div class="d-evts">
    {% if cal_d13_tev0n != "" %}<div class="d-ev"><span class="t">{{cal_d13_tev0t}}</span>{{cal_d13_tev0n}}</div>{% endif %}
    {% if cal_d13_tev1n != "" %}<div class="d-ev"><span class="t">{{cal_d13_tev1t}}</span>{{cal_d13_tev1n}}</div>{% endif %}
  </div>
  <div class="d-allday">
    {% if cal_d13_hol != "" %}<span class="d-hol">{{cal_d13_hol}}</span>{% endif %}
    {% if cal_d13_aev0 != "" %}<span class="d-aev">{{cal_d13_aev0}}</span>{% endif %}
    {% if cal_d13_aev1 != "" %}<span class="d-aev">{{cal_d13_aev1}}</span>{% endif %}
  </div>
</div>
  </div>

  <div class="cal-row">
<div class="day {{cal_d14_today}} {{cal_d14_wknd}}{% if cal_d14_hol != "" %} hol{% endif %}">
  <div class="d-hdr">
    {% if cal_d14_month_label != "" %}<div class="d-month">{{cal_d14_month_label}}</div>{% endif %}
    <div class="d-dayline"><span class="d-dow">{{cal_d14_dow}}</span><span class="d-dom">{{cal_d14_dom}}</span></div>
  </div>
  <div class="d-evts">
    {% if cal_d14_tev0n != "" %}<div class="d-ev"><span class="t">{{cal_d14_tev0t}}</span>{{cal_d14_tev0n}}</div>{% endif %}
    {% if cal_d14_tev1n != "" %}<div class="d-ev"><span class="t">{{cal_d14_tev1t}}</span>{{cal_d14_tev1n}}</div>{% endif %}
  </div>
  <div class="d-allday">
    {% if cal_d14_hol != "" %}<span class="d-hol">{{cal_d14_hol}}</span>{% endif %}
    {% if cal_d14_aev0 != "" %}<span class="d-aev">{{cal_d14_aev0}}</span>{% endif %}
    {% if cal_d14_aev1 != "" %}<span class="d-aev">{{cal_d14_aev1}}</span>{% endif %}
  </div>
</div>
<div class="day {{cal_d15_today}} {{cal_d15_wknd}}{% if cal_d15_hol != "" %} hol{% endif %}">
  <div class="d-hdr">
    {% if cal_d15_month_label != "" %}<div class="d-month">{{cal_d15_month_label}}</div>{% endif %}
    <div class="d-dayline"><span class="d-dow">{{cal_d15_dow}}</span><span class="d-dom">{{cal_d15_dom}}</span></div>
  </div>
  <div class="d-evts">
    {% if cal_d15_tev0n != "" %}<div class="d-ev"><span class="t">{{cal_d15_tev0t}}</span>{{cal_d15_tev0n}}</div>{% endif %}
    {% if cal_d15_tev1n != "" %}<div class="d-ev"><span class="t">{{cal_d15_tev1t}}</span>{{cal_d15_tev1n}}</div>{% endif %}
  </div>
  <div class="d-allday">
    {% if cal_d15_hol != "" %}<span class="d-hol">{{cal_d15_hol}}</span>{% endif %}
    {% if cal_d15_aev0 != "" %}<span class="d-aev">{{cal_d15_aev0}}</span>{% endif %}
    {% if cal_d15_aev1 != "" %}<span class="d-aev">{{cal_d15_aev1}}</span>{% endif %}
  </div>
</div>
<div class="day {{cal_d16_today}} {{cal_d16_wknd}}{% if cal_d16_hol != "" %} hol{% endif %}">
  <div class="d-hdr">
    {% if cal_d16_month_label != "" %}<div class="d-month">{{cal_d16_month_label}}</div>{% endif %}
    <div class="d-dayline"><span class="d-dow">{{cal_d16_dow}}</span><span class="d-dom">{{cal_d16_dom}}</span></div>
  </div>
  <div class="d-evts">
    {% if cal_d16_tev0n != "" %}<div class="d-ev"><span class="t">{{cal_d16_tev0t}}</span>{{cal_d16_tev0n}}</div>{% endif %}
    {% if cal_d16_tev1n != "" %}<div class="d-ev"><span class="t">{{cal_d16_tev1t}}</span>{{cal_d16_tev1n}}</div>{% endif %}
  </div>
  <div class="d-allday">
    {% if cal_d16_hol != "" %}<span class="d-hol">{{cal_d16_hol}}</span>{% endif %}
    {% if cal_d16_aev0 != "" %}<span class="d-aev">{{cal_d16_aev0}}</span>{% endif %}
    {% if cal_d16_aev1 != "" %}<span class="d-aev">{{cal_d16_aev1}}</span>{% endif %}
  </div>
</div>
<div class="day {{cal_d17_today}} {{cal_d17_wknd}}{% if cal_d17_hol != "" %} hol{% endif %}">
  <div class="d-hdr">
    {% if cal_d17_month_label != "" %}<div class="d-month">{{cal_d17_month_label}}</div>{% endif %}
    <div class="d-dayline"><span class="d-dow">{{cal_d17_dow}}</span><span class="d-dom">{{cal_d17_dom}}</span></div>
  </div>
  <div class="d-evts">
    {% if cal_d17_tev0n != "" %}<div class="d-ev"><span class="t">{{cal_d17_tev0t}}</span>{{cal_d17_tev0n}}</div>{% endif %}
    {% if cal_d17_tev1n != "" %}<div class="d-ev"><span class="t">{{cal_d17_tev1t}}</span>{{cal_d17_tev1n}}</div>{% endif %}
  </div>
  <div class="d-allday">
    {% if cal_d17_hol != "" %}<span class="d-hol">{{cal_d17_hol}}</span>{% endif %}
    {% if cal_d17_aev0 != "" %}<span class="d-aev">{{cal_d17_aev0}}</span>{% endif %}
    {% if cal_d17_aev1 != "" %}<span class="d-aev">{{cal_d17_aev1}}</span>{% endif %}
  </div>
</div>
<div class="day {{cal_d18_today}} {{cal_d18_wknd}}{% if cal_d18_hol != "" %} hol{% endif %}">
  <div class="d-hdr">
    {% if cal_d18_month_label != "" %}<div class="d-month">{{cal_d18_month_label}}</div>{% endif %}
    <div class="d-dayline"><span class="d-dow">{{cal_d18_dow}}</span><span class="d-dom">{{cal_d18_dom}}</span></div>
  </div>
  <div class="d-evts">
    {% if cal_d18_tev0n != "" %}<div class="d-ev"><span class="t">{{cal_d18_tev0t}}</span>{{cal_d18_tev0n}}</div>{% endif %}
    {% if cal_d18_tev1n != "" %}<div class="d-ev"><span class="t">{{cal_d18_tev1t}}</span>{{cal_d18_tev1n}}</div>{% endif %}
  </div>
  <div class="d-allday">
    {% if cal_d18_hol != "" %}<span class="d-hol">{{cal_d18_hol}}</span>{% endif %}
    {% if cal_d18_aev0 != "" %}<span class="d-aev">{{cal_d18_aev0}}</span>{% endif %}
    {% if cal_d18_aev1 != "" %}<span class="d-aev">{{cal_d18_aev1}}</span>{% endif %}
  </div>
</div>
<div class="day {{cal_d19_today}} {{cal_d19_wknd}}{% if cal_d19_hol != "" %} hol{% endif %}">
  <div class="d-hdr">
    {% if cal_d19_month_label != "" %}<div class="d-month">{{cal_d19_month_label}}</div>{% endif %}
    <div class="d-dayline"><span class="d-dow">{{cal_d19_dow}}</span><span class="d-dom">{{cal_d19_dom}}</span></div>
  </div>
  <div class="d-evts">
    {% if cal_d19_tev0n != "" %}<div class="d-ev"><span class="t">{{cal_d19_tev0t}}</span>{{cal_d19_tev0n}}</div>{% endif %}
    {% if cal_d19_tev1n != "" %}<div class="d-ev"><span class="t">{{cal_d19_tev1t}}</span>{{cal_d19_tev1n}}</div>{% endif %}
  </div>
  <div class="d-allday">
    {% if cal_d19_hol != "" %}<span class="d-hol">{{cal_d19_hol}}</span>{% endif %}
    {% if cal_d19_aev0 != "" %}<span class="d-aev">{{cal_d19_aev0}}</span>{% endif %}
    {% if cal_d19_aev1 != "" %}<span class="d-aev">{{cal_d19_aev1}}</span>{% endif %}
  </div>
</div>
<div class="day {{cal_d20_today}} {{cal_d20_wknd}}{% if cal_d20_hol != "" %} hol{% endif %}">
  <div class="d-hdr">
    {% if cal_d20_month_label != "" %}<div class="d-month">{{cal_d20_month_label}}</div>{% endif %}
    <div class="d-dayline"><span class="d-dow">{{cal_d20_dow}}</span><span class="d-dom">{{cal_d20_dom}}</span></div>
  </div>
  <div class="d-evts">
    {% if cal_d20_tev0n != "" %}<div class="d-ev"><span class="t">{{cal_d20_tev0t}}</span>{{cal_d20_tev0n}}</div>{% endif %}
    {% if cal_d20_tev1n != "" %}<div class="d-ev"><span class="t">{{cal_d20_tev1t}}</span>{{cal_d20_tev1n}}</div>{% endif %}
  </div>
  <div class="d-allday">
    {% if cal_d20_hol != "" %}<span class="d-hol">{{cal_d20_hol}}</span>{% endif %}
    {% if cal_d20_aev0 != "" %}<span class="d-aev">{{cal_d20_aev0}}</span>{% endif %}
    {% if cal_d20_aev1 != "" %}<span class="d-aev">{{cal_d20_aev1}}</span>{% endif %}
  </div>
</div>
  </div>

</div>

<script>
(function(){
/* Layout: read actual viewport, stamp explicit heights  same pattern as dgs-v3/go.* */
var VW=window.innerWidth||800,VH=window.innerHeight||480;
var HDR=28;
var avail=VH-HDR;
var TOP_H=Math.floor(avail*0.24);     // top strip ~24%
var FC_H=Math.max(88,Math.floor(avail*0.20)); // forecast ~20%, min 88px
var CAL_H=avail-TOP_H-FC_H;           // calendar fills rest
var CAL_ROW_H=Math.floor(CAL_H/3);    // each row = third (3 rows)

var topEl=document.querySelector('.top');
var fcEl=document.querySelector('.fc-strip');
var calEl=document.querySelector('.cal');
var calRows=document.querySelectorAll('.cal-row');

if(topEl) topEl.style.height=TOP_H+'px';
if(fcEl)  fcEl.style.height=FC_H+'px';
if(calEl){calEl.style.height=CAL_H+'px';calEl.style.flex='none';}
calRows.forEach(function(r){r.style.height=CAL_ROW_H+'px';r.style.flex='none';});


var NS='http://www.w3.org/2000/svg';
function mk(tag,a){var e=document.createElementNS(NS,tag);for(var k in a)e.setAttribute(k,a[k]);return e;}



/* Forecast icons + temperature range bars */
var IC={
  'Clear':'<circle cx="27" cy="27" r="9" fill="#000"/><line x1="27" y1="4" x2="27" y2="12" stroke="#000" stroke-width="2.5" stroke-linecap="round"/><line x1="27" y1="42" x2="27" y2="50" stroke="#000" stroke-width="2.5" stroke-linecap="round"/><line x1="4" y1="27" x2="12" y2="27" stroke="#000" stroke-width="2.5" stroke-linecap="round"/><line x1="42" y1="27" x2="50" y2="27" stroke="#000" stroke-width="2.5" stroke-linecap="round"/><line x1="11" y1="11" x2="17" y2="17" stroke="#000" stroke-width="2.5" stroke-linecap="round"/><line x1="37" y1="37" x2="43" y2="43" stroke="#000" stroke-width="2.5" stroke-linecap="round"/><line x1="43" y1="11" x2="37" y2="17" stroke="#000" stroke-width="2.5" stroke-linecap="round"/><line x1="11" y1="43" x2="17" y2="37" stroke="#000" stroke-width="2.5" stroke-linecap="round"/>',
  'Mostly Clear':'<circle cx="20" cy="19" r="8" fill="#000"/><line x1="20" y1="5" x2="20" y2="11" stroke="#000" stroke-width="2" stroke-linecap="round"/><line x1="5" y1="19" x2="11" y2="19" stroke="#000" stroke-width="2" stroke-linecap="round"/><line x1="9" y1="9" x2="14" y2="13" stroke="#000" stroke-width="2" stroke-linecap="round"/><line x1="31" y1="9" x2="26" y2="13" stroke="#000" stroke-width="2" stroke-linecap="round"/><path d="M18 34 Q13 34 13 29 Q13 24 18 23 Q18 17 24 16 Q30 15 33 20 Q38 20 40 24 Q45 24 45 29 Q45 34 40 34 Z" fill="#000"/>',
  'Partly Cloudy':'<circle cx="18" cy="18" r="8" fill="#000"/><line x1="18" y1="4" x2="18" y2="10" stroke="#000" stroke-width="2" stroke-linecap="round"/><line x1="4" y1="18" x2="10" y2="18" stroke="#000" stroke-width="2" stroke-linecap="round"/><line x1="8" y1="8" x2="13" y2="13" stroke="#000" stroke-width="2" stroke-linecap="round"/><line x1="28" y1="8" x2="23" y2="13" stroke="#000" stroke-width="2" stroke-linecap="round"/><path d="M15 40 Q10 40 10 35 Q10 30 16 29 Q16 23 22 22 Q28 21 31 26 Q36 26 38 30 Q44 30 44 35 Q44 40 38 40 Z" fill="#000"/>',
  'Overcast':'<path d="M8 36 Q3 36 3 30 Q3 24 9 23 Q9 14 18 12 Q28 10 32 19 Q38 19 41 24 Q48 24 48 30 Q48 36 41 36 Z" fill="#000"/><path d="M14 46 Q10 46 10 42 Q10 38 15 37 Q15 32 21 31 Q27 30 30 35 Q35 35 37 39 Q42 39 42 43 Q42 47 37 47 Z" fill="#555"/>',
  'Drizzle':'<path d="M10 26 Q6 26 6 20 Q6 14 12 13 Q12 5 21 3 Q30 1 34 10 Q40 10 42 15 Q48 15 48 21 Q48 27 42 27 Z" fill="#000"/><line x1="18" y1="33" x2="16" y2="43" stroke="#666" stroke-width="2.5" stroke-linecap="round"/><line x1="27" y1="33" x2="25" y2="43" stroke="#666" stroke-width="2.5" stroke-linecap="round"/><line x1="36" y1="33" x2="34" y2="43" stroke="#666" stroke-width="2.5" stroke-linecap="round"/>',
  'Rain':'<path d="M10 25 Q6 25 6 19 Q6 13 12 12 Q12 4 21 2 Q30 0 34 9 Q40 9 42 14 Q48 14 48 20 Q48 26 42 26 Z" fill="#000"/><line x1="16" y1="32" x2="12" y2="46" stroke="#444" stroke-width="3" stroke-linecap="round"/><line x1="27" y1="32" x2="23" y2="46" stroke="#444" stroke-width="3" stroke-linecap="round"/><line x1="38" y1="32" x2="34" y2="46" stroke="#444" stroke-width="3" stroke-linecap="round"/>',
  'Showers':'<path d="M10 25 Q6 25 6 19 Q6 13 12 12 Q12 4 21 2 Q30 0 34 9 Q40 9 42 14 Q48 14 48 20 Q48 26 42 26 Z" fill="#000"/><line x1="16" y1="32" x2="13" y2="42" stroke="#444" stroke-width="2.5" stroke-linecap="round"/><line x1="27" y1="32" x2="24" y2="42" stroke="#444" stroke-width="2.5" stroke-linecap="round"/><line x1="38" y1="32" x2="35" y2="42" stroke="#444" stroke-width="2.5" stroke-linecap="round"/>',
  'Snow':'<path d="M10 23 Q6 23 6 17 Q6 11 12 10 Q12 2 21 0 Q30 -2 34 7 Q40 7 42 12 Q48 12 48 18 Q48 24 42 24 Z" fill="#000"/><line x1="27" y1="28" x2="27" y2="52" stroke="#444" stroke-width="2.5" stroke-linecap="round"/><line x1="15" y1="34" x2="39" y2="46" stroke="#444" stroke-width="2.5" stroke-linecap="round"/><line x1="39" y1="34" x2="15" y2="46" stroke="#444" stroke-width="2.5" stroke-linecap="round"/>',
  'Thunderstorm':'<path d="M10 23 Q6 23 6 17 Q6 11 12 10 Q12 2 21 0 Q30 -2 34 7 Q40 7 42 12 Q48 12 48 18 Q48 24 42 24 Z" fill="#000"/><polygon points="30,27 22,42 28,42 22,54 35,36 29,36" fill="#333"/>'
};

var fcols=document.querySelectorAll('.fc-col');
var his=[],los=[];
fcols.forEach(function(col){his.push(parseFloat(col.dataset.hi)||0);los.push(parseFloat(col.dataset.lo)||0);});
var mnT=Math.min.apply(null,los)-2,mxT=Math.max.apply(null,his)+2,rngT=mxT-mnT||1;

fcols.forEach(function(col,i){
  /* Icon */
  var svg=document.getElementById('fi'+i);
  if(svg){var cond=(col.dataset.cond||'').trim();svg.innerHTML=IC[cond]||IC['Overcast'];}
  /* Temperature bar */
  var bar=document.getElementById('fb'+i);
  if(bar){
    var lx=((los[i]-mnT)/rngT*92+4).toFixed(1);
    var hx=((his[i]-mnT)/rngT*92+4).toFixed(1);
    var w=(+hx-+lx).toFixed(1);
    bar.innerHTML='<rect x="2" y="1" width="96" height="4" fill="#e0e0e0" rx="2"/>'
      +'<rect x="'+lx+'" y="0" width="'+w+'" height="6" fill="#333" rx="2"/>';
  }
});

/* Moon phase SVG (standalone below dial) */
var msvg=document.getElementById('msvg');
if(msvg){
  var phase=parseFloat('{{ moon_phase }}')||0;
  var mr=22,mc=25;
  msvg.appendChild(mk('circle',{cx:mc,cy:mc,r:mr,fill:'#1a1a1a',stroke:'#777','stroke-width':'1'}));
  if(phase>0.98||(phase>=0&&phase<0.02)){msvg.appendChild(mk('circle',{cx:mc,cy:mc,r:mr,fill:'#e8e8e8'}));}
  else if(phase>=0.02){var wax=phase<=0.5,ang=phase*2*Math.PI,ex=mr*Math.cos(ang),aE=Math.abs(ex).toFixed(1),ms3,md3;
    if(wax){ms3=(ex>=0)?0:1;md3='M '+mc+','+(mc-mr)+' A '+mr+','+mr+' 0 0 1 '+mc+','+(mc+mr)+' A '+aE+','+mr+' 0 0 '+ms3+' '+mc+','+(mc-mr)+'Z';}
    else{ms3=(ex>=0)?1:0;md3='M '+mc+','+(mc-mr)+' A '+mr+','+mr+' 0 0 0 '+mc+','+(mc+mr)+' A '+aE+','+mr+' 0 0 '+ms3+' '+mc+','+(mc-mr)+'Z';}
    msvg.appendChild(mk('path',{d:md3,fill:'#e8e8e8'}));}
}
/* Solar dial for indoor/solar col */
var sdial=document.getElementById('sdial');
if(sdial){
  var NS2='http://www.w3.org/2000/svg';
  function mk2(tag,a){var e=document.createElementNS(NS2,tag);for(var k in a)e.setAttribute(k,a[k]);return e;}
  var sr3='{{ sunrise }}',ss3='{{ sunset }}';
  if(sr3&&sr3.indexOf(':')>=0&&ss3&&ss3.indexOf(':')>=0){
    var dW2=110,dH2=62,R2=28,CX2=55,CY2=31;
    function toMin2(t){var p=t.split(':');return parseInt(p[0])*60+parseInt(p[1]);}
    function mta2(m){return 270-m/1440*360;}
    function txy2(r,deg){var rad=deg*Math.PI/180;return[CX2+r*Math.cos(rad),CY2-r*Math.sin(rad)];}
    var rA2=mta2(toMin2(sr3)),sA2=mta2(toMin2(ss3));
    var ri2=txy2(R2,rA2),si2=txy2(R2,sA2),sp2=((rA2-sA2)+360)%360;
    sdial.appendChild(mk2('circle',{cx:CX2,cy:CY2,r:R2,fill:'#E8E8E8'}));
    sdial.appendChild(mk2('path',{d:'M '+CX2+','+CY2+' L '+ri2[0].toFixed(1)+','+ri2[1].toFixed(1)+' A '+R2+' '+R2+' 0 '+(sp2>180?1:0)+' 1 '+si2[0].toFixed(1)+' '+si2[1].toFixed(1)+' Z',fill:'#FFD700'}));
    sdial.appendChild(mk2('circle',{cx:CX2,cy:CY2,r:R2,fill:'none',stroke:'#999','stroke-width':'1.5'}));
  }
}
})();
</script>
```
