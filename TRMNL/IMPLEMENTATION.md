# Kaslo TRMNL Dashboard  Implementation Reference

**Display**: TRMNL X e-ink 1040x780px, 16-level gray, scale_factor 1.8 (native panel 1872x1404)
**Server**: knotwork.ca | `/home/kw_9g92aw/knotwork.ca/`
**Local mirror**: `/Users/kendrick/Documents/WEBhosting/knotwork.ca/`
**Device key**: `kw_40e818911c1980bcd56dc4aff37a820f811990a130eb771fd9e21a536edc55ea`

---

## File Map

```
knotwork.ca/
 kaslo-api.php               MAIN ENDPOINT — all plugins poll this
 kaslo-flush.php             OPcache + cache reset (run after any PHP deploy)
 data/
    todos.json               Hand-edited todo items (kaslo-api.php reads directly)
 /tmp/                       (auto-created by kaslo-api.php, not committed)
    kaslo_wx.json            EcoWitt real-time weather (TTL 5 min)
    kaslo_forecast3.json     Open-Meteo forecast + pressure (TTL 30 min)
    kaslo_ical.json          4x iCloud iCal feeds (TTL 30 min)
    kaslo_dgs_board_NNN.json Board position per game ID (TTL 15 min)
 TRMNL/
     IMPLEMENTATION.md       This file
     VARIABLES-REFERENCE.md  Full variable listing (authoritative)
     BEST-PRACTICES.html     PHP gotchas, Liquid rules, testing
     PORTRAIT-MODE.md        Portrait orientation guide
     kaslo-test.sh           Test suite (syntax + remote)
     plugin-dashboard.md     PRIMARY: full-screen dashboard
     plugin-cal2wks.md       2-week calendar (new)
     plugin-go-todo.md       Go board + todo sidebar (game=0)
     plugin-go-cal.md        Go board + calendar sidebar (game=1)
     plugin-go-weather.md    Go board + weather sidebar (game=2)
     plugin-weather-v4.md    Full-screen weather panels
     KasloTRMNL.txt          Simple 3-column weather
```

---

## Dependency Graph

```
EcoWitt v3 API (api.ecowitt.net) ──────────────────────────────────────────┐
Open-Meteo (7-day forecast + hourly pressure, past_days=3) ────────────────┤
4x iCloud iCal feeds ──────────────────────────────────────────────────────┤──> kaslo-api.php ──> TRMNL plugins
data/todos.json (hand-edited) ─────────────────────────────────────────────┤
DGS SGF endpoint (when &game=N) ───────────────────────────────────────────┘
```

**kaslo-api.php** is fully self-contained — it fetches all sources directly
and caches each to `/tmp/kaslo_*.json`. It does NOT depend on or read from
separate device API scripts or `data/*.json` cache files.

**Active file location**: `/Users/kendrick/Documents/WEBhosting/knotwork.ca/kaslo-api.php`
(the TRMNL/ subdirectory copy is documentation only)

---

## PHP Files

### `kaslo-api.php`  Unified self-contained endpoint (PRIMARY)
**Active file**: `/Users/kendrick/Documents/WEBhosting/knotwork.ca/kaslo-api.php`
**URL**: `https://knotwork.ca/kaslo-api.php?key=KEY[&game=N]`
**Used by**: plugin-dashboard, plugin-cal2wks, plugin-go-todo, plugin-go-cal, plugin-go-weather, plugin-weather-v4, KasloTRMNL

Fetches all data internally and caches to `/tmp/kaslo_*.json`. Does NOT call
separate device API scripts or read from `data/*.json` cache files.

| Source | Constant | TTL | Cache key |
|---|---|---|---|
| EcoWitt v3 API (real-time) | `ECOWITT_URL` | 300s (5 min) | `kaslo_wx.json` |
| Open-Meteo (forecast + pressure) | `OPEN_METEO_URL` | 1800s (30 min) | `kaslo_forecast3.json` |
| 4× iCloud iCal feeds | `ICAL_FEEDS[]` | 1800s (30 min) | `kaslo_ical.json` |
| DGS SGF board (when `&game=N`) | `DGS_SGF_BASE` | 900s (15 min) | `kaslo_dgs_board_NNN.json` |

Key constants to maintain:
```php
const CAL_DAYS = 14;   // calendar window days (0..13)
const TZ       = 'America/Vancouver';
const TTL_WX   = 300;    // 5 min
const TTL_FC   = 1800;   // 30 min
const TTL_DGS  = 900;    // 15 min
const TTL_ICAL = 1800;   // 30 min
```

**EcoWitt API credentials** (in `ECOWITT_URL`):
- `application_key`: C6FD389063D6A82CC7A68532000A5962
- `api_key`: 1c2c26a5-a293-4f82-a69a-9e77b6447344
- `mac`: E0:5A:1B:21:11:57
- Endpoint: `api.ecowitt.net/api/v3/device/real_time?...&call_back=all`
- Units: temp_unitid=1 (°C), pressure_unitid=3 (hPa), wind_speed_unitid=7 (km/h), rainfall_unitid=12 (mm)
- Rain field: `rainfall_piezo` (not `rainfall`)

**4 iCal feeds** (`ICAL_FEEDS` array):
- BC EHS + WORK calendar
- Family calendar
- Kendrick Stuff ← all events extracted as todos (not shown on calendar grid)
- SD8 calendar

**Todo sources** merged in `build_todo()`:
1. `data/todos.json` (hand-edited)
2. All events from "Kendrick Stuff" iCal feed (event date = due date, priority 3)
3. Any event in any feed titled `TODO: <text>` (same treatment)

**Critical bug fixes applied (session Jul 2026):**
- `f0–f6_*` calendar event fields now sourced from `$cal_days[$i]`, not weather cache
- `(cond ? $a : $b)[] = $x` ternary-on-LHS syntax removed throughout
- `past_days=3` offset corrected: `$off = 3` skips to today's forecast day
- Moon phase computed locally (Open-Meteo `moon_phase` daily param removed from API)
- EcoWitt changed from share-page URL to real v3 API

---

### `kaslo-flush.php`  Cache reset
**URL**: `https://knotwork.ca/kaslo-flush.php`
**Run after**: any PHP file deploy

Deletes all `/tmp/kaslo_*.json` caches and calls `opcache_invalidate()` +
`opcache_reset()`. On the next plugin poll, kaslo-api.php fetches fresh data
from all sources and rebuilds caches automatically.

---

## TRMNL Plugins

### `plugin-dashboard.md`  PRIMARY PLUGIN 
**URL**: `kaslo-api.php?key=KEY` (no game param)
**Settings**: Strategy=Polling, Verb=GET, Remove bleed margin=Yes

Full-screen dashboard with adaptive JS layout (window.innerWidth/innerHeight):

```
 28px
 KASLO  Dashboard                                             HH:MM  Day Mon D  header

 WEATHER  IKASLO6               TO DO (N OPEN)     INDOOR  SOLAR              
  temp [moon] sunrise            urgent items     23     (solar dial SVG)
  Clear  Gibbous sunset           rest items       45%hm   04:42 20:48    TOP_H
  Feels Hi/Lo                    ...                                           24%
  Wind  Rain  Humidity                                                       

 [DOW BAND full]   [DOW BAND]      [DOW BAND]          [BAND]   [BAND]  ...   FC_H
  22/10 28%    21/15 62%    ...                            rain        18-20%
 temp bar      bar                                                     
 TUE 2             WED 3                                                      
 4px white
 Jun 2    Wed 3    Thu 4     Fri 5    Sat 6    Sun 7    Mon 8              row 1
 events   events   events   events    events   events   events             32%
                                                                      
 2px
 [9]  [10] [11] [12] [13] [14] [15]                                              row 2
 2px
 [16] [17] [18] [19] [20] [21] [22]                                              row 3
 2px
 [23] [24] [25] [26] [27] [28] [29]                                              row 4

```

**JS layout proportions** (at 780px screen, 28px header):
- `avail` = 752px
- `TOP_H` = `floor(avail * 0.24)`  180px
- `FC_H` = `max(88, floor(avail * 0.18))`  135px
- `CAL_H` = avail - TOP_H - FC_H  437px
- `CAL_ROW1_H` = `floor(CAL_H * 0.32)`  139px (current week, bigger)
- `CAL_ROW_H` = `floor((CAL_H - CAL_ROW1_H) / 3)`  99px (weeks 2-4)

**Forecast strip columns** (`.fc-col`):
- Top: full-width DOW band (`Tuesday` etc.  JS expands from "Tue" via `DOW_FULL` lookup)
- Middle: weather icon (46px SVG) + hi/lo temps + precip % + temp range bar
- Right edge: rainfall gauge bar (absolute positioned, `top:30px`)
- Badge: lightning bolt or snowflake SVG when condition = Thunderstorm/Snow
- Bottom: date number only (no DOW repeated)

**Calendar day cells** (`.day`):
- Dark band (`.d-hdr`): shows date number OR month abbreviation on month-change days
  - Week 1 today: shows "Jun 2" (month_abbr + dom)
  - Weeks 2-4 band: `#686868` (lighter than today's `#484848`)
- Events (`.d-ev`): 18px in week 1, 11px in weeks 2-4
- BC EHS events rendered as black chicklet tags (`.d-ev.ehs`), 13px in week 1
- All-day chicklets (`.d-aev`, `.d-hol`): sit just below events

**Day-of-week colour scheme**:
| Day | Background |
|---|---|
| Mon / Wed / Fri | `#fff` white |
| Tue / Thu | `#f2f2f2` very light gray |
| Sat / Sun | `#e8e8e8` light gray |
| Holiday | `#c8c8c8` medium gray |
| Today | `#484848` dark gray, white text |

**Variables used**: weather bare aliases (`temp`, `condition`, `feels`, `hi`, `lo`, `wind_kmh`, `wdir`, `rain_day`, `humidity`, `pressure`, `indoor_temp`, `indoor_hum`, `sunrise`, `sunset`), `moon_phase`, `moon_name`, `f0_*`–`f6_*`, `cal_d0_*`–`cal_d13_*`, `todo_u0_text`–`todo_u5_text`, `todo.total`

---

### `plugin-go-todo.md`  Go board + Todo (game=0)
**URL**: `kaslo-api.php?key=KEY&game=0`

- Left 67%: 1919 Go board SVG (golden #D4A740 bg, JS-sized)
- Right 33%: Todo list  urgent ( prefix) + rest ( prefix), 17px font
- **Variables**: `go_*`, `todo_*`

---

### `plugin-go-cal.md`  Go board + Calendar (game=1)
**URL**: `kaslo-api.php?key=KEY&game=1`

- Left 67%: Go board
- Right 33%: 7-day calendar sidebar, each day: dark header + weather icon + events
- **Variables**: `go_*`, `cal_d0_*``cal_d6_*`, `f0_*``f6_*`

---

### `plugin-go-weather.md`  Go board + Weather (game=2)
**URL**: `kaslo-api.php?key=KEY&game=2`

- Left 67%: Go board
- Right 33%: Indoor temp  outdoor temp  condition  stats  solar dial  moon phase
- Solar dial: `#E8E8E8` bg, `#fff` day arc
- Moon: white lit portion, `#888` dark portion, `#e8e8e8` background panel
- **Variables**: `go_*`, `wx_*` bare aliases, `fc_*`, `sun_*`, `moon_*`

---

### `plugin-weather-v4.md`  Full-screen visual weather
**URL**: `kaslo-api.php?key=KEY`

- Header  3 panels: WEATHER / MOON (JS SVG) / SOLAR CLOCK (twilight arcs)  5-day forecast strip
- Moon phase computed in JS (same Julian Date formula as kaslo-api.php PHP version)
- Solar clock: astronomical/nautical/civil/day wedges from `sunrise`/`sunset`
- **Variables**: bare aliases + `f0_*``f4_*` including calendar events per day

---

### `KasloTRMNL.txt`  Simple weather (3-column)
**URL**: `kaslo-api.php?key=KEY`

- Header | Col 1: current conditions | Col 2: stats 22 grid | Col 3: 7-day forecast | Footer
- Hardcoded 1040x780px (non-adaptive) — needs update for TRMNL X
- **Variables**: bare aliases (`temp`, `condition`, `sunrise` etc.) + `f0_*``f6_*`

---

## kaslo-api.php Variable Reference

See **[VARIABLES-REFERENCE.md](VARIABLES-REFERENCE.md)** for the complete, authoritative listing.

Key points:
- Flat keys use **bare names only** — no `wx_*` prefix (e.g. `temp`, not `wx_temp`)
- Calendar window is **14 days**: `cal_d0_*` through `cal_d13_*`
- Sun times are `sunrise` / `sunset` (not `sun_rise` / `sun_set`)
- `daylight` key does **not** exist in flat output
- Todo flat keys: only `todo_u0_text` through `todo_u5_text`; `todo.total` is nested
- Pressure fields (`fc_pressure_*`) are in the nested `fc` object
- Moon phase computed locally via Julian Date (Open-Meteo moon_phase param removed)

---

## CRITICAL LAYOUT RULE

Use `width:100%;height:100%` on html/body. Compute ALL dimensions from
`window.innerWidth` / `window.innerHeight` in JS. Stamp explicit px values
onto DOM. Never hardcode 1040px or 780px.

```js
var VW = window.innerWidth  || 1040;
var VH = window.innerHeight || 780;
var HDR = 28;
var avail = VH - HDR;
// ... compute section heights as fractions of avail ...
// ... stamp onto elements via element.style.height = h + 'px'
```

Reference implementation: `plugin-dgs-v3.md`

---

## Board Rendering (go.* plugins)

```
Background:  #D4A740 (golden amber)
Grid lines:  #3A2000 (border 1.6px, inner 0.6px)
Hoshi dots:  #3A2000, r = max(1.2, cell*0.1)
Black stone: fill #111, stroke #000 0.4px + white glint ellipse
White stone: fill #f8f8f8, stroke #444 1px
Stone radius: min(cell*0.47, 11)
Last move:   circle r=stone*0.32, fill #fff (black stone) or #222 (white stone)
viewBox: 0 0 420 420 | Coordinate labels A-T / 1-19
Board rendered via innerHTML SVG (NOT appendChild)
```

---

## Deployment Checklist

**After any PHP file change:**
```
curl https://knotwork.ca/kaslo-flush.php
```
Always flush — this resets OPcache and deletes all `/tmp/kaslo_*.json` caches.
Fresh data is fetched from EcoWitt, Open-Meteo, and iCal on the next plugin poll.

**TRMNL Plugin URLs:**
| Plugin | File | URL |
|---|---|---|
| Dashboard | plugin-dashboard.md | `kaslo-api.php?key=KEY` |
| 2-week calendar | plugin-cal2wks.md | `kaslo-api.php?key=KEY` |
| Go + Todo | plugin-go-todo.md | `kaslo-api.php?key=KEY&game=0` |
| Go + Calendar | plugin-go-cal.md | `kaslo-api.php?key=KEY&game=1` |
| Go + Weather | plugin-go-weather.md | `kaslo-api.php?key=KEY&game=2` |
| Weather panels | plugin-weather-v4.md | `kaslo-api.php?key=KEY` |
| Weather simple | KasloTRMNL.txt | `kaslo-api.php?key=KEY` |

**Plugin settings**: Strategy=Polling, Verb=GET, Remove bleed margin=Yes
**Paste**: markup between the ` ```html ` fences only
**Intervals**: go.* plugins 15 min (DGS TTL), weather 10 min, dashboard 15 min

---

## Maintenance

### EcoWitt API key (if ever rotated)
1. Get new `api_key` from ecowitt.net
2. Update `ECOWITT_URL` constant in `kaslo-api.php`
3. Run `kaslo-flush.php` to bust old weather cache

### BC Holidays
- Defined in `kaslo-api.php` — `BC_HOLIDAYS` array
- Covers 2026 — add 2027 dates before year-end

### Open-Meteo moon_phase — do not re-add
- `daily=moon_phase` parameter returns 400 error from Open-Meteo
- Moon phase computed via `compute_moon_phase()` (Julian Date formula) in kaslo-api.php
- Same formula used in plugin-weather-v4.md JS — results agree

### Cache locations
| File | TTL | Contents |
|---|---|---|
| `/tmp/kaslo_wx.json` | 5 min | EcoWitt real-time conditions |
| `/tmp/kaslo_forecast3.json` | 30 min | Open-Meteo 7-day forecast + hourly pressure |
| `/tmp/kaslo_ical.json` | 30 min | 4× iCloud iCal feeds parsed |
| `/tmp/kaslo_dgs_board_NNN.json` | 15 min | DGS SGF board position per game |

All `/tmp/kaslo_*.json` files are deleted by `kaslo-flush.php` and rebuilt
automatically on the next poll. No persistent `data/*.json` cache files.
