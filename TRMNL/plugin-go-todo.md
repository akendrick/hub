# Plugin: go.todo
# Layout: 2/3 Go board (DGS game 0) + 1/3 To-Do list
# Based on: plugin-dgs-v3.md layout architecture
#
# Polling URL:
#   https://knotwork.ca/kaslo-api.php?key=YOUR_DEVICE_KEY&game=0
#
# Settings: Strategy: Polling | Verb: GET | Remove bleed margin: Yes
# Interval: 15 minutes minimum (DGS rate limit)

## Markup (Full tab)

```html
<style>
*{box-sizing:border-box;margin:0;padding:0}
html,body{width:800px;height:480px;overflow:hidden;background:#fff;color:#000;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif}
body{display:grid;grid-template-rows:24px 456px}

.hdr{display:flex;align-items:center;padding:0 10px;border-bottom:2px solid #000;gap:8px;overflow:hidden}
.hdr-t{font-weight:800;font-size:12px;letter-spacing:.06em;text-transform:uppercase;flex-shrink:0}
.hdr-vs{font-size:12px;font-weight:700}
.hdr-stone{display:inline-block;width:10px;height:10px;border-radius:50%;vertical-align:-1px;border:1px solid #444;flex-shrink:0}
.hdr-stone.b{background:#111;border-color:#111}
.hdr-stone.w{background:#fff;border-color:#555}
.hdr-meta{font-size:10px;color:#666}
.badge{background:#000;color:#fff;font-size:9px;font-weight:700;padding:1px 5px;border-radius:2px;flex-shrink:0}
.hdr-r{font-size:10px;color:#555;margin-left:auto}

.content{display:flex;flex-direction:row;width:800px;height:456px;overflow:hidden}
.board-panel{flex:0 0 534px;width:534px;height:456px;overflow:hidden;border-right:2px solid #000;background:#D4A740;display:flex;align-items:flex-start;justify-content:flex-start}
#bw{width:444px;height:444px;flex-shrink:0}

/* Todo panel */
.todo-panel{flex:0 0 266px;width:266px;height:456px;overflow:hidden;display:flex;flex-direction:column}
.todo-subhdr{height:22px;border-bottom:2px solid #000;display:flex;justify-content:space-between;align-items:center;padding:0 8px;flex-shrink:0}
.todo-subhdr-t{font-size:11px;font-weight:800;letter-spacing:.05em;text-transform:uppercase}
.todo-subhdr-n{font-size:10px;color:#888}
.todo-body{flex:1;overflow:hidden;display:flex;flex-direction:column}

.sec-hdr{background:#111;color:#fff;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.1em;padding:3px 8px;flex-shrink:0}
.item{padding:4px 8px;border-bottom:1px solid #eee;display:flex;flex-direction:column;justify-content:center;flex-shrink:0}
.item.z{background:#f5f5f5}
.item-t{font-size:16px;font-weight:600;line-height:1.2;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.item-m{font-size:10px;color:#999;margin-top:1px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.empty{font-size:13px;color:#ccc;font-style:italic;padding:10px 8px}
.more{font-size:10px;color:#bbb;padding:2px 8px;flex-shrink:0;font-style:italic}
</style>

<div class="hdr">
  <span class="hdr-t">Go &middot; DGS</span>
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

  <div class="todo-panel">
    <div class="todo-subhdr">
      <span class="todo-subhdr-t">To Do</span>
      <span class="todo-subhdr-n">{{ todo_total }} open</span>
    </div>
    <div class="todo-body">
      <div class="sec-hdr">Urgent</div>
      {% if todo_u0_text %}
        <div class="item"><div class="item-t">{{ todo_u0_text }}</div>{% if todo_u0_meta != "" %}<div class="item-m">{{ todo_u0_meta }}</div>{% endif %}</div>
        {% if todo_u1_text %}<div class="item z"><div class="item-t">{{ todo_u1_text }}</div>{% if todo_u1_meta != "" %}<div class="item-m">{{ todo_u1_meta }}</div>{% endif %}</div>{% endif %}
        {% if todo_u2_text %}<div class="item"><div class="item-t">{{ todo_u2_text }}</div>{% if todo_u2_meta != "" %}<div class="item-m">{{ todo_u2_meta }}</div>{% endif %}</div>{% endif %}
        {% if todo_u3_text %}<div class="item z"><div class="item-t">{{ todo_u3_text }}</div>{% if todo_u3_meta != "" %}<div class="item-m">{{ todo_u3_meta }}</div>{% endif %}</div>{% endif %}
        {% if todo_u_more %}<div class="more">{{ todo_u_more }}</div>{% endif %}
      {% else %}
        <div class="empty">Nothing urgent</div>
      {% endif %}
      <div class="sec-hdr">Everything Else</div>
      {% if todo_r0_text %}
        <div class="item"><div class="item-t">{{ todo_r0_text }}</div>{% if todo_r0_meta != "" %}<div class="item-m">{{ todo_r0_meta }}</div>{% endif %}</div>
        {% if todo_r1_text %}<div class="item z"><div class="item-t">{{ todo_r1_text }}</div>{% if todo_r1_meta != "" %}<div class="item-m">{{ todo_r1_meta }}</div>{% endif %}</div>{% endif %}
        {% if todo_r2_text %}<div class="item"><div class="item-t">{{ todo_r2_text }}</div>{% if todo_r2_meta != "" %}<div class="item-m">{{ todo_r2_meta }}</div>{% endif %}</div>{% endif %}
        {% if todo_r3_text %}<div class="item z"><div class="item-t">{{ todo_r3_text }}</div>{% if todo_r3_meta != "" %}<div class="item-m">{{ todo_r3_meta }}</div>{% endif %}</div>{% endif %}
        {% if todo_r4_text %}<div class="item"><div class="item-t">{{ todo_r4_text }}</div>{% if todo_r4_meta != "" %}<div class="item-m">{{ todo_r4_meta }}</div>{% endif %}</div>{% endif %}
        {% if todo_r5_text %}<div class="item z"><div class="item-t">{{ todo_r5_text }}</div>{% if todo_r5_meta != "" %}<div class="item-m">{{ todo_r5_meta }}</div>{% endif %}</div>{% endif %}
        {% if todo_r6_text %}<div class="item"><div class="item-t">{{ todo_r6_text }}</div>{% if todo_r6_meta != "" %}<div class="item-m">{{ todo_r6_meta }}</div>{% endif %}</div>{% endif %}
        {% if todo_r7_text %}<div class="item z"><div class="item-t">{{ todo_r7_text }}</div>{% if todo_r7_meta != "" %}<div class="item-m">{{ todo_r7_meta }}</div>{% endif %}</div>{% endif %}
        {% if todo_r_more %}<div class="more">{{ todo_r_more }}</div>{% endif %}
      {% else %}
        <div class="empty">All clear</div>
      {% endif %}
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
var W=444,H=444,VW=420,VH=420,pad=14,gridPx=VW-pad*2,cell=gridPx/18,sr=Math.min(cell*0.47,11),ox=pad,oy=pad;
var S=[];
S.push('<rect x="'+(ox-pad*0.5).toFixed(1)+'" y="'+(oy-pad*0.5).toFixed(1)+'" width="'+(gridPx+pad).toFixed(1)+'" height="'+(gridPx+pad).toFixed(1)+'" fill="#D4A740" rx="2"/>');
for(var i=0;i<19;i++){var sw=(i===0||i===18)?'1.6':'0.6';var gx=(ox+i*cell).toFixed(1),gy=(oy+i*cell).toFixed(1);S.push('<line x1="'+gx+'" y1="'+oy+'" x2="'+gx+'" y2="'+(oy+gridPx).toFixed(1)+'" stroke="#3A2000" stroke-width="'+sw+'"/>');S.push('<line x1="'+ox+'" y1="'+gy+'" x2="'+(ox+gridPx).toFixed(1)+'" y2="'+gy+'" stroke="#3A2000" stroke-width="'+sw+'"/>');}
var hr=Math.max(1.2,cell*0.1);
[[3,3],[3,9],[3,15],[9,3],[9,9],[9,15],[15,3],[15,9],[15,15]].forEach(function(s){S.push('<circle cx="'+(ox+s[0]*cell).toFixed(1)+'" cy="'+(oy+s[1]*cell).toFixed(1)+'" r="'+hr.toFixed(1)+'" fill="#3A2000"/>');});
BK.forEach(function(s){var cx=(ox+s[0]*cell).toFixed(1),cy=(oy+s[1]*cell).toFixed(1);S.push('<circle cx="'+cx+'" cy="'+cy+'" r="'+sr.toFixed(1)+'" fill="#111" stroke="#000" stroke-width="0.4"/>');S.push('<ellipse cx="'+(ox+s[0]*cell-sr*0.28).toFixed(1)+'" cy="'+(oy+s[1]*cell-sr*0.28).toFixed(1)+'" rx="'+(sr*0.22).toFixed(1)+'" ry="'+(sr*0.14).toFixed(1)+'" fill="rgba(255,255,255,0.18)" transform="rotate(-30,'+cx+','+cy+')"/>');});
WH.forEach(function(s){S.push('<circle cx="'+(ox+s[0]*cell).toFixed(1)+'" cy="'+(oy+s[1]*cell).toFixed(1)+'" r="'+sr.toFixed(1)+'" fill="#f8f8f8" stroke="#444" stroke-width="1"/>');});
if(LC>=0&&LR>=0)S.push('<circle cx="'+(ox+LC*cell).toFixed(1)+'" cy="'+(oy+LR*cell).toFixed(1)+'" r="'+(sr*0.32).toFixed(1)+'" fill="'+(LCOL==='B'?'#fff':'#222')+'"/>');
document.getElementById('bw').innerHTML='<svg xmlns="http://www.w3.org/2000/svg" width="'+W+'" height="'+H+'" viewBox="0 0 '+VW+' '+VH+'" preserveAspectRatio="xMinYMin meet" style="display:block">'+S.join('')+'</svg>';
})();
</script>
```
