# Kaslo TRMNL Dashboard  Implementation Reference

**Display**: TRMNL e-ink 800x480px, 16-level gray, scale_factor 1.8
**Server**: knotwork.ca | `/home/kw_9g92aw/knotwork.ca/`
**Local mirror**: `/Users/kendrick/Documents/WEBhosting/knotwork.ca/`
**Device key**: `kw_40e818911c1980bcd56dc4aff37a820f811990a130eb771fd9e21a536edc55ea`

---

## File Map

```
knotwork.ca/
 kaslo-api.php               MAIN AGGREGATOR  all plugins poll this
 kaslo-flush.php             Cache reset + rebuild (run after any PHP deploy)
 weather-device-api.php      EcoWitt + Open-Meteo  data/weather-cache.json
 cal-device-api.php          3x iCloud iCal  data/cal-cache-v3.json
 dgs-device-api.php          DGS scrape  data/dgs-cache.json
 todo-device-api.php         todo.json  JSON (used by legacy todo plugin)
 data/
    weather-cache.json      Written by weather-device-api.php (TTL 10 min)
    cal-cache-v3.json       Written by cal-device-api.php (TTL 20 min)
    dgs-cache.json          Written by dgs-device-api.php (TTL 5 min)
    todo.json               Hand-edited; read directly by kaslo-api.php
 /tmp/
    kaslo4_om.json          Moon phase + pressure (TTL 1 hr)
    kaslo4_brd_NNN.json     Board position per game (TTL 15 min)
 TRMNL/
     IMPLEMENTATION.md       This file
     plugin-dashboard.md     PRIMARY PLUGIN: full-screen dashboard
     plugin-go-todo.md       Go board + todo sidebar (game=0)
     plugin-go-cal.md        Go board + calendar sidebar (game=1)
     plugin-go-weather.md    Go board + weather sidebar (game=2)
     plugin-weather-v4.md    Full-screen weather panels
     KasloTRMNL.txt          Simple 3-column weather
     kaslo-api.php           Symlink/copy of server kaslo-api.php
```

---

## Dependency Graph

```
EcoWitt IKASLO6 
Open-Meteo (forecast + pressure) > weather-device-api.php                 
                                          > data/weather-cache.json       
                                                                             > kaslo-api.php > TRMNL plugins
3x iCloud iCal > cal-device-api.php                                        
                        > data/cal-cache-v3.json                          
                                                                             
DGS > dgs-device-api.php > data/dgs-cache.json 
                                                                             
data/todo.json (hand-edited) 
                                                                             
Open-Meteo (pressure history) > /tmp/kaslo4_om.json 
DGS SGF endpoint > /tmp/kaslo4_brd_NNN.json
```

**kaslo-api.php** is the single URL all dashboard/go plugins hit. It only
READS caches  it never re-fetches EcoWitt or iCal directly.

---

## PHP Files

### `kaslo-api.php`  Unified aggregator (PRIMARY)
**URL**: `https://knotwork.ca/kaslo-api.php?key=KEY[&game=N]`
**Used by**: plugin-dashboard, plugin-go-todo, plugin-go-cal, plugin-go-weather, plugin-weather-v4, KasloTRMNL

Reads from:
- `data/weather-cache.json`  current weather + 7-day forecast
- `data/cal-cache-v3.json`  28-day calendar events
- `data/dgs-cache.json`  DGS game list (only when `&game=N`)
- `data/todo.json`  todo items
- `/tmp/kaslo4_om.json`  moon phase + pressure (fetches Open-Meteo if stale)
- `/tmp/kaslo4_brd_NNN.json`  board position (fetches DGS SGF if stale)

Key constants to maintain:
```php
const REMINDER_ANCHOR = '2026-05-28';  // Update when EcoWitt key refreshed
const REMINDER_TEXT   = 'Refresh EcoWitt Sharing API';
```

---

### `kaslo-flush.php`  Full cache reset
**URL**: `https://knotwork.ca/kaslo-flush.php`
**Run after**: any PHP file deploy

Actions performed (in order):
1. OPcache invalidate + reset for kaslo-api.php
2. Delete `/tmp/kaslo4_om.json` (moon/pressure)
3. Delete `/tmp/kaslo4_brd_*.json` (all board caches)
4. Delete `data/weather-cache.json` (forces 7-day forecast rebuild)
5. HTTP GET `weather-device-api.php`  rebuilds weather cache
6. HTTP GET `cal-device-api.php`  rebuilds 28-day calendar cache

Output confirms: "Weather rebuilt: 7 forecast days" and "Calendar rebuilt: 28 days"

---

### `weather-device-api.php`  Weather data source
**URL**: `https://knotwork.ca/weather-device-api.php?key=KEY`
**Writes**: `data/weather-cache.json` (TTL 10 min)
**Used by**: kaslo-api.php reads its cache; must be polled separately to stay fresh

Sources:
- **EcoWitt IKASLO6**: current temp, humidity, wind, rain, pressure, UV, solar, indoor
- **Open-Meteo** (`forecast_days=7`): hi/lo, weather code, precip, sunrise/sunset for f0f6
- **cal-cache-v3.json**: merges calendar events into forecast days (f0_tev0n etc.)

Output flat keys: `temp`, `feels`, `condition`, `humidity`, `pressure`, `wind_kmh`, `gust_kmh`, `wdir`, `rain_day`, `rain_rate`, `rain_week`, `uvi`, `solar`, `indoor_temp`, `indoor_hum`, `sunrise`, `sunset`, `daylight`, `hi`, `lo` plus `f0_*` through `f6_*`

**Note**: `forecast_days=7` is set  all 7 days have hi/lo/condition data.

---

### `cal-device-api.php`  Calendar data source
**URL**: `https://knotwork.ca/cal-device-api.php?key=KEY`
**Writes**: `data/cal-cache-v3.json` (TTL 20 min)
**Used by**: kaslo-api.php reads its cache; weather-device-api.php merges it

Sources: 3 iCloud iCal feeds
- `personal` = BC EHS + WORK calendar (label used for EHS chicklet styling)
- `personal2` = Kendrick Stuff
- `personal3` = Family

Key features:
- **WINDOW_DAYS = 28** (4 weeks, d0d27)
- **RRULE expansion**: FREQ=DAILY, WEEKLY, MONTHLY  recurring events generate occurrences across the full 28-day window with correct UNTIL/COUNT handling
- **EXDATE**: exception dates respected (cancelled recurring instances skipped)
- **Name abbreviations** via `abbreviate_names()`:
  - "Sarah" / "sarah"  "SA"
  - "Skylet" / "skylet"  "SK"
  - "Kendrick" / "kendrick"  "KL"
- **tev{j}c**: calendar label per event (used for EHS chicklet class in dashboard)

Output: flat keys `d0_dow`, `d0_dom`, `d0_date`, `d0_today`, `d0_wknd`, `d0_hol`, `d0_tev0n/t/c`, `d0_tev1n/t/c`, `d0_tev2n/t/c`, `d0_tmore`, `d0_aev0/1/2`, `d0_amore` ... repeated for d0d27

---

### `dgs-device-api.php`  Dragon Go Server
**URL**: `https://knotwork.ca/dgs-device-api.php?key=KEY`
**Writes**: `data/dgs-cache.json` (TTL 5 min)
**Used by**: kaslo-api.php (reads cache when `&game=N`)

- User: shrimphead, UID 24738
- Login + HTML scrape (quick_status.php endpoint broken)
- DGS config: `/home/kw_9g92aw/knotwork.ca/config/dgs-config.json`

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

**JS layout proportions** (at 480px screen, 28px header):
- `avail` = 452px
- `TOP_H` = `floor(avail * 0.24)`  108px
- `FC_H` = `max(88, floor(avail * 0.18))`  88px
- `CAL_H` = avail - TOP_H - FC_H  256px
- `CAL_ROW1_H` = `floor(CAL_H * 0.32)`  81px (current week, bigger)
- `CAL_ROW_H` = `floor((CAL_H - CAL_ROW1_H) / 3)`  58px (weeks 2-4)

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

**Variables used**: weather bare aliases (`temp`, `condition`, `feels`, `hi`, `lo`, `wind_kmh`, `wdir`, `rain_day`, `humidity`, `pressure`, `indoor_temp`, `indoor_hum`, `sunrise`, `sunset`), `moon_phase`, `moon_name`, `f0_*``f6_*`, `cal_d0_*``cal_d27_*`, `todo_u0_*``todo_u5_*`, `todo_r0_*``todo_r7_*`, `todo_total`

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
- Hardcoded 800480px (non-adaptive)
- **Variables**: bare aliases (`temp`, `condition`, `sunrise` etc.) + `f0_*``f6_*`

---

## kaslo-api.php Variable Reference

All variables are flat top-level JSON keys  no nested objects, no arrays.

### Weather (bare aliases + wx_* prefix both available)
| Variable | Example | Source |
|---|---|---|
| `temp` / `wx_temp` | `"15"` | EcoWitt outdoor temp C |
| `feels` / `wx_feels` | `"13"` | EcoWitt feels-like C |
| `condition` / `wx_condition` | `"Clear"` | Open-Meteo WMO code |
| `hi` / `wx_hi` | `"22"` | Today forecast high C |
| `lo` / `wx_lo` | `"10"` | Today forecast low C |
| `wind_kmh` / `wx_wind_kmh` | `"10"` | EcoWitt wind speed |
| `gust_kmh` / `wx_gust_kmh` | `"15"` | EcoWitt gust |
| `wdir` / `wx_wdir` | `"NE"` | Compass direction |
| `rain_day` / `wx_rain_day` | `"0.0"` | Daily rain mm |
| `rain_rate` / `wx_rain_rate` | `"0.0"` | Rain rate mm/h |
| `rain_week` / `wx_rain_week` | `"2.4"` | Weekly rain mm |
| `humidity` / `wx_humidity` | `"74"` | Outdoor humidity % |
| `dew` / `wx_dew` | `"9"` | Dew point C |
| `pressure` / `wx_pressure` | `"948"` | Relative pressure hPa |
| `uvi` / `wx_uvi` | `"2"` | UV index |
| `solar` / `wx_solar` | `"320"` | Solar W/m |
| `indoor_temp` / `wx_indoor_temp` | `"23"` | Indoor temp C |
| `indoor_hum` / `wx_indoor_hum` | `"45"` | Indoor humidity % |
| `sunrise` / `wx_sunrise` | `"04:42"` | HH:MM |
| `sunset` / `wx_sunset` | `"20:48"` | HH:MM |
| `daylight` / `wx_daylight` | `"16h 06m daylight"` | Duration string |

### Forecast (f0_* through f6_*  7 days)
| Variable | Example | Notes |
|---|---|---|
| `f0_dow` | `"Tue"` | 3-letter day of week |
| `f0_hi` / `f0_lo` | `"22"` / `"10"` | C |
| `f0_condition` | `"Partly Cloudy"` | Used for icon lookup in JS |
| `f0_pop_str` | `"28%"` | Empty if 10% |
| `f0_mm_str` | `"0.3mm"` | Empty if <0.1mm |
| `f0_hol` | `"Canada Day"` | Holiday on that day |
| `f0_tev0n` / `f0_tev0t` | `"SA dentist"` / `"11:00"` | Timed event 0 |
| `f0_tev1n` / `f0_tev1t` | `""` / `""` | Timed event 1 |
| `f0_aev0` / `f0_aev1` | `"TRIP"` / `""` | All-day events |
| `fc_pressure_now` | `"948.2"` | Current pressure hPa |
| `fc_pressure_trend` | `"rising"` | rising / falling / steady |
| `fc_pressure_json` | `"[948,949,...]"` | Hourly history array |

### Sun & Moon
| Variable | Example | Notes |
|---|---|---|
| `sun_rise` / `sun_set` | `"04:42"` / `"20:48"` | For go.* plugins |
| `moon_phase` | `0.73` | 0=new, 0.5=full; Julian Date formula |
| `moon_name` | `"Waning Gibbous"` | Age-based name |

### Calendar (cal_d0_* through cal_d27_*  28 days)
| Variable | Example | Notes |
|---|---|---|
| `cal_d0_dow` | `"Tue"` | 3-letter DOW |
| `cal_d0_dom` | `"2"` | Day of month (no leading zero) |
| `cal_d0_date` | `"2026-06-02"` | ISO date |
| `cal_d0_month_label` | `"Jul"` | Only on first day of new month |
| `cal_d0_month_abbr` | `"Jun"` | 3-letter month every day (for today band) |
| `cal_d0_today` | `"today"` | `"today"` or `""` |
| `cal_d0_wknd` | `"wknd"` | `"wknd"` or `""` |
| `cal_d0_hol` | `"Canada Day"` | Holiday name or `""` |
| `cal_d0_tev0n` | `"SA dentist"` | Timed event 0 name (name-abbreviated) |
| `cal_d0_tev0t` | `"11:00"` | Timed event 0 time |
| `cal_d0_tev0c` | `"personal"` | Calendar label  "personal" = BC EHS |
| `cal_d0_tev1n/t/c` | | Timed event 1 |
| `cal_d0_tev2n/t/c` | | Timed event 2 |
| `cal_d0_tmore` | `"+1 more"` | Overflow count |
| `cal_d0_aev0/1/2` | `"TRIP"` | All-day events |
| `cal_d0_amore` | `""` | All-day overflow |
| `cal_d0_f_cond` | `"Rain"` | Forecast condition |
| `cal_d0_f_hi` / `cal_d0_f_lo` | `"22"` / `"10"` | Forecast hi/lo |
| `cal_d0_f_pop` | `"28%"` | Precip probability |
| `cal_d0_f_mm` | `"0.3mm"` | Precip amount |

**Name abbreviations** applied at parse time in cal-device-api.php:
- Sarah/sarah  SA  Skylet/skylet  SK  Kendrick/kendrick  KL

### Todo (todo_*)
| Variable | Example | Notes |
|---|---|---|
| `todo_total` | `"11"` | Total open items |
| `todo_u0_text``todo_u5_text` | `"Report Cards"` | Up to 6 urgent items |
| `todo_u0_meta``todo_u5_meta` | `"Tomorrow"` | Due date / tags |
| `todo_u_more` | `"+2 more"` | Overflow or `""` |
| `todo_r0_text``todo_r7_text` | `"Chop firewood"` | Up to 8 rest items |
| `todo_r0_meta``todo_r7_meta` | `""` | Due date |
| `todo_r_more` | `""` | Overflow |

### Go Board (go_*)
| Variable | Notes |
|---|---|
| `go_opponent` | DGS handle |
| `go_color` | `"b"` or `"w"` |
| `go_moves` | Move number |
| `go_time_left` | Time remaining |
| `go_my_turn_count` | Games awaiting your move |
| `go_total_games` | Active 1919 games |
| `go_board_black_json` | `"[[col,row],...]"` |
| `go_board_white_json` | `"[[col,row],...]"` |
| `go_last_col/row/color` | Last move |

---

## CRITICAL LAYOUT RULE

Use `width:100%;height:100%` on html/body. Compute ALL dimensions from
`window.innerWidth` / `window.innerHeight` in JS. Stamp explicit px values
onto DOM. Never hardcode 800px or 480px.

```js
var VW = window.innerWidth  || 800;
var VH = window.innerHeight || 480;
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
Always flush  this resets OPcache AND rebuilds both weather and calendar caches.

**TRMNL Plugin URLs:**
| Plugin | File | URL |
|---|---|---|
| Dashboard  | plugin-dashboard.md | `kaslo-api.php?key=KEY` |
| Go + Todo | plugin-go-todo.md | `kaslo-api.php?key=KEY&game=0` |
| Go + Calendar | plugin-go-cal.md | `kaslo-api.php?key=KEY&game=1` |
| Go + Weather | plugin-go-weather.md | `kaslo-api.php?key=KEY&game=2` |
| Weather panels | plugin-weather-v4.md | `kaslo-api.php?key=KEY` |
| Weather simple | KasloTRMNL.txt | `kaslo-api.php?key=KEY` |
| (legacy) Todo | plugin-todo-v5.2.md | `todo-device-api.php?key=KEY` |
| (legacy) Calendar | plugin-calendar-v6.md | `cal-device-api.php?key=KEY` |
| (layout ref) DGS | plugin-dgs-v3.md | `dgs-device-api.php?key=KEY` |

**Plugin settings**: Strategy=Polling, Verb=GET, Remove bleed margin=Yes
**Paste**: markup between the ` ```html ` fences only
**Intervals**: go.* plugins 15 min (DGS TTL), weather 10 min, dashboard 15 min

---

## Maintenance

### EcoWitt API key (~every 30 days)
1. Refresh at ecowitt.net
2. Update `api_key` in `weather-device-api.php` and `ECOWITT_URL`
3. Update `REMINDER_ANCHOR` in `kaslo-api.php` to today's date
4. Run `kaslo-flush.php`

### BC Holidays
- Defined in `cal-device-api.php`  `BC_HOLIDAYS` array
- Covers 2026  add 2027 dates before year-end

### Calendar name abbreviations
- Defined in `cal-device-api.php`  `abbreviate_names()` function
- Current: SarahSA, SkyletSK, KendrickKL
- Applied at parse time; takes effect after cache rebuild (kaslo-flush)

### RRULE recurring events
- `cal-device-api.php` expands FREQ=DAILY, WEEKLY, MONTHLY
- UNTIL (with or without time component), COUNT, INTERVAL all handled
- EXDATE exception dates respected
- BYDAY not expanded (uses start-date's weekday for weekly events)

### Open-Meteo moon_phase discontinued
- `daily=moon_phase` returns error  do not add back
- Moon phase computed via `compute_moon_phase()` (Julian Date) in kaslo-api.php
- Same formula in plugin-weather-v4.md JS  results match

### Cache locations
| File | Written by | TTL | Contents |
|---|---|---|---|
| `data/weather-cache.json` | weather-device-api.php | 10 min | EcoWitt + 7-day forecast |
| `data/cal-cache-v3.json` | cal-device-api.php | 20 min | 28-day calendar, RRULE expanded |
| `data/dgs-cache.json` | dgs-device-api.php | 5 min | DGS game list |
| `/tmp/kaslo4_om.json` | kaslo-api.php | 1 hr | Moon phase + hourly pressure |
| `/tmp/kaslo4_brd_NNN.json` | kaslo-api.php | 15 min | Board position per game ID |

### weather-device-api.php must be polled externally
- kaslo-api.php reads FROM its cache but never refreshes it
- Ensure at least one TRMNL plugin polls `weather-device-api.php?key=KEY` every 10 min
- kaslo-flush.php now does this once on demand  not sufficient for continuous freshness
