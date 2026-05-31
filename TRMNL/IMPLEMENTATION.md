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

Board SVG uses innerHTML (NOT appendChild) with explicit viewBox:
```js
element.innerHTML = '<svg width="444" height="444" viewBox="0 0 420 420" ...>' + svgString + '</svg>';
```

---

## Data Sources

### EcoWitt weather station IKASLO6
- **API**: `https://api.ecowitt.net/api/v3/device/real_time?application_key=C6FD389063D6A82CC7A68532000A5962&api_key=1c2c26a5-a293-4f82-a69a-9e77b6447344&mac=E0:5A:1B:21:11:57&call_back=all&temp_unitid=1&pressure_unitid=3&wind_speed_unitid=7&rainfall_unitid=12&solar_irradiance_unitid=16`
- **Cache**: `data/weather-cache.json` (TTL 10 min, written by weather-device-api.php)

### Open-Meteo 5-day forecast
- **API**: `https://api.open-meteo.com/v1/forecast?latitude=49.912&longitude=-116.908&current=weather_code&daily=temperature_2m_max,temperature_2m_min,precipitation_sum,precipitation_probability_max,weather_code,sunrise,sunset&wind_speed_unit=kmh&timezone=America%2FVancouver&forecast_days=5`
- **Note**: Free, no API key. Returns f0–f4 matching weather-device-api output.

### Dragon Go Server (DGS)
- **User**: shrimphead  |  **UID**: 24738
- **API**: Login + scrape (dgs-device-api.php) — quick_status.php endpoint is broken
- **Cache**: `data/dgs-cache.json` (TTL 5 min)
- **SGF fetch**: `https://www.dragongoserver.net/sgf.php?gid=GAMEID`
- **Board cache**: `/tmp/kaslo4_board_GAMEID.json` (TTL 15 min)

### iCal Calendar feeds (3 iCloud feeds)
- **Cache**: `data/cal-cache-v3.json` (TTL 20 min, written by cal-device-api.php)

### Todos
- **File**: `todo.json` (same directory as PHP files)

---

## PHP Files on Server

### kaslo-api.php — Unified API for go.* plugins
**URL**: `https://knotwork.ca/kaslo-api.php?key=KEY[&game=N]`

Reads existing cache files, adds moon/pressure from Open-Meteo, computes board position from SGF.

**Response — flat top-level keys** (all accessible as `{{ variable_name }}` in TRMNL):

#### Go board (go_*)
| Variable | Description |
|---|---|
| `go_opponent` | Opponent DGS handle |
| `go_color` | Your stone colour lowercase: `b` or `w` |
| `go_color_upper` | `B` or `W` |
| `go_moves` | Move number |
| `go_time_left` | Time remaining string |
| `go_my_turn` | `"true"` or `""` |
| `go_my_turn_count` | Number of games waiting for your move |
| `go_total_games` | Total active 19x19 games |
| `go_board_black_json` | `[[col,row],...]` JSON string |
| `go_board_white_json` | `[[col,row],...]` JSON string |
| `go_last_col` | Last move column (-1 if none) |
| `go_last_row` | Last move row |
| `go_last_color` | `B` or `W` |

#### Weather (wx_*)
| Variable | Description |
|---|---|
| `wx_temp` | Current temp °C (integer) |
| `wx_condition` | Condition string e.g. "Clear" |
| `wx_feels` | Feels like °C |
| `wx_hi` / `wx_lo` | Today forecast hi/lo |
| `wx_wind_kmh` | Wind speed |
| `wx_gust_kmh` | Gust speed |
| `wx_wdir` | Wind direction e.g. "SW" |
| `wx_rain_day` | Rain today mm |
| `wx_rain_rate` | Rain rate mm/h |
| `wx_rain_week` | Weekly rain mm |
| `wx_humidity` | Humidity % |
| `wx_dew` | Dew point °C |
| `wx_pressure` | Pressure hPa |
| `wx_uvi` | UV index |
| `wx_solar` | Solar W/m² |
| `wx_indoor_temp` | Indoor temp °C |
| `wx_indoor_hum` | Indoor humidity % |
| `wx_sunrise` / `wx_sunset` | HH:MM |

#### Forecast (f0_* to f4_*)
| Variable | Description |
|---|---|
| `f0_dow` | Day of week e.g. "Thu" |
| `f0_hi` / `f0_lo` | Hi/lo °C |
| `f0_condition` | Condition string |
| `f0_pop_str` | Precip probability e.g. "55%" |
| `f0_mm_str` | Precip amount e.g. "10.7mm" |
| `fc_condition` | Current condition (same as wx_condition) |
| `fc_pressure_now` | Current pressure hPa |
| `fc_pressure_trend` | "rising" / "falling" / "steady" |
| `fc_pressure_json` | Hourly pressure history JSON array string |

#### Sun & Moon
| Variable | Description |
|---|---|
| `sun_rise` / `sun_set` | HH:MM from sunrise/sunset |
| `moon_phase` | 0.0–1.0 (0=new, 0.5=full) |
| `moon_name` | Phase name e.g. "Waxing Gibbous" |

#### Calendar (cal_d0_* to cal_d4_*)
| Variable | Description |
|---|---|
| `cal_d0_dow` | Day of week abbrev e.g. "Thu" |
| `cal_d0_dom` | Day of month e.g. "28" |
| `cal_d0_today` | `"today"` or `""` |
| `cal_d0_wknd` | `"wknd"` or `""` |
| `cal_d0_hol` | Holiday name or `""` |
| `cal_d0_tev0n` / `cal_d0_tev0t` | Timed event 0 name / time |
| `cal_d0_tev1n` / `cal_d0_tev1t` | Timed event 1 name / time |
| `cal_d0_aev0` / `cal_d0_aev1` | All-day event 0 / 1 |
| `cal_d0_f_cond` | Weather condition for that day |
| `cal_d0_f_hi` / `cal_d0_f_lo` | Hi/lo for that day |
| `cal_d0_f_pop` | Precip probability for that day |

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

**EcoWitt reminder** (auto-injected into todo and calendar):
- `REMINDER_ANCHOR` = '2026-05-28' — update this when you refresh the EcoWitt key
- Appears in todo as urgent when <= 3 days away, otherwise in rest
- Appears in calendar on its due date

---

## TRMNL Plugins

### Plugin layout (800×480)
```
┌─────────────────────────────────────────────────┐  24px header
│ TITLE  ● Opponent  Mv N  Time  [N to move]  HH:MM│
├────────────────────────────────────┬────────────┤
│                                    │            │
│         Go Board                   │  Sidebar   │
│         444×444 SVG                │  266px     │
│         in 534px panel             │            │
│         (gold background fills     │            │
│          remaining 90px)           │            │
│                                    │            │
└────────────────────────────────────┴────────────┘
       534px                              266px
```

### go.todo (game=0)
- **File**: `plugin-go-todo.md`
- **URL**: `kaslo-api.php?key=KEY&game=0`
- **Sidebar**: Urgent items (up to 4) + Everything Else (up to 8)
- **Variables used**: go_*, todo_*

### go.cal (game=1)
- **File**: `plugin-go-cal.md`
- **URL**: `kaslo-api.php?key=KEY&game=1`
- **Sidebar**: 5-day calendar with weather per day + events
- **Variables used**: go_*, cal_d0_*–cal_d4_*, f0_*–f4_*

### go.weather (game=2)
- **File**: `plugin-go-weather.md`
- **URL**: `kaslo-api.php?key=KEY&game=2`
- **Sidebar**: Indoor temp → current conditions → stats → pressure sparkline → moon phase → solar dial
- **Variables used**: go_*, wx_*, fc_*, sun_*, moon_*

---

## Existing Standalone Plugins (unchanged)

### KasloTRMNL (weather full-screen)
- **File**: `KasloTRMNL.txt`
- **URLs**: IDX_0 = EcoWitt real-time API, IDX_1 = Open-Meteo 7-day
- **Layout**: 3 cols — current conditions / stats 2×2 grid / 7-day forecast
- **Note**: Uses `##{{ }}` prefix for Liquid (two polling URLs)

### plugin-todo-v5.2 (todo full-screen)
- **File**: `plugin-todo-v5.2.md`
- **URL**: `todo-device-api.php?key=KEY`
- **Layout**: Left=Urgent (large font) / Right=Important+Rest
- **Note**: Uses flat vars u0_text, i0_text, r0_text etc.

### plugin-calendar-v6 (calendar full-screen)
- **File**: `plugin-calendar-v6.md`
- **URL**: `cal-device-api.php?key=KEY`
- **Layout**: 7 rows × 3 cols (date / timed events / all-day + holidays)
- **Note**: Uses flat vars d0_dow, d0_dom, d0_tev0n etc.

### plugin-dgs-v3 (DGS multi-board)
- **File**: `plugin-dgs-v3.md`
- **URL**: `dgs-device-api.php?key=KEY`
- **Layout**: Main board (534px) + side panel with 2 compact boards + game list
- **Note**: THIS IS THE REFERENCE for board rendering and layout approach
- **Board**: innerHTML SVG, viewBox 420×420, board bg #D4A740
- **Rotation**: Changes every 30 min via `Math.floor(Date.now()/1800000)`

---

## Board Rendering Reference (from dgs-v3)

```
Container: width:534px, height:456px, background:#D4A740
Board div: width:444px, height:444px (square, anchored top-left)
SVG: width=444, height=444, viewBox="0 0 420 420"

Board colours:
  Background:  #D4A740 (golden amber)
  Grid lines:  #3A2000 (border 1.6px, inner 0.6px)
  Hoshi dots:  #3A2000
  Black stone: fill #111, stroke #000 0.4px + white glint ellipse
  White stone: fill #f8f8f8, stroke #444 1px
  Stone radius: min(cell*0.47, 11)
  Last move:   circle r=stone*0.32, fill #fff (on black) or #222 (on white)

Grid geometry (VW=VH=420, pad=14):
  gridPx = 420 - 14*2 = 392
  cell = 392/18 = 21.78px
  ox=oy=14
```

---

## Deployment Checklist

1. Upload PHP files to `/home/kw_9g92aw/knotwork.ca/`:
   - `kaslo-api.php` (go.* unified endpoint)
   - Existing: `weather-device-api.php`, `dgs-device-api.php`, `cal-device-api.php`, `todo-device-api.php`

2. After uploading `kaslo-api.php`, clear OPcache:
   ```
   curl https://knotwork.ca/kaslo-flush.php
   ```

3. In TRMNL, set polling URLs:
   - go.todo: `kaslo-api.php?key=KEY&game=0`
   - go.cal:  `kaslo-api.php?key=KEY&game=1`
   - go.weather: `kaslo-api.php?key=KEY&game=2`
   - KasloTRMNL IDX_0: EcoWitt URL, IDX_1: Open-Meteo URL
   - todo: `todo-device-api.php?key=KEY`
   - calendar: `cal-device-api.php?key=KEY`
   - DGS: `dgs-device-api.php?key=KEY`

4. Paste plugin markup into TRMNL Edit Markup → Full tab
   (copy only HTML between ``` fences, not the header comments)

5. Set all plugins: Strategy=Polling, Verb=GET, Remove bleed margin=Yes

6. Interval: 15 min minimum for go.* (DGS rate limit), others can be shorter

---

## Maintenance

### EcoWitt sharing key expires every ~30 days
- Refresh at: https://www.ecowitt.net/home/share
- After refreshing: update `REMINDER_ANCHOR` in `kaslo-api.php`
- The kaslo-api.php reminder will automatically surface in go.todo and go.cal

### DGS credentials
- Username: Shrimphead, UID: 24738
- Config: `/home/kw_9g92aw/knotwork.ca/config/dgs-config.json`
- Note: quick_status.php is broken; dgs-device-api.php uses login+scrape

### Cache locations
- `/tmp/kaslo4_om.json` — moon/pressure cache (1hr TTL)
- `/tmp/kaslo4_board_GAMEID.json` — board positions (15min TTL)
- `data/weather-cache.json` — EcoWitt+forecast (10min TTL)
- `data/dgs-cache.json` — DGS games (5min TTL)
- `data/cal-cache-v3.json` — calendar (20min TTL)
