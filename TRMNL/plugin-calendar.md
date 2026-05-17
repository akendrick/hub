# Plugin 3: Kaslo Calendar
**Strategy:** Polling · **Remove Bleed Margin:** ✅

Requires `cal-device-api.php` deployed on knotwork.ca (see separate file).

## Polling URL
```
https://knotwork.ca/cal-device-api.php?key=YOUR_DEVICE_KEY
```
- `IDX_0` → `{ days: [ {date, dow, dom, is_today, is_weekend, holiday, events[]}, … ] }`
- `IDX_0.days[0]` = today, `IDX_0.days[13]` = 13 days from now

---

## Markup

Two-week grid — 7 columns, 2 rows of cells, events listed inside each cell.

```html
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html,body{width:800px;height:480px;overflow:hidden;background:#fff;color:#000}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Arial,sans-serif;font-size:12px;display:flex;flex-direction:column}

/* ── Header ── */
.hdr{display:flex;justify-content:space-between;align-items:center;padding:0 12px;height:28px;border-bottom:2px solid #000;flex-shrink:0}
.hdr-title{font-weight:700;font-size:13px;letter-spacing:.04em}
.hdr-time{font-size:11px;color:#555}

/* ── Two-week body ── */
.weeks{display:flex;flex-direction:column;flex:1;min-height:0}

/* Week separator label */
.week-lbl{padding:2px 10px;font-size:8px;text-transform:uppercase;letter-spacing:.1em;color:#bbb;background:#fafafa;border-bottom:1px solid #eee;border-top:1px solid #eee;flex-shrink:0}

/* 7-column grid row */
.week{display:grid;grid-template-columns:repeat(7,1fr);flex:1;min-height:0}

/* Day cell */
.day{border-right:1px solid #e8e8e8;border-bottom:1px solid #e8e8e8;padding:3px 5px;display:flex;flex-direction:column;overflow:hidden;min-height:0}
.day:last-child{border-right:none}
.day:nth-child(n+8){border-bottom:none} /* last row */

/* Today highlight */
.day.today{background:#f5f5f5}
.day.today .day-num{font-weight:800;text-decoration:underline}

/* Weekend */
.day.weekend .day-name,.day.weekend .day-num{color:#999}

/* Holiday: invert the day number */
.day.holiday .day-num{background:#000;color:#fff;padding:0 2px;border-radius:1px}

/* Day header */
.day-head{display:flex;justify-content:space-between;align-items:baseline;margin-bottom:3px;flex-shrink:0}
.day-name{font-size:8px;text-transform:uppercase;letter-spacing:.06em;color:#aaa}
.day-num{font-size:13px;font-weight:700;line-height:1}

/* Events */
.ev{font-size:9px;line-height:1.35;margin-bottom:1px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;border-left:2px solid #000;padding-left:3px}
.ev.personal {border-left-color:#000}
.ev.personal2{border-left-color:#666}
.ev.personal3{border-left-color:#bbb}
.ev-time{color:#888;margin-right:2px}
.hol-lbl{font-size:8px;color:#fff;background:#000;padding:0 3px;border-radius:1px;margin-bottom:2px;display:inline-block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100%}
.more{font-size:8px;color:#aaa;font-style:italic}
</style>

<div class="hdr">
  <div class="hdr-title">Calendar &middot; Kaslo</div>
  <div class="hdr-time">##{{ trmnl.user.time | date: "%H:%M &middot; %a %b %-d" }}</div>
</div>

<div class="weeks">

  ##{% comment %} ── Week 1: days 0–6 ── ##{% endcomment %}
  <div class="week-lbl">This week</div>
  <div class="week">
    ##{% for i in (0..6) %}
      ##{% assign day = IDX_0.days[i] %}
      <div class="day##{% if day.is_today %} today##{% endif %}##{% if day.is_weekend %} weekend##{% endif %}##{% if day.holiday != "" %} holiday##{% endif %}">
        <div class="day-head">
          <span class="day-name">##{{ day.dow }}</span>
          <span class="day-num">##{{ day.dom }}</span>
        </div>
        ##{% if day.holiday != "" %}
          <div class="hol-lbl">##{{ day.holiday | truncate: 14 }}</div>
        ##{% endif %}
        ##{% assign evcount = day.events | size %}
        ##{% for ev in day.events limit: 3 %}
          <div class="ev ##{{ ev.cal }}">
            ##{% if ev.time %}<span class="ev-time">##{{ ev.time }}</span>##{% endif %}##{{ ev.summary | truncate: 20 }}
          </div>
        ##{% endfor %}
        ##{% if evcount > 3 %}
          <div class="more">+##{{ evcount | minus: 3 }} more</div>
        ##{% endif %}
      </div>
    ##{% endfor %}
  </div>

  ##{% comment %} ── Week 2: days 7–13 ── ##{% endcomment %}
  <div class="week-lbl">Next week</div>
  <div class="week">
    ##{% for i in (7..13) %}
      ##{% assign day = IDX_0.days[i] %}
      <div class="day##{% if day.is_today %} today##{% endif %}##{% if day.is_weekend %} weekend##{% endif %}##{% if day.holiday != "" %} holiday##{% endif %}">
        <div class="day-head">
          <span class="day-name">##{{ day.dow }}</span>
          <span class="day-num">##{{ day.dom }}</span>
        </div>
        ##{% if day.holiday != "" %}
          <div class="hol-lbl">##{{ day.holiday | truncate: 14 }}</div>
        ##{% endif %}
        ##{% assign evcount = day.events | size %}
        ##{% for ev in day.events limit: 3 %}
          <div class="ev ##{{ ev.cal }}">
            ##{% if ev.time %}<span class="ev-time">##{{ ev.time }}</span>##{% endif %}##{{ ev.summary | truncate: 20 }}
          </div>
        ##{% endfor %}
        ##{% if evcount > 3 %}
          <div class="more">+##{{ evcount | minus: 3 }} more</div>
        ##{% endif %}
      </div>
    ##{% endfor %}
  </div>

</div>
```

---

## Notes

**Event border colour** — three shades for three calendars:
- `personal` → solid black left border
- `personal2` → mid-grey
- `personal3` → light grey

**Timed events** show `HH:MM` prefix; all-day events show summary only.

**Multi-day events** (e.g. a trip spanning several days) appear on every day
they span within the 14-day window — the PHP handles the expansion.

**Holidays** invert the day number (white-on-black) and show a label pill above
events. The BC holiday dates in `cal-device-api.php` are hardcoded for 2026 —
update them each January.

**iCloud fetch** — PHP fetches the iCloud URLs server-side (no CORS), using a
browser User-Agent to avoid 403s. If a feed fails, the rest still load.
