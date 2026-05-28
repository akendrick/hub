# Plugin: go.todo
# Layout: 2/3 Go board (DGS game 0) + 1/3 To-Do list
#
# Polling URL:
#   https://knotwork.ca/kaslo-api.php?key=YOUR_DEVICE_KEY&game=0
#
# Settings: Strategy: Polling | Verb: GET | Remove bleed margin: Yes
# Interval: 15 minutes minimum (DGS rate limit)

## Markup (Full tab)

```html
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html,body{width:800px;height:480px;overflow:hidden;background:#fff;color:#000}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Arial,sans-serif;font-size:12px;display:flex;flex-direction:column}

.hdr{height:24px;display:flex;align-items:center;padding:0 10px;border-bottom:2px solid #000;flex-shrink:0;gap:10px}
.hdr-title{font-weight:800;font-size:12px;letter-spacing:.06em;text-transform:uppercase}
.hdr-vs{font-size:12px;font-weight:700}
.hdr-stone{display:inline-block;width:10px;height:10px;border-radius:50%;vertical-align:-1px;border:1px solid #444;flex-shrink:0}
.hdr-stone.b{background:#111;border-color:#111}
.hdr-stone.w{background:#fff;border-color:#555}
.hdr-meta{font-size:10px;color:#666}
.hdr-time{font-size:10px;color:#555;margin-left:auto}
.badge{background:#000;color:#fff;font-size:9px;font-weight:700;padding:1px 5px;border-radius:2px;flex-shrink:0}

.body{display:flex;flex:1;min-height:0}
.board-col{width:533px;flex-shrink:0;overflow:hidden;border-right:2px solid #000}

.todo-col{flex:1;display:flex;flex-direction:column;overflow:hidden}
.todo-hdr{height:22px;display:flex;justify-content:space-between;align-items:center;padding:0 10px;border-bottom:2px solid #000;flex-shrink:0}
.todo-hdr-t{font-size:11px;font-weight:800;letter-spacing:.05em;text-transform:uppercase}
.todo-hdr-n{font-size:10px;color:#888}
.todo-body{flex:1;overflow:hidden;display:flex;flex-direction:column}

.sec-hdr{background:#111;color:#fff;font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:.12em;padding:3px 10px;flex-shrink:0}
.item{padding:4px 10px;border-bottom:1px solid #f0f0f0;display:flex;flex-direction:column;justify-content:center;flex-shrink:0}
.item.z{background:#f5f5f5}
.item-t{font-size:13px;font-weight:600;line-height:1.2;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.item-m{font-size:9px;color:#999;margin-top:1px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.empty{font-size:11px;color:#ccc;font-style:italic;padding:10px}
</style>

<div class="hdr">
  <span class="hdr-title">Go &middot; DGS</span>
  {% if IDX_0.go.opponent != "" %}
    <span class="hdr-stone {{ IDX_0.go.color | downcase }}"></span>
    <span class="hdr-vs">{{ IDX_0.go.opponent }}</span>
    <span class="hdr-meta">Mv {{ IDX_0.go.moves }}</span>
    {% if IDX_0.go.time_left != "" %}<span class="hdr-meta">{{ IDX_0.go.time_left }}</span>{% endif %}
  {% endif %}
  {% if IDX_0.go.my_turn_count > 0 %}<span class="badge">{{ IDX_0.go.my_turn_count }} to move</span>{% endif %}
  <span class="hdr-time">{{ trmnl.user.time | date: "%H:%M %a %-d" }}</span>
</div>

<div class="body">

  <div class="board-col">
    <svg id="bsvg" width="533" height="456" style="display:block"></svg>
  </div>

  <div class="todo-col">
    <div class="todo-hdr">
      <span class="todo-hdr-t">To Do</span>
      <span class="todo-hdr-n">{{ IDX_0.todo.total }} open</span>
    </div>
    <div class="todo-body">

      <div class="sec-hdr">Urgent</div>
      {% if IDX_0.todo.urgent[0] %}
        {% for item in IDX_0.todo.urgent limit:6 %}
          <div class="item{% cycle '', ' z' %}">
            <div class="item-t">{{ item.text }}</div>
            {% if item.due_label != "" %}<div class="item-m">{{ item.due_label }}{% if item.tags_str != "" %} &middot; {{ item.tags_str }}{% endif %}</div>{% endif %}
          </div>
        {% endfor %}
      {% else %}
        <div class="empty">Nothing urgent</div>
      {% endif %}

      <div class="sec-hdr">Everything Else</div>
      {% if IDX_0.todo.rest[0] %}
        {% for item in IDX_0.todo.rest limit:8 %}
          <div class="item{% cycle '', ' z' %}">
            <div class="item-t">{{ item.text }}</div>
            {% if item.due_label != "" %}<div class="item-m">{{ item.due_label }}</div>{% endif %}
          </div>
        {% endfor %}
      {% else %}
        <div class="empty">All clear</div>
      {% endif %}

    </div>
  </div>

</div>

<script>
(function(){
  var BK   = JSON.parse('{{ IDX_0.go.board_black_json }}' || '[]');
  var WH   = JSON.parse('{{ IDX_0.go.board_white_json }}' || '[]');
  var LC   = parseInt('{{ IDX_0.go.last_col }}');
  var LR   = parseInt('{{ IDX_0.go.last_row }}');
  var LCOL = '{{ IDX_0.go.last_color }}';

  var W=533, H=456, PAD=16;
  var BD=H-PAD*2, CELL=BD/18, OX=(W-BD)/2, OY=PAD, R=CELL/2*0.93;
  var svg=document.getElementById('bsvg');
  var NS='http://www.w3.org/2000/svg';
  function mk(tag,a){var e=document.createElementNS(NS,tag);for(var k in a)e.setAttribute(k,a[k]);return e;}

  svg.appendChild(mk('rect',{x:OX-CELL*0.65,y:OY-CELL*0.65,width:BD+CELL*1.3,height:BD+CELL*1.3,fill:'#e8d5a0',rx:3}));
  for(var i=0;i<19;i++){
    svg.appendChild(mk('line',{x1:OX,y1:OY+i*CELL,x2:OX+BD,y2:OY+i*CELL,stroke:'#6b5a36','stroke-width':0.8}));
    svg.appendChild(mk('line',{x1:OX+i*CELL,y1:OY,x2:OX+i*CELL,y2:OY+BD,stroke:'#6b5a36','stroke-width':0.8}));
  }
  [[3,3],[3,9],[3,15],[9,3],[9,9],[9,15],[15,3],[15,9],[15,15]].forEach(function(s){
    svg.appendChild(mk('circle',{cx:OX+s[0]*CELL,cy:OY+s[1]*CELL,r:CELL*0.11,fill:'#4a3a20'}));
  });
  BK.forEach(function(s){svg.appendChild(mk('circle',{cx:OX+s[0]*CELL,cy:OY+s[1]*CELL,r:R,fill:'#111',stroke:'#000','stroke-width':0.5}));});
  WH.forEach(function(s){svg.appendChild(mk('circle',{cx:OX+s[0]*CELL,cy:OY+s[1]*CELL,r:R,fill:'#f0f0f0',stroke:'#333','stroke-width':1}));});
  if(LC>=0&&LR>=0) svg.appendChild(mk('circle',{cx:OX+LC*CELL,cy:OY+LR*CELL,r:R*0.28,fill:LCOL==='B'?'#eee':'#111'}));
})();
</script>
```
