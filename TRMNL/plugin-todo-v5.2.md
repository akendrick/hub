# Plugin: Kaslo ToDo (v5 — left=Urgent, right split Important/Rest, zebra rows, tags)
#
# Polling URL:
#   https://knotwork.ca/todo-device-api.php?key=kw_40e818911c1980bcd56dc4aff37a820f811990a130eb771fd9e21a536edc55ea
#
# Variables: u0_text/u0_meta…u3 (urgent, up to 4)
#            i0_text/i0_meta…i5 (important, up to 6)
#            r0_text/r0_meta…r7 (rest, up to 8)
#            u_count, i_count, r_count, total
#            u_more, i_more, r_more  ('' or '+N more')
#            *_meta = pre-formatted "Due  ·  TAG1 TAG2" sub-line
#
# Layout:
#   LEFT  50% — URGENT (full height, large font)
#   RIGHT 50% — top half: IMPORTANT / bottom half: EVERYTHING ELSE
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

.hdr{height:46px;display:flex;justify-content:space-between;align-items:center;padding:0 14px;border-bottom:3px solid #000;flex-shrink:0}
.hdr-t{font-weight:800;font-size:15px;letter-spacing:.05em}
.hdr-r{font-size:12px;color:#666}

.body{display:flex;flex:1;min-height:0}

/* ── LEFT: Urgent ── */
.lcol{width:50%;display:flex;flex-direction:column;border-right:3px solid #000;overflow:hidden}

/* ── RIGHT: Important (top) + Everything Else (bottom) ── */
.rcol{width:50%;display:flex;flex-direction:column;overflow:hidden}
.rsec{flex:1;display:flex;flex-direction:column;min-height:0;overflow:hidden}
.rsec+.rsec{border-top:2px solid #000}

/* ── Section headers — all-caps, inverted ── */
.hd{font-size:18px;font-weight:900;text-transform:uppercase;letter-spacing:.15em;padding:5px 13px;flex-shrink:0;background:#111;color:#fff}

/* ── Item lists ── */
.ul,.il,.rl{display:flex;flex-direction:column;flex:1;min-height:0;overflow:hidden}

/* ── Urgent items ── */
.ui{flex:1 1 auto;min-height:32px;padding:4px 5px;display:flex;flex-direction:column;justify-content:center}
.ui.z{background:#f2f2f2}
.ut{font-size:46px;font-weight:800;line-height:1}
.um{font-size:14px;color:white;background-color:darkgray;font-weight:500;margin-top:3px}

/* ── Important items ── */
.ii{flex:1 1 auto;min-height:36px;padding:5px 13px;display:flex;flex-direction:column;justify-content:center}
.ii.z{background:#f2f2f2}
.it::first-line{font-size:36px;font-weight:600;line-height:1.25}
  .it{font-size:20;font-weight:300;}
.im{font-size:11px;color:#888;margin-top:2px}

/* ── Rest items ── */
.ri{flex:1 1 auto;min-height:28px;padding:4px 13px;display:flex;flex-direction:column;justify-content:center}
.ri.z{background:#f2f2f2}
.rt::first-line{font-size:36;font-weight:700;}
.rt{font-size:30px;font-weight:500;line-height:1.2}
.rm{font-size:10px;color:darkgray;margin-top:1px}

.empty{font-size:14px;color:#bbb;font-style:italic;padding:14px 13px}
.more{font-size:11px;color:#aaa;padding:3px 13px;flex-shrink:0}
</style>

<div class="hdr">
  <div class="hdr-t">TO DO &middot; KASLO</div>
  <div class="hdr-r">{{ total }} open &middot; {{ trmnl.user.time | date: "%H:%M %a %-d" }}</div>
</div>

<div class="body">

  <div class="lcol">
    <div class="hd">Urgent</div>
    <div class="ul">
      {% if u0_text %}
        <div class="ui"><div class="ut">{{ u0_text }}</div>{% if u0_meta %}<div class="um">{{ u0_meta }}</div>{% endif %}</div>
        {% if u1_text %}<div class="ui z"><div class="ut">{{ u1_text }}</div>{% if u1_meta %}<div class="um">{{ u1_meta }}</div>{% endif %}</div>{% endif %}
        {% if u2_text %}<div class="ui"><div class="ut">{{ u2_text }}</div>{% if u2_meta %}<div class="um">{{ u2_meta }}</div>{% endif %}</div>{% endif %}
        {% if u3_text %}<div class="ui z"><div class="ut">{{ u3_text }}</div>{% if u3_meta %}<div class="um">{{ u3_meta }}</div>{% endif %}</div>{% endif %}
        {% if u_more %}<div class="more">{{ u_more }}</div>{% endif %}
      {% else %}
        <div class="empty">Nothing urgent &mdash; good work</div>
      {% endif %}
    </div>
  </div>

  <div class="rcol">

    <div class="rsec">
      <div class="hd">Important</div>
      <div class="il">
        {% if i0_text %}
          <div class="ii"><div class="it">{{ i0_text }}</div>{% if i0_meta %}<div class="im">{{ i0_meta }}</div>{% endif %}</div>
          {% if i1_text %}<div class="ii z"><div class="it">{{ i1_text }}</div>{% if i1_meta %}<div class="im">{{ i1_meta }}</div>{% endif %}</div>{% endif %}
          {% if i2_text %}<div class="ii"><div class="it">{{ i2_text }}</div>{% if i2_meta %}<div class="im">{{ i2_meta }}</div>{% endif %}</div>{% endif %}
          {% if i3_text %}<div class="ii z"><div class="it">{{ i3_text }}</div>{% if i3_meta %}<div class="im">{{ i3_meta }}</div>{% endif %}</div>{% endif %}
          {% if i4_text %}<div class="ii"><div class="it">{{ i4_text }}</div>{% if i4_meta %}<div class="im">{{ i4_meta }}</div>{% endif %}</div>{% endif %}
          {% if i5_text %}<div class="ii z"><div class="it">{{ i5_text }}</div>{% if i5_meta %}<div class="im">{{ i5_meta }}</div>{% endif %}</div>{% endif %}
          {% if i_more %}<div class="more">{{ i_more }}</div>{% endif %}
        {% else %}
          <div class="empty">Nothing here</div>
        {% endif %}
      </div>
    </div>

    <div class="rsec">
      <div class="hd">Everything Else</div>
      <div class="rl">
        {% if r0_text %}
          <div class="ri"><div class="rt">{{ r0_text }}</div>{% if r0_meta %}<div class="rm">{{ r0_meta }}</div>{% endif %}</div>
          {% if r1_text %}<div class="ri z"><div class="rt">{{ r1_text }}</div>{% if r1_meta %}<div class="rm">{{ r1_meta }}</div>{% endif %}</div>{% endif %}
          {% if r2_text %}<div class="ri"><div class="rt">{{ r2_text }}</div>{% if r2_meta %}<div class="rm">{{ r2_meta }}</div>{% endif %}</div>{% endif %}
          {% if r3_text %}<div class="ri z"><div class="rt">{{ r3_text }}</div>{% if r3_meta %}<div class="rm">{{ r3_meta }}</div>{% endif %}</div>{% endif %}
          {% if r4_text %}<div class="ri"><div class="rt">{{ r4_text }}</div>{% if r4_meta %}<div class="rm">{{ r4_meta }}</div>{% endif %}</div>{% endif %}
          {% if r5_text %}<div class="ri z"><div class="rt">{{ r5_text }}</div>{% if r5_meta %}<div class="rm">{{ r5_meta }}</div>{% endif %}</div>{% endif %}
          {% if r6_text %}<div class="ri"><div class="rt">{{ r6_text }}</div>{% if r6_meta %}<div class="rm">{{ r6_meta }}</div>{% endif %}</div>{% endif %}
          {% if r7_text %}<div class="ri z"><div class="rt">{{ r7_text }}</div>{% if r7_meta %}<div class="rm">{{ r7_meta }}</div>{% endif %}</div>{% endif %}
          {% if r_more %}<div class="more">{{ r_more }}</div>{% endif %}
        {% else %}
          <div class="empty">All clear</div>
        {% endif %}
      </div>
    </div>

  </div>

</div>

```
