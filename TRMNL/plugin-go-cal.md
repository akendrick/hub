# Plugin: go.cal
# Layout: 2/3 Go board + 1/3 7-day calendar
# Each day: dark header (DOW big + date) | body = 3/4 events + 1/4 weather
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

/* Calendar */
.cal-hdr{height:18px;border-bottom:2px solid #000;display:flex;align-items:center;padding:0 6px;flex-shrink:0}
.cal-hdr-t{font-size:10px;font-weight:800;letter-spacing:.05em;text-transform:uppercase}
.days{display:flex;flex-direction:column;flex:1;min-height:0;overflow:hidden}

/* Day box — light gray base, lighter for weekends, dark for today */
.day{flex:1;min-height:0;border-bottom:1px solid #bbb;overflow:hidden;display:flex;flex-direction:column;background:#e8e8e8;color:#000}
.day:last-child{border-bottom:none}
.day.wknd{background:#f2f2f2}
.day.today{background:#3a3a3a;color:#fff}

/* Header bar: day name (large) + date */
.d-bar{flex-shrink:0;display:flex;align-items:baseline;gap:5px;padding:2px 5px;background:#222;color:#fff}
.day.today .d-bar{background:#000}
.d-dow{font-size:13px;font-weight:900;text-transform:uppercase;letter-spacing:.04em}
.d-dom{font-size:11px;font-weight:400;opacity:.75}

/* Body: 3/4 calendar | 1/4 weather */
.d-body{flex:1;min-height:0;display:flex;flex-direction:row;overflow:hidden}
.d-cal{flex:4;min-width:0;overflow:hidden;padding:2px 3px;display:flex;flex-direction:column;justify-content:space-between}
.d-wx{flex:1;min-width:0;overflow:hidden;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:2px 2px;border-left:1px solid rgba(0,0,0,0.12)}
.day.today .d-wx{border-left-color:rgba(255,255,255,0.2)}

/* Timed events */
.d-evts{overflow:hidden;display:flex;flex-direction:column;gap:0;flex:1;min-height:0}
.d-ev{font-size:12px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;line-height:1.35;color:#111}
.day.today .d-ev{color:#ddd}
.d-ev .t{font-size:10px;color:#888;margin-right:2px}
.day.today .d-ev .t{color:#aaa}

/* Full-day tags */
.d-tags{display:flex;flex-wrap:nowrap;overflow:hidden;gap:2px;flex-shrink:0;margin-top:1px}
.d-tag{font-size:7px;font-weight:800;background:#222;color:#fff;padding:1px 3px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;border-radius:1px}
.day.today .d-tag{background:#000}

/* Weather panel */
.d-wx-icon{font-size:32px;line-height:1;text-align:center}
.d-wx-pop{font-size:11px;text-align:center;color:#555;line-height:1.3;margin-top:1px}
.day.today .d-wx-pop{color:#bbb}
.d-wx-temp{font-size:11px;font-weight:700;text-align:center;margin-top:1px}
.day.today .d-wx-temp{color:#eee}
</style>

<div class="hdr">
  <div class="hdr-t">Go &middot; Cal</div>
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
    <div class="cal-hdr"><span class="cal-hdr-t">Cal &middot; Kaslo</span></div>
    <div class="days">

<div class="day {{ cal_d0_today }} {{ cal_d0_wknd }}"><div class="d-bar"><span class="d-dow">{{ cal_d0_dow }}</span><span class="d-dom">{{ cal_d0_dom }}</span></div><div class="d-body"><div class="d-cal"><div class="d-evts">{% if cal_d0_tev0n != "" %}<div class="d-ev"><span class="t">{{ cal_d0_tev0t }}</span>{{ cal_d0_tev0n }}</div>{% endif %}{% if cal_d0_tev1n != "" %}<div class="d-ev"><span class="t">{{ cal_d0_tev1t }}</span>{{ cal_d0_tev1n }}</div>{% endif %}</div><div class="d-tags">{% if cal_d0_hol != "" %}<span class="d-tag">{{ cal_d0_hol }}</span>{% endif %}{% if cal_d0_aev0 != "" %}<span class="d-tag">{{ cal_d0_aev0 }}</span>{% endif %}{% if cal_d0_aev1 != "" %}<span class="d-tag">{{ cal_d0_aev1 }}</span>{% endif %}</div></div><div class="d-wx">{% if cal_d0_f_icon != "" %}<div class="d-wx-icon">{{ cal_d0_f_icon }}</div>{% endif %}<div class="d-wx-pop">{% if cal_d0_f_pop != "" %}{{ cal_d0_f_pop }}{% endif %}{% if cal_d0_f_mm != "" %}<br>{{ cal_d0_f_mm }}{% endif %}</div>{% if cal_d0_f_hi != "" %}<div class="d-wx-temp">{{ cal_d0_f_hi }}&deg;/{{ cal_d0_f_lo }}&deg;</div>{% endif %}</div></div></div>

<div class="day {{ cal_d1_today }} {{ cal_d1_wknd }}"><div class="d-bar"><span class="d-dow">{{ cal_d1_dow }}</span><span class="d-dom">{{ cal_d1_dom }}</span></div><div class="d-body"><div class="d-cal"><div class="d-evts">{% if cal_d1_tev0n != "" %}<div class="d-ev"><span class="t">{{ cal_d1_tev0t }}</span>{{ cal_d1_tev0n }}</div>{% endif %}{% if cal_d1_tev1n != "" %}<div class="d-ev"><span class="t">{{ cal_d1_tev1t }}</span>{{ cal_d1_tev1n }}</div>{% endif %}</div><div class="d-tags">{% if cal_d1_hol != "" %}<span class="d-tag">{{ cal_d1_hol }}</span>{% endif %}{% if cal_d1_aev0 != "" %}<span class="d-tag">{{ cal_d1_aev0 }}</span>{% endif %}{% if cal_d1_aev1 != "" %}<span class="d-tag">{{ cal_d1_aev1 }}</span>{% endif %}</div></div><div class="d-wx">{% if cal_d1_f_icon != "" %}<div class="d-wx-icon">{{ cal_d1_f_icon }}</div>{% endif %}<div class="d-wx-pop">{% if cal_d1_f_pop != "" %}{{ cal_d1_f_pop }}{% endif %}{% if cal_d1_f_mm != "" %}<br>{{ cal_d1_f_mm }}{% endif %}</div>{% if cal_d1_f_hi != "" %}<div class="d-wx-temp">{{ cal_d1_f_hi }}&deg;/{{ cal_d1_f_lo }}&deg;</div>{% endif %}</div></div></div>

<div class="day {{ cal_d2_today }} {{ cal_d2_wknd }}"><div class="d-bar"><span class="d-dow">{{ cal_d2_dow }}</span><span class="d-dom">{{ cal_d2_dom }}</span></div><div class="d-body"><div class="d-cal"><div class="d-evts">{% if cal_d2_tev0n != "" %}<div class="d-ev"><span class="t">{{ cal_d2_tev0t }}</span>{{ cal_d2_tev0n }}</div>{% endif %}{% if cal_d2_tev1n != "" %}<div class="d-ev"><span class="t">{{ cal_d2_tev1t }}</span>{{ cal_d2_tev1n }}</div>{% endif %}</div><div class="d-tags">{% if cal_d2_hol != "" %}<span class="d-tag">{{ cal_d2_hol }}</span>{% endif %}{% if cal_d2_aev0 != "" %}<span class="d-tag">{{ cal_d2_aev0 }}</span>{% endif %}{% if cal_d2_aev1 != "" %}<span class="d-tag">{{ cal_d2_aev1 }}</span>{% endif %}</div></div><div class="d-wx">{% if cal_d2_f_icon != "" %}<div class="d-wx-icon">{{ cal_d2_f_icon }}</div>{% endif %}<div class="d-wx-pop">{% if cal_d2_f_pop != "" %}{{ cal_d2_f_pop }}{% endif %}{% if cal_d2_f_mm != "" %}<br>{{ cal_d2_f_mm }}{% endif %}</div>{% if cal_d2_f_hi != "" %}<div class="d-wx-temp">{{ cal_d2_f_hi }}&deg;/{{ cal_d2_f_lo }}&deg;</div>{% endif %}</div></div></div>

<div class="day {{ cal_d3_today }} {{ cal_d3_wknd }}"><div class="d-bar"><span class="d-dow">{{ cal_d3_dow }}</span><span class="d-dom">{{ cal_d3_dom }}</span></div><div class="d-body"><div class="d-cal"><div class="d-evts">{% if cal_d3_tev0n != "" %}<div class="d-ev"><span class="t">{{ cal_d3_tev0t }}</span>{{ cal_d3_tev0n }}</div>{% endif %}{% if cal_d3_tev1n != "" %}<div class="d-ev"><span class="t">{{ cal_d3_tev1t }}</span>{{ cal_d3_tev1n }}</div>{% endif %}</div><div class="d-tags">{% if cal_d3_hol != "" %}<span class="d-tag">{{ cal_d3_hol }}</span>{% endif %}{% if cal_d3_aev0 != "" %}<span class="d-tag">{{ cal_d3_aev0 }}</span>{% endif %}{% if cal_d3_aev1 != "" %}<span class="d-tag">{{ cal_d3_aev1 }}</span>{% endif %}</div></div><div class="d-wx">{% if cal_d3_f_icon != "" %}<div class="d-wx-icon">{{ cal_d3_f_icon }}</div>{% endif %}<div class="d-wx-pop">{% if cal_d3_f_pop != "" %}{{ cal_d3_f_pop }}{% endif %}{% if cal_d3_f_mm != "" %}<br>{{ cal_d3_f_mm }}{% endif %}</div>{% if cal_d3_f_hi != "" %}<div class="d-wx-temp">{{ cal_d3_f_hi }}&deg;/{{ cal_d3_f_lo }}&deg;</div>{% endif %}</div></div></div>

<div class="day {{ cal_d4_today }} {{ cal_d4_wknd }}"><div class="d-bar"><span class="d-dow">{{ cal_d4_dow }}</span><span class="d-dom">{{ cal_d4_dom }}</span></div><div class="d-body"><div class="d-cal"><div class="d-evts">{% if cal_d4_tev0n != "" %}<div class="d-ev"><span class="t">{{ cal_d4_tev0t }}</span>{{ cal_d4_tev0n }}</div>{% endif %}{% if cal_d4_tev1n != "" %}<div class="d-ev"><span class="t">{{ cal_d4_tev1t }}</span>{{ cal_d4_tev1n }}</div>{% endif %}</div><div class="d-tags">{% if cal_d4_hol != "" %}<span class="d-tag">{{ cal_d4_hol }}</span>{% endif %}{% if cal_d4_aev0 != "" %}<span class="d-tag">{{ cal_d4_aev0 }}</span>{% endif %}{% if cal_d4_aev1 != "" %}<span class="d-tag">{{ cal_d4_aev1 }}</span>{% endif %}</div></div><div class="d-wx">{% if cal_d4_f_icon != "" %}<div class="d-wx-icon">{{ cal_d4_f_icon }}</div>{% endif %}<div class="d-wx-pop">{% if cal_d4_f_pop != "" %}{{ cal_d4_f_pop }}{% endif %}{% if cal_d4_f_mm != "" %}<br>{{ cal_d4_f_mm }}{% endif %}</div>{% if cal_d4_f_hi != "" %}<div class="d-wx-temp">{{ cal_d4_f_hi }}&deg;/{{ cal_d4_f_lo }}&deg;</div>{% endif %}</div></div></div>

<div class="day {{ cal_d5_today }} {{ cal_d5_wknd }}"><div class="d-bar"><span class="d-dow">{{ cal_d5_dow }}</span><span class="d-dom">{{ cal_d5_dom }}</span></div><div class="d-body"><div class="d-cal"><div class="d-evts">{% if cal_d5_tev0n != "" %}<div class="d-ev"><span class="t">{{ cal_d5_tev0t }}</span>{{ cal_d5_tev0n }}</div>{% endif %}{% if cal_d5_tev1n != "" %}<div class="d-ev"><span class="t">{{ cal_d5_tev1t }}</span>{{ cal_d5_tev1n }}</div>{% endif %}</div><div class="d-tags">{% if cal_d5_hol != "" %}<span class="d-tag">{{ cal_d5_hol }}</span>{% endif %}{% if cal_d5_aev0 != "" %}<span class="d-tag">{{ cal_d5_aev0 }}</span>{% endif %}{% if cal_d5_aev1 != "" %}<span class="d-tag">{{ cal_d5_aev1 }}</span>{% endif %}</div></div><div class="d-wx">{% if cal_d5_f_icon != "" %}<div class="d-wx-icon">{{ cal_d5_f_icon }}</div>{% endif %}<div class="d-wx-pop">{% if cal_d5_f_pop != "" %}{{ cal_d5_f_pop }}{% endif %}{% if cal_d5_f_mm != "" %}<br>{{ cal_d5_f_mm }}{% endif %}</div>{% if cal_d5_f_hi != "" %}<div class="d-wx-temp">{{ cal_d5_f_hi }}&deg;/{{ cal_d5_f_lo }}&deg;</div>{% endif %}</div></div></div>

<div class="day {{ cal_d6_today }} {{ cal_d6_wknd }}"><div class="d-bar"><span class="d-dow">{{ cal_d6_dow }}</span><span class="d-dom">{{ cal_d6_dom }}</span></div><div class="d-body"><div class="d-cal"><div class="d-evts">{% if cal_d6_tev0n != "" %}<div class="d-ev"><span class="t">{{ cal_d6_tev0t }}</span>{{ cal_d6_tev0n }}</div>{% endif %}{% if cal_d6_tev1n != "" %}<div class="d-ev"><span class="t">{{ cal_d6_tev1t }}</span>{{ cal_d6_tev1n }}</div>{% endif %}</div><div class="d-tags">{% if cal_d6_hol != "" %}<span class="d-tag">{{ cal_d6_hol }}</span>{% endif %}{% if cal_d6_aev0 != "" %}<span class="d-tag">{{ cal_d6_aev0 }}</span>{% endif %}{% if cal_d6_aev1 != "" %}<span class="d-tag">{{ cal_d6_aev1 }}</span>{% endif %}</div></div><div class="d-wx">{% if cal_d6_f_icon != "" %}<div class="d-wx-icon">{{ cal_d6_f_icon }}</div>{% endif %}<div class="d-wx-pop">{% if cal_d6_f_pop != "" %}{{ cal_d6_f_pop }}{% endif %}{% if cal_d6_f_mm != "" %}<br>{{ cal_d6_f_mm }}{% endif %}</div>{% if cal_d6_f_hi != "" %}<div class="d-wx-temp">{{ cal_d6_f_hi }}&deg;/{{ cal_d6_f_lo }}&deg;</div>{% endif %}</div></div></div>

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
var sr=Math.min(cell*0.47,11),ox=pad+lblPad,oy=pad;var S=[];
S.push('<rect x="'+(ox-pad*0.5).toFixed(1)+'" y="'+(oy-pad*0.5).toFixed(1)+'" width="'+(gridPx+pad).toFixed(1)+'" height="'+(gridPx+pad).toFixed(1)+'" fill="#D4A740" rx="2"/>');
var FILES='ABCDEFGHJKLMNOPQRST';
for(var ci=0;ci<19;ci++){S.push('<text x="'+(ox+ci*cell).toFixed(1)+'" y="'+(oy+gridPx+lblPad*0.78).toFixed(1)+'" text-anchor="middle" font-size="9" font-family="monospace" fill="#7A4A00">'+FILES[ci]+'</text>');S.push('<text x="'+(ox-lblPad*0.5).toFixed(1)+'" y="'+(oy+ci*cell+3.5).toFixed(1)+'" text-anchor="middle" font-size="9" font-family="monospace" fill="#7A4A00">'+(19-ci)+'</text>');}
for(var gi=0;gi<19;gi++){var sw=(gi===0||gi===18)?'1.6':'0.6';var gx=(ox+gi*cell).toFixed(1),gy=(oy+gi*cell).toFixed(1);S.push('<line x1="'+gx+'" y1="'+oy+'" x2="'+gx+'" y2="'+(oy+gridPx).toFixed(1)+'" stroke="#3A2000" stroke-width="'+sw+'"/>');S.push('<line x1="'+ox+'" y1="'+gy+'" x2="'+(ox+gridPx).toFixed(1)+'" y2="'+gy+'" stroke="#3A2000" stroke-width="'+sw+'"/>');}
var hr2=Math.max(1.2,cell*0.1);
[[3,3],[3,9],[3,15],[9,3],[9,9],[9,15],[15,3],[15,9],[15,15]].forEach(function(s){S.push('<circle cx="'+(ox+s[0]*cell).toFixed(1)+'" cy="'+(oy+s[1]*cell).toFixed(1)+'" r="'+hr2.toFixed(1)+'" fill="#3A2000"/>');});
BK.forEach(function(s){var cx=(ox+s[0]*cell).toFixed(1),cy=(oy+s[1]*cell).toFixed(1);S.push('<circle cx="'+cx+'" cy="'+cy+'" r="'+sr.toFixed(1)+'" fill="#111" stroke="#000" stroke-width="0.4"/>');S.push('<ellipse cx="'+(ox+s[0]*cell-sr*0.28).toFixed(1)+'" cy="'+(oy+s[1]*cell-sr*0.28).toFixed(1)+'" rx="'+(sr*0.22).toFixed(1)+'" ry="'+(sr*0.14).toFixed(1)+'" fill="rgba(255,255,255,0.18)" transform="rotate(-30,'+cx+','+cy+')"/>');});
WH.forEach(function(s){S.push('<circle cx="'+(ox+s[0]*cell).toFixed(1)+'" cy="'+(oy+s[1]*cell).toFixed(1)+'" r="'+sr.toFixed(1)+'" fill="#f8f8f8" stroke="#444" stroke-width="1"/>');});
if(LC>=0&&LR>=0)S.push('<circle cx="'+(ox+LC*cell).toFixed(1)+'" cy="'+(oy+LR*cell).toFixed(1)+'" r="'+(sr*0.32).toFixed(1)+'" fill="'+(LCOL==="B"?"#fff":"#222")+'"/>');
document.getElementById('bw-main').innerHTML='<svg xmlns="http://www.w3.org/2000/svg" width="'+brdSz+'" height="'+brdSz+'" viewBox="0 0 420 420" preserveAspectRatio="xMinYMin meet" style="display:block">'+S.join('')+'</svg>';
})();
})();
</script>
```
