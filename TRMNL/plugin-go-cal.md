# Plugin: go.cal
# Layout: 2/3 Go board (DGS game 1) + 1/3 5-day calendar with weather
# Based on: plugin-dgs-v3.md layout architecture
#
# Polling URL:
#   https://knotwork.ca/kaslo-api.php?key=YOUR_DEVICE_KEY&game=1
#
# Settings: Strategy: Polling | Verb: GET | Remove bleed margin: Yes
# Interval: 15 minutes minimum (DGS rate limit)

## Markup (Full tab)

```html
<style>
*{box-sizing:border-box;margin:0;padding:0}
html,body{width:800px;height:480px;overflow:hidden;background:#fff;color:#000;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif}
body{display:grid;grid-template-rows:24px 456px}

/* Header */
.hdr{display:flex;align-items:center;padding:0 10px;border-bottom:2px solid #000;gap:8px;overflow:hidden}
.hdr-t{font-weight:800;font-size:12px;letter-spacing:.06em;text-transform:uppercase;flex-shrink:0}
.hdr-vs{font-size:12px;font-weight:700}
.hdr-stone{display:inline-block;width:10px;height:10px;border-radius:50%;vertical-align:-1px;border:1px solid #444;flex-shrink:0}
.hdr-stone.b{background:#111;border-color:#111}
.hdr-stone.w{background:#fff;border-color:#555}
.hdr-meta{font-size:10px;color:#666}
.badge{background:#000;color:#fff;font-size:9px;font-weight:700;padding:1px 5px;border-radius:2px;flex-shrink:0}
.hdr-r{font-size:10px;color:#555;margin-left:auto}

/* Content row — explicit dimensions matching dgs-v3 */
.content{display:flex;flex-direction:row;width:800px;height:456px;overflow:hidden}

/* Board panel */
.board-panel{flex:0 0 534px;width:534px;height:456px;overflow:hidden;border-right:2px solid #000;background:#D4A740;display:flex;align-items:flex-start;justify-content:flex-start}
#bw{width:444px;height:444px;flex-shrink:0}

/* Calendar panel */
.cal-panel{flex:0 0 266px;width:266px;height:456px;overflow:hidden;display:flex;flex-direction:column}
.cal-hdr{height:22px;border-bottom:2px solid #000;display:flex;align-items:center;padding:0 8px;flex-shrink:0}
.cal-hdr-t{font-size:11px;font-weight:800;letter-spacing:.05em;text-transform:uppercase}
.days{display:flex;flex-direction:column;flex:1;min-height:0;overflow:hidden}

/* Day rows — 5 days, each gets ~86px */
.day{flex:1;min-height:0;padding:4px 8px;border-bottom:1px solid #ccc;overflow:hidden;display:flex;flex-direction:column}
.day:last-child{border-bottom:none}
.day.today{background:#e8e8e8}
.day.wknd .d-tag{background:#111;color:#fff;padding:1px 4px}
.d-top{display:flex;align-items:baseline;gap:4px;flex-shrink:0;flex-wrap:nowrap;overflow:hidden}
.d-tag{font-size:12px;font-weight:900;text-transform:uppercase;letter-spacing:.03em;color:#333;white-space:nowrap;flex-shrink:0}
.d-date{font-size:24px;font-weight:200;line-height:1;flex-shrink:0}
.day.today .d-date{font-weight:800}
.d-wx{font-size:11px;color:#555;flex:1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-left:3px;align-self:center}
.d-temps{font-size:12px;font-weight:700;white-space:nowrap;flex-shrink:0}
.d-lo{font-weight:400;color:#888}
.d-evts{flex:1;overflow:hidden;display:flex;flex-direction:column;justify-content:flex-end;gap:1px;padding-top:2px}
.d-ev{font-size:12px;color:#222;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;line-height:1.3}
.d-ev .et{color:#999;font-size:10px}
.d-ev.hol{font-weight:800;font-size:11px;background:#222;color:#fff;padding:1px 4px;align-self:flex-start}
</style>

<div class="hdr">
  <span class="hdr-t">Go &middot; Cal</span>
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

  <div class="cal-panel">
    <div class="cal-hdr"><span class="cal-hdr-t">Cal &middot; Kaslo</span></div>
    <div class="days">
<div class="day {{ cal_d0_today }} {{ cal_d0_wknd }}"><div class="d-top"><span class="d-tag">{{ cal_d0_dow }}</span><span class="d-date">{{ cal_d0_dom }}</span>{% if cal_d0_f_cond != "" %}<span class="d-wx">{{ cal_d0_f_cond }}{% if cal_d0_f_pop != "" %} &middot;{{ cal_d0_f_pop }}{% endif %}</span>{% endif %}<span class="d-temps">{{ cal_d0_f_hi }}&deg;<span class="d-lo">/{{ cal_d0_f_lo }}&deg;</span></span></div><div class="d-evts">{% if cal_d0_hol != "" %}<div class="d-ev hol">{{ cal_d0_hol }}</div>{% endif %}{% if cal_d0_tev0n != "" %}<div class="d-ev"><span class="et">{{ cal_d0_tev0t }} </span>{{ cal_d0_tev0n }}</div>{% endif %}{% if cal_d0_aev0 != "" %}<div class="d-ev">{{ cal_d0_aev0 }}</div>{% endif %}</div></div>
<div class="day {{ cal_d1_today }} {{ cal_d1_wknd }}"><div class="d-top"><span class="d-tag">{{ cal_d1_dow }}</span><span class="d-date">{{ cal_d1_dom }}</span>{% if cal_d1_f_cond != "" %}<span class="d-wx">{{ cal_d1_f_cond }}{% if cal_d1_f_pop != "" %} &middot;{{ cal_d1_f_pop }}{% endif %}</span>{% endif %}<span class="d-temps">{{ cal_d1_f_hi }}&deg;<span class="d-lo">/{{ cal_d1_f_lo }}&deg;</span></span></div><div class="d-evts">{% if cal_d1_hol != "" %}<div class="d-ev hol">{{ cal_d1_hol }}</div>{% endif %}{% if cal_d1_tev0n != "" %}<div class="d-ev"><span class="et">{{ cal_d1_tev0t }} </span>{{ cal_d1_tev0n }}</div>{% endif %}{% if cal_d1_aev0 != "" %}<div class="d-ev">{{ cal_d1_aev0 }}</div>{% endif %}</div></div>
<div class="day {{ cal_d2_today }} {{ cal_d2_wknd }}"><div class="d-top"><span class="d-tag">{{ cal_d2_dow }}</span><span class="d-date">{{ cal_d2_dom }}</span>{% if cal_d2_f_cond != "" %}<span class="d-wx">{{ cal_d2_f_cond }}{% if cal_d2_f_pop != "" %} &middot;{{ cal_d2_f_pop }}{% endif %}</span>{% endif %}<span class="d-temps">{{ cal_d2_f_hi }}&deg;<span class="d-lo">/{{ cal_d2_f_lo }}&deg;</span></span></div><div class="d-evts">{% if cal_d2_hol != "" %}<div class="d-ev hol">{{ cal_d2_hol }}</div>{% endif %}{% if cal_d2_tev0n != "" %}<div class="d-ev"><span class="et">{{ cal_d2_tev0t }} </span>{{ cal_d2_tev0n }}</div>{% endif %}{% if cal_d2_aev0 != "" %}<div class="d-ev">{{ cal_d2_aev0 }}</div>{% endif %}</div></div>
<div class="day {{ cal_d3_today }} {{ cal_d3_wknd }}"><div class="d-top"><span class="d-tag">{{ cal_d3_dow }}</span><span class="d-date">{{ cal_d3_dom }}</span>{% if cal_d3_f_cond != "" %}<span class="d-wx">{{ cal_d3_f_cond }}{% if cal_d3_f_pop != "" %} &middot;{{ cal_d3_f_pop }}{% endif %}</span>{% endif %}<span class="d-temps">{{ cal_d3_f_hi }}&deg;<span class="d-lo">/{{ cal_d3_f_lo }}&deg;</span></span></div><div class="d-evts">{% if cal_d3_hol != "" %}<div class="d-ev hol">{{ cal_d3_hol }}</div>{% endif %}{% if cal_d3_tev0n != "" %}<div class="d-ev"><span class="et">{{ cal_d3_tev0t }} </span>{{ cal_d3_tev0n }}</div>{% endif %}{% if cal_d3_aev0 != "" %}<div class="d-ev">{{ cal_d3_aev0 }}</div>{% endif %}</div></div>
<div class="day {{ cal_d4_today }} {{ cal_d4_wknd }}"><div class="d-top"><span class="d-tag">{{ cal_d4_dow }}</span><span class="d-date">{{ cal_d4_dom }}</span>{% if cal_d4_f_cond != "" %}<span class="d-wx">{{ cal_d4_f_cond }}{% if cal_d4_f_pop != "" %} &middot;{{ cal_d4_f_pop }}{% endif %}</span>{% endif %}<span class="d-temps">{{ cal_d4_f_hi }}&deg;<span class="d-lo">/{{ cal_d4_f_lo }}&deg;</span></span></div><div class="d-evts">{% if cal_d4_hol != "" %}<div class="d-ev hol">{{ cal_d4_hol }}</div>{% endif %}{% if cal_d4_tev0n != "" %}<div class="d-ev"><span class="et">{{ cal_d4_tev0t }} </span>{{ cal_d4_tev0n }}</div>{% endif %}{% if cal_d4_aev0 != "" %}<div class="d-ev">{{ cal_d4_aev0 }}</div>{% endif %}</div></div>
    </div>
  </div>

</div>

<script>
(function(){
var BK=JSON.parse('{{ go_board_black_json }}'||'[]');
var WH=JSON.parse('{{ go_board_white_json }}'||'[]');
var LC=parseInt('{{ go_last_col }}');
var LR=parseInt('{{ go_last_row }}');
var LCOL='{{ go_last_color }}';

// Board renderer — innerHTML SVG matching dgs-v3 exactly
var W=444,H=444,VW=420,VH=420,pad=14;
var gridPx=VW-pad*2; // 392
var cell=gridPx/18;
var sr=Math.min(cell*0.47,11);
var ox=pad,oy=pad;
var S=[];
// Background
S.push('<rect x="'+(ox-pad*0.5).toFixed(1)+'" y="'+(oy-pad*0.5).toFixed(1)+'"'
  +' width="'+(gridPx+pad).toFixed(1)+'" height="'+(gridPx+pad).toFixed(1)+'"'
  +' fill="#D4A740" rx="2"/>');
// Grid
for(var i=0;i<19;i++){
  var sw=(i===0||i===18)?'1.6':'0.6';
  var gx=(ox+i*cell).toFixed(1),gy=(oy+i*cell).toFixed(1);
  S.push('<line x1="'+gx+'" y1="'+oy+'" x2="'+gx+'" y2="'+(oy+gridPx).toFixed(1)+'" stroke="#3A2000" stroke-width="'+sw+'"/>');
  S.push('<line x1="'+ox+'" y1="'+gy+'" x2="'+(ox+gridPx).toFixed(1)+'" y2="'+gy+'" stroke="#3A2000" stroke-width="'+sw+'"/>');
}
// Hoshi
var hr=Math.max(1.2,cell*0.1);
[[3,3],[3,9],[3,15],[9,3],[9,9],[9,15],[15,3],[15,9],[15,15]].forEach(function(s){
  S.push('<circle cx="'+(ox+s[0]*cell).toFixed(1)+'" cy="'+(oy+s[1]*cell).toFixed(1)+'" r="'+hr.toFixed(1)+'" fill="#3A2000"/>');
});
// Stones
BK.forEach(function(s){
  var cx=(ox+s[0]*cell).toFixed(1),cy=(oy+s[1]*cell).toFixed(1);
  S.push('<circle cx="'+cx+'" cy="'+cy+'" r="'+sr.toFixed(1)+'" fill="#111" stroke="#000" stroke-width="0.4"/>');
  S.push('<ellipse cx="'+(ox+s[0]*cell-sr*0.28).toFixed(1)+'" cy="'+(oy+s[1]*cell-sr*0.28).toFixed(1)+'"'
    +' rx="'+(sr*0.22).toFixed(1)+'" ry="'+(sr*0.14).toFixed(1)+'" fill="rgba(255,255,255,0.18)"'
    +' transform="rotate(-30,'+cx+','+cy+')"/>');
});
WH.forEach(function(s){
  S.push('<circle cx="'+(ox+s[0]*cell).toFixed(1)+'" cy="'+(oy+s[1]*cell).toFixed(1)+'" r="'+sr.toFixed(1)+'" fill="#f8f8f8" stroke="#444" stroke-width="1"/>');
});
if(LC>=0&&LR>=0){
  S.push('<circle cx="'+(ox+LC*cell).toFixed(1)+'" cy="'+(oy+LR*cell).toFixed(1)+'"'
    +' r="'+(sr*0.32).toFixed(1)+'" fill="'+(LCOL==='B'?'#fff':'#222')+'"/>');
}
document.getElementById('bw').innerHTML='<svg xmlns="http://www.w3.org/2000/svg"'
  +' width="'+W+'" height="'+H+'" viewBox="0 0 '+VW+' '+VH+'"'
  +' preserveAspectRatio="xMinYMin meet" style="display:block">'+S.join('')+'</svg>';
})();
</script>
```
