# Plugin: Kaslo To Do (v2 — full-screen fill)
#
# Settings: Strategy: Polling | Verb: GET | Remove bleed margin: Yes
#
# IMPORTANT: Paste ONLY the HTML below (starting with <style>) into the
# TRMNL "Full" markup tab. Do NOT paste this markdown wrapper or the ``` fences.

## Markup (Full tab)

```html
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html,body{width:100vw;height:100vh;overflow:hidden;background:#fff;color:#000}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Arial,sans-serif;font-size:12px;display:flex;flex-direction:column}

.hdr{display:flex;justify-content:space-between;align-items:center;padding:0 14px;height:30px;border-bottom:2px solid #000;flex-shrink:0}
.hdr-title{font-weight:700;font-size:13px;letter-spacing:.04em}
.hdr-count{font-size:11px;color:#888}
.hdr-time{font-size:11px;color:#555}

.cols{display:grid;grid-template-columns:1fr 1fr;flex:1;min-height:0}
.tcol{display:flex;flex-direction:column;overflow:hidden}
.tcol+.tcol{border-left:1px solid #ccc}

.col-hdr{padding:6px 14px 5px;border-bottom:1px solid #e8e8e8;font-size:9px;text-transform:uppercase;letter-spacing:.1em;color:#aaa;flex-shrink:0}

.items{padding:8px 14px;overflow:hidden;flex:1}

.item{margin-bottom:9px;line-height:1.3}
.item-text{font-size:13px;font-weight:600;display:block;color:#000}
.item-text.p1{font-weight:800}
.item-text.p2{font-weight:700}
.item-text.p4,.item-text.p5{color:#555;font-weight:400}
.item-meta{margin-top:1px;display:flex;gap:6px;align-items:center;flex-wrap:wrap}
.due{font-size:10px;font-weight:600}
.due.overdue{color:#c00}
.due.today{color:#d06000}
.due.tomorrow{color:#d06000}
.due.soon{color:#888}
.tag{font-size:8px;font-weight:700;letter-spacing:.04em;background:#111;color:#fff;padding:1px 4px;border-radius:1px;text-transform:uppercase}

.empty{font-size:11px;color:#ccc;font-style:italic;padding:2px 0}
</style>

<div class="hdr">
  <div class="hdr-title">To Do &middot; Kaslo</div>
  <div class="hdr-count">##{{ IDX_0.total }} open</div>
  <div class="hdr-time">##{{ trmnl.user.time | date: "%H:%M &middot; %a %b %-d" }}</div>
</div>

<div class="cols">

  <div class="tcol">
    <div class="col-hdr">Urgent / Soon</div>
    <div class="items">
      ##{% if IDX_0.urgent.size == 0 %}
        <div class="empty">Nothing urgent ✓</div>
      ##{% else %}
        ##{% for item in IDX_0.urgent limit: 10 %}
          <div class="item">
            <span class="item-text p##{{ item.priority }}">##{{ item.text | truncate: 40 }}</span>
            <div class="item-meta">
              ##{% if item.due_label != "" %}
                ##{% assign dl = item.due_label %}
                ##{% if dl contains "overdue" %}<span class="due overdue">##{{ dl }}</span>
                ##{% elsif dl == "Today" %}<span class="due today">##{{ dl }}</span>
                ##{% elsif dl == "Tomorrow" %}<span class="due tomorrow">##{{ dl }}</span>
                ##{% else %}<span class="due soon">##{{ dl }}</span>
                ##{% endif %}
              ##{% endif %}
              ##{% for tag in item.tags %}<span class="tag">##{{ tag }}</span>##{% endfor %}
            </div>
          </div>
        ##{% endfor %}
      ##{% endif %}
    </div>
  </div>

  <div class="tcol">
    <div class="col-hdr">Everything Else</div>
    <div class="items">
      ##{% if IDX_0.rest.size == 0 %}
        <div class="empty">All clear ✓</div>
      ##{% else %}
        ##{% for item in IDX_0.rest limit: 12 %}
          <div class="item">
            <span class="item-text p##{{ item.priority }}">##{{ item.text | truncate: 40 }}</span>
            ##{% if item.due_label != "" or item.tags.size > 0 %}
              <div class="item-meta">
                ##{% if item.due_label != "" %}<span class="due soon">##{{ item.due_label }}</span>##{% endif %}
                ##{% for tag in item.tags %}<span class="tag">##{{ tag }}</span>##{% endfor %}
              </div>
            ##{% endif %}
          </div>
        ##{% endfor %}
      ##{% endif %}
    </div>
  </div>

</div>
```
