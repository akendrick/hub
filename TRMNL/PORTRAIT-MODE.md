# Portrait Mode — TRMNL X

**Device:** TRMNL X (1040×780 landscape / 780×1040 portrait)
**Status:** Natively supported — no CSS hacks required

---

## Enabling Portrait Mode

Device Settings > Color & Rotation > Screen Rotation → **Portrait** → Save

The TRMNL X accelerometer detects physical orientation. After saving, the backend
regenerates screens at the plugin's normal refresh rate. Use **Force Refresh** in the
dashboard to apply immediately.

---

## Dimensions

| Orientation | CSS width | CSS height | Native panel |
|---|---|---|---|
| Landscape (default) | 1040px | 780px | 1872×1404 |
| Portrait | 780px | 1040px | 1404×1872 |

Scale factor is 1.8 in both orientations. `window.innerWidth` and `window.innerHeight`
report the correct values natively — no manual swapping needed.

---

## JS Layout in Portrait

Because `window.innerWidth/Height` are correct natively, the standard layout pattern
works unchanged. The fallback values just need to reflect portrait dimensions:

```js
// Landscape plugin
var VW = window.innerWidth  || 1040;
var VH = window.innerHeight || 780;

// Portrait plugin
var VW = window.innerWidth  || 780;
var VH = window.innerHeight || 1040;
var HDR = 28;
var avail = VH - HDR;   // 1012px available below header
```

---

## Framework Support

The TRMNL X framework uses the `screen--v2` device class. The `screen--portrait`
modifier swaps the CSS dimension variables automatically:

```
CSS variable   Landscape    Portrait
--screen-w     1040px       780px
--screen-h     780px        1040px
```

Orientation-specific utility prefix: `portrait:` (e.g. `portrait:grid--cols-1`).
Rich text content fills the full screen width in portrait automatically.

---

## Portrait Layout Proportions (plugin-dashboard reference)

At 780px wide × 1040px tall with 28px header:

- `avail` = 1012px
- A single-column stacked layout works well: weather → todo → forecast → calendar
- `TOP_H` = `floor(1012 * 0.20)` → 202px (weather + solar + moon)
- `FC_H` = `floor(1012 * 0.20)` → 202px (7-day forecast — more room than landscape)
- `CAL_H` = 1012 - 202 - 202 → 608px (calendar — dominant section)
- `CAL_ROW1_H` = `floor(608 * 0.28)` → 170px (current week, larger)
- `CAL_ROW_H` = `floor((608 - 170) / 3)` → 146px (weeks 2–4)

These proportions give the calendar more breathing room in portrait than in landscape.
Adjust to taste — the proportions are not fixed rules.

---

## Landscape Slot Sizes (for mashup plugins)

TRMNL X supports split-screen layouts. CSS viewport per slot:

| Slot | Width | Height |
|---|---|---|
| Full | 1040px | 780px |
| Half horizontal (top/bottom) | 1040px | 390px |
| Half vertical (left/right) | 520px | 780px |
| Quadrant | 520px | 390px |

In portrait, width and height swap in each slot (e.g. full portrait = 780×1040).

---

## Plugin Settings Reminder

All TRMNL X plugins:

| Setting | Value |
|---|---|
| Strategy | Polling |
| Verb | GET |
| Remove bleed margin | Yes |
| Screen Rotation | Portrait (if using portrait layout) |
