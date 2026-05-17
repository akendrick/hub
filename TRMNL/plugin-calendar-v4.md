# Plugin: Kaslo Calendar (v4 — week at a glance, stripped down, large fonts)
#
# Polling URL:
#   IDX_0 → https://knotwork.ca/cal-device-api.php?key=kw_40e818911c1980bcd56dc4aff37a820f811990a130eb771fd9e21a536edc55ea
#
# Layout:
#   Thin header bar (title + time)
#   Full-height 7-column grid — one column per day
#   Each column: day name → huge date number → holiday pill → events
#
# Design goals:
#   - Minimal CSS (no shadows, gradients, complex borders) → smaller rendered bitmap
#   - Very large date numbers for distance reading
#   - Today column lightly highlighted
#   - Weekends subtly grayed
#
# Settings: Strategy: Polling | Verb: GET | Remove bleed margin: Yes
#
# IMPORTANT: Paste ONLY the HTML below (starting with <style>) into the
# TRMNL "Full" markup tab. Do NOT paste this header or the ``` fences.

## Markup (Full tab)

```html
<style>
*{box-sizing:border-box;margin:0;padding:0}
html,body{width:100vw;height:100vh;overflow:hidden;background:#fff;color:#000}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;display:flex;flex-direction:column}

.hdr{height:28px;display:flex;justify-content:space-between;align-items:center;padding:0 12px;border-bottom:3px solid #000;flex-shrink:0}
.hdr-t{font-size:15px;font-weight:800;letter-spacing:.05em}
.hdr-r{font-size:12px;color:#555}

.week{display:grid;grid-template-columns:repeat(7,1fr);flex:1;min-height:0}

.day{display:flex;flex-direction:column;padding:8px 7px 6px;border-right:2px solid #ccc;overflow:hidden}
.day:last-child{border-right:none}
.day.wknd{background:#f4f4f4}
.day.today{background:#ebebeb}

.d-name{font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:#aaa;flex-shrink:0}
.day.today .d-name{color:#000}

.d-num{font-size:76px;font-weight:200;line-height:.95;flex-shrink:0}
.day.today .d-num{font-weight:800}

.hol{font-size:11px;font-weight:700;text-transform:uppercase;background:#222;color:#fff;padding:2px 5px;margin:5px 0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;flex-shrink:0}

.evts{display:flex;flex-direction:column;gap:5px;margin-top:6px;overflow:hidden;flex:1}

.ev-time{font-size:11px;color:#999;display:block;line-height:1}
.ev-name{font-size:17px;font-weight:600;line-height:1.2;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:block}

.more{font-size:11px;color:#bbb;margin-top:3px}
</style>

<div class="hdr">
  <div class="hdr-t">CALENDAR &middot; KASLO</div>
  <div class="hdr-r">##{{ trmnl.user.time | date: "%H:%M &middot; %a %b %-d" }}</div>
</div>

<div class="week">
  ##{% for d in IDX_0.days limit:7 %}
  <div class="day##{% if d.is_today %} today##{% endif %}##{% if d.is_weekend %} wknd##{% endif %}">

    <div class="d-name">##{{ d.dow }}</div>
    <div class="d-num">##{{ d.dom }}</div>

    ##{% if d.holiday != "" %}
      <div class="hol">##{{ d.holiday }}</div>
    ##{% endif %}

    <div class="evts">
      ##{% assign ec = d.events | size %}
      ##{% for ev in d.events limit:4 %}
        <div>
          ##{% if ev.time %}<span class="ev-time">##{{ ev.time }}</span>##{% endif %}
          <span class="ev-name">##{{ ev.summary | truncate: 20 }}</span>
        </div>
      ##{% endfor %}
      ##{% if ec > 4 %}<div class="more">+##{{ ec | minus: 4 }} more</div>##{% endif %}
    </div>

  </div>
  ##{% endfor %}
</div>
```
