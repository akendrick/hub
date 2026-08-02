# Kaslo TRMNL Dashboard

Personal e-ink dashboard for Kaslo BC — showing Go games, weather, calendar, and todos
on a TRMNL X display.

**Server:** knotwork.ca  
**Display:** TRMNL X (1040×780 landscape / 780×1040 portrait, 16-level gray, scale_factor 1.8, native panel 1872×1404)  
**Local mirror:** `/Users/kendrick/Documents/WEBhosting/knotwork.ca/`

---

## Documentation

| Document | Purpose |
|---|---|
| [IMPLEMENTATION.md](IMPLEMENTATION.md) | Architecture, dependency graph, file map, PHP API reference, layout proportions, deployment checklist |
| [BEST-PRACTICES.html](BEST-PRACTICES.html) | PHP gotchas, Liquid rules, testing strategy (5 levels), deployment checklist |
| [VARIABLES-REFERENCE.md](VARIABLES-REFERENCE.md) | Quick-lookup table for every kaslo-api.php output variable |
| [PORTRAIT-MODE.md](PORTRAIT-MODE.md) | Portrait orientation research — OG device limitations, CSS transform workaround, TRMNL X comparison |

---

## Plugins

| Plugin | File | URL param |
|---|---|---|
| Full dashboard | [plugin-dashboard.md](plugin-dashboard.md) | `kaslo-api.php?key=KEY` |
| 2-week calendar | [plugin-cal2wks.md](plugin-cal2wks.md) | `kaslo-api.php?key=KEY` |
| Go + Todo | [plugin-go-todo.md](../WEBhosting/knotwork.ca/TRMNL/plugin-go-todo.md) | `&game=0` |
| Go + Calendar | [plugin-go-cal.md](../WEBhosting/knotwork.ca/TRMNL/plugin-go-cal.md) | `&game=1` |
| Go + Weather | [plugin-go-weather.md](../WEBhosting/knotwork.ca/TRMNL/plugin-go-weather.md) | `&game=2` |
| Weather panels | [plugin-weather-v4.md](plugin-weather-v4.md) | `kaslo-api.php?key=KEY` |
| Weather simple | [KasloTRMNL.txt](KasloTRMNL.txt) | `kaslo-api.php?key=KEY` |

---

## PHP Backend

| File | Role |
|---|---|
| `kaslo-api.php` | Self-contained endpoint — fetches EcoWitt, Open-Meteo, iCal, DGS directly |
| `kaslo-flush.php` | OPcache reset + delete `/tmp/kaslo_*.json` — run after every PHP deploy |
| `data/todos.json` | Hand-edited todo items (read directly by kaslo-api.php) |

---

## Testing

```bash
# Syntax check all PHP files + remote smoke test
chmod +x kaslo-test.sh
./kaslo-test.sh

# Syntax check only (no network)
./kaslo-test.sh syntax

# Remote smoke test only (after deploy)
./kaslo-test.sh remote
```

See [BEST-PRACTICES.html](BEST-PRACTICES.html) § Testing Strategy for the full five-level approach.

---

## Quick deploy checklist

1. `php -l kaslo-api.php` — no errors
2. `grep -P '[^\x00-\x7F]' kaslo-api.php` — no output
3. Upload via SFTP
4. `curl https://knotwork.ca/kaslo-flush.php` — OPcache + cache reset confirmed
5. `./kaslo-test.sh remote` — all green
