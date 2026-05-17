# Plugin 4: Go Games · DGS

## Files to deploy to knotwork.ca
- `dgs-proxy.php` — drop next to `kaslo-weather.php`, set `DEVICE_KEY`

---

## Plugin Settings

**Name:** Go Games · DGS

**Strategy:** Polling

**Polling URL(s):**
```
https://knotwork.ca/dgs-proxy.php?key=YOUR_DEVICE_KEY
```
`IDX_0` → `{ user, my_turn[], their_turn[], total, my_turn_count }`

**Polling verb:** GET

**Polling headers:** *(blank)*

**Polling body:** *(blank)*

**Enable OAuth:** No

**Form fields:** *(blank)*

**Remove bleed margin:** Yes

**Enable dark mode:** No

**Framework CSS version:** Latest

---

## Markup

Paste into the **Full** tab of Edit Markup.

```html
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html,body{width:800px;height:480px;overflow:hidden;background:#fff;color:#000}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Arial,sans-serif;font-size:12px;display:flex;flex-direction:column}

/* ── Header ── */
.hdr{display:flex;justify-content:space-between;align-items:center;padding:0 14px;height:30px;border-bottom:2px solid #000;flex-shrink:0}
.hdr-l{display:flex;align-items:center;gap:10px}
.hdr-title{font-weight:700;font-size:13px;letter-spacing:.04em}
.hdr-user{font-size:11px;color:#888}
.badge-turn{background:#000;color:#fff;font-size:10px;font-weight:700;padding:2px 7px;border-radius:2px}
.hdr-time{font-size:11px;color:#555}

/* ── Two-column body ── */
.cols{display:grid;grid-template-columns:1fr 1fr;flex:1;min-height:0}
.panel{display:flex;flex-direction:column;overflow:hidden}
.panel+.panel{border-left:1px solid #ccc}
.panel-hdr{padding:5px 14px;border-bottom:1px solid #e8e8e8;font-size:9px;text-transform:uppercase;letter-spacing:.1em;color:#aaa;display:flex;justify-content:space-between;flex-shrink:0}
.panel-hdr b{color:#000;font-size:11px}

/* ── Game rows ── */
.games{padding:0;overflow:hidden;flex:1}
.game{display:grid;grid-template-columns:1fr auto;align-items:start;padding:8px 14px;border-bottom:1px solid #f0f0f0;gap:4px}
.game:last-child{border-bottom:none}

/* left side */
.g-opp{font-size:13px;font-weight:700;line-height:1.2}
.g-meta{font-size:10px;color:#777;margin-top:2px}
.g-stone{display:inline-block;width:10px;height:10px;border-radius:50%;vertical-align:-1px;border:1px solid #bbb;margin-right:3px}
.g-stone.b{background:#000;border-color:#000}
.g-stone.w{background:#fff;border-color:#000}

/* right side */
.g-right{text-align:right}
.g-gid{font-size:9px;color:#bbb;font-family:monospace}
.g-time{font-size:10px;color:#777;margin-top:2px}
.g-time.urgent{color:#c00;font-weight:600}

/* empty state */
.empty{padding:20px 14px;font-size:11px;color:#ccc;font-style:italic}
.all-clear{padding:16px 14px}
.all-clear-big{font-size:28px;font-weight:200;color:#ccc;line-height:1}
.all-clear-sub{font-size:11px;color:#bbb;margin-top:4px}
</style>

<div class="hdr">
  <div class="hdr-l">
    <span class="hdr-title">Go &middot; Dragon Go Server</span>
    <span class="hdr-user">##{{ IDX_0.user }}</span>
    ##{% if IDX_0.my_turn_count > 0 %}
      <span class="badge-turn">##{{ IDX_0.my_turn_count }} to move</span>
    ##{% endif %}
  </div>
  <div class="hdr-time">##{{ trmnl.user.time | date: "%H:%M &middot; %a %b %-d" }}</div>
</div>

<div class="cols">

  <!-- ── Your move ── -->
  <div class="panel">
    <div class="panel-hdr">
      <span>Your move</span>
      <b>##{{ IDX_0.my_turn_count }}</b>
    </div>
    <div class="games">
      ##{% if IDX_0.my_turn.size == 0 %}
        <div class="all-clear">
          <div class="all-clear-big">✓</div>
          <div class="all-clear-sub">All clear — no moves waiting</div>
        </div>
      ##{% else %}
        ##{% for g in IDX_0.my_turn limit: 8 %}
          <div class="game">
            <div>
              <div class="g-opp">
                <span class="g-stone ##{{ g.color | downcase }}"></span>##{{ g.opponent }}
              </div>
              <div class="g-meta">
                ##{{ g.size }}×##{{ g.size }}
                ##{% if g.handicap > 0 %}&nbsp;&bull;&nbsp;##{{ g.handicap }}H##{% endif %}
                &nbsp;&bull;&nbsp; Move ##{{ g.moves }}
              </div>
            </div>
            <div class="g-right">
              <div class="g-gid">###{{ g.gid }}</div>
              ##{% if g.time_left != "" %}
                ##{% assign tl = g.time_left | downcase %}
                ##{% if tl contains "h" and tl contains "0d" %}
                  <div class="g-time urgent">##{{ g.time_left }}</div>
                ##{% else %}
                  <div class="g-time">##{{ g.time_left }}</div>
                ##{% endif %}
              ##{% endif %}
            </div>
          </div>
        ##{% endfor %}
      ##{% endif %}
    </div>
  </div>

  <!-- ── Their move ── -->
  <div class="panel">
    <div class="panel-hdr">
      <span>Waiting for opponent</span>
      <b>##{{ IDX_0.their_turn | size }}</b>
    </div>
    <div class="games">
      ##{% if IDX_0.their_turn.size == 0 %}
        <div class="empty">No games waiting on opponents</div>
      ##{% else %}
        ##{% for g in IDX_0.their_turn limit: 8 %}
          <div class="game">
            <div>
              <div class="g-opp">
                <span class="g-stone ##{{ g.color | downcase }}"></span>##{{ g.opponent }}
              </div>
              <div class="g-meta">
                ##{{ g.size }}×##{{ g.size }}
                ##{% if g.handicap > 0 %}&nbsp;&bull;&nbsp;##{{ g.handicap }}H##{% endif %}
                &nbsp;&bull;&nbsp; Move ##{{ g.moves }}
              </div>
            </div>
            <div class="g-right">
              <div class="g-gid">###{{ g.gid }}</div>
              ##{% if g.time_left != "" %}
                <div class="g-time">##{{ g.time_left }}</div>
              ##{% endif %}
            </div>
          </div>
        ##{% endfor %}
      ##{% endif %}
    </div>
  </div>

</div>
```

---

## Notes

**Rate limit** — DGS asks that `quick_status.php` not be abused. Set the TRMNL
polling interval to **15 minutes minimum**. That's plenty for a correspondence
game server where moves happen a few times per day.

**`quick_status.php` is public** — no DGS password needed in the proxy. The
endpoint intentionally exposes running games for any user. The only secret here
is your TRMNL device key, which stays in the TRMNL polling URL (server-side).

**Stone colour indicator** — the small filled/empty circle before the opponent's
name shows which colour you're playing: ● black / ○ white.

**Time urgency** — the proxy parses DGS's time remaining string (e.g.
`"F: 0d 8h"` = Fischer clock, 8 hours left). The markup highlights times
containing `0d` (less than 24 hours) in red.

**Game IDs** — shown as `#12345` so you can go directly to
`dragongoserver.net/game.php?gid=12345` on your computer.

**Format note** — DGS has changed the `quick_status.php` format occasionally
over the years. If games don't appear, open `dgs-proxy.php` and temporarily
`error_log($raw)` to inspect the raw response, then adjust the field offsets in
`parse_quick_status()` accordingly.
