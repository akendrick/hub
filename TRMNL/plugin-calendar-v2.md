# Plugin 3: Kaslo Calendar (v2 — slim markup)
#
# Fixes:
#   - "file size too big: 919752" — removed heavy CSS, simplified HTML structure
#   - "read Timeout" — deploy updated cal-device-api.php which serves from cache
#
# Settings unchanged from v1:
#   Strategy: Polling | Verb: GET | Remove bleed margin: Yes

## Markup (Full tab)

```html
<style>
*{box-sizing:border-box;margin:0;padding:0}
html,body{width:800px;height:480px;overflow:hidden;background:#fff;color:#000}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:11px;display:flex;flex-direction:column}

.hdr{display:flex;justify-content:space-between;align-items:center;padding:0 10px;height:26px;border-bottom:2px solid #000;flex-shrink:0}
.hdr-t{font-weight:700;font-size:12px;letter-spacing:.04em}
.hdr-d{font-size:10px;color:#666}

.weeks{flex:1;display:flex;flex-direction:column;min-height:0}
.wlbl{padding:2px 8px;font-size:8px;text-transform:uppercase;letter-spacing:.08em;color:#bbb;background:#f8f8f8;border-bottom:1px solid #eee;border-top:1px solid #eee;flex-shrink:0}
.week{display:grid;grid-template-columns:repeat(7,1fr);flex:1;min-height:0}

.day{border-right:1px solid #e8e8e8;border-bottom:1px solid #e8e8e8;padding:2px 4px;overflow:hidden;display:flex;flex-direction:column}
.day:last-child{border-right:none}
.week:last-child .day{border-bottom:none}

.day.today{background:#f4f4f4}
.day.today .dn{text-decoration:underline;font-weight:800}
.day.wknd .dl,.day.wknd .dn{color:#aaa}
.day.hol .dn{background:#000;color:#fff;padding:0 2px}

.dh{display:flex;justify-content:space-between;align-items:baseline;margin-bottom:2px}
.dl{font-size:7px;text-transform:uppercase;letter-spacing:.05em;color:#bbb}
.dn{font-size:12px;font-weight:700;line-height:1}

.hl{font-size:7px;background:#000;color:#fff;padding:1px 3px;margin-bottom:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}

.ev{font-size:8px;line-height:1.3;margin-bottom:1px;padding-left:3px;border-left:1px solid #000;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.ev.personal2{border-left-color:#777}
.ev.personal3{border-left-color:#ccc}
.et{color:#aaa;margin-right:1px}
.more{font-size:7px;color:#bbb}
</style>

<div class="hdr">
  <div class="hdr-t">Calendar &middot; Kaslo</div>
  <div class="hdr-d">##{{ trmnl.user.time | date: "%H:%M &middot; %a %b %-d" }}</div>
</div>

<div class="weeks">
  <div class="wlbl">This week</div>
  <div class="week">
    ##{% for i in (0..6) %}##{% assign d = IDX_0.days[i] %}
    <div class="day##{% if d.is_today %} today##{% endif %}##{% if d.is_weekend %} wknd##{% endif %}##{% if d.holiday != "" %} hol##{% endif %}">
      <div class="dh"><span class="dl">##{{ d.dow }}</span><span class="dn">##{{ d.dom }}</span></div>
      ##{% if d.holiday != "" %}<div class="hl">##{{ d.holiday }}</div>##{% endif %}
      ##{% assign ec = d.events | size %}
      ##{% for ev in d.events limit:3 %}
      <div class="ev ##{{ ev.cal }}">##{% if ev.time %}<span class="et">##{{ ev.time }}</span>##{% endif %}##{{ ev.summary | truncate:18 }}</div>
      ##{% endfor %}
      ##{% if ec > 3 %}<div class="more">+##{{ ec | minus:3 }}</div>##{% endif %}
    </div>
    ##{% endfor %}
  </div>

  <div class="wlbl">Next week</div>
  <div class="week">
    ##{% for i in (7..13) %}##{% assign d = IDX_0.days[i] %}
    <div class="day##{% if d.is_today %} today##{% endif %}##{% if d.is_weekend %} wknd##{% endif %}##{% if d.holiday != "" %} hol##{% endif %}">
      <div class="dh"><span class="dl">##{{ d.dow }}</span><span class="dn">##{{ d.dom }}</span></div>
      ##{% if d.holiday != "" %}<div class="hl">##{{ d.holiday }}</div>##{% endif %}
      ##{% assign ec = d.events | size %}
      ##{% for ev in d.events limit:3 %}
      <div class="ev ##{{ ev.cal }}">##{% if ev.time %}<span class="et">##{{ ev.time }}</span>##{% endif %}##{{ ev.summary | truncate:18 }}</div>
      ##{% endfor %}
      ##{% if ec > 3 %}<div class="more">+##{{ ec | minus:3 }}</div>##{% endif %}
    </div>
    ##{% endfor %}
  </div>
</div>
```
