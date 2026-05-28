# Plugin: go.cal
# Layout: 2/3 Go board (DGS game 1) + 1/3 7-day calendar with weather
#
# Polling URL:
#   https://knotwork.ca/kaslo-api.php?key=YOUR_DEVICE_KEY&game=1
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

.cal-col{flex:1;display:flex;flex-direction:column;overflow:hidden}
.cal-col-hdr{height:22px;display:flex;align-items:center;padding:0 8px;border-bottom:2px solid #000;flex-shrink:0}
.cal-col-t{font-size:10px;font-weight:800;letter-spacing:.05em;text-transform:uppercase}

.days{display:flex;flex-direction:column;flex:1;min-height:0}
.day{display:flex;flex-direction:column;flex:1;min-height:0;padding:3px 8px;border-bottom:1px solid #ddd;overflow:hidden}
.day:last-child{border-bottom:none}
.day.today{background:#ebebeb}
.day.wknd .d-tag{background:#111;color:#fff}

.d-top{display:flex;align-items:baseline;gap:5px;flex-shrink:0}
.d-tag{font-size:10px;font-weight:900;text-transform:uppercase;letter-spacing:.04em;color:#333;white-space:nowrap}
.d-date{font-size:18px;font-weight:200;line-height:1;flex-shrink:0}
.day.today .d-date{font-weight:800}
.d-wx{font-size:10px;color:#555;flex:1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-left:4px}
.d-hi{font-size:11px;font-weight:700;color:#000;white-space:nowrap}
.d-lo{font-size:10px;color:#888;white-space:nowrap}

.d-evts{flex:1;overflow:hidden;display:flex;flex-direction:column;justify-content:flex-end;gap:1px}
.d-ev{font-size:10px;color:#333;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;line-height:1.3}
.d-ev .et{color:#aaa;font-size:9px}
.d-ev.hol{font-weight:700;font-size:9px;background:#222;color:#fff;padding:0 4px;align-self:flex-start}
</style>

<div class="hdr">
  <span class="hdr-title">Go &middot; Cal</span>
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

  <div class="cal-col">
    <div class="cal-col-hdr"><span class="cal-col-t">Calendar &middot; Kaslo</span></div>
    <div class="days">

      {% for day in IDX_0.cal.days limit:7 %}
        {% assign _wi = forloop.index0 %}
        {% assign _fc = IDX_0.fc.days[_wi] %}

        <div class="day{% if day.is_today %} today{% endif %}{% if day.is_weekend %} wknd{% endif %}">
          <div class="d-top">
            <span class="d-tag">{{ day.dow }}</span>
            <span class="d-date">{{ day.dom }}</span>
            {% if _fc.condition != "" %}
              <span class="d-wx">{{ _fc.condition }}{% if _fc.pop > 10 %} &middot; {{ _fc.pop }}%{% endif %}</span>
            {% endif %}
            <span class="d-hi">{{ _fc.hi }}&deg;</span>
            <span class="d-lo">/{{ _fc.lo }}&deg;</span>
          </div>
          <div class="d-evts">
            {% if day.holiday != "" %}<div class="d-ev hol">{{ day.holiday }}</div>{% endif %}
            {% for ev in day.events_timed limit:2 %}
              <div class="d-ev"><span class="et">{{ ev.time }} </span>{{ ev.summary }}</div>
            {% endfor %}
            {% for ev in day.events_allday limit:1 %}
              <div class="d-ev">{{ ev.summary }}</div>
            {% endfor %}
          </div>
        </div>

      {% endfor %}

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
