# Plugin: Kaslo Calendar (v3 — large top week, two smaller weeks below)
#
# API: cal-device-api.php now returns 21 days (WINDOW_DAYS = 21)
# Layout:
#   Top ~40%  — current week (days 0–6), large cells, event title + time
#   Mid ~30%  — next week (days 7–13), smaller cells, event names only
#   Bot ~30%  — week after (days 14–20), same as mid
#
# Settings: Strategy: Polling | Verb: GET | Remove bleed margin: Yes
#
# KEY FIX for no-data: uses  limit/offset  iteration instead of days[i]
# index notation — more reliable in TRMNL's Liquid engine.
#
# IMPORTANT: Paste ONLY the HTML below (starting with <style>) into the
# TRMNL "Full" markup tab. Do NOT paste this markdown wrapper or the ``` fences.
#
# ── Merge-variable cache fix ──────────────────────────────────────────────────
# If data still shows blank after saving: edit the polling URL, add &v=3,
# Save + Force Refresh. Then remove &v=3, Save + Force Refresh again.

## Markup (Full tab)

```html
<style>
*{box-sizing:border-box;margin:0;padding:0}
html,body{width:100vw;height:100vh;overflow:hidden;background:#fff;color:#000}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:11px;display:flex;flex-direction:column}

/* ── Header ───────────────────────────────────────────────────────────── */
.hdr{display:flex;justify-content:space-between;align-items:center;padding:0 12px;height:30px;border-bottom:2px solid #000;flex-shrink:0}
.hdr-t{font-weight:800;font-size:14px;letter-spacing:.04em}
.hdr-d{font-size:11px;color:#666}

/* ── Week blocks ──────────────────────────────────────────────────────── */
.wb{display:flex;flex-direction:column;min-height:0}
.wb.big{flex:2}
.wb.sml{flex:1.5}

.wlbl{padding:2px 10px;font-size:8px;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:#666;background:#eee;border-top:2px solid #999;border-bottom:1px solid #bbb;flex-shrink:0}

/* ── 7-column grid ────────────────────────────────────────────────────── */
.wk{display:grid;grid-template-columns:repeat(7,1fr);flex:1;min-height:0}

/* ── Day cell base ────────────────────────────────────────────────────── */
.day{border-right:2px solid #bbb;padding:4px 5px;overflow:hidden;display:flex;flex-direction:column}
.day:last-child{border-right:none}
.wb.big .wk{border-bottom:2px solid #999}

/* Weekend = light gray, Holiday = medium gray */
.day.wknd{background:#f4f4f4}
.day.hol{background:#e2e2e2}

/* ── Day header (name + number) ───────────────────────────────────────── */
.dh{display:flex;justify-content:space-between;align-items:baseline;flex-shrink:0;margin-bottom:3px}
.dn-label{font-size:8px;text-transform:uppercase;letter-spacing:.05em;color:#999}
.dn-num{font-size:16px;font-weight:800;line-height:1}

/* Today: underline the number */
.day.today .dn-num{text-decoration:underline}

/* Weekend dims the day name + number */
.day.wknd .dn-label,.day.wknd .dn-num{color:#aaa}

/* Holiday pill */
.hol-pill{font-size:8px;font-weight:700;background:#555;color:#fff;padding:1px 4px;margin-bottom:3px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;flex-shrink:0}

/* ── Events in the BIG top row ─────────────────────────────────────────── */
.wb.big .ev{margin-bottom:3px;overflow:hidden}
.wb.big .ev-title{font-size:11px;font-weight:600;display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;line-height:1.3;border-left:2px solid #000;padding-left:3px}
.wb.big .ev-title.personal2{border-left-color:#888}
.wb.big .ev-title.personal3{border-left-color:#bbb}
.wb.big .ev-time{font-size:9px;color:#999;display:block;padding-left:5px}
.wb.big .ev-more{font-size:8px;color:#bbb;margin-top:1px}

/* ── Events in SMALLER rows — name only, no tiny metadata ─────────────── */
.wb.sml .day{padding:3px 4px}
.wb.sml .dh{margin-bottom:2px}
.wb.sml .dn-label{font-size:7px}
.wb.sml .dn-num{font-size:12px}
.wb.sml .hol-pill{font-size:7px;margin-bottom:2px}
.wb.sml .ev-name{font-size:9px;font-weight:500;line-height:1.25;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-bottom:1px;padding-left:2px;border-left:1px solid #aaa}
.wb.sml .ev-more{font-size:7px;color:#bbb}
</style>

<div class="hdr">
  <div class="hdr-t">Calendar &middot; Kaslo</div>
  <div class="hdr-d">##{{ trmnl.user.time | date: "%H:%M &middot; %a %b %-d" }}</div>
</div>

<!-- ── THIS WEEK (large) ─────────────────────────────────────────────────── -->
<div class="wb big">
  <div class="wlbl">This week</div>
  <div class="wk">
    ##{% for d in IDX_0.days limit:7 %}
    <div class="day##{% if d.is_today %} today##{% endif %}##{% if d.is_weekend %} wknd##{% endif %}##{% if d.holiday != "" %} hol##{% endif %}">
      <div class="dh">
        <span class="dn-label">##{{ d.dow }}</span>
        <span class="dn-num">##{{ d.dom }}</span>
      </div>
      ##{% if d.holiday != "" %}<div class="hol-pill">##{{ d.holiday }}</div>##{% endif %}
      ##{% assign ec = d.events | size %}
      ##{% for ev in d.events limit:3 %}
        <div class="ev">
          <span class="ev-title ##{{ ev.cal }}">##{{ ev.summary | truncate:20 }}</span>
          ##{% if ev.time %}<span class="ev-time">##{{ ev.time }}</span>##{% endif %}
        </div>
      ##{% endfor %}
      ##{% if ec > 3 %}<div class="ev-more">+##{{ ec | minus:3 }}</div>##{% endif %}
    </div>
    ##{% endfor %}
  </div>
</div>

<!-- ── NEXT WEEK (smaller) ───────────────────────────────────────────────── -->
<div class="wb sml">
  <div class="wlbl">Next week</div>
  <div class="wk">
    ##{% for d in IDX_0.days limit:7 offset:7 %}
    <div class="day##{% if d.is_weekend %} wknd##{% endif %}##{% if d.holiday != "" %} hol##{% endif %}">
      <div class="dh">
        <span class="dn-label">##{{ d.dow }}</span>
        <span class="dn-num">##{{ d.dom }}</span>
      </div>
      ##{% if d.holiday != "" %}<div class="hol-pill">##{{ d.holiday }}</div>##{% endif %}
      ##{% assign ec = d.events | size %}
      ##{% for ev in d.events limit:3 %}
        <div class="ev-name">##{{ ev.summary | truncate:16 }}</div>
      ##{% endfor %}
      ##{% if ec > 3 %}<div class="ev-more">+##{{ ec | minus:3 }}</div>##{% endif %}
    </div>
    ##{% endfor %}
  </div>
</div>

<!-- ── WEEK AFTER (smaller) ─────────────────────────────────────────────── -->
<div class="wb sml">
  <div class="wlbl">##{{ IDX_0.days[14].dow | capitalize }} ##{{ IDX_0.days[14].dom }} &ndash; ##{{ IDX_0.days[20].dow | capitalize }} ##{{ IDX_0.days[20].dom }}</div>
  <div class="wk">
    ##{% for d in IDX_0.days limit:7 offset:14 %}
    <div class="day##{% if d.is_weekend %} wknd##{% endif %}##{% if d.holiday != "" %} hol##{% endif %}">
      <div class="dh">
        <span class="dn-label">##{{ d.dow }}</span>
        <span class="dn-num">##{{ d.dom }}</span>
      </div>
      ##{% if d.holiday != "" %}<div class="hol-pill">##{{ d.holiday }}</div>##{% endif %}
      ##{% assign ec = d.events | size %}
      ##{% for ev in d.events limit:3 %}
        <div class="ev-name">##{{ ev.summary | truncate:16 }}</div>
      ##{% endfor %}
      ##{% if ec > 3 %}<div class="ev-more">+##{{ ec | minus:3 }}</div>##{% endif %}
    </div>
    ##{% endfor %}
  </div>
</div>
```
