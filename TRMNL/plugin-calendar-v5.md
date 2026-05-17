# Plugin: Kaslo Calendar (v5 — flat vars, no loops, week at a glance)
#
# Polling URL:
#   https://knotwork.ca/cal-device-api.php?key=kw_40e818911c1980bcd56dc4aff37a820f811990a130eb771fd9e21a536edc55ea
#
# Variables: d0_dow…d6_dow, d0_dom…d6_dom, d0_today/d0_wknd ("today"/"wknd" or ""),
#            d0_hol…d6_hol, d0_ev0…d6_ev3, d0_more…d6_more
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

.week{display:grid;grid-template-columns:repeat(7,1fr);flex:1;min-height:0;overflow:hidden}

.day{min-width:0;display:flex;flex-direction:column;padding:8px 7px 6px;border-right:2px solid #ccc;overflow:hidden}
.day:last-child{border-right:none}
.day.wknd{background:#f4f4f4}
.day.today{background:#ebebeb}

.d-name{font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:#aaa;flex-shrink:0}
.day.today .d-name{color:#000}

.d-num{font-size:72px;font-weight:200;line-height:.95;flex-shrink:0}
.day.today .d-num{font-weight:800}

.hol{font-size:11px;font-weight:700;text-transform:uppercase;background:#222;color:#fff;padding:2px 5px;margin:4px 0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;flex-shrink:0}

.evts{display:flex;flex-direction:column;gap:4px;margin-top:5px;overflow:hidden;flex:1}
.ev{font-size:16px;font-weight:600;line-height:1.2;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.more{font-size:11px;color:#bbb;margin-top:2px}
</style>

<div class="hdr">
  <div class="hdr-t">CALENDAR &middot; KASLO</div>
  <div class="hdr-r">##{{ trmnl.user.time | date: "%H:%M &middot; %a %b %-d" }}</div>
</div>

<div class="week">
<div class="day ##{{ d0_today }} ##{{ d0_wknd }}"><div class="d-name">##{{ d0_dow }}</div><div class="d-num">##{{ d0_dom }}</div>##{% if d0_hol != "" %}<div class="hol">##{{ d0_hol }}</div>##{% endif %}<div class="evts">##{% if d0_ev0 != "" %}<div class="ev">##{{ d0_ev0 }}</div>##{% endif %}##{% if d0_ev1 != "" %}<div class="ev">##{{ d0_ev1 }}</div>##{% endif %}##{% if d0_ev2 != "" %}<div class="ev">##{{ d0_ev2 }}</div>##{% endif %}##{% if d0_ev3 != "" %}<div class="ev">##{{ d0_ev3 }}</div>##{% endif %}##{% if d0_more > 0 %}<div class="more">+##{{ d0_more }} more</div>##{% endif %}</div></div>
<div class="day ##{{ d1_today }} ##{{ d1_wknd }}"><div class="d-name">##{{ d1_dow }}</div><div class="d-num">##{{ d1_dom }}</div>##{% if d1_hol != "" %}<div class="hol">##{{ d1_hol }}</div>##{% endif %}<div class="evts">##{% if d1_ev0 != "" %}<div class="ev">##{{ d1_ev0 }}</div>##{% endif %}##{% if d1_ev1 != "" %}<div class="ev">##{{ d1_ev1 }}</div>##{% endif %}##{% if d1_ev2 != "" %}<div class="ev">##{{ d1_ev2 }}</div>##{% endif %}##{% if d1_ev3 != "" %}<div class="ev">##{{ d1_ev3 }}</div>##{% endif %}##{% if d1_more > 0 %}<div class="more">+##{{ d1_more }} more</div>##{% endif %}</div></div>
<div class="day ##{{ d2_today }} ##{{ d2_wknd }}"><div class="d-name">##{{ d2_dow }}</div><div class="d-num">##{{ d2_dom }}</div>##{% if d2_hol != "" %}<div class="hol">##{{ d2_hol }}</div>##{% endif %}<div class="evts">##{% if d2_ev0 != "" %}<div class="ev">##{{ d2_ev0 }}</div>##{% endif %}##{% if d2_ev1 != "" %}<div class="ev">##{{ d2_ev1 }}</div>##{% endif %}##{% if d2_ev2 != "" %}<div class="ev">##{{ d2_ev2 }}</div>##{% endif %}##{% if d2_ev3 != "" %}<div class="ev">##{{ d2_ev3 }}</div>##{% endif %}##{% if d2_more > 0 %}<div class="more">+##{{ d2_more }} more</div>##{% endif %}</div></div>
<div class="day ##{{ d3_today }} ##{{ d3_wknd }}"><div class="d-name">##{{ d3_dow }}</div><div class="d-num">##{{ d3_dom }}</div>##{% if d3_hol != "" %}<div class="hol">##{{ d3_hol }}</div>##{% endif %}<div class="evts">##{% if d3_ev0 != "" %}<div class="ev">##{{ d3_ev0 }}</div>##{% endif %}##{% if d3_ev1 != "" %}<div class="ev">##{{ d3_ev1 }}</div>##{% endif %}##{% if d3_ev2 != "" %}<div class="ev">##{{ d3_ev2 }}</div>##{% endif %}##{% if d3_ev3 != "" %}<div class="ev">##{{ d3_ev3 }}</div>##{% endif %}##{% if d3_more > 0 %}<div class="more">+##{{ d3_more }} more</div>##{% endif %}</div></div>
<div class="day ##{{ d4_today }} ##{{ d4_wknd }}"><div class="d-name">##{{ d4_dow }}</div><div class="d-num">##{{ d4_dom }}</div>##{% if d4_hol != "" %}<div class="hol">##{{ d4_hol }}</div>##{% endif %}<div class="evts">##{% if d4_ev0 != "" %}<div class="ev">##{{ d4_ev0 }}</div>##{% endif %}##{% if d4_ev1 != "" %}<div class="ev">##{{ d4_ev1 }}</div>##{% endif %}##{% if d4_ev2 != "" %}<div class="ev">##{{ d4_ev2 }}</div>##{% endif %}##{% if d4_ev3 != "" %}<div class="ev">##{{ d4_ev3 }}</div>##{% endif %}##{% if d4_more > 0 %}<div class="more">+##{{ d4_more }} more</div>##{% endif %}</div></div>
<div class="day ##{{ d5_today }} ##{{ d5_wknd }}"><div class="d-name">##{{ d5_dow }}</div><div class="d-num">##{{ d5_dom }}</div>##{% if d5_hol != "" %}<div class="hol">##{{ d5_hol }}</div>##{% endif %}<div class="evts">##{% if d5_ev0 != "" %}<div class="ev">##{{ d5_ev0 }}</div>##{% endif %}##{% if d5_ev1 != "" %}<div class="ev">##{{ d5_ev1 }}</div>##{% endif %}##{% if d5_ev2 != "" %}<div class="ev">##{{ d5_ev2 }}</div>##{% endif %}##{% if d5_ev3 != "" %}<div class="ev">##{{ d5_ev3 }}</div>##{% endif %}##{% if d5_more > 0 %}<div class="more">+##{{ d5_more }} more</div>##{% endif %}</div></div>
<div class="day ##{{ d6_today }} ##{{ d6_wknd }}"><div class="d-name">##{{ d6_dow }}</div><div class="d-num">##{{ d6_dom }}</div>##{% if d6_hol != "" %}<div class="hol">##{{ d6_hol }}</div>##{% endif %}<div class="evts">##{% if d6_ev0 != "" %}<div class="ev">##{{ d6_ev0 }}</div>##{% endif %}##{% if d6_ev1 != "" %}<div class="ev">##{{ d6_ev1 }}</div>##{% endif %}##{% if d6_ev2 != "" %}<div class="ev">##{{ d6_ev2 }}</div>##{% endif %}##{% if d6_ev3 != "" %}<div class="ev">##{{ d6_ev3 }}</div>##{% endif %}##{% if d6_more > 0 %}<div class="more">+##{{ d6_more }} more</div>##{% endif %}</div></div>
</div>
```
