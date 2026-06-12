<style>
*{box-sizing:border-box;margin:0;padding:0}
html,body{width:100%;height:100%;overflow:hidden;background:#fff;color:#000;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif}
body{display:flex;flex-direction:column}

/* Header */
.hdr{height:28px;flex-shrink:0;display:flex;justify-content:space-between;align-items:center;padding:0 12px;border-bottom:2px solid #000}
.hdr-t{font-weight:800;font-size:14px;letter-spacing:.04em}
.hdr-r{font-size:11px;color:#555}

/* Top strip: 3 equal columns */
.top{flex:0 0 33%;display:grid;grid-template-columns:1fr 1fr 1fr;border-bottom:2px solid #000;overflow:hidden}
.top-col{overflow:hidden;display:flex;flex-direction:column}
.top-col+.top-col{border-left:1px solid #ccc}
.col-lbl{font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:#888;flex-shrink:0;padding:5px 8px 2px}

/* Left col: 2 sub-cols (weather | moon+sun) */
.col-left-inner{flex:1;display:grid;grid-template-columns:55% 45%;min-height:0;overflow:hidden}
.wx-sub-col{overflow:hidden;padding:0 7px 5px;display:flex;flex-direction:column;align-items:center;justify-content:center}
.moon-sub-col{overflow:hidden;padding:0 7px 5px;display:flex;flex-direction:column;align-items:center;justify-content:center;background:#e8e8e8;border-radius:4px;margin:3px 5px 3px 0}
.w-temp{font-size:62px;font-weight:300;line-height:1;flex-shrink:0;text-align:center}
.w-temp sup{font-size:19px;vertical-align:.6em;font-weight:400}
.w-cond{font-size:16px;font-weight:700;flex-shrink:0;margin-top:1px;text-align:center}
.w-sub{font-size:12px;color:#555;flex-shrink:0;line-height:1.45;text-align:center}
.moon-icon-wrap{flex-shrink:0}
.moon-nm{font-size:11px;color:#444;font-style:italic;text-align:center;margin-top:3px;flex-shrink:0}
.sun-times-v{font-size:12px;font-weight:700;text-align:center;color:#333;margin-top:4px;flex-shrink:0;line-height:1.6}

/* Todo column - immediate (urgent) items only */
.td-inner{flex:1;min-height:0;overflow:hidden;padding:0 8px 5px;display:flex;flex-direction:column}
.td-list{flex:1;overflow:hidden;display:flex;flex-direction:column;gap:2px}
.td-item{font-size:28px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;line-height:1.3;padding:2px 4px}
.td-item:nth-child(odd){background:#f6f6f6}
.td-item:nth-child(even){background:#fff}

/* Right col: Indoor temp + Solar dial */
.col-indoor-inner{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;overflow:hidden;padding:4px}
.indoor-big{font-size:82px;font-weight:200;line-height:1;text-align:center;color:#000}
.indoor-big sup{font-size:31px;vertical-align:.5em;font-weight:300}
.indoor-hum{font-size:17px;color:#555;text-align:center;margin-top:4px}

/* Calendar  bottom 2/3: current week + next week */
.cal{flex:1;min-height:0;display:flex;flex-direction:column;overflow:hidden}
.cal-row{display:grid;grid-template-columns:repeat(7,1fr);border-bottom:2px solid #555;overflow:hidden;min-height:0}
.cal-row:last-child{border-bottom:none}
.cal-row:nth-child(1){flex:0 0 72%}
.cal-row:nth-child(2){flex:0 0 28%}

/* Day cells */
.day{overflow:hidden;display:flex;flex-direction:column;border-right:1px solid #ddd;padding:3px 4px;background:#fff;min-height:0;min-width:0}
.day:last-child{border-right:none}
.day.wknd{background:#e8e8e8}
.day.alt{background:#f2f2f2}
.day.hol{background:#c8c8c8}
.day.today{background:#484848;color:#fff}
.d-hdr{flex-shrink:0;background:#444;margin:-3px -4px 0;padding:2px 4px;display:flex;flex-direction:column}
.cal-row:nth-child(2) .d-hdr{background:#686868}
.day.today .d-hdr{background:transparent}
.d-dow{font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.08em;line-height:1.3;color:#fff}
.d-dom{font-size:18px;font-weight:900;line-height:1.15;color:#fff}
.day.today .d-dow,.day.today .d-dom{color:#fff}

/* Forecast weather block (current week only) */
.d-wx{display:flex;align-items:center;justify-content:center;gap:5px;flex-shrink:0;margin:3px 0;padding-bottom:3px;border-bottom:1px solid #ddd}
.day.today .d-wx{border-bottom-color:#666}
.d-wx-icon{width:34px;height:34px;flex-shrink:0;overflow:visible}
.d-wx-temps{display:flex;flex-direction:column;line-height:1}
.d-wx-hi{font-size:18px;font-weight:800;color:#000}
.d-wx-lo{font-size:13px;font-weight:400;color:#888}
.d-wx-pop{font-size:12px;color:#666}
.day.today .d-wx-hi{color:#fff}
.day.today .d-wx-lo{color:#bbb}
.day.today .d-wx-pop{color:#ccc}

/* Current week (row 1) larger */
.cal-row:nth-child(1) .d-dow{font-size:13px}
.cal-row:nth-child(1) .d-dom{font-size:24px}
.cal-row:nth-child(1) .d-wx-icon{width:44px;height:44px}
.cal-row:nth-child(1) .d-wx-hi{font-size:22px}
.cal-row:nth-child(1) .d-wx-lo{font-size:16px}
.cal-row:nth-child(1) .d-wx-pop{font-size:14px}
.cal-row:nth-child(1) .d-ev{font-size:22px;line-height:1.35}
.cal-row:nth-child(1) .d-ev .t{font-size:14px}
.cal-row:nth-child(1) .d-ev.ehs{font-size:16px;padding:2px 6px;line-height:1.5}
.cal-row:nth-child(1) .d-allday{margin-top:5px;gap:3px}
.cal-row:nth-child(1) .d-aev,.cal-row:nth-child(1) .d-hol{font-size:12px;padding:2px 5px}

/* Next week (row 2) */
.cal-row:nth-child(2) .d-dow{font-size:11px}
.cal-row:nth-child(2) .d-dom{font-size:15px}
.cal-row:nth-child(2) .d-ev{font-size:14px;line-height:1.35}
.cal-row:nth-child(2) .d-ev .t{font-size:11px}
.cal-row:nth-child(2) .d-ev.ehs{font-size:11px;padding:1px 5px;line-height:1.6}
.cal-row:nth-child(2) .d-aev,.cal-row:nth-child(2) .d-hol{font-size:11px;padding:1px 5px}

.d-evts{flex:1;min-height:0;overflow:hidden;display:flex;flex-direction:column;gap:2px}
.d-ev{font-size:13px;white-space:normal;overflow:hidden;line-height:1.35;color:#222;word-break:break-word}
.day.today .d-ev{color:#fff}
.d-ev .t{font-size:11px;color:#888;margin-right:2px}
.day.today .d-ev .t{color:#ccc}
.d-ev.ehs{font-size:8px;font-weight:800;background:#111;color:#fff;padding:1px 4px;border-radius:1px;line-height:1.6;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.d-ev.ehs .t{display:none}
.d-allday{flex-shrink:0;display:flex;flex-wrap:wrap;overflow:hidden;gap:2px;margin-top:4px;padding-top:1px}
.d-hol{font-size:8px;font-weight:800;background:#555;color:#fff;padding:1px 3px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;border-radius:1px}
.d-aev{font-size:8px;font-weight:800;background:#444;color:#fff;padding:1px 3px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;border-radius:1px}
.day.today .d-ev.ehs{background:#fff;color:#111}
.day.today .d-hol,.day.today .d-aev{background:#bbb;color:#222}
</style>



<div class="hdr">
  <div class="hdr-t">KASLO &middot; 2-Week Calendar</div>
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
        <svg id="msvg" width="60" height="60" class="moon-icon-wrap" style="display:block"></svg>
        <div class="moon-nm">{{ moon_name }}</div>
        <div class="sun-times-v">&uarr; {{ sunrise }}<br>&darr; {{ sunset }}</div>
      </div>
    </div>
  </div>

  <!-- Middle: Todo (immediate items only) -->
  <div class="top-col">
    <div class="col-lbl">To Do &nbsp;&middot;&nbsp; Immediate</div>
    <div class="td-inner">
      <div class="td-list">
        {% if todo_u0_text %}<div class="td-item">{{ todo_u0_text }}</div>{% endif %}
        {% if todo_u1_text %}<div class="td-item">{{ todo_u1_text }}</div>{% endif %}
        {% if todo_u2_text %}<div class="td-item">{{ todo_u2_text }}</div>{% endif %}
        {% if todo_u3_text %}<div class="td-item">{{ todo_u3_text }}</div>{% endif %}
        {% if todo_u4_text %}<div class="td-item">{{ todo_u4_text }}</div>{% endif %}
        {% if todo_u5_text %}<div class="td-item">{{ todo_u5_text }}</div>{% endif %}
      </div>
    </div>
  </div>

  <!-- Right: Indoor temp (left) + Solar dial (right) -->
  <div class="top-col" style="overflow:hidden;display:flex;flex-direction:column">
    <div class="col-lbl">Indoor &middot; Solar</div>
    <div style="flex:1;min-height:0;display:grid;grid-template-columns:1fr 1fr;overflow:hidden">
      <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;padding:4px">
        <div class="indoor-big">{{ indoor_temp }}<sup>&deg;</sup></div>
        <div class="indoor-hum">{{ indoor_hum }}% hum</div>
      </div>
      <div style="border-left:1px solid #e0e0e0;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:4px;background:#e8e8e8;border-radius:4px;margin:3px 5px 3px 0">
        <svg id="sdial" width="132" height="74" style="display:block"></svg>
        <div style="font-size:11px;font-weight:700;color:#333;text-align:center;margin-top:3px">&uarr;{{ sunrise }} &darr;{{ sunset }}</div>
      </div>
    </div>
  </div>

</div>

<!-- Calendar: row 1 = current week (larger), row 2 = next week -->
<div class="cal">

  <div class="cal-row">
<div class="day {{ cal_d0_today }} {{ cal_d0_wknd }}{% if cal_d0_dow == "Tue" or cal_d0_dow == "Thu" %} alt{% endif %}{% if cal_d0_hol != "" %} hol{% endif %}">
  <div class="d-hdr">
    <div class="d-dow">{{cal_d0_dow}}</div>
    <div class="d-dom">{% if cal_d0_month_label != "" %}{{cal_d0_month_label}} {% endif %}{{cal_d0_dom}}</div>
  </div>
  <div class="d-wx" data-cond="{{f0_condition}}">
    <svg class="d-wx-icon" id="dwi0" viewBox="0 0 54 54"></svg>
    <div class="d-wx-temps"><span class="d-wx-hi">{{f0_hi}}&deg;</span><span class="d-wx-lo">{{f0_lo}}&deg;</span></div>
    {% if f0_pop_str != "" %}<div class="d-wx-pop">{{f0_pop_str}}</div>{% endif %}
  </div>
  <div class="d-evts">
    {% if cal_d0_tev0n != "" %}{% if cal_d0_tev0c == "personal" %}<div class="d-ev ehs"><span class="t">{{cal_d0_tev0t}}</span>{{cal_d0_tev0n}}</div>{% else %}<div class="d-ev"><span class="t">{{cal_d0_tev0t}}</span>{{cal_d0_tev0n}}</div>{% endif %}{% endif %}
    {% if cal_d0_tev1n != "" %}{% if cal_d0_tev1c == "personal" %}<div class="d-ev ehs"><span class="t">{{cal_d0_tev1t}}</span>{{cal_d0_tev1n}}</div>{% else %}<div class="d-ev"><span class="t">{{cal_d0_tev1t}}</span>{{cal_d0_tev1n}}</div>{% endif %}{% endif %}
    {% if cal_d0_tev2n != "" %}{% if cal_d0_tev2c == "personal" %}<div class="d-ev ehs"><span class="t">{{cal_d0_tev2t}}</span>{{cal_d0_tev2n}}</div>{% else %}<div class="d-ev"><span class="t">{{cal_d0_tev2t}}</span>{{cal_d0_tev2n}}</div>{% endif %}{% endif %}
  </div>
  <div class="d-allday">
    {% if cal_d0_hol != "" %}<span class="d-hol">{{cal_d0_hol}}</span>{% endif %}
    {% if cal_d0_aev0 != "" %}<span class="d-aev">{{cal_d0_aev0}}</span>{% endif %}
    {% if cal_d0_aev1 != "" %}<span class="d-aev">{{cal_d0_aev1}}</span>{% endif %}
    {% if cal_d0_aev2 != "" %}<span class="d-aev">{{cal_d0_aev2}}</span>{% endif %}
  </div>
</div>
<div class="day {{ cal_d1_today }} {{ cal_d1_wknd }}{% if cal_d1_dow == "Tue" or cal_d1_dow == "Thu" %} alt{% endif %}{% if cal_d1_hol != "" %} hol{% endif %}">
  <div class="d-hdr">
    <div class="d-dow">{{cal_d1_dow}}</div>
    <div class="d-dom">{% if cal_d1_month_label != "" %}{{cal_d1_month_label}} {% endif %}{{cal_d1_dom}}</div>
  </div>
  <div class="d-wx" data-cond="{{f1_condition}}">
    <svg class="d-wx-icon" id="dwi1" viewBox="0 0 54 54"></svg>
    <div class="d-wx-temps"><span class="d-wx-hi">{{f1_hi}}&deg;</span><span class="d-wx-lo">{{f1_lo}}&deg;</span></div>
    {% if f1_pop_str != "" %}<div class="d-wx-pop">{{f1_pop_str}}</div>{% endif %}
  </div>
  <div class="d-evts">
    {% if cal_d1_tev0n != "" %}{% if cal_d1_tev0c == "personal" %}<div class="d-ev ehs"><span class="t">{{cal_d1_tev0t}}</span>{{cal_d1_tev0n}}</div>{% else %}<div class="d-ev"><span class="t">{{cal_d1_tev0t}}</span>{{cal_d1_tev0n}}</div>{% endif %}{% endif %}
    {% if cal_d1_tev1n != "" %}{% if cal_d1_tev1c == "personal" %}<div class="d-ev ehs"><span class="t">{{cal_d1_tev1t}}</span>{{cal_d1_tev1n}}</div>{% else %}<div class="d-ev"><span class="t">{{cal_d1_tev1t}}</span>{{cal_d1_tev1n}}</div>{% endif %}{% endif %}
    {% if cal_d1_tev2n != "" %}{% if cal_d1_tev2c == "personal" %}<div class="d-ev ehs"><span class="t">{{cal_d1_tev2t}}</span>{{cal_d1_tev2n}}</div>{% else %}<div class="d-ev"><span class="t">{{cal_d1_tev2t}}</span>{{cal_d1_tev2n}}</div>{% endif %}{% endif %}
  </div>
  <div class="d-allday">
    {% if cal_d1_hol != "" %}<span class="d-hol">{{cal_d1_hol}}</span>{% endif %}
    {% if cal_d1_aev0 != "" %}<span class="d-aev">{{cal_d1_aev0}}</span>{% endif %}
    {% if cal_d1_aev1 != "" %}<span class="d-aev">{{cal_d1_aev1}}</span>{% endif %}
    {% if cal_d1_aev2 != "" %}<span class="d-aev">{{cal_d1_aev2}}</span>{% endif %}
  </div>
</div>
<div class="day {{ cal_d2_today }} {{ cal_d2_wknd }}{% if cal_d2_dow == "Tue" or cal_d2_dow == "Thu" %} alt{% endif %}{% if cal_d2_hol != "" %} hol{% endif %}">
  <div class="d-hdr">
    <div class="d-dow">{{cal_d2_dow}}</div>
    <div class="d-dom">{% if cal_d2_month_label != "" %}{{cal_d2_month_label}} {% endif %}{{cal_d2_dom}}</div>
  </div>
  <div class="d-wx" data-cond="{{f2_condition}}">
    <svg class="d-wx-icon" id="dwi2" viewBox="0 0 54 54"></svg>
    <div class="d-wx-temps"><span class="d-wx-hi">{{f2_hi}}&deg;</span><span class="d-wx-lo">{{f2_lo}}&deg;</span></div>
    {% if f2_pop_str != "" %}<div class="d-wx-pop">{{f2_pop_str}}</div>{% endif %}
  </div>
  <div class="d-evts">
    {% if cal_d2_tev0n != "" %}{% if cal_d2_tev0c == "personal" %}<div class="d-ev ehs"><span class="t">{{cal_d2_tev0t}}</span>{{cal_d2_tev0n}}</div>{% else %}<div class="d-ev"><span class="t">{{cal_d2_tev0t}}</span>{{cal_d2_tev0n}}</div>{% endif %}{% endif %}
    {% if cal_d2_tev1n != "" %}{% if cal_d2_tev1c == "personal" %}<div class="d-ev ehs"><span class="t">{{cal_d2_tev1t}}</span>{{cal_d2_tev1n}}</div>{% else %}<div class="d-ev"><span class="t">{{cal_d2_tev1t}}</span>{{cal_d2_tev1n}}</div>{% endif %}{% endif %}
    {% if cal_d2_tev2n != "" %}{% if cal_d2_tev2c == "personal" %}<div class="d-ev ehs"><span class="t">{{cal_d2_tev2t}}</span>{{cal_d2_tev2n}}</div>{% else %}<div class="d-ev"><span class="t">{{cal_d2_tev2t}}</span>{{cal_d2_tev2n}}</div>{% endif %}{% endif %}
  </div>
  <div class="d-allday">
    {% if cal_d2_hol != "" %}<span class="d-hol">{{cal_d2_hol}}</span>{% endif %}
    {% if cal_d2_aev0 != "" %}<span class="d-aev">{{cal_d2_aev0}}</span>{% endif %}
    {% if cal_d2_aev1 != "" %}<span class="d-aev">{{cal_d2_aev1}}</span>{% endif %}
    {% if cal_d2_aev2 != "" %}<span class="d-aev">{{cal_d2_aev2}}</span>{% endif %}
  </div>
</div>
<div class="day {{ cal_d3_today }} {{ cal_d3_wknd }}{% if cal_d3_dow == "Tue" or cal_d3_dow == "Thu" %} alt{% endif %}{% if cal_d3_hol != "" %} hol{% endif %}">
  <div class="d-hdr">
    <div class="d-dow">{{cal_d3_dow}}</div>
    <div class="d-dom">{% if cal_d3_month_label != "" %}{{cal_d3_month_label}} {% endif %}{{cal_d3_dom}}</div>
  </div>
  <div class="d-wx" data-cond="{{f3_condition}}">
    <svg class="d-wx-icon" id="dwi3" viewBox="0 0 54 54"></svg>
    <div class="d-wx-temps"><span class="d-wx-hi">{{f3_hi}}&deg;</span><span class="d-wx-lo">{{f3_lo}}&deg;</span></div>
    {% if f3_pop_str != "" %}<div class="d-wx-pop">{{f3_pop_str}}</div>{% endif %}
  </div>
  <div class="d-evts">
    {% if cal_d3_tev0n != "" %}{% if cal_d3_tev0c == "personal" %}<div class="d-ev ehs"><span class="t">{{cal_d3_tev0t}}</span>{{cal_d3_tev0n}}</div>{% else %}<div class="d-ev"><span class="t">{{cal_d3_tev0t}}</span>{{cal_d3_tev0n}}</div>{% endif %}{% endif %}
    {% if cal_d3_tev1n != "" %}{% if cal_d3_tev1c == "personal" %}<div class="d-ev ehs"><span class="t">{{cal_d3_tev1t}}</span>{{cal_d3_tev1n}}</div>{% else %}<div class="d-ev"><span class="t">{{cal_d3_tev1t}}</span>{{cal_d3_tev1n}}</div>{% endif %}{% endif %}
    {% if cal_d3_tev2n != "" %}{% if cal_d3_tev2c == "personal" %}<div class="d-ev ehs"><span class="t">{{cal_d3_tev2t}}</span>{{cal_d3_tev2n}}</div>{% else %}<div class="d-ev"><span class="t">{{cal_d3_tev2t}}</span>{{cal_d3_tev2n}}</div>{% endif %}{% endif %}
  </div>
  <div class="d-allday">
    {% if cal_d3_hol != "" %}<span class="d-hol">{{cal_d3_hol}}</span>{% endif %}
    {% if cal_d3_aev0 != "" %}<span class="d-aev">{{cal_d3_aev0}}</span>{% endif %}
    {% if cal_d3_aev1 != "" %}<span class="d-aev">{{cal_d3_aev1}}</span>{% endif %}
    {% if cal_d3_aev2 != "" %}<span class="d-aev">{{cal_d3_aev2}}</span>{% endif %}
  </div>
</div>
<div class="day {{ cal_d4_today }} {{ cal_d4_wknd }}{% if cal_d4_dow == "Tue" or cal_d4_dow == "Thu" %} alt{% endif %}{% if cal_d4_hol != "" %} hol{% endif %}">
  <div class="d-hdr">
    <div class="d-dow">{{cal_d4_dow}}</div>
    <div class="d-dom">{% if cal_d4_month_label != "" %}{{cal_d4_month_label}} {% endif %}{{cal_d4_dom}}</div>
  </div>
  <div class="d-wx" data-cond="{{f4_condition}}">
    <svg class="d-wx-icon" id="dwi4" viewBox="0 0 54 54"></svg>
    <div class="d-wx-temps"><span class="d-wx-hi">{{f4_hi}}&deg;</span><span class="d-wx-lo">{{f4_lo}}&deg;</span></div>
    {% if f4_pop_str != "" %}<div class="d-wx-pop">{{f4_pop_str}}</div>{% endif %}
  </div>
  <div class="d-evts">
    {% if cal_d4_tev0n != "" %}{% if cal_d4_tev0c == "personal" %}<div class="d-ev ehs"><span class="t">{{cal_d4_tev0t}}</span>{{cal_d4_tev0n}}</div>{% else %}<div class="d-ev"><span class="t">{{cal_d4_tev0t}}</span>{{cal_d4_tev0n}}</div>{% endif %}{% endif %}
    {% if cal_d4_tev1n != "" %}{% if cal_d4_tev1c == "personal" %}<div class="d-ev ehs"><span class="t">{{cal_d4_tev1t}}</span>{{cal_d4_tev1n}}</div>{% else %}<div class="d-ev"><span class="t">{{cal_d4_tev1t}}</span>{{cal_d4_tev1n}}</div>{% endif %}{% endif %}
    {% if cal_d4_tev2n != "" %}{% if cal_d4_tev2c == "personal" %}<div class="d-ev ehs"><span class="t">{{cal_d4_tev2t}}</span>{{cal_d4_tev2n}}</div>{% else %}<div class="d-ev"><span class="t">{{cal_d4_tev2t}}</span>{{cal_d4_tev2n}}</div>{% endif %}{% endif %}
  </div>
  <div class="d-allday">
    {% if cal_d4_hol != "" %}<span class="d-hol">{{cal_d4_hol}}</span>{% endif %}
    {% if cal_d4_aev0 != "" %}<span class="d-aev">{{cal_d4_aev0}}</span>{% endif %}
    {% if cal_d4_aev1 != "" %}<span class="d-aev">{{cal_d4_aev1}}</span>{% endif %}
    {% if cal_d4_aev2 != "" %}<span class="d-aev">{{cal_d4_aev2}}</span>{% endif %}
  </div>
</div>
<div class="day {{ cal_d5_today }} {{ cal_d5_wknd }}{% if cal_d5_dow == "Tue" or cal_d5_dow == "Thu" %} alt{% endif %}{% if cal_d5_hol != "" %} hol{% endif %}">
  <div class="d-hdr">
    <div class="d-dow">{{cal_d5_dow}}</div>
    <div class="d-dom">{% if cal_d5_month_label != "" %}{{cal_d5_month_label}} {% endif %}{{cal_d5_dom}}</div>
  </div>
  <div class="d-wx" data-cond="{{f5_condition}}">
    <svg class="d-wx-icon" id="dwi5" viewBox="0 0 54 54"></svg>
    <div class="d-wx-temps"><span class="d-wx-hi">{{f5_hi}}&deg;</span><span class="d-wx-lo">{{f5_lo}}&deg;</span></div>
    {% if f5_pop_str != "" %}<div class="d-wx-pop">{{f5_pop_str}}</div>{% endif %}
  </div>
  <div class="d-evts">
    {% if cal_d5_tev0n != "" %}{% if cal_d5_tev0c == "personal" %}<div class="d-ev ehs"><span class="t">{{cal_d5_tev0t}}</span>{{cal_d5_tev0n}}</div>{% else %}<div class="d-ev"><span class="t">{{cal_d5_tev0t}}</span>{{cal_d5_tev0n}}</div>{% endif %}{% endif %}
    {% if cal_d5_tev1n != "" %}{% if cal_d5_tev1c == "personal" %}<div class="d-ev ehs"><span class="t">{{cal_d5_tev1t}}</span>{{cal_d5_tev1n}}</div>{% else %}<div class="d-ev"><span class="t">{{cal_d5_tev1t}}</span>{{cal_d5_tev1n}}</div>{% endif %}{% endif %}
    {% if cal_d5_tev2n != "" %}{% if cal_d5_tev2c == "personal" %}<div class="d-ev ehs"><span class="t">{{cal_d5_tev2t}}</span>{{cal_d5_tev2n}}</div>{% else %}<div class="d-ev"><span class="t">{{cal_d5_tev2t}}</span>{{cal_d5_tev2n}}</div>{% endif %}{% endif %}
  </div>
  <div class="d-allday">
    {% if cal_d5_hol != "" %}<span class="d-hol">{{cal_d5_hol}}</span>{% endif %}
    {% if cal_d5_aev0 != "" %}<span class="d-aev">{{cal_d5_aev0}}</span>{% endif %}
    {% if cal_d5_aev1 != "" %}<span class="d-aev">{{cal_d5_aev1}}</span>{% endif %}
    {% if cal_d5_aev2 != "" %}<span class="d-aev">{{cal_d5_aev2}}</span>{% endif %}
  </div>
</div>
<div class="day {{ cal_d6_today }} {{ cal_d6_wknd }}{% if cal_d6_dow == "Tue" or cal_d6_dow == "Thu" %} alt{% endif %}{% if cal_d6_hol != "" %} hol{% endif %}">
  <div class="d-hdr">
    <div class="d-dow">{{cal_d6_dow}}</div>
    <div class="d-dom">{% if cal_d6_month_label != "" %}{{cal_d6_month_label}} {% endif %}{{cal_d6_dom}}</div>
  </div>
  <div class="d-wx" data-cond="{{f6_condition}}">
    <svg class="d-wx-icon" id="dwi6" viewBox="0 0 54 54"></svg>
    <div class="d-wx-temps"><span class="d-wx-hi">{{f6_hi}}&deg;</span><span class="d-wx-lo">{{f6_lo}}&deg;</span></div>
    {% if f6_pop_str != "" %}<div class="d-wx-pop">{{f6_pop_str}}</div>{% endif %}
  </div>
  <div class="d-evts">
    {% if cal_d6_tev0n != "" %}{% if cal_d6_tev0c == "personal" %}<div class="d-ev ehs"><span class="t">{{cal_d6_tev0t}}</span>{{cal_d6_tev0n}}</div>{% else %}<div class="d-ev"><span class="t">{{cal_d6_tev0t}}</span>{{cal_d6_tev0n}}</div>{% endif %}{% endif %}
    {% if cal_d6_tev1n != "" %}{% if cal_d6_tev1c == "personal" %}<div class="d-ev ehs"><span class="t">{{cal_d6_tev1t}}</span>{{cal_d6_tev1n}}</div>{% else %}<div class="d-ev"><span class="t">{{cal_d6_tev1t}}</span>{{cal_d6_tev1n}}</div>{% endif %}{% endif %}
    {% if cal_d6_tev2n != "" %}{% if cal_d6_tev2c == "personal" %}<div class="d-ev ehs"><span class="t">{{cal_d6_tev2t}}</span>{{cal_d6_tev2n}}</div>{% else %}<div class="d-ev"><span class="t">{{cal_d6_tev2t}}</span>{{cal_d6_tev2n}}</div>{% endif %}{% endif %}
  </div>
  <div class="d-allday">
    {% if cal_d6_hol != "" %}<span class="d-hol">{{cal_d6_hol}}</span>{% endif %}
    {% if cal_d6_aev0 != "" %}<span class="d-aev">{{cal_d6_aev0}}</span>{% endif %}
    {% if cal_d6_aev1 != "" %}<span class="d-aev">{{cal_d6_aev1}}</span>{% endif %}
    {% if cal_d6_aev2 != "" %}<span class="d-aev">{{cal_d6_aev2}}</span>{% endif %}
  </div>
</div>
  </div>

  <div class="cal-row">
<div class="day {{ cal_d7_today }} {{ cal_d7_wknd }}{% if cal_d7_dow == "Tue" or cal_d7_dow == "Thu" %} alt{% endif %}{% if cal_d7_hol != "" %} hol{% endif %}">
  <div class="d-hdr">{% if cal_d7_month_label != "" %}<span class="d-dom">{{cal_d7_month_label}}</span>{% else %}<span class="d-dom">{{cal_d7_dom}}</span>{% endif %}</div>
  <div class="d-evts">
    {% if cal_d7_tev0n != "" %}{% if cal_d7_tev0c == "personal" %}<div class="d-ev ehs"><span class="t">{{cal_d7_tev0t}}</span>{{cal_d7_tev0n}}</div>{% else %}<div class="d-ev"><span class="t">{{cal_d7_tev0t}}</span>{{cal_d7_tev0n}}</div>{% endif %}{% endif %}
    {% if cal_d7_tev1n != "" %}{% if cal_d7_tev1c == "personal" %}<div class="d-ev ehs"><span class="t">{{cal_d7_tev1t}}</span>{{cal_d7_tev1n}}</div>{% else %}<div class="d-ev"><span class="t">{{cal_d7_tev1t}}</span>{{cal_d7_tev1n}}</div>{% endif %}{% endif %}
    {% if cal_d7_tev2n != "" %}{% if cal_d7_tev2c == "personal" %}<div class="d-ev ehs"><span class="t">{{cal_d7_tev2t}}</span>{{cal_d7_tev2n}}</div>{% else %}<div class="d-ev"><span class="t">{{cal_d7_tev2t}}</span>{{cal_d7_tev2n}}</div>{% endif %}{% endif %}
  </div>
  <div class="d-allday">
    {% if cal_d7_hol != "" %}<span class="d-hol">{{cal_d7_hol}}</span>{% endif %}
    {% if cal_d7_aev0 != "" %}<span class="d-aev">{{cal_d7_aev0}}</span>{% endif %}
    {% if cal_d7_aev1 != "" %}<span class="d-aev">{{cal_d7_aev1}}</span>{% endif %}
    {% if cal_d7_aev2 != "" %}<span class="d-aev">{{cal_d7_aev2}}</span>{% endif %}
  </div>
</div>
<div class="day {{ cal_d8_today }} {{ cal_d8_wknd }}{% if cal_d8_dow == "Tue" or cal_d8_dow == "Thu" %} alt{% endif %}{% if cal_d8_hol != "" %} hol{% endif %}">
  <div class="d-hdr">{% if cal_d8_month_label != "" %}<span class="d-dom">{{cal_d8_month_label}}</span>{% else %}<span class="d-dom">{{cal_d8_dom}}</span>{% endif %}</div>
  <div class="d-evts">
    {% if cal_d8_tev0n != "" %}{% if cal_d8_tev0c == "personal" %}<div class="d-ev ehs"><span class="t">{{cal_d8_tev0t}}</span>{{cal_d8_tev0n}}</div>{% else %}<div class="d-ev"><span class="t">{{cal_d8_tev0t}}</span>{{cal_d8_tev0n}}</div>{% endif %}{% endif %}
    {% if cal_d8_tev1n != "" %}{% if cal_d8_tev1c == "personal" %}<div class="d-ev ehs"><span class="t">{{cal_d8_tev1t}}</span>{{cal_d8_tev1n}}</div>{% else %}<div class="d-ev"><span class="t">{{cal_d8_tev1t}}</span>{{cal_d8_tev1n}}</div>{% endif %}{% endif %}
    {% if cal_d8_tev2n != "" %}{% if cal_d8_tev2c == "personal" %}<div class="d-ev ehs"><span class="t">{{cal_d8_tev2t}}</span>{{cal_d8_tev2n}}</div>{% else %}<div class="d-ev"><span class="t">{{cal_d8_tev2t}}</span>{{cal_d8_tev2n}}</div>{% endif %}{% endif %}
  </div>
  <div class="d-allday">
    {% if cal_d8_hol != "" %}<span class="d-hol">{{cal_d8_hol}}</span>{% endif %}
    {% if cal_d8_aev0 != "" %}<span class="d-aev">{{cal_d8_aev0}}</span>{% endif %}
    {% if cal_d8_aev1 != "" %}<span class="d-aev">{{cal_d8_aev1}}</span>{% endif %}
    {% if cal_d8_aev2 != "" %}<span class="d-aev">{{cal_d8_aev2}}</span>{% endif %}
  </div>
</div>
<div class="day {{ cal_d9_today }} {{ cal_d9_wknd }}{% if cal_d9_dow == "Tue" or cal_d9_dow == "Thu" %} alt{% endif %}{% if cal_d9_hol != "" %} hol{% endif %}">
  <div class="d-hdr">{% if cal_d9_month_label != "" %}<span class="d-dom">{{cal_d9_month_label}}</span>{% else %}<span class="d-dom">{{cal_d9_dom}}</span>{% endif %}</div>
  <div class="d-evts">
    {% if cal_d9_tev0n != "" %}{% if cal_d9_tev0c == "personal" %}<div class="d-ev ehs"><span class="t">{{cal_d9_tev0t}}</span>{{cal_d9_tev0n}}</div>{% else %}<div class="d-ev"><span class="t">{{cal_d9_tev0t}}</span>{{cal_d9_tev0n}}</div>{% endif %}{% endif %}
    {% if cal_d9_tev1n != "" %}{% if cal_d9_tev1c == "personal" %}<div class="d-ev ehs"><span class="t">{{cal_d9_tev1t}}</span>{{cal_d9_tev1n}}</div>{% else %}<div class="d-ev"><span class="t">{{cal_d9_tev1t}}</span>{{cal_d9_tev1n}}</div>{% endif %}{% endif %}
    {% if cal_d9_tev2n != "" %}{% if cal_d9_tev2c == "personal" %}<div class="d-ev ehs"><span class="t">{{cal_d9_tev2t}}</span>{{cal_d9_tev2n}}</div>{% else %}<div class="d-ev"><span class="t">{{cal_d9_tev2t}}</span>{{cal_d9_tev2n}}</div>{% endif %}{% endif %}
  </div>
  <div class="d-allday">
    {% if cal_d9_hol != "" %}<span class="d-hol">{{cal_d9_hol}}</span>{% endif %}
    {% if cal_d9_aev0 != "" %}<span class="d-aev">{{cal_d9_aev0}}</span>{% endif %}
    {% if cal_d9_aev1 != "" %}<span class="d-aev">{{cal_d9_aev1}}</span>{% endif %}
    {% if cal_d9_aev2 != "" %}<span class="d-aev">{{cal_d9_aev2}}</span>{% endif %}
  </div>
</div>
<div class="day {{ cal_d10_today }} {{ cal_d10_wknd }}{% if cal_d10_dow == "Tue" or cal_d10_dow == "Thu" %} alt{% endif %}{% if cal_d10_hol != "" %} hol{% endif %}">
  <div class="d-hdr">{% if cal_d10_month_label != "" %}<span class="d-dom">{{cal_d10_month_label}}</span>{% else %}<span class="d-dom">{{cal_d10_dom}}</span>{% endif %}</div>
  <div class="d-evts">
    {% if cal_d10_tev0n != "" %}{% if cal_d10_tev0c == "personal" %}<div class="d-ev ehs"><span class="t">{{cal_d10_tev0t}}</span>{{cal_d10_tev0n}}</div>{% else %}<div class="d-ev"><span class="t">{{cal_d10_tev0t}}</span>{{cal_d10_tev0n}}</div>{% endif %}{% endif %}
    {% if cal_d10_tev1n != "" %}{% if cal_d10_tev1c == "personal" %}<div class="d-ev ehs"><span class="t">{{cal_d10_tev1t}}</span>{{cal_d10_tev1n}}</div>{% else %}<div class="d-ev"><span class="t">{{cal_d10_tev1t}}</span>{{cal_d10_tev1n}}</div>{% endif %}{% endif %}
    {% if cal_d10_tev2n != "" %}{% if cal_d10_tev2c == "personal" %}<div class="d-ev ehs"><span class="t">{{cal_d10_tev2t}}</span>{{cal_d10_tev2n}}</div>{% else %}<div class="d-ev"><span class="t">{{cal_d10_tev2t}}</span>{{cal_d10_tev2n}}</div>{% endif %}{% endif %}
  </div>
  <div class="d-allday">
    {% if cal_d10_hol != "" %}<span class="d-hol">{{cal_d10_hol}}</span>{% endif %}
    {% if cal_d10_aev0 != "" %}<span class="d-aev">{{cal_d10_aev0}}</span>{% endif %}
    {% if cal_d10_aev1 != "" %}<span class="d-aev">{{cal_d10_aev1}}</span>{% endif %}
    {% if cal_d10_aev2 != "" %}<span class="d-aev">{{cal_d10_aev2}}</span>{% endif %}
  </div>
</div>
<div class="day {{ cal_d11_today }} {{ cal_d11_wknd }}{% if cal_d11_dow == "Tue" or cal_d11_dow == "Thu" %} alt{% endif %}{% if cal_d11_hol != "" %} hol{% endif %}">
  <div class="d-hdr">{% if cal_d11_month_label != "" %}<span class="d-dom">{{cal_d11_month_label}}</span>{% else %}<span class="d-dom">{{cal_d11_dom}}</span>{% endif %}</div>
  <div class="d-evts">
    {% if cal_d11_tev0n != "" %}{% if cal_d11_tev0c == "personal" %}<div class="d-ev ehs"><span class="t">{{cal_d11_tev0t}}</span>{{cal_d11_tev0n}}</div>{% else %}<div class="d-ev"><span class="t">{{cal_d11_tev0t}}</span>{{cal_d11_tev0n}}</div>{% endif %}{% endif %}
    {% if cal_d11_tev1n != "" %}{% if cal_d11_tev1c == "personal" %}<div class="d-ev ehs"><span class="t">{{cal_d11_tev1t}}</span>{{cal_d11_tev1n}}</div>{% else %}<div class="d-ev"><span class="t">{{cal_d11_tev1t}}</span>{{cal_d11_tev1n}}</div>{% endif %}{% endif %}
    {% if cal_d11_tev2n != "" %}{% if cal_d11_tev2c == "personal" %}<div class="d-ev ehs"><span class="t">{{cal_d11_tev2t}}</span>{{cal_d11_tev2n}}</div>{% else %}<div class="d-ev"><span class="t">{{cal_d11_tev2t}}</span>{{cal_d11_tev2n}}</div>{% endif %}{% endif %}
  </div>
  <div class="d-allday">
    {% if cal_d11_hol != "" %}<span class="d-hol">{{cal_d11_hol}}</span>{% endif %}
    {% if cal_d11_aev0 != "" %}<span class="d-aev">{{cal_d11_aev0}}</span>{% endif %}
    {% if cal_d11_aev1 != "" %}<span class="d-aev">{{cal_d11_aev1}}</span>{% endif %}
    {% if cal_d11_aev2 != "" %}<span class="d-aev">{{cal_d11_aev2}}</span>{% endif %}
  </div>
</div>
<div class="day {{ cal_d12_today }} {{ cal_d12_wknd }}{% if cal_d12_dow == "Tue" or cal_d12_dow == "Thu" %} alt{% endif %}{% if cal_d12_hol != "" %} hol{% endif %}">
  <div class="d-hdr">{% if cal_d12_month_label != "" %}<span class="d-dom">{{cal_d12_month_label}}</span>{% else %}<span class="d-dom">{{cal_d12_dom}}</span>{% endif %}</div>
  <div class="d-evts">
    {% if cal_d12_tev0n != "" %}{% if cal_d12_tev0c == "personal" %}<div class="d-ev ehs"><span class="t">{{cal_d12_tev0t}}</span>{{cal_d12_tev0n}}</div>{% else %}<div class="d-ev"><span class="t">{{cal_d12_tev0t}}</span>{{cal_d12_tev0n}}</div>{% endif %}{% endif %}
    {% if cal_d12_tev1n != "" %}{% if cal_d12_tev1c == "personal" %}<div class="d-ev ehs"><span class="t">{{cal_d12_tev1t}}</span>{{cal_d12_tev1n}}</div>{% else %}<div class="d-ev"><span class="t">{{cal_d12_tev1t}}</span>{{cal_d12_tev1n}}</div>{% endif %}{% endif %}
    {% if cal_d12_tev2n != "" %}{% if cal_d12_tev2c == "personal" %}<div class="d-ev ehs"><span class="t">{{cal_d12_tev2t}}</span>{{cal_d12_tev2n}}</div>{% else %}<div class="d-ev"><span class="t">{{cal_d12_tev2t}}</span>{{cal_d12_tev2n}}</div>{% endif %}{% endif %}
  </div>
  <div class="d-allday">
    {% if cal_d12_hol != "" %}<span class="d-hol">{{cal_d12_hol}}</span>{% endif %}
    {% if cal_d12_aev0 != "" %}<span class="d-aev">{{cal_d12_aev0}}</span>{% endif %}
    {% if cal_d12_aev1 != "" %}<span class="d-aev">{{cal_d12_aev1}}</span>{% endif %}
    {% if cal_d12_aev2 != "" %}<span class="d-aev">{{cal_d12_aev2}}</span>{% endif %}
  </div>
</div>
<div class="day {{ cal_d13_today }} {{ cal_d13_wknd }}{% if cal_d13_dow == "Tue" or cal_d13_dow == "Thu" %} alt{% endif %}{% if cal_d13_hol != "" %} hol{% endif %}">
  <div class="d-hdr">{% if cal_d13_month_label != "" %}<span class="d-dom">{{cal_d13_month_label}}</span>{% else %}<span class="d-dom">{{cal_d13_dom}}</span>{% endif %}</div>
  <div class="d-evts">
    {% if cal_d13_tev0n != "" %}{% if cal_d13_tev0c == "personal" %}<div class="d-ev ehs"><span class="t">{{cal_d13_tev0t}}</span>{{cal_d13_tev0n}}</div>{% else %}<div class="d-ev"><span class="t">{{cal_d13_tev0t}}</span>{{cal_d13_tev0n}}</div>{% endif %}{% endif %}
    {% if cal_d13_tev1n != "" %}{% if cal_d13_tev1c == "personal" %}<div class="d-ev ehs"><span class="t">{{cal_d13_tev1t}}</span>{{cal_d13_tev1n}}</div>{% else %}<div class="d-ev"><span class="t">{{cal_d13_tev1t}}</span>{{cal_d13_tev1n}}</div>{% endif %}{% endif %}
    {% if cal_d13_tev2n != "" %}{% if cal_d13_tev2c == "personal" %}<div class="d-ev ehs"><span class="t">{{cal_d13_tev2t}}</span>{{cal_d13_tev2n}}</div>{% else %}<div class="d-ev"><span class="t">{{cal_d13_tev2t}}</span>{{cal_d13_tev2n}}</div>{% endif %}{% endif %}
  </div>
  <div class="d-allday">
    {% if cal_d13_hol != "" %}<span class="d-hol">{{cal_d13_hol}}</span>{% endif %}
    {% if cal_d13_aev0 != "" %}<span class="d-aev">{{cal_d13_aev0}}</span>{% endif %}
    {% if cal_d13_aev1 != "" %}<span class="d-aev">{{cal_d13_aev1}}</span>{% endif %}
    {% if cal_d13_aev2 != "" %}<span class="d-aev">{{cal_d13_aev2}}</span>{% endif %}
  </div>
</div>
  </div>

</div>

<script>
(function(){
var NS='http://www.w3.org/2000/svg';
function mk(tag,a){var e=document.createElementNS(NS,tag);for(var k in a)e.setAttribute(k,a[k]);return e;}

/* 7-day forecast icons (current week only) */
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
for(var di=0;di<7;di++){
  var dwi=document.getElementById('dwi'+di);
  if(!dwi) continue;
  var cond=(dwi.parentElement.dataset.cond||'').trim();
  dwi.innerHTML=IC[cond]||IC['Overcast'];
}

/* Moon phase SVG */
var msvg=document.getElementById('msvg');
if(msvg){
  var phase=parseFloat('{{ moon_phase }}')||0;
  var mr=27,mc=30;
  msvg.appendChild(mk('circle',{cx:mc,cy:mc,r:mr,fill:'#888',stroke:'#666','stroke-width':'1'}));
  if(phase>0.98||(phase>=0&&phase<0.02)){msvg.appendChild(mk('circle',{cx:mc,cy:mc,r:mr,fill:'#fff'}));}
  else if(phase>=0.02){var wax=phase<=0.5,ang=phase*2*Math.PI,ex=mr*Math.cos(ang),aE=Math.abs(ex).toFixed(1),ms3,md3;
    if(wax){ms3=(ex>=0)?0:1;md3='M '+mc+','+(mc-mr)+' A '+mr+','+mr+' 0 0 1 '+mc+','+(mc+mr)+' A '+aE+','+mr+' 0 0 '+ms3+' '+mc+','+(mc-mr)+'Z';}
    else{ms3=(ex>=0)?1:0;md3='M '+mc+','+(mc-mr)+' A '+mr+','+mr+' 0 0 0 '+mc+','+(mc+mr)+' A '+aE+','+mr+' 0 0 '+ms3+' '+mc+','+(mc-mr)+'Z';}
    msvg.appendChild(mk('path',{d:md3,fill:'#fff'}));}
}

/* Solar dial */
var sdial=document.getElementById('sdial');
if(sdial){
  var sr3='{{ sunrise }}',ss3='{{ sunset }}';
  if(sr3&&sr3.indexOf(':')>=0&&ss3&&ss3.indexOf(':')>=0){
    var R2=34,CX2=66,CY2=37;
    function toMin2(t){var p=t.split(':');return parseInt(p[0])*60+parseInt(p[1]);}
    function mta2(m){return 270-m/1440*360;}
    function txy2(r,deg){var rad=deg*Math.PI/180;return[CX2+r*Math.cos(rad),CY2-r*Math.sin(rad)];}
    var rA2=mta2(toMin2(sr3)),sA2=mta2(toMin2(ss3));
    var ri2=txy2(R2,rA2),si2=txy2(R2,sA2),sp2=((rA2-sA2)+360)%360;
    sdial.appendChild(mk('circle',{cx:CX2,cy:CY2,r:R2,fill:'#bbb'}));
    sdial.appendChild(mk('path',{d:'M '+CX2+','+CY2+' L '+ri2[0].toFixed(1)+','+ri2[1].toFixed(1)+' A '+R2+' '+R2+' 0 '+(sp2>180?1:0)+' 1 '+si2[0].toFixed(1)+' '+si2[1].toFixed(1)+' Z',fill:'#fff'}));
    sdial.appendChild(mk('circle',{cx:CX2,cy:CY2,r:R2,fill:'none',stroke:'#888','stroke-width':'1.5'}));
  }
}
})();
</script>
