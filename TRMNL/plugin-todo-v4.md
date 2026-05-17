# Plugin: Kaslo ToDo (v4 — flat top-level vars, no IDX_0, no loops)
#
# Polling URL:
#   https://knotwork.ca/todo-device-api.php?key=kw_40e818911c1980bcd56dc4aff37a820f811990a130eb771fd9e21a536edc55ea
#
# Variables: u0_text/u0_due…u3 (urgent), i0_text/i0_due…i5 (important),
#            r0_text/r0_due…r7 (rest), u_count, i_count, r_count, total
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

.hdr{display:flex;justify-content:space-between;align-items:center;padding:0 16px;height:38px;border-bottom:3px solid #000;flex-shrink:0}
.hdr-t{font-weight:800;font-size:17px;letter-spacing:.04em}
.hdr-r{font-size:13px;color:#666}

.urgent-section{flex:0 0 44%;border-bottom:2px solid #000;padding:10px 16px 8px;overflow:hidden;display:flex;flex-direction:column}
.sec-lbl{font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.12em;color:#aaa;margin-bottom:8px;flex-shrink:0}
.urgent-list{display:flex;flex-direction:column;gap:5px;overflow:hidden}
.u-item{display:flex;justify-content:space-between;align-items:baseline;overflow:hidden}
.u-text{font-size:26px;font-weight:800;line-height:1.1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;flex:1}
.u-due{font-size:13px;font-weight:700;white-space:nowrap;margin-left:14px;flex-shrink:0;color:#666}
.no-urgent{font-size:20px;color:#ccc;font-style:italic}

.bottom{display:grid;grid-template-columns:1fr 1fr;flex:1;min-height:0}
.bcol{display:flex;flex-direction:column;padding:10px 14px;overflow:hidden}
.bcol+.bcol{border-left:2px solid #ddd}

.i-text{font-size:19px;font-weight:700;line-height:1.2;display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.i-due{font-size:11px;color:#777;margin-top:1px;margin-bottom:5px}

.r-text{font-size:15px;font-weight:500;line-height:1.3;display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.r-due{font-size:10px;color:#999;margin-top:1px;margin-bottom:4px}

.empty{font-size:14px;color:#ccc;font-style:italic}
.more{font-size:11px;color:#bbb;margin-top:4px}
</style>

<div class="hdr">
  <div class="hdr-t">To Do &middot; Kaslo</div>
  <div class="hdr-r">##{{ total }} open &middot; ##{{ trmnl.user.time | date: "%H:%M %a %-d" }}</div>
</div>

<div class="urgent-section">
  <div class="sec-lbl">Urgent</div>
  <div class="urgent-list">
    ##{% if u_count == 0 %}
      <div class="no-urgent">Nothing urgent &mdash; good work</div>
    ##{% else %}
      ##{% if u0_text != "" %}<div class="u-item"><span class="u-text">##{{ u0_text }}</span>##{% if u0_due != "" %}<span class="u-due">##{{ u0_due }}</span>##{% endif %}</div>##{% endif %}
      ##{% if u1_text != "" %}<div class="u-item"><span class="u-text">##{{ u1_text }}</span>##{% if u1_due != "" %}<span class="u-due">##{{ u1_due }}</span>##{% endif %}</div>##{% endif %}
      ##{% if u2_text != "" %}<div class="u-item"><span class="u-text">##{{ u2_text }}</span>##{% if u2_due != "" %}<span class="u-due">##{{ u2_due }}</span>##{% endif %}</div>##{% endif %}
      ##{% if u3_text != "" %}<div class="u-item"><span class="u-text">##{{ u3_text }}</span>##{% if u3_due != "" %}<span class="u-due">##{{ u3_due }}</span>##{% endif %}</div>##{% endif %}
      ##{% if u_more > 0 %}<div class="more">+##{{ u_more }} more</div>##{% endif %}
    ##{% endif %}
  </div>
</div>

<div class="bottom">
  <div class="bcol">
    <div class="sec-lbl">Important</div>
    ##{% if i_count == 0 %}
      <div class="empty">Nothing here</div>
    ##{% else %}
      ##{% if i0_text != "" %}<span class="i-text">##{{ i0_text }}</span>##{% if i0_due != "" %}<div class="i-due">##{{ i0_due }}</div>##{% endif %}##{% endif %}
      ##{% if i1_text != "" %}<span class="i-text">##{{ i1_text }}</span>##{% if i1_due != "" %}<div class="i-due">##{{ i1_due }}</div>##{% endif %}##{% endif %}
      ##{% if i2_text != "" %}<span class="i-text">##{{ i2_text }}</span>##{% if i2_due != "" %}<div class="i-due">##{{ i2_due }}</div>##{% endif %}##{% endif %}
      ##{% if i3_text != "" %}<span class="i-text">##{{ i3_text }}</span>##{% if i3_due != "" %}<div class="i-due">##{{ i3_due }}</div>##{% endif %}##{% endif %}
      ##{% if i4_text != "" %}<span class="i-text">##{{ i4_text }}</span>##{% if i4_due != "" %}<div class="i-due">##{{ i4_due }}</div>##{% endif %}##{% endif %}
      ##{% if i5_text != "" %}<span class="i-text">##{{ i5_text }}</span>##{% if i5_due != "" %}<div class="i-due">##{{ i5_due }}</div>##{% endif %}##{% endif %}
      ##{% if i_more > 0 %}<div class="more">+##{{ i_more }} more</div>##{% endif %}
    ##{% endif %}
  </div>

  <div class="bcol">
    <div class="sec-lbl">Everything Else</div>
    ##{% if r_count == 0 %}
      <div class="empty">All clear</div>
    ##{% else %}
      ##{% if r0_text != "" %}<span class="r-text">##{{ r0_text }}</span>##{% if r0_due != "" %}<div class="r-due">##{{ r0_due }}</div>##{% endif %}##{% endif %}
      ##{% if r1_text != "" %}<span class="r-text">##{{ r1_text }}</span>##{% if r1_due != "" %}<div class="r-due">##{{ r1_due }}</div>##{% endif %}##{% endif %}
      ##{% if r2_text != "" %}<span class="r-text">##{{ r2_text }}</span>##{% if r2_due != "" %}<div class="r-due">##{{ r2_due }}</div>##{% endif %}##{% endif %}
      ##{% if r3_text != "" %}<span class="r-text">##{{ r3_text }}</span>##{% if r3_due != "" %}<div class="r-due">##{{ r3_due }}</div>##{% endif %}##{% endif %}
      ##{% if r4_text != "" %}<span class="r-text">##{{ r4_text }}</span>##{% if r4_due != "" %}<div class="r-due">##{{ r4_due }}</div>##{% endif %}##{% endif %}
      ##{% if r5_text != "" %}<span class="r-text">##{{ r5_text }}</span>##{% if r5_due != "" %}<div class="r-due">##{{ r5_due }}</div>##{% endif %}##{% endif %}
      ##{% if r6_text != "" %}<span class="r-text">##{{ r6_text }}</span>##{% if r6_due != "" %}<div class="r-due">##{{ r6_due }}</div>##{% endif %}##{% endif %}
      ##{% if r7_text != "" %}<span class="r-text">##{{ r7_text }}</span>##{% if r7_due != "" %}<div class="r-due">##{{ r7_due }}</div>##{% endif %}##{% endif %}
      ##{% if r_more > 0 %}<div class="more">+##{{ r_more }} more</div>##{% endif %}
    ##{% endif %}
  </div>
</div>
```
