# Plugin: Kaslo Calendar (v6 — vertical rows, 3-section: Day / Events / Reminders)
#
# Polling URL:
#   https://knotwork.ca/cal-device-api.php?key=kw_40e818911c1980bcd56dc4aff37a820f811990a130eb771fd9e21a536edc55ea
#
# Layout: 7 rows × 3 columns
#   LEFT  16% — day name + large date number
#   MID   55% — timed events: TIME | EVENT NAME (two sub-columns, large font)
#   RIGHT 29% — all-day events + holidays
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

.week{display:flex;flex-direction:column;flex:1;min-height:0}

.row{display:grid;grid-template-columns:21% 50% 29%;min-height:0;flex:1;border-bottom:1px solid #ccc}
.row:last-child{border-bottom:none}
.row.today{background:#e8e8e8}

/* ── DAY column ── */
.dc{display:flex;flex-direction:column;justify-content:center;align-items:flex-start;padding:2px 8px;min-width:0;overflow:hidden}
.d-name{font-size:22px;font-weight:900;text-transform:uppercase;letter-spacing:.06em;color:#bbb;line-height:1;flex-shrink:0}
.row.today .d-name{color:#555}
.d-num{font-size:58px;font-weight:200;line-height:.95;flex-shrink:0}
.row.today .d-num{font-weight:800}

/* Weekend: invert only the date block; rest of row slightly gray */
.row.wknd .dc{background:#000}
.row.wknd .d-name{color:#888}
.row.wknd .d-num{color:#fff;font-weight:900}
.row.wknd .ec,.row.wknd .rc{background:#f0f0f0}

/* ── EVENTS column — two sub-columns: time | name ── */
.ec{display:flex;flex-direction:column;justify-content:center;padding:4px 10px;min-width:0;overflow:hidden;gap:3px}
.tev{display:grid;grid-template-columns:4.5em 1fr;align-items:baseline;gap:0 10px;min-width:0}
.tev-t{font-size:18px;font-weight:300;color:#999;white-space:nowrap;flex-shrink:0}
.tev-n{font-size:26px;font-weight:800;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;min-width:0}
.tev-more{font-size:11px;color:#bbb;padding-left:5.5em}

/* ── REMINDERS column ── */
.rc{display:flex;flex-direction:column;justify-content:center;padding:4px 10px;min-width:0;overflow:hidden;gap:3px;border-left:none}
.hol{font-size:11px;font-weight:800;text-transform:uppercase;background:#222;color:#fff;padding:2px 6px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;align-self:flex-start;flex-shrink:0}
.aev{font-size:14px;font-weight:500;color:#444;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;line-height:1.3}
.aev-more{font-size:11px;color:#bbb}
</style>

<div class="hdr">
  <div class="hdr-t">CALENDAR &middot; KASLO</div>
  <div class="hdr-r">##{{ trmnl.user.time | date: "%H:%M &middot; %a %b %-d" }}</div>
</div>

<div class="week">
<div class="row ##{{ d0_today }} ##{{ d0_wknd }}"><div class="dc"><div class="d-name">##{{ d0_dow }}</div><div class="d-num">##{{ d0_dom }}</div></div><div class="ec">##{% if d0_tev0n %}<div class="tev"><span class="tev-t">##{{ d0_tev0t }}</span><span class="tev-n">##{{ d0_tev0n }}</span></div>##{% endif %}##{% if d0_tev1n %}<div class="tev"><span class="tev-t">##{{ d0_tev1t }}</span><span class="tev-n">##{{ d0_tev1n }}</span></div>##{% endif %}##{% if d0_tmore %}<div class="tev-more">##{{ d0_tmore }}</div>##{% endif %}</div><div class="rc">##{% if d0_hol %}<div class="hol">##{{ d0_hol }}</div>##{% endif %}##{% if d0_aev0 %}<div class="aev">##{{ d0_aev0 }}</div>##{% endif %}##{% if d0_aev1 %}<div class="aev">##{{ d0_aev1 }}</div>##{% endif %}##{% if d0_aev2 %}<div class="aev">##{{ d0_aev2 }}</div>##{% endif %}##{% if d0_amore %}<div class="aev-more">##{{ d0_amore }}</div>##{% endif %}</div></div>
<div class="row ##{{ d1_today }} ##{{ d1_wknd }}"><div class="dc"><div class="d-name">##{{ d1_dow }}</div><div class="d-num">##{{ d1_dom }}</div></div><div class="ec">##{% if d1_tev0n %}<div class="tev"><span class="tev-t">##{{ d1_tev0t }}</span><span class="tev-n">##{{ d1_tev0n }}</span></div>##{% endif %}##{% if d1_tev1n %}<div class="tev"><span class="tev-t">##{{ d1_tev1t }}</span><span class="tev-n">##{{ d1_tev1n }}</span></div>##{% endif %}##{% if d1_tmore %}<div class="tev-more">##{{ d1_tmore }}</div>##{% endif %}</div><div class="rc">##{% if d1_hol %}<div class="hol">##{{ d1_hol }}</div>##{% endif %}##{% if d1_aev0 %}<div class="aev">##{{ d1_aev0 }}</div>##{% endif %}##{% if d1_aev1 %}<div class="aev">##{{ d1_aev1 }}</div>##{% endif %}##{% if d1_aev2 %}<div class="aev">##{{ d1_aev2 }}</div>##{% endif %}##{% if d1_amore %}<div class="aev-more">##{{ d1_amore }}</div>##{% endif %}</div></div>
<div class="row ##{{ d2_today }} ##{{ d2_wknd }}"><div class="dc"><div class="d-name">##{{ d2_dow }}</div><div class="d-num">##{{ d2_dom }}</div></div><div class="ec">##{% if d2_tev0n %}<div class="tev"><span class="tev-t">##{{ d2_tev0t }}</span><span class="tev-n">##{{ d2_tev0n }}</span></div>##{% endif %}##{% if d2_tev1n %}<div class="tev"><span class="tev-t">##{{ d2_tev1t }}</span><span class="tev-n">##{{ d2_tev1n }}</span></div>##{% endif %}##{% if d2_tmore %}<div class="tev-more">##{{ d2_tmore }}</div>##{% endif %}</div><div class="rc">##{% if d2_hol %}<div class="hol">##{{ d2_hol }}</div>##{% endif %}##{% if d2_aev0 %}<div class="aev">##{{ d2_aev0 }}</div>##{% endif %}##{% if d2_aev1 %}<div class="aev">##{{ d2_aev1 }}</div>##{% endif %}##{% if d2_aev2 %}<div class="aev">##{{ d2_aev2 }}</div>##{% endif %}##{% if d2_amore %}<div class="aev-more">##{{ d2_amore }}</div>##{% endif %}</div></div>
<div class="row ##{{ d3_today }} ##{{ d3_wknd }}"><div class="dc"><div class="d-name">##{{ d3_dow }}</div><div class="d-num">##{{ d3_dom }}</div></div><div class="ec">##{% if d3_tev0n %}<div class="tev"><span class="tev-t">##{{ d3_tev0t }}</span><span class="tev-n">##{{ d3_tev0n }}</span></div>##{% endif %}##{% if d3_tev1n %}<div class="tev"><span class="tev-t">##{{ d3_tev1t }}</span><span class="tev-n">##{{ d3_tev1n }}</span></div>##{% endif %}##{% if d3_tmore %}<div class="tev-more">##{{ d3_tmore }}</div>##{% endif %}</div><div class="rc">##{% if d3_hol %}<div class="hol">##{{ d3_hol }}</div>##{% endif %}##{% if d3_aev0 %}<div class="aev">##{{ d3_aev0 }}</div>##{% endif %}##{% if d3_aev1 %}<div class="aev">##{{ d3_aev1 }}</div>##{% endif %}##{% if d3_aev2 %}<div class="aev">##{{ d3_aev2 }}</div>##{% endif %}##{% if d3_amore %}<div class="aev-more">##{{ d3_amore }}</div>##{% endif %}</div></div>
<div class="row ##{{ d4_today }} ##{{ d4_wknd }}"><div class="dc"><div class="d-name">##{{ d4_dow }}</div><div class="d-num">##{{ d4_dom }}</div></div><div class="ec">##{% if d4_tev0n %}<div class="tev"><span class="tev-t">##{{ d4_tev0t }}</span><span class="tev-n">##{{ d4_tev0n }}</span></div>##{% endif %}##{% if d4_tev1n %}<div class="tev"><span class="tev-t">##{{ d4_tev1t }}</span><span class="tev-n">##{{ d4_tev1n }}</span></div>##{% endif %}##{% if d4_tmore %}<div class="tev-more">##{{ d4_tmore }}</div>##{% endif %}</div><div class="rc">##{% if d4_hol %}<div class="hol">##{{ d4_hol }}</div>##{% endif %}##{% if d4_aev0 %}<div class="aev">##{{ d4_aev0 }}</div>##{% endif %}##{% if d4_aev1 %}<div class="aev">##{{ d4_aev1 }}</div>##{% endif %}##{% if d4_aev2 %}<div class="aev">##{{ d4_aev2 }}</div>##{% endif %}##{% if d4_amore %}<div class="aev-more">##{{ d4_amore }}</div>##{% endif %}</div></div>
<div class="row ##{{ d5_today }} ##{{ d5_wknd }}"><div class="dc"><div class="d-name">##{{ d5_dow }}</div><div class="d-num">##{{ d5_dom }}</div></div><div class="ec">##{% if d5_tev0n %}<div class="tev"><span class="tev-t">##{{ d5_tev0t }}</span><span class="tev-n">##{{ d5_tev0n }}</span></div>##{% endif %}##{% if d5_tev1n %}<div class="tev"><span class="tev-t">##{{ d5_tev1t }}</span><span class="tev-n">##{{ d5_tev1n }}</span></div>##{% endif %}##{% if d5_tmore %}<div class="tev-more">##{{ d5_tmore }}</div>##{% endif %}</div><div class="rc">##{% if d5_hol %}<div class="hol">##{{ d5_hol }}</div>##{% endif %}##{% if d5_aev0 %}<div class="aev">##{{ d5_aev0 }}</div>##{% endif %}##{% if d5_aev1 %}<div class="aev">##{{ d5_aev1 }}</div>##{% endif %}##{% if d5_aev2 %}<div class="aev">##{{ d5_aev2 }}</div>##{% endif %}##{% if d5_amore %}<div class="aev-more">##{{ d5_amore }}</div>##{% endif %}</div></div>
<div class="row ##{{ d6_today }} ##{{ d6_wknd }}"><div class="dc"><div class="d-name">##{{ d6_dow }}</div><div class="d-num">##{{ d6_dom }}</div></div><div class="ec">##{% if d6_tev0n %}<div class="tev"><span class="tev-t">##{{ d6_tev0t }}</span><span class="tev-n">##{{ d6_tev0n }}</span></div>##{% endif %}##{% if d6_tev1n %}<div class="tev"><span class="tev-t">##{{ d6_tev1t }}</span><span class="tev-n">##{{ d6_tev1n }}</span></div>##{% endif %}##{% if d6_tmore %}<div class="tev-more">##{{ d6_tmore }}</div>##{% endif %}</div><div class="rc">##{% if d6_hol %}<div class="hol">##{{ d6_hol }}</div>##{% endif %}##{% if d6_aev0 %}<div class="aev">##{{ d6_aev0 }}</div>##{% endif %}##{% if d6_aev1 %}<div class="aev">##{{ d6_aev1 }}</div>##{% endif %}##{% if d6_aev2 %}<div class="aev">##{{ d6_aev2 }}</div>##{% endif %}##{% if d6_amore %}<div class="aev-more">##{{ d6_amore }}</div>##{% endif %}</div></div>
</div>
```
