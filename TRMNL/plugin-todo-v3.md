# Plugin: Kaslo To Do (v3 — large fonts, 3-section layout)
#
# Layout: Urgent (top half, very large) | Important (bottom-left) | Rest (bottom-right)
# API now returns 3 buckets: urgent / important / rest
#
# Settings: Strategy: Polling | Verb: GET | Remove bleed margin: Yes
#
# IMPORTANT: Paste ONLY the HTML below (starting with <style>) into the
# TRMNL "Full" markup tab. Do NOT paste this markdown wrapper or the ``` fences.
#
# ── Data fix: if data still shows blank after saving ─────────────────────────
# TRMNL may have cached empty merge variables from before the API was fixed.
# To force a re-fetch: edit the polling URL to add &v=3 at the end, Save,
# Force Refresh. Then remove &v=3, Save, Force Refresh again.

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
.u-due{font-size:13px;font-weight:700;white-space:nowrap;margin-left:14px;flex-shrink:0}
.u-due.overdue{color:#000}
.u-due.soon{color:#666}
.no-urgent{font-size:20px;color:#ccc;font-style:italic}

.bottom{display:grid;grid-template-columns:1fr 1fr;flex:1;min-height:0}
.bcol{display:flex;flex-direction:column;padding:10px 14px;overflow:hidden}
.bcol+.bcol{border-left:2px solid #ddd}

.imp-list{display:flex;flex-direction:column;gap:6px;overflow:hidden;flex:1}
.i-item{}
.i-text{font-size:19px;font-weight:700;line-height:1.2;display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.i-due{font-size:11px;color:#777;margin-top:1px}

.rest-list{display:flex;flex-direction:column;gap:4px;overflow:hidden;flex:1}
.r-item{}
.r-text{font-size:15px;font-weight:500;line-height:1.3;display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.r-due{font-size:10px;color:#999;margin-top:1px}

.empty{font-size:14px;color:#ccc;font-style:italic}
</style>

<div class="hdr">
  <div class="hdr-t">To Do &middot; Kaslo</div>
  <div class="hdr-r">##{{ IDX_0.total }} open &middot; ##{{ trmnl.user.time | date: "%H:%M %a %-d" }}</div>
</div>

<div class="urgent-section">
  <div class="sec-lbl">Urgent</div>
  <div class="urgent-list">
    ##{% assign usz = IDX_0.urgent | size %}
    ##{% if usz == 0 %}
      <div class="no-urgent">Nothing urgent &mdash; good work</div>
    ##{% else %}
      ##{% for item in IDX_0.urgent limit:4 %}
        <div class="u-item">
          <span class="u-text">##{{ item.text }}</span>
          ##{% if item.due_label != "" %}
            ##{% assign dl = item.due_label %}
            ##{% if dl contains "overdue" %}
              <span class="u-due overdue">##{{ dl }}</span>
            ##{% else %}
              <span class="u-due soon">##{{ dl }}</span>
            ##{% endif %}
          ##{% endif %}
        </div>
      ##{% endfor %}
    ##{% endif %}
  </div>
</div>

<div class="bottom">
  <div class="bcol">
    <div class="sec-lbl">Important</div>
    <div class="imp-list">
      ##{% assign isz = IDX_0.important | size %}
      ##{% if isz == 0 %}
        <div class="empty">Nothing here</div>
      ##{% else %}
        ##{% for item in IDX_0.important limit:6 %}
          <div class="i-item">
            <span class="i-text">##{{ item.text }}</span>
            ##{% if item.due_label != "" %}<div class="i-due">##{{ item.due_label }}</div>##{% endif %}
          </div>
        ##{% endfor %}
      ##{% endif %}
    </div>
  </div>

  <div class="bcol">
    <div class="sec-lbl">Everything Else</div>
    <div class="rest-list">
      ##{% assign rsz = IDX_0.rest | size %}
      ##{% if rsz == 0 %}
        <div class="empty">All clear</div>
      ##{% else %}
        ##{% for item in IDX_0.rest limit:8 %}
          <div class="r-item">
            <span class="r-text">##{{ item.text }}</span>
            ##{% if item.due_label != "" %}<div class="r-due">##{{ item.due_label }}</div>##{% endif %}
          </div>
        ##{% endfor %}
      ##{% endif %}
    </div>
  </div>
</div>
```
