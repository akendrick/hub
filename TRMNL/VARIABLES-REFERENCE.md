# kaslo-api.php — Variable Quick Reference

All variables are flat top-level JSON keys. Access in Liquid as `{{ variable_name }}`
(single polling URL = IDX_0 namespace).

**Device:** TRMNL X — landscape 1040×780, portrait 780×1040

Polling URL: `https://knotwork.ca/kaslo-api.php?key=KEY[&game=N]`

> **Note on nested vs flat:** The response also contains nested objects (`wx`, `fc`,
> `sun`, `moon`, `todo`, `cal`, `go`) at the top level. TRMNL auto-flattens one level
> deep using underscores, so `fc.days` and `go.opponent` are accessible as `fc_days`
> and `go_opponent`. Prefer the explicit flat keys listed below where they exist.

---

## Weather — current conditions

Bare names only — no `wx_*` prefix in the flat output.

| Variable | Example | Notes |
|---|---|---|
| `temp` | `"15"` | Outdoor temp °C, integer string (EcoWitt) |
| `feels` | `"13"` | Feels-like °C, integer string |
| `hi` | `"22"` | Today forecast high °C |
| `lo` | `"10"` | Today forecast low °C |
| `condition` | `"Partly Cloudy"` | WMO condition string (from Open-Meteo) |
| `wind_kmh` | `"10"` | Wind speed km/h, integer string |
| `gust_kmh` | `"15"` | Gust km/h, integer string |
| `wdir` | `"NE"` | Compass direction (8-point) |
| `rain_day` | `"0.0"` | Daily rain mm |
| `rain_rate` | `"0.0"` | Rain rate mm/h |
| `rain_week` | `"2.4"` | Weekly rain mm |
| `humidity` | `"74"` | Outdoor humidity % |
| `dew` | `"9"` | Dew point °C, integer string |
| `pressure` | `"948"` | Relative pressure hPa |
| `uvi` | `"2"` | UV index |
| `solar` | `"320"` | Solar radiation W/m², integer string |
| `indoor_temp` | `"23"` | Indoor temp °C, integer string |
| `indoor_hum` | `"45"` | Indoor humidity % |
| `sunrise` | `"04:42"` | Today sunrise HH:MM (from Open-Meteo) |
| `sunset` | `"20:48"` | Today sunset HH:MM (from Open-Meteo) |
| `moon_phase` | `0.73` | Float 0–1: 0=new, 0.25=first quarter, 0.5=full |
| `moon_name` | `"Waning Gibbous"` | One of 8 age-based names |

> `temp_full` (full-precision float string) and `wx.*` sub-fields are in the
> nested `wx` object but not exposed as flat keys.

---

## Forecast — 7 days (f0_* through f6_*)

`f0` = today, `f1` = tomorrow, … `f6` = 6 days out. Calendar events are now merged
into forecast days correctly (sourced from iCal, not weather cache).

| Variable | Example | Notes |
|---|---|---|
| `f0_dow` | `"Tue"` | 3-letter day of week |
| `f0_hi` | `"22"` | High °C |
| `f0_lo` | `"10"` | Low °C |
| `f0_condition` | `"Partly Cloudy"` | WMO condition string (use for JS icon lookup) |
| `f0_pop_str` | `"28%"` | Precip probability; `""` if 0% |
| `f0_mm_str` | `"0.3mm"` | Precip amount; `""` if 0mm |
| `f0_hol` | `"Canada Day"` | BC holiday name or `""` |
| `f0_tev0n` / `f0_tev0t` | `"SA dentist"` / `"11:00"` | Timed event 0 name / time |
| `f0_tev1n` / `f0_tev1t` | `""` / `""` | Timed event 1 |
| `f0_tmore` | `"+1 more"` | Overflow timed events or `""` |
| `f0_aev0` / `f0_aev1` / `f0_aev2` | `"TRIP"` | All-day events |
| `f0_amore` | `""` | All-day overflow count or `""` |

Pressure and moon summary are in the nested `fc` object:
`fc_pressure_now` (float), `fc_pressure_trend` (`"rising"`/`"falling"`),
`fc_pressure_history_json` (JSON array of hourly hPa readings).

---

## Calendar — 14 days (cal_d0_* through cal_d13_*)

`cal_d0` = today, `cal_d13` = 13 days out. **Calendar window is 14 days** (`CAL_DAYS=14`).

| Variable | Example | Notes |
|---|---|---|
| `cal_d0_date` | `"2026-06-02"` | ISO date |
| `cal_d0_dow` | `"Tue"` | 3-letter DOW |
| `cal_d0_dom` | `"2"` | Day of month, no leading zero |
| `cal_d0_month_label` | `"Jul"` | 3-letter month; non-empty **only** on 1st day of month |
| `cal_d0_today` | `"today"` | `"today"` or `""` |
| `cal_d0_wknd` | `"wknd"` | `"wknd"` or `""` |
| `cal_d0_hol` | `"Canada Day"` | BC holiday name or `""` |
| `cal_d0_tev0n` | `"SA dentist"` | Timed event 0 name |
| `cal_d0_tev0t` | `"11:00"` | Timed event 0 time |
| `cal_d0_tev0c` | `""` | Calendar label — always `""` in current version |
| `cal_d0_tev1n/t/c` | | Timed event 1 |
| `cal_d0_tev2n/t/c` | | Timed event 2 |
| `cal_d0_tmore` | `"+1 more"` | Overflow timed events or `""` |
| `cal_d0_aev0` / `cal_d0_aev1` / `cal_d0_aev2` | `"TRIP"` | All-day events |
| `cal_d0_amore` | `""` | All-day overflow or `""` |

> `cal_d*_f_cond`, `cal_d*_f_hi`, `cal_d*_f_lo`, `cal_d*_f_pop`, `cal_d*_f_mm`,
> `cal_d*_month_abbr` — **not available** in the current version.

### Todo sources — calendar integration

All events from the **"Kendrick Stuff"** iCal feed are treated as todo items (not shown
on the calendar grid). Any event in any feed whose title matches `TODO: ...` is also
extracted as a todo. These merge with `data/todos.json` items.

---

## Todo (todo_*)

Only urgent-item text is available as explicit flat keys.
All detail (due labels, overdue flag, rest items) is in the nested `todo` object.

| Variable | Example | Notes |
|---|---|---|
| `todo_u0_text` … `todo_u5_text` | `"Report Cards"` | Up to 6 urgent items, text only |

For richer access in templates, use the nested object:
- `todo.total` — total open item count
- `todo.urgent[N].due_label` — `"Today"` / `"Tomorrow"` / `"3d overdue"` / `"Jun 12"`
- `todo.urgent[N].overdue` — boolean
- `todo.rest[N].text` — rest-priority items

**Urgency rules:**
- Priority ≤ 2 → urgent
- Overdue (past due date) → urgent (sorts to top, persists until deleted)
- Due within 3 days → urgent
- Everything else → rest

---

## Go Board (go_*) — requires &game=N

Go data is in the nested `go` object. TRMNL auto-flattens to `go_*` in Liquid.

| Variable | Example | Notes |
|---|---|---|
| `go_opponent` | `"GreenMonk"` | DGS handle |
| `go_color` | `"b"` | Your colour: `"b"` or `"w"` |
| `go_moves` | `47` | Move number |
| `go_time_left` | `"3d 2h"` | Time remaining string |
| `go_my_turn` | `true` | Boolean |
| `go_my_turn_count` | `2` | Games awaiting your move |
| `go_total_games` | `5` | Total active 19×19 games |
| `go_game_index` | `0` | Which game slot (0/1/2) |
| `go_board_black_json` | `"[[3,4],[5,6],...]"` | Black stone positions [col,row] 1-indexed |
| `go_board_white_json` | `"[[2,3],...]"` | White stone positions |
| `go_last_col` | `10` | Last move column |
| `go_last_row` | `7` | Last move row |
| `go_last_color` | `"b"` | Colour of last move |

---

## Cache TTLs

| Cache | Key | TTL | Source |
|---|---|---|---|
| Weather | `/tmp/kaslo_wx.json` | 300s (5 min) | EcoWitt v3 API |
| Forecast + moon | `/tmp/kaslo_forecast3.json` | 1800s (30 min) | Open-Meteo |
| DGS board | `/tmp/kaslo_dgs_board_NNN.json` | 900s (15 min) | DGS SGF endpoint |
| Calendar + todos | `/tmp/kaslo_ical.json` | 1800s (30 min) | 4× iCloud iCal feeds |

---

## TRMNL built-in variable

| Variable | Notes |
|---|---|
| `trmnl.user.time` | Device local time; use with Liquid date filter |

Usage: `{{ trmnl.user.time | date: "%H:%M · %a %-d" }}`
