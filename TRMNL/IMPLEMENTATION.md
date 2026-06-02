# Kaslo TRMNL Dashboard — Implementation Reference

All plugins run on a TRMNL e-ink display (800x480px, 16-level gray, scale_factor 1.8).
Server: knotwork.ca  |  Server path: /home/kw_9g92aw/knotwork.ca/
Local mirror: /Users/kendrick/Documents/WEBhosting/knotwork.ca/

---

## CRITICAL LAYOUT RULE

Use `width:100%;height:100%` on html/body and compute ALL panel dimensions
from `window.innerWidth/innerHeight` in JavaScript. Stamp explicit px values
onto DOM elements after computing them. Never hardcode 800px or 480px —
TRMNL's effective render viewport varies by device (scale_factor:1.8 gives ~444px wide).

Reference: plugin-dgs-v3.md which renders correctly every time.

```css
/* CSS: adaptive, no hardcoded pixels */
html,body { width:100%; height:100%; overflow:hidden; }
body { display:flex; flex-direction:column; }
.content { display:flex; flex-direction:row; flex:1; min-height:0; overflow:hidden; }
.main-panel { flex-shrink:0; overflow:hidden; }  /* width set by JS */
.side-panel { flex:1; min-width:0; overflow:hidden; }
```

```js
/* JS: read actual viewport, compute, stamp onto elements */
var VW = window.innerWidth  || 800;
var VH = window.innerHeight || 480;
var mainW  = Math.floor(VW * 0.67);
var brdSz  = Math.min(mainW, VH - HDR_H);
document.getElementById('main-panel').style.width = mainW + 'px';
document.getElementById('bw-main').style.width    = brdSz + 'px';
document.getElementById('bw-main').style.height   = brdSz + 'px';
/* Board SVG rendered via innerHTML with explicit viewBox — NOT appendChild */
element.innerHTML = '<svg width="'+brdSz+'" height="'+brdSz+'" viewBox="0 0 420 420" ...>';
```

---

## API Architecture

```
EcoWitt station ──┐
Open-Meteo       ──┤──> weather-device-api.php ──> data/weather-cache.json ──┐
                   │                                                           │
iCloud calendars ──┤──> cal-device-api.php ──────> data/cal-cache-v3.json ───┤
                   │                                                           ├──> kaslo-api.php ──> TRMNL plugins
DGS (scrape) ─────┤──> dgs-device-api.php ──────> data/dgs-cache.json ───────┤
                   │                                                           │
todo.json ─────────┘───────────────────────────────────────────────────────────┘
```

**weather-device-api.php** must be polled regularly (10 min) to keep `data/weather-cache.json` fresh.
kaslo-api.php only *reads* from that cache — it does not fetch EcoWitt or Open-Meteo directly for weather.

---

## Data Sources

### EcoWitt weather station IKASLO6
- **API**: `https://api.ecowitt.net/api/v3/device/real_time?application_key=C6FD389063D6A82CC7A68532000A5962&api_key=1c2c26a5-a293-4f82-a69a-9e77b6447344&mac=E0:5A:1B:21:11:57&call_back=all&temp_unitid=1&pressure_unitid=3&wind_speed_unitid=7&rainfall_unitid=12&solar_irradiance_unitid=16`
- **Fetched by**: `weather-device-api.php` → written to `data/weather-cache.json` (TTL 10 min)

### Open-Meteo 5–7 day forecast
- **API**: Free, no key. Kaslo BC: lat=49.912, lon=-116.908
- **Fetched by**: `weather-device-api.php` (forecast f0–f6) + kaslo-api.php (hourly pressure, past_days=3)
- **Note**: `moon_phase` daily variable was **removed by Open-Meteo** — moon is now computed via Julian Date in PHP

### Dragon Go Server (DGS)
- **User**: shrimphead  |  **UID**: 24738
- **API**: Login + HTML scrape — `quick_status.php` endpoint is broken
- **Fetched by**: `dgs-device-api.php` → `data/dgs-cache.json` (TTL 5 min)
- **SGF fetch**: `https://www.dragongoserver.net/sgf.php?gid=GAMEID`
- **Board cache**: `/tmp/kaslo4_brd_GAMEID.json` (TTL 15 min)

### iCal Calendar (3 iCloud feeds)
- **Labels**: personal, personal2, personal3
- **Fetched by**: `cal-device-api.php` → `data/cal-cache-v3.json` (TTL 20 min)

### Todos
- **File**: `todo.json` (read directly by kaslo-api.php)

### Moon Phase
- **Source**: PHP Julian Date calculation in kaslo-api.php (`compute_moon_phase()`)
- **Same formula** as plugin-weather-v4.md's JavaScript — results match
- Open-Meteo `moon_phase` daily variable is no longer supported

---

## PHP Files on Server

### weather-device-api.php — Weather data source
**URL**: `https://knotwork.ca/weather-device-api.php?key=KEY`
- Fetches EcoWitt + Open-Meteo, merges calendar events into forecast days
- Writes `data/weather-cache.json`
- Must be polled regularly to keep cache fresh
- Returns: flat vars `temp`, `condition`, `sunrise`, `sunset`, `daylight`, `f0_*` through `f6_*` including `f0_hol`, `f0_tev0n` etc.

### kaslo-api.php — Unified aggregator
**URL**: `https://knotwork.ca/kaslo-api.php?key=KEY[&game=N]`
- Reads all cache files, computes moon phase + pressure trend
- Fetches SGF for board position (game=N only)
- Returns ALL variables as flat top-level keys (no IDX prefix)
- Backward-compatible: exports both `wx_temp` AND bare `temp`, `wx_sunrise` AND `sunrise` etc.

**Response variables:**

#### Backward-compatible bare aliases (for plugin-weather-v4 / KasloTRMNL)
`temp`, `temp_full`, `feels`, `condition`, `hi`, `lo`, `humidity`, `dew`, `wind_kmh`, `gust_kmh`, `wdir`, `pressure`, `uvi`, `solar`, `rain_day`, `rain_rate`, `rain_week`, `indoor_temp`, `indoor_hum`, `sunrise`, `sunset`, `daylight`

#### Go board (go_*)
| Variable | Description |
|---|---|
| `go_opponent` | Opponent DGS handle |
| `go_color` | Your stone colour lowercase: `b` or `w` |
| `go_moves` | Move number |
| `go_time_left` | Time remaining string |
| `go_my_turn_count` | Games waiting for your move |
| `go_total_games` | Total active 19×19 games |
| `go_board_black_json` | `[[col,row],...]` JSON string |
| `go_board_white_json` | `[[col,row],...]` JSON string |
| `go_last_col` / `go_last_row` / `go_last_color` | Last move position |

#### Weather (wx_* prefix + bare alias)
| Variable | Description |
|---|---|
| `wx_temp` / `temp` | Current temp °C |
| `wx_condition` / `condition` | e.g. "Clear" |
| `wx_feels` / `feels` | Feels like °C |
| `wx_hi` / `hi` | Today forecast high °C |
| `wx_lo` / `lo` | Today forecast low °C |
| `wx_wind_kmh` / `wind_kmh` | Wind speed |
| `wx_gust_kmh` / `gust_kmh` | Gust speed |
| `wx_wdir` / `wdir` | Direction e.g. "SW" |
| `wx_rain_day` / `rain_day` | Rain today mm |
| `wx_rain_rate` / `rain_rate` | Rain rate mm/h |
| `wx_rain_week` / `rain_week` | Weekly rain mm |
| `wx_humidity` / `humidity` | Humidity % |
| `wx_dew` / `dew` | Dew point °C |
| `wx_pressure` / `pressure` | Pressure hPa |
| `wx_uvi` / `uvi` | UV index |
| `wx_solar` / `solar` | Solar W/m² |
| `wx_indoor_temp` / `indoor_temp` | Indoor temp °C |
| `wx_indoor_hum` / `indoor_hum` | Indoor humidity % |
| `wx_sunrise` / `sunrise` | HH:MM |
| `wx_sunset` / `sunset` | HH:MM |
| `wx_daylight` / `daylight` | e.g. "16h 06m daylight" |

#### Forecast (f0_* to f6_* — 7 days)
| Variable | Description |
|---|---|
| `f0_dow` | Day of week e.g. "Tue" |
| `f0_hi` / `f0_lo` | Hi/lo °C |
| `f0_condition` | Condition string |
| `f0_pop_str` | Precip probability e.g. "55%" |
| `f0_mm_str` | Precip amount e.g. "10.7mm" |
| `f0_hol` | Holiday name (from cal cache) |
| `f0_tev0n` / `f0_tev0t` | Timed event 0 name / time |
| `f0_tev1n` / `f0_tev1t` | Timed event 1 name / time |
| `f0_aev0` / `f0_aev1` / `f0_aev2` | All-day events |
| `f0_icon` | Weather Unicode symbol e.g. "☁" |
| `fc_pressure_now` | Current pressure hPa |
| `fc_pressure_trend` | "rising" / "falling" / "steady" |
| `fc_pressure_json` | Hourly pressure history JSON array |

#### Sun & Moon
| Variable | Description |
|---|---|
| `sun_rise` / `sun_set` | HH:MM (for go.* plugins) |
| `moon_phase` | 0.0–1.0 (0=new, 0.5=full) — Julian Date calculation |
| `moon_name` | Phase name e.g. "Waning Gibbous" |

#### Calendar (cal_d0_* to cal_d6_* — 7 days)
| Variable | Description |
|---|---|
| `cal_d0_dow` | Day of week e.g. "Thu" |
| `cal_d0_dom` | Day of month e.g. "28" |
| `cal_d0_today` | `"today"` or `""` |
| `cal_d0_wknd` | `"wknd"` or `""` |
| `cal_d0_hol` | Holiday name or `""` |
| `cal_d0_tev0n` / `cal_d0_tev0t` | Timed event 0 name / time |
| `cal_d0_tev1n` / `cal_d0_tev1t` | Timed event 1 name / time |
| `cal_d0_aev0` / `cal_d0_aev1` | All-day events |
| `cal_d0_f_cond` | Weather condition for that day |
| `cal_d0_f_icon` | Weather Unicode symbol |
| `cal_d0_f_hi` / `cal_d0_f_lo` | Hi/lo for that day |
| `cal_d0_f_pop` | Precip probability |
| `cal_d0_f_mm` | Precip amount |

#### Todo (todo_*)
| Variable | Description |
|---|---|
| `todo_total` | Total open items |
| `todo_u0_text` … `todo_u3_text` | Urgent item text (up to 4) |
| `todo_u0_meta` … `todo_u3_meta` | Urgent item due/tags |
| `todo_u_more` | e.g. "+2 more" or `""` |
| `todo_r0_text` … `todo_r7_text` | Rest item text (up to 8) |
| `todo_r0_meta` … `todo_r7_meta` | Rest item due |
| `todo_r_more` | e.g. "+1 more" or `""` |

**EcoWitt API reminder** (auto-injected):
- `REMINDER_ANCHOR` = '2026-05-28' in kaslo-api.php — update when you refresh the EcoWitt key
- Appears in todo urgent when ≤3 days away; in calendar on due date

---

## TRMNL Plugins

### go.* plugins — shared layout (67% board / 33% sidebar)
```
┌─────────────────────────────────────────┐  36px header (JS-sized)
│ TITLE  ● Opponent  Mv N  Time  HH:MM   │
├─────────────────────────────────────────┤
│                            │            │
│    Go Board (67% width)    │  Sidebar   │
│    444×444 SVG             │  (33%)     │
│    Golden #D4A740 bg       │            │
│    Coordinate labels A-T   │            │
│                            │            │
└────────────────────────────┴────────────┘
```

### go.todo (game=0)
- **File**: `plugin-go-todo.md`
- **URL**: `kaslo-api.php?key=KEY&game=0`
- **Sidebar**: Urgent items (up to 4, 17px) + Everything Else (up to 8)
- **Variables**: go_*, todo_*

### go.cal (game=1)
- **File**: `plugin-go-cal.md`
- **URL**: `kaslo-api.php?key=KEY&game=1`
- **Sidebar**: 7-day calendar — each day has dark header bar (DOW+date), 3/4 events + 1/4 weather icon/precip/temp
- **Backgrounds**: normal=#e8e8e8, weekend=#f2f2f2, today=#3a3a3a (dark, white text)
- **Variables**: go_*, cal_d0_*–cal_d6_*, f0_*–f6_*

### go.weather (game=2)
- **File**: `plugin-go-weather.md`
- **URL**: `kaslo-api.php?key=KEY&game=2`
- **Sidebar** (top→bottom): Indoor temp · outdoor temp · condition · stats · solar dial (E8E8E8 bg, FFD700 day arc) · moon (above dial if in sky, below if set)
- **Variables**: go_*, wx_*, fc_*, sun_*, moon_*

---

## Standalone Plugins

### KasloTRMNL — Weather simple 3-column
- **File**: `KasloTRMNL.txt`
- **URL**: `kaslo-api.php?key=KEY` (no game param)
- **Layout**: Header | 3 cols: current conditions / stats 2×2 / 7-day forecast | Footer (indoor + sunrise/sunset)
- **Variables**: bare aliases (`temp`, `condition`, `sunrise` etc.) + `f0_*`–`f6_*`
- **Note**: Uses `800px×480px` hardcoded (full-screen, not go.* adaptive layout)

### plugin-weather-v4 — Weather visual panels
- **File**: `plugin-weather-v4.md`
- **URL**: `kaslo-api.php?key=KEY` (no game param)
- **Layout**: Header | 3 equal panels: WEATHER / MOON PHASE (JS SVG) / SOLAR CLOCK (JS twilight arc SVG) | 5-day forecast strip with SVG icons + calendar events + temp range bar
- **Variables**: bare aliases (`temp`, `condition`, `sunrise`, `daylight` etc.) + `f0_*`–`f4_*` including `f0_hol`, `f0_tev0n` etc.
- **Moon**: Computed by JS (Julian Date) — same formula as kaslo-api.php; Open-Meteo moon_phase was discontinued
- **Solar clock**: Draws twilight arcs (astronomical/nautical/civil/day) from `sunrise`/`sunset`

### plugin-todo-v5.2 — Todo full-screen
- **File**: `plugin-todo-v5.2.md`
- **URL**: `todo-device-api.php?key=KEY` (direct, NOT kaslo-api)
- **Layout**: Left=Urgent large font / Right=Important+Rest
- **Variables**: flat u0_text, i0_text, r0_text etc.

### plugin-calendar-v6 — Calendar full-screen
- **File**: `plugin-calendar-v6.md`
- **URL**: `cal-device-api.php?key=KEY` (direct, NOT kaslo-api)
- **Layout**: 7 rows × 3 cols (date / timed events / all-day + holidays)

### plugin-dgs-v3 — DGS multi-board ⭐ LAYOUT REFERENCE
- **File**: `plugin-dgs-v3.md`
- **URL**: `dgs-device-api.php?key=KEY` (direct)
- **Layout**: Main board (67%) + side panel (33%) with 2 compact boards + game list
- **THIS IS THE REFERENCE** for board rendering + adaptive layout approach
- **Board**: innerHTML SVG, viewBox 420×420, bg #D4A740, coordinate labels A-T / 1-19
- **Rotation**: Changes every 30 min via `Math.floor(Date.now()/1800000)`

---

## Board Rendering Reference (from dgs-v3)

```
Board colours:
  Background:  #D4A740 (golden amber)
  Grid lines:  #3A2000 (border 1.6px, inner 0.6px)
  Hoshi dots:  #3A2000, r = max(1.2, cell*0.1)
  Black stone: fill #111, stroke #000 0.4px + white glint ellipse
  White stone: fill #f8f8f8, stroke #444 1px
  Stone radius: min(cell*0.47, 11)
  Last move:   circle r=stone*0.32, fill #fff (on black) or #222 (on white)

Grid geometry (viewBox 420×420, pad=14, lblPad=18):
  gridPx = 420 - 14*2 - 18 = 374 (with coordinate labels)
  cell = 374/18 = 20.78px
  ox = pad+lblPad = 32, oy = pad = 14
```

---

## Deployment Checklist

1. Upload PHP files to `/home/kw_9g92aw/knotwork.ca/`:
   - `kaslo-api.php` — primary aggregator
   - `kaslo-flush.php` — clears OPcache + `/tmp/kaslo4_*.json` data caches
   - Existing (keep running): `weather-device-api.php`, `dgs-device-api.php`, `cal-device-api.php`, `todo-device-api.php`

2. After deploying kaslo-api.php, always flush:
   ```
   curl https://knotwork.ca/kaslo-flush.php
   ```
   This clears both OPcache AND stale data caches (/tmp/kaslo4_om.json etc.)

3. TRMNL polling URLs:
   | Plugin | URL |
   |---|---|
   | go.todo | `kaslo-api.php?key=KEY&game=0` |
   | go.cal | `kaslo-api.php?key=KEY&game=1` |
   | go.weather | `kaslo-api.php?key=KEY&game=2` |
   | KasloTRMNL | `kaslo-api.php?key=KEY` |
   | plugin-weather-v4 | `kaslo-api.php?key=KEY` |
   | plugin-todo-v5.2 | `todo-device-api.php?key=KEY` |
   | plugin-calendar-v6 | `cal-device-api.php?key=KEY` |
   | plugin-dgs-v3 | `dgs-device-api.php?key=KEY` |
   | **weather-device-api** | Must still be polled to refresh cache! |

4. Plugin settings: Strategy=Polling, Verb=GET, Remove bleed margin=Yes
5. Paste markup from .md/.txt files (between ` ```html ` fences only)
6. Intervals: 15 min min for go.* (DGS), 10 min for weather, shorter for others

---

## Maintenance

### EcoWitt API key / sharing link (~30 days)
- Refresh at: ecowitt.net
- Update `REMINDER_ANCHOR` in kaslo-api.php (triggers todo + calendar reminder)

### weather-device-api.php must stay polled
- kaslo-api.php reads FROM its cache but does NOT refresh it
- Ensure at least one TRMNL plugin or cron polls `weather-device-api.php?key=KEY` every 10 min
- If cache goes stale, all weather data (temp, forecast, sunrise etc.) disappears from all plugins

### Open-Meteo moon_phase discontinued
- As of 2026: `daily=moon_phase` returns error from Open-Meteo
- Moon phase now computed via `compute_moon_phase()` in kaslo-api.php (Julian Date)
- Same formula used in plugin-weather-v4.md JS — results are consistent
- Cached in `/tmp/kaslo4_om.json` (1hr TTL)

### DGS credentials
- Username: Shrimphead, UID: 24738
- Config: `/home/kw_9g92aw/knotwork.ca/config/dgs-config.json`
- quick_status.php endpoint broken; dgs-device-api.php uses login+scrape

### Cache locations
| File | Written by | TTL | Contents |
|---|---|---|---|
| `data/weather-cache.json` | weather-device-api.php | 10 min | EcoWitt + forecast |
| `data/dgs-cache.json` | dgs-device-api.php | 5 min | DGS game list |
| `data/cal-cache-v3.json` | cal-device-api.php | 20 min | Calendar events |
| `/tmp/kaslo4_om.json` | kaslo-api.php | 1 hr | Moon phase + pressure history |
| `/tmp/kaslo4_brd_NNN.json` | kaslo-api.php | 15 min | Board position per game |
