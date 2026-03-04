<?php
// ── Inline cache injection ─────────────────────────────────────────────────
// Reads server-side cache files and embeds them directly into the page HTML.
// The browser receives last-known-good data with zero extra HTTP round-trips.
$_KW_CACHE_DIR = __DIR__ . '/cache/';
function kw_read_cache(string $k): string {
    global $_KW_CACHE_DIR;
    $f = $_KW_CACHE_DIR . $k . '.json';
    if (!file_exists($f) || !is_readable($f)) return 'null';
    $raw = file_get_contents($f);
    if (!$raw || json_decode($raw) === null) return 'null';
    return json_encode([
        'ts' => filemtime($f),          // unix timestamp of cache write
        'age' => time() - filemtime($f), // seconds old at render time
        'd'  => json_decode($raw),       // the actual data payload
    ]);
}
$_kw_fc  = kw_read_cache('forecast');
$_kw_ic  = kw_read_cache('ical');
$_kw_obs = kw_read_cache('obs');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>IKASLO6 · Kaslo BC</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
html, body {
  width: 100%; max-width: 1440px; margin: 0 auto;
  min-height: 100vh; height: auto;
  overflow-x: hidden;
  background: #fff; color: #000;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}
body { padding: 10px; display: flex; flex-direction: column; gap: 0; }

/* ── HEADER BAR ── */
.hdr {
  display: flex; align-items: center; gap: 10px;
  border-bottom: 2px solid #000;
  padding-bottom: 5px; margin-bottom: 8px;
  flex-shrink: 0;
}
.hdr-left { font-weight: 700; font-size: 13px; letter-spacing: 2px; text-transform: uppercase; flex-shrink: 0; }
.hdr-meta {
  flex: 1; display: flex; align-items: center; gap: 16px;
  padding: 0 14px;
  border-left: 1px solid #ddd; border-right: 1px solid #ddd;
}
.hdr-meta-item { display: flex; align-items: center; gap: 5px; font-size: 10px; color: #888; white-space: nowrap; }
.hdr-meta-item b { color: #222; font-weight: 600; }
.dot { display: inline-block; width: 7px; height: 7px; border-radius: 50%; background: #000; animation: blink 2s infinite; flex-shrink: 0; }
.dot.off { background: #bbb; animation: none; }
@keyframes blink { 0%,100%{opacity:1} 50%{opacity:0.2} }
.hdr-right { font-size: 11px; color: #555; flex-shrink: 0; }
.hdr-logout { font-size: 10px; color: #bbb; text-decoration: none; letter-spacing: 0.5px; text-transform: uppercase; flex-shrink: 0; padding: 2px 6px; border: 1px solid #eee; }
.hdr-logout:hover { color: #000; border-color: #000; }

/* ═══════════════════════════════════════════════
   ROW A: 3-column hero (full width — todo is now COL2)
   COL1 outdoor ≈ 45%  |  COL2 todo ≈ 20%  |  COL3 light+indoor ≈ 35%
═══════════════════════════════════════════════ */
.main-panels {
  display: grid;
  grid-template-columns: 1fr;   /* hero spans full width */
  gap: 0;
  border: 2px solid #000;
  border-bottom: 3px solid #000;
  flex-shrink: 0;
}

/* 3-column hero panel */
.hero-panel {
  display: grid;
  grid-template-columns: 5fr 4fr 3fr;
  overflow: hidden;
  min-height: 230px;
}

/* ── COL1: outdoor temp + icon side by side + feels row + stats ── */
.hero-col1 {
  display: flex; flex-direction: column;
  padding: 10px 12px 8px;
  border-right: 1px solid #e8e8e8;
}
/* Temp and weather icon sit side by side at the top */
.hero-top-row {
  display: flex; align-items: flex-start; gap: 28px; flex-shrink: 0;
}
.hero-temp-wrap { display: flex; align-items: flex-start; }
.hero-temp {
  font-size: 110px; font-weight: 800;
  line-height: 0.88; letter-spacing: -4px; color: #000;
  display: flex; align-items: flex-start;
}
.hero-temp-unit { font-size: 22px; font-weight: 300; color: #555; letter-spacing: 0; margin-top: 7px; }
/* Weather icon block — same height-weight as temp, vertically centered */
.hero-icon-block {
  display: flex; flex-direction: column; align-items: center;
  justify-content: center; padding-top: 4px; gap: 2px;
}
.hero-wx-icon-big { font-size: 141px; line-height: 1; }
.hero-wx-desc { font-size: 9px; color: #555; text-align: center; text-transform: uppercase; letter-spacing: 0.5px; }
.hero-wx-precip { font-size: 10px; color: #555; text-align: center; margin-top: 3px; }
/* Feels / Hum / Dew — all on ONE horizontal line */
.hero-feels-row {
  display: flex; flex-wrap: wrap; gap: 10px 18px;
  padding: 5px 0 4px;
  border-top: 1px solid #eee; border-bottom: 1px solid #eee;
  margin: 5px 0;
}
.hero-feels { font-size: 11px; color: #777; white-space: nowrap; }
.hero-feels b { color: #000; font-weight: 700; font-size: 13px; }
/* 2×2 mini-stats grid — bottom of COL1 */
.hero-stats {
  display: grid; grid-template-columns: repeat(2,1fr);
  border-top: 1px solid #eee;
  margin-top: auto; padding-top: 0; gap: 0;
}
.hstat {
  padding: 2px 6px;
  border-right: 1px solid #eee;
  border-bottom: 1px solid #f0f0f0;
  display: flex; flex-direction: column; justify-content: center;
}
.hstat:nth-child(even) { border-right: none; }
.hstat:nth-child(n+3)  { border-bottom: none; }
.hstat-lbl { font-size: 7px; font-weight: 700; letter-spacing: 1.2px; text-transform: uppercase; color: #bbb; }
.hstat-val { font-size: 13px; font-weight: 700; line-height: 1; white-space: nowrap; }
.hstat-unit { font-size: 9px; font-weight: 400; color: #888; margin-left: 1px; }
.hstat-sub { font-size: 8px; color: #888; white-space: nowrap; }
.hstat-trend { display: inline-block; font-size: 7px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; border: 1px solid #000; padding: 1px 3px; margin-top: 2px; align-self: flex-start; }
.hstat-precip-row { display: flex; gap: 6px; margin-top: 1px; }
.hph-lbl { font-size: 7px; color: #bbb; letter-spacing: 0.8px; text-transform: uppercase; }
.hph-val { font-size: 11px; font-weight: 700; }
.hph-unit { font-size: 7px; color: #aaa; }

/* ── COL2: TODO panel ── */
.hero-col2 {
  display: flex; flex-direction: column;
  border-right: 1px solid #e8e8e8;
  overflow: hidden;
}
/* TODO PANEL with black header bar */
.todo-panel {
  flex: 1; display: flex; flex-direction: column; overflow: hidden;
}
.todo-header {
  background: #000; color: #fff;
  font-size: 10px; font-weight: 700; letter-spacing: 2px;
  text-transform: uppercase;
  padding: 7px 10px;
  display: flex; align-items: center; justify-content: space-between;
  flex-shrink: 0;
}
.todo-header a { color: #888; text-decoration: none; font-size: 13px; line-height: 1; }
.todo-header a:hover { color: #fff; }
.todo-list-inner { padding: 6px 10px; display: flex; flex-direction: column; gap: 3px; flex: 1; overflow-y: auto; }
.todo-item {
  display: flex; flex-direction: column;
  font-size: 11px; color: #333; line-height: 1.4;
  padding: 3px 0; border-bottom: 1px solid #f0f0f0;
}
.todo-item-text { font-size: 11px; font-weight: 600; color: #222; line-height: 1.3; }
.todo-item-meta { font-size: 9px; color: #aaa; line-height: 1.3; margin-top: 1px; }
.todo-text.done { text-decoration: line-through; color: #bbb; }

/* ── COL3: Light + Indoor ── */
.hero-col3 {
  display: flex; flex-direction: column;
  padding: 8px 10px 8px;
  gap: 5px;
}
/* Daylight arc + moon side by side */
.light-moon-row {
  display: flex; align-items: flex-end; justify-content: center; gap: 10px;
}
.daylight-wrap { display: flex; flex-direction: column; align-items: center; gap: 2px; }
.daylight-canvas { display: block; width: 130px !important; height: 130px !important; }
.daylight-hrs { font-size: 8px; color: #888; letter-spacing: 0.3px; text-align: center; }
.moon-wrap-big { display: flex; flex-direction: column; align-items: center; gap: 3px; }
.moon-wrap-big canvas { display: block; width: 80px; height: 80px; }
.moon-lbl { font-size: 8px; color: #aaa; letter-spacing: 0.5px; text-transform: uppercase; text-align: center; }
/* Sunrise / sunset with SVG icons */
.sun-times-row {
  display: flex; justify-content: space-around; align-items: center;
  border-top: 1px solid #eee; border-bottom: 1px solid #eee;
  padding: 5px 0;
}
.sun-time-item { display: flex; align-items: center; gap: 6px; }
.sun-svg { display: block; flex-shrink: 0; }
.sun-time { font-size: 13px; font-weight: 700; color: #333; white-space: nowrap; }
/* Indoor section at bottom of COL3 */
.indoor-section {
  display: flex; align-items: baseline; gap: 10px;
  padding-top: 2px;
  justify-content: center;
}
.hero-indoor-lbl { font-size: 8px; color: #bbb; text-transform: uppercase; letter-spacing: 1px; }
.hero-indoor-val { font-size: 73px; font-weight: 700; color: #444; line-height: 1.0; }
.hero-indoor-unit { font-size: 18px; color: #888; font-weight: 300; }
.hero-indoor-hum-block { display: flex; flex-direction: column; align-items: flex-start; gap: 2px; }
.hero-indoor-hum { font-size: 13px; color: #888; font-weight: 500; }

/* ── ROW B: DAY LABELS (week 1 weather) ── */
.day-label-strip {
  display: grid; grid-template-columns: repeat(7,1fr);
  border-left: 2px solid #000; border-right: 2px solid #000;
  border-bottom: 1px solid #ddd;
  flex-shrink: 0;
}
.day-label-col {
  display: flex; flex-direction: row; align-items: center; justify-content: center;
  padding: 5px 4px;
  border-right: 1px solid #eee;
  gap: 5px;
}
.day-label-col:last-child { border-right: none; }
/* All week-1 day bars are grey; weekdays mid-grey, weekends black */
.day-label-col         { background: #f0f0f0; }
.day-label-col .dl-name { color: #555; }
.day-label-col .dl-date { color: #666; }
.day-label-col.today   { background: #e4e4e4; }
.day-label-col.weekend { background: #000; border-right-color: #222; }
.day-label-col.weekend .dl-name { color: #fff; }
.day-label-col.weekend .dl-date { color: #aaa; }
.day-label-col.today.weekend { background: #222; }
.dl-name { font-size: 15px; font-weight: 700; letter-spacing: 0.5px; text-transform: uppercase; }
.dl-date { font-size: 15px; font-weight: 600; }

/* ── ROW C: CHART ── */
.chart-wrap {
  border-left: 2px solid #000; border-right: 2px solid #000;
  flex-shrink: 0;
}
canvas.main-chart { width: 100% !important; display: block; }

/* ── ROW D: FORECAST DETAIL STRIP ── */
.fc-strip {
  display: grid; grid-template-columns: repeat(7,1fr);
  border: 2px solid #000; border-top: none;
  border-bottom: 1px solid #ddd;  /* lighter separator before calendar */
  flex-shrink: 0;
}
.fc-col {
  display: flex; flex-direction: column; align-items: center;
  padding: 5px 3px 6px;
  border-right: 1px solid #eee; gap: 2px;
}
.fc-col:last-child { border-right: none; }
.fc-col.today { background: #f7f7f7; }
.fc-col.weekend { background: #000; border-right-color: #222; }
.fc-col.weekend .fc-hi { color: #fff; }
.fc-col.weekend .fc-lo { color: #777; }
.fc-col.weekend .fc-mm-big { color: #ccc; }
.fc-col.weekend .fc-pop-big { color: #888; }
.fc-col.today.weekend { background: #222; }
/* Weekend icon visibility — strong invert + white glow so emoji read on black bg */
.fc-col.weekend .fc-icon { filter: invert(1) hue-rotate(180deg) brightness(1.5); }
.fc-mid { display: flex; align-items: center; gap: 5px; }
.fc-icon { font-size: 30px; line-height: 1; }
.fc-precip-side { display: flex; flex-direction: column; align-items: flex-start; gap: 1px; }
.fc-mm-big { font-size: 11px; font-weight: 700; color: #444; white-space: nowrap; }
.fc-pop-big { font-size: 10px; color: #888; white-space: nowrap; }
.fc-hilo { display: flex; gap: 5px; align-items: baseline; }
.fc-hi { font-size: 15px; font-weight: 700; color: #000; }
.fc-lo { font-size: 12px; font-weight: 400; color: #888; }

/* ── ROW E: WEEK-1 CALENDAR ── */
.cal-strip {
  display: grid; grid-template-columns: repeat(7,1fr);
  border-left: 2px solid #000; border-right: 2px solid #000;
  flex-shrink: 0; min-height: 120px;  /* 3+ events visible per cell */
}
/* ── MULTI-DAY ZONE (seamless below cal-strip) ── */
.multiday-zone {
  display: grid; grid-template-columns: repeat(7,1fr);
  grid-auto-rows: 17px;
  gap: 2px 0;
  border-left: 2px solid #000; border-right: 2px solid #000;
  border-bottom: 3px solid #000;
  padding: 3px 0 3px;
  min-height: 0;
}
.multiday-zone:not(:has(.md-bar)) { display: none; }
/* Fallback for browsers that don't support :has() */
.multiday-zone.has-events { display: grid; }
.multiday-zone.no-events  { display: none; }
.md-bar {
  position: relative; overflow: hidden;
  background: #c8c8c8;
  height: 15px; margin: 0;   /* flush — no gaps between day columns */
  border-radius: 0;
}
.md-label {
  position: absolute; right: 5px; top: 50%; transform: translateY(-50%);
  font-size: 9px; font-weight: 700; color: #222;
  white-space: nowrap; letter-spacing: 0.2px; text-transform: uppercase;
  letter-spacing: 0.5px;
}
/* Week 2 — plain calendar header */
.cal2-label-strip {
  display: grid; grid-template-columns: repeat(7,1fr);
  border-left: 2px solid #000; border-right: 2px solid #000;
  border-top: 2px solid #000; border-bottom: 1px solid #ccc;
  background: #f0f0f0;   /* light grey for visual separation */
  flex-shrink: 0;
}
/* Week 2 day-label colours: dark grey text, darker on weekend */
.cal2-label-strip .day-label-col { background: #f0f0f0; }
.cal2-label-strip .dl-name { color: #555; }
.cal2-label-strip .dl-date { color: #666; }
.cal2-label-strip .day-label-col.weekend { background: #d8d8d8; border-right-color: #ccc; }
.cal2-label-strip .day-label-col.weekend .dl-name { color: #333; }
.cal2-label-strip .day-label-col.weekend .dl-date { color: #555; }
.cal-strip-2 {
  display: grid; grid-template-columns: repeat(7,1fr);
  border-left: 2px solid #000; border-right: 2px solid #000;
  flex-shrink: 0; min-height: 120px;
}
/* Week 3 — same visual style as week 2 */
.cal3-label-strip {
  display: grid; grid-template-columns: repeat(7,1fr);
  border-left: 2px solid #000; border-right: 2px solid #000;
  border-top: 2px solid #000; border-bottom: 1px solid #ccc;
  background: #f0f0f0;
  flex-shrink: 0;
}
.cal3-label-strip .day-label-col { background: #f0f0f0; }
.cal3-label-strip .dl-name { color: #555; }
.cal3-label-strip .dl-date { color: #666; }
.cal3-label-strip .day-label-col.weekend { background: #d8d8d8; border-right-color: #ccc; }
.cal3-label-strip .day-label-col.weekend .dl-name { color: #333; }
.cal3-label-strip .day-label-col.weekend .dl-date { color: #555; }
.cal-strip-3 {
  display: grid; grid-template-columns: repeat(7,1fr);
  border-left: 2px solid #000; border-right: 2px solid #000;
  flex-shrink: 0; min-height: 120px;
}
/* Week-3 multiday zone */
.multiday-zone-3 {
  display: grid; grid-template-columns: repeat(7,1fr);
  grid-auto-rows: 17px;
  gap: 2px 0;
  border-left: 2px solid #000; border-right: 2px solid #000;
  border-bottom: 3px solid #000;
  padding: 3px 0 3px;
  min-height: 0;
}
.multiday-zone-3:not(:has(.md-bar)) { display: none; }
.multiday-zone-3.no-events { display: none; }
.multiday-zone-3.has-events { display: grid; }

/* Week-2 multiday zone */
.multiday-zone-2 {
  display: grid; grid-template-columns: repeat(7,1fr);
  grid-auto-rows: 17px;
  gap: 2px 0;
  border-left: 2px solid #000; border-right: 2px solid #000;
  border-bottom: 3px solid #000;
  padding: 3px 0 3px;
  min-height: 0;
}
.multiday-zone-2:not(:has(.md-bar)) { display: none; }
.multiday-zone-2.no-events { display: none; }
.multiday-zone-2.has-events { display: grid; }
/* Shared calendar cell styles */
.cal-cell {
  border-right: 1px solid #eee;
  padding: 4px 5px;
  display: flex; flex-direction: column; gap: 3px;
  overflow: hidden;
}
.cal-cell .cal-todo { order: -1; }
.cal-cell:last-child { border-right: none; }
.cal-cell.holiday { background: #000; border-right-color: #222; }
.cal-cell.holiday .cal-holiday-label {
  font-size: 8px; font-weight: 700; color: #fff;
  letter-spacing: 0.5px; text-transform: uppercase; opacity: 0.85; line-height: 1.3;
}
.cal-cell.holiday .cal-empty  { color: #444; }
.cal-cell.holiday .cal-event  { background: #1a1a1a; border-left-color: #fff; color: #fff; }
.cal-cell.holiday .cal-event-time { color: #999; }
.cal-empty { font-size: 9px; color: #ddd; letter-spacing: 0.5px; text-transform: uppercase; }
.cal-event {
  font-size: 9px; font-weight: 500; color: #222;
  background: #f4f4f4; border-left: 2px solid #000;
  padding: 2px 4px; border-radius: 1px;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis; line-height: 1.3;
}
.cal-todo {
  background: #333 !important; border-left-color: #111 !important;
  color: #fff !important; font-weight: 700 !important;
}
.cal-event-time { font-size: 8px; color: #888; font-weight: 400; display: block; }
.cal-loading { font-size: 9px; color: #bbb; font-style: italic; }
/* ── STALE CACHE BANNER ── */
#staleBanner {
  background: #fffbe6; color: #a07800; font-size: 10px; font-weight: 500;
  text-align: center; padding: 4px 8px; letter-spacing: 0.3px;
  border-bottom: 1px solid #e8d88a; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center; gap: 8px;
}
#staleBanner .stale-fresh { font-size: 9px; color: #bbb; }

/* ── RESPONSIVE LAYOUT ── */
@media (max-width: 900px) {
  .hero-panel  { grid-template-columns: 1fr 1fr; min-height: auto; }
  .hero-col3   { display: none; }
  .hero-temp { font-size: 80px; }
  .hero-indoor-val { font-size: 50px; }
}
@media (max-width: 640px) {
  .hero-panel { grid-template-columns: 1fr; }
  .hero-col2, .hero-col3 { display: none; }
  .hero-temp { font-size: 80px; }
  .chart-wrap { display: none; }
  .fc-strip { display: none; }
  .day-label-strip { grid-template-columns: repeat(4,1fr); overflow: hidden; }
  .day-label-col:nth-child(n+5) { display: none; }
  .cal-strip { grid-template-columns: repeat(4,1fr); }
  .cal-cell:nth-child(n+5) { display: none; }
  .cal2-label-strip { grid-template-columns: repeat(4,1fr); }
  .cal2-label-strip > *:nth-child(n+5) { display: none; }
  .cal-strip-2 { grid-template-columns: repeat(4,1fr); }
  .cal-strip-2 > .cal-cell:nth-child(n+5) { display: none; }
  .multiday-zone, .multiday-zone-2, .multiday-zone-3 { display: none; }
  .cal3-label-strip { display: none; }
  .cal-strip-3 { display: none; }
  .hdr { flex-wrap: wrap; }
  .hdr-meta { border-left: none; border-right: none; padding: 4px 0; }
}
</style>
</head>
<body>

<!-- ── HEADER ── -->
<div class="hdr">
  <div class="hdr-left">IKASLO6 · Kaslo BC</div>
  <div class="hdr-meta">
    <div class="hdr-meta-item"><span class="dot" id="liveDot"></span><b id="statusText">—</b></div>
    <div class="hdr-meta-item" id="fcSource">—</div>
    <div class="hdr-meta-item">↺ <b><span id="nextRefresh">60</span>s</b></div>
  </div>
  <div class="hdr-right" id="obsTime">—</div>
  <a href="/logout.php" class="hdr-logout" title="Log out">⎋ out</a>
</div>

<!-- ── STALE DATA BANNER (shown when displaying cached data) ── -->
<div id="staleBanner" style="display:none" role="status" aria-live="polite">
  <span>⏳ Showing cached data</span>
  <span id="staleAgeText" class="stale-fresh"></span>
  <span class="stale-fresh" id="staleBannerMsg">· Refreshing…</span>
</div>

<!-- ── ROW A: HERO PANEL (3 columns: outdoor | todo | light+indoor) ── -->
<div class="main-panels">
  <div class="hero-panel">

    <!-- COL1: Outdoor — big temp + icon side by side, feels row, stats grid -->
    <div class="hero-col1">
      <div class="hero-top-row">
        <div class="hero-temp-wrap">
          <div class="hero-temp"><span id="tempC">—</span><span class="hero-temp-unit">°</span></div>
        </div>
        <div class="hero-icon-block">
          <div class="hero-wx-icon-big" id="todayIcon">—</div>
          <div class="hero-wx-desc" id="todayDesc"></div>
          <div class="hero-wx-precip" id="todayPrecip"></div>
        </div>
      </div>
      <!-- Feels / Hum / Dew / Hi-Lo — all on one line -->
      <div class="hero-feels-row">
        <span class="hero-feels">Feels <b id="heatIndex">—</b>°</span>
        <span class="hero-feels">Hum <b id="humidity">—</b>%</span>
        <span class="hero-feels">Dew <b id="dewpt">—</b>°</span>
        <span class="hero-feels">Hi <b id="todayHi">—</b> · Lo <b id="todayLo">—</b></span>
      </div>
      <!-- 2×2 mini-stats: UV · Pressure · Wind · Precip -->
      <div class="hero-stats">
        <div class="hstat">
          <div class="hstat-lbl">UV / Solar</div>
          <div class="hstat-val" style="font-size:18px"><span id="uv">—</span></div>
          <div class="hstat-sub" style="font-size:7px"><span id="solar">—</span> W/m²</div>
        </div>
        <div class="hstat">
          <div class="hstat-lbl">Pressure</div>
          <div class="hstat-val"><span id="pressure">—</span><span class="hstat-unit">hPa</span></div>
          <div class="hstat-trend" id="trendPill">—</div>
        </div>
        <div class="hstat">
          <div class="hstat-lbl">Wind</div>
          <div class="hstat-val"><span id="windSpeed">—</span><span class="hstat-unit">km/h</span></div>
          <div class="hstat-sub"><span id="windDir">—</span> · <span id="windGust">—</span> gust</div>
        </div>
        <div class="hstat">
          <div class="hstat-lbl">Precip 24h</div>
          <div class="hstat-val" style="font-size:15px"><span id="precip24h">0</span><span class="hstat-unit">mm</span></div>
          <div class="hstat-sub" style="font-size:7px">Rate <span id="precipRate">0.00</span><span class="hstat-unit">/hr</span></div>
          <div class="hstat-sub" style="font-size:7px">7d <span id="precip30d">0</span><span class="hstat-unit">mm</span></div>
          <span id="precipTotal" style="display:none"></span>
        </div>
      </div>
    </div>

    <!-- COL2: TODO list (no checkboxes; description/due shown below each item) -->
    <div class="hero-col2">
      <div class="todo-panel" id="todoPanel">
        <div class="todo-header">
          <span>To Do</span>
          <a href="/todo.php" target="_blank" title="Manage list">✎</a>
        </div>
        <div class="todo-list-inner" id="todoList"></div>
      </div>
    </div>

    <!-- COL3: Daylight arc + moon | Sunrise/Sunset SVG | Indoor temp+humidity -->
    <div class="hero-col3">
      <!-- Daylight arc (bigger) and moon phase side by side -->
      <div class="light-moon-row">
        <div class="daylight-wrap">
          <canvas id="daylightCanvas" class="daylight-canvas" width="130" height="130"></canvas>
          <div class="daylight-hrs" id="daylightHrs"></div>
        </div>
        <div class="moon-wrap-big">
          <canvas id="moonCanvas"></canvas>
          <div class="moon-lbl" id="moonLbl">—</div>
        </div>
      </div>
      <!-- Sunrise and sunset with hand-drawn SVG icons -->
      <div class="sun-times-row">
        <div class="sun-time-item">
          <svg class="sun-svg" viewBox="0 0 24 20" width="24" height="20" fill="none" stroke="#333" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round">
          <line x1="0" y1="14" x2="24" y2="14"/>
          <path d="M4.5 14 A7.5 7.5 0 0 1 19.5 14"/>
          <line x1="12" y1="6.5" x2="12" y2="1.5"/>
          <polyline points="9.5,4 12,1.5 14.5,4"/>
          <line x1="5.5" y1="10" x2="3" y2="8"/>
          <line x1="18.5" y1="10" x2="21" y2="8"/>
        </svg>
          <span class="sun-time" id="sunriseTime">—</span>
        </div>
        <div class="sun-time-item">
          <svg class="sun-svg" viewBox="0 0 24 20" width="24" height="20" fill="none" stroke="#333" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round">
          <line x1="0" y1="14" x2="24" y2="14"/>
          <path d="M4.5 14 A7.5 7.5 0 0 1 19.5 14 Z" fill="#333"/>
          <line x1="12" y1="6.5" x2="12" y2="1.5"/>
          <polyline points="9.5,4 12,6.5 14.5,4"/>
          <line x1="5.5" y1="10" x2="3" y2="8"/>
          <line x1="18.5" y1="10" x2="21" y2="8"/>
        </svg>
          <span class="sun-time" id="sunsetTime">—</span>
        </div>
      </div>
      <!-- Indoor temp + humidity -->
      <div class="indoor-section">
        <div class="hero-indoor-val"><span id="indoorTempC">—</span><span class="hero-indoor-unit">°</span></div>
        <div class="hero-indoor-hum-block">
          <div class="hero-indoor-lbl">Indoor</div>
          <div class="hero-indoor-hum">Hum <span id="indoorHum">—</span>%</div>
        </div>
      </div>
    </div>

  </div>
</div>


<!-- ── ROW B: DAY LABELS ── -->
<div class="day-label-strip" id="dayLabelStrip">
  <div class="day-label-col" style="grid-column:1/8;text-align:center;padding:6px;color:#ccc;font-size:10px">Loading…</div>
</div>

<!-- ── ROW C: HI/LO CHART ── -->
<div class="chart-wrap">
  <canvas id="chart" class="main-chart" height="120"></canvas>
</div>

<!-- ── ROW D: FORECAST DETAIL STRIP ── -->
<div class="fc-strip" id="fcStrip">
  <div style="grid-column:1/8;text-align:center;padding:8px;color:#bbb;font-size:10px">Loading forecast…</div>
</div>

<!-- ── ROW E: WEEK-1 CALENDAR ── -->
<div class="cal-strip" id="calStrip">
  <div class="cal-cell" style="grid-column:1/8"><span class="cal-loading">Loading calendar…</span></div>
</div>
<!-- ── WEEK-1 MULTI-DAY ZONE (seamless, collapses when empty) ── -->
<div class="multiday-zone no-events" id="multidayZone1"></div>

<!-- ── ROW F: WEEK-2 DAY LABELS ── -->
<div class="cal2-label-strip" id="cal2LabelStrip">
  <div style="grid-column:1/8;text-align:center;padding:4px;color:#ccc;font-size:10px">Week 2…</div>
</div>

<!-- ── ROW G: WEEK-2 CALENDAR ── -->
<div class="cal-strip-2" id="calStrip2">
  <div class="cal-cell" style="grid-column:1/8"><span class="cal-loading">Loading…</span></div>
</div>
<!-- ── WEEK-2 MULTI-DAY ZONE ── -->
<div class="multiday-zone-2 no-events" id="multidayZone2"></div>

<!-- ── ROW H: WEEK-3 DAY LABELS ── -->
<div class="cal3-label-strip" id="cal3LabelStrip">
  <div style="grid-column:1/8;text-align:center;padding:4px;color:#ccc;font-size:10px">Week 3…</div>
</div>

<!-- ── ROW I: WEEK-3 CALENDAR ── -->
<div class="cal-strip-3" id="calStrip3">
  <div class="cal-cell" style="grid-column:1/8"><span class="cal-loading">Loading…</span></div>
</div>
<!-- ── WEEK-3 MULTI-DAY ZONE ── -->
<div class="multiday-zone-3 no-events" id="multidayZone3"></div>

<script>
// v3.3 — cache-layer
// ── INLINE CACHE (injected by PHP at render time) ─────────────────────────
// INIT_CACHE.forecast / .ical / .obs are objects {ts, age, d} or null.
// We use these to populate the page instantly, before any network requests.
const INIT_CACHE = {
  forecast: <?= $_kw_fc ?>,
  ical:     <?= $_kw_ic ?>,
  obs:      <?= $_kw_obs ?>,
};

// ── CACHE LAYER ──────────────────────────────────────────────────────────
const CACHE_API = window.location.origin + '/weather-cache.php';

// POST new data to the server cache (fire-and-forget — never blocks UI)
function saveCache(key, data) {
  try {
    fetch(CACHE_API + '?key=' + key, {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify(data),
    }).catch(e => console.warn('Cache save error (' + key + '):', e.message));
  } catch(e) { console.warn('saveCache exception:', e.message); }
}

// Stale-banner management
const _stale = new Set();       // sources currently showing stale data
const _fetchErrors = new Set(); // sources that failed their most recent fetch

function fmtAge(ts) {
  const sec = Math.round(Date.now()/1000 - ts);
  if (sec < 90)    return sec + 's old';
  if (sec < 3600)  return Math.floor(sec/60) + 'm old';
  if (sec < 86400) return Math.floor(sec/3600) + 'h old';
  return Math.floor(sec/86400) + 'd old';
}

function updateStaleBanner() {
  const el = document.getElementById('staleBanner');
  if (!el) return;
  if (_stale.size === 0 && _fetchErrors.size === 0) { el.style.display = 'none'; return; }
  el.style.display = '';
  const parts = [];
  if (_stale.has('forecast') && INIT_CACHE.forecast) parts.push('forecast ' + fmtAge(INIT_CACHE.forecast.ts));
  if (_stale.has('ical')     && INIT_CACHE.ical)     parts.push('calendar ' + fmtAge(INIT_CACHE.ical.ts));
  if (_stale.has('obs')      && INIT_CACHE.obs)      parts.push('weather '  + fmtAge(INIT_CACHE.obs.ts));
  const ageEl = document.getElementById('staleAgeText');
  if (ageEl) ageEl.textContent = parts.length ? '(' + parts.join(' · ') + ')' : '';
  const msgEl = document.getElementById('staleBannerMsg');
  if (msgEl) {
    if (_fetchErrors.size > 0) {
      msgEl.textContent = '· Fetch failed: ' + [..._fetchErrors].join(', ') + ' — will retry';
      msgEl.style.color = '#c07000';
    } else {
      msgEl.textContent = '· Refreshing…';
      msgEl.style.color = '';
    }
  }
}

function markStale(source)   { _stale.add(source);       _fetchErrors.delete(source); updateStaleBanner(); }
function markFresh(source)   { _stale.delete(source);    _fetchErrors.delete(source); updateStaleBanner(); }
function markError(source)   { _fetchErrors.add(source); updateStaleBanner(); }

// v3.2 — priority15+multiday+public
// ── API CONFIG ────────────────────────────────────────────────
// ── EcoWitt (sole observations source — WU removed) ────────────
const ECO_APP_KEY  = 'C6FD389063D6A82CC7A68532000A5962';
const ECO_API_KEY  = '1c2c26a5-a293-4f82-a69a-9e77b6447344';
const ECO_MAC      = 'E0:5A:1B:21:11:57';
// call_back=all pulls: outdoor, indoor, wind, pressure, rainfall, solar_and_uvi, lightning, co2
const ECO_URL      = `https://api.ecowitt.net/api/v3/device/real_time?application_key=${ECO_APP_KEY}&api_key=${ECO_API_KEY}&mac=${ECO_MAC}&call_back=all&temp_unitid=1&pressure_unitid=3&wind_speed_unitid=7&rainfall_unitid=12&solar_irradiance_unitid=16`;

const LAT          = 49.912, LON = -116.908;
const FORECAST_URL = `https://api.open-meteo.com/v1/forecast?latitude=${LAT}&longitude=${LON}&daily=temperature_2m_max,temperature_2m_min,precipitation_sum,precipitation_probability_max,weathercode,sunrise,sunset&timezone=America%2FVancouver&forecast_days=16`;  // Open-Meteo free tier max; week-3 cells show '—' if unavailable

// ── CALENDAR SOURCES ─────────────────────────────────────────
const CORS_PROXY = window.location.origin + '/ical-proxy.php?url='; // same-origin PHP proxy — no CORS issues

// Remote iCal feeds — managed via todo.html Calendar tab
// Stored in localStorage 'kaslo_cals'; hard-coded list is the default
const DEFAULT_CALS = [
  { url: 'https://p162-caldav.icloud.com/published/2/MTI5MzgzNTk0MTI5MzgzNWY9cTYgK0OwIRz4UCfQYKvJY44bQNHC73gDwS5V1U5jUkV9ynvy7uipCHeNIfenut1Eq0LNCaulcS6IDwnCXzpP_iBTsC0Fc21d3SMFtSvB_1CnVBymPyAyPnzFyGkhsg', label: 'personal',  color: 'dark',   enabled: true },
  { url: 'https://p102-caldav.icloud.com/published/2/MTI5MzgzNTk0MTI5MzgzNWY9cTYgK0OwIRz4UCfQYKvnN6NvIE36DmOUbrBgiLcGN7ezhsYo-YXFxAw38AN_vpaiEFRiXefJROr9Az80VcU', label: 'personal2', color: 'medium', enabled: true },
  { url: 'https://p101-caldav.icloud.com/published/2/Mjc4Mjk1ODMxMjc4Mjk1OPiLHZnPp67Ltgtp3v229x8qT-uPdlC-Sg6bv_JZdLUiimpxJVvfu-OL9CBtnZ3CMevVIgwICabIi9WTyZIKqHA', label: 'personal3', color: 'light',  enabled: true },
];
const CALS_KEY = 'kaslo_cals';
function getActiveCals() {
  try {
    const saved = JSON.parse(localStorage.getItem(CALS_KEY));
    if (Array.isArray(saved) && saved.length) return saved.filter(c => c.enabled !== false);
  } catch(e) {}
  return DEFAULT_CALS.filter(c => c.enabled !== false);
}
const REMOTE_CALS = getActiveCals();

// Cal colour mapping → border-left colour on .cal-event
const CAL_COLORS = { light: '#aaa', medium: '#555', dark: '#000' };

// BC public holidays — embedded, used to invert calendar cells black
const BC_HOLIDAYS_ICS = `BEGIN:VCALENDAR
VERSION:2.0
CALSCALE:GREGORIAN
PRODID:-//pcraig3//hols//EN
METHOD:PUBLISH
X-PUBLISHED-TTL:PT1H
BEGIN:VEVENT
UID:lTfjyVcGIMG3R7EzRRYoxYJ/Tjs=@hols.ca
SUMMARY:New Year’s Day
DTSTAMP:20260224T213555Z
DTSTART;VALUE=DATE:20260101
DTEND;VALUE=DATE:20260102
DESCRIPTION:National holiday
END:VEVENT
BEGIN:VEVENT
UID:RFE8Dv48rYJiuLk33l+MPJVayTQ=@hols.ca
SUMMARY:Family Day
DTSTAMP:20260224T213555Z
DTSTART;VALUE=DATE:20260216
DTEND;VALUE=DATE:20260217
DESCRIPTION:Observed by AB\\, BC\\, NB\\, ON\\, and SK.
END:VEVENT
BEGIN:VEVENT
UID:OfPV7E6iPUB8aM+ACNThNNJgmaU=@hols.ca
SUMMARY:Good Friday
DTSTAMP:20260224T213555Z
DTSTART;VALUE=DATE:20260403
DTEND;VALUE=DATE:20260404
DESCRIPTION:National holiday
END:VEVENT
BEGIN:VEVENT
UID:aGZ/lAdXqSPwjGcpMjulCsJ5xqU=@hols.ca
SUMMARY:Victoria Day
DTSTAMP:20260224T213555Z
DTSTART;VALUE=DATE:20260518
DTEND;VALUE=DATE:20260519
DESCRIPTION:Observed by AB\\, BC\\, MB\\, NT\\, NU\\, ON\\, SK\\, YT\\, and federal
	 industries.
END:VEVENT
BEGIN:VEVENT
UID:YLvAadcDKjgJ67RweLxTpfOJGLA=@hols.ca
SUMMARY:Canada Day
DTSTAMP:20260224T213555Z
DTSTART;VALUE=DATE:20260701
DTEND;VALUE=DATE:20260702
DESCRIPTION:National holiday
END:VEVENT
BEGIN:VEVENT
UID:UP5+EJhwkq+8dF7Ilh5tQuVhiQk=@hols.ca
SUMMARY:British Columbia Day
DTSTAMP:20260224T213555Z
DTSTART;VALUE=DATE:20260803
DTEND;VALUE=DATE:20260804
DESCRIPTION:Observed by British Columbia.
END:VEVENT
BEGIN:VEVENT
UID:Z+FhvuEueaqkF8xsRQXQy1/bIyQ=@hols.ca
SUMMARY:Labour Day
DTSTAMP:20260224T213555Z
DTSTART;VALUE=DATE:20260907
DTEND;VALUE=DATE:20260908
DESCRIPTION:National holiday
END:VEVENT
BEGIN:VEVENT
UID:3UYzIChotmJP7QPw7vuUltWULqA=@hols.ca
SUMMARY:National Day for Truth and Reconciliation
DTSTAMP:20260224T213555Z
DTSTART;VALUE=DATE:20260930
DTEND;VALUE=DATE:20261001
DESCRIPTION:Observed by BC\\, NT\\, PE\\, YT\\, and federal industries.
END:VEVENT
BEGIN:VEVENT
UID:b7W2bTjrU39dYx9ITGaTb7UmVYU=@hols.ca
SUMMARY:Thanksgiving
DTSTAMP:20260224T213555Z
DTSTART;VALUE=DATE:20261012
DTEND;VALUE=DATE:20261013
DESCRIPTION:Observed by AB\\, BC\\, MB\\, NT\\, NU\\, ON\\, QC\\, SK\\, YT\\, and fe
	deral industries.
END:VEVENT
BEGIN:VEVENT
UID:CfekWpUvT2id9QGy0gSzW7LB6d0=@hols.ca
SUMMARY:Remembrance Day
DTSTAMP:20260224T213555Z
DTSTART;VALUE=DATE:20261111
DTEND;VALUE=DATE:20261112
DESCRIPTION:Observed by AB\\, BC\\, NB\\, NL\\, NT\\, NU\\, PE\\, SK\\, YT\\, and fe
	deral industries.
END:VEVENT
BEGIN:VEVENT
UID:E/b85rpSP/bLx0YUdsLn3bcKBD4=@hols.ca
SUMMARY:Christmas Day
DTSTAMP:20260224T213555Z
DTSTART;VALUE=DATE:20261225
DTEND;VALUE=DATE:20261226
DESCRIPTION:National holiday
END:VEVENT
END:VCALENDAR`;

// Canada-wide holidays — also embedded for completeness
const CA_HOLIDAYS_ICS = `BEGIN:VCALENDAR
VERSION:2.0
CALSCALE:GREGORIAN
PRODID:-//pcraig3//hols//EN
METHOD:PUBLISH
X-PUBLISHED-TTL:PT1H
BEGIN:VEVENT
UID:lTfjyVcGIMG3R7EzRRYoxYJ/Tjs=@hols.ca
SUMMARY:New Year’s Day
DTSTAMP:20260224T213542Z
DTSTART;VALUE=DATE:20260101
DTEND;VALUE=DATE:20260102
DESCRIPTION:National holiday
END:VEVENT
BEGIN:VEVENT
UID:kmAcuKYeRK2554KTcdItaBT/fK8=@hols.ca
SUMMARY:Louis Riel Day (MB)
DTSTAMP:20260224T213542Z
DTSTART;VALUE=DATE:20260216
DTEND;VALUE=DATE:20260217
DESCRIPTION:This is not a national holiday\\; it may not be observed in your
	 region
END:VEVENT
BEGIN:VEVENT
UID:fw9n2mmihUvskVj7OnC4Ifgz6IY=@hols.ca
SUMMARY:Islander Day (PE)
DTSTAMP:20260224T213542Z
DTSTART;VALUE=DATE:20260216
DTEND;VALUE=DATE:20260217
DESCRIPTION:This is not a national holiday\\; it may not be observed in your
	 region
END:VEVENT
BEGIN:VEVENT
UID:RFE8Dv48rYJiuLk33l+MPJVayTQ=@hols.ca
SUMMARY:Family Day (AB\\, BC\\, NB\\, ON\\, SK)
DTSTAMP:20260224T213542Z
DTSTART;VALUE=DATE:20260216
DTEND;VALUE=DATE:20260217
DESCRIPTION:This is not a national holiday\\; it may not be observed in your
	 region
END:VEVENT
BEGIN:VEVENT
UID:oZUl7CQOudUzWuL/u+/jmodtCM0=@hols.ca
SUMMARY:Heritage Day (NS)
DTSTAMP:20260224T213542Z
DTSTART;VALUE=DATE:20260216
DTEND;VALUE=DATE:20260217
DESCRIPTION:This is not a national holiday\\; it may not be observed in your
	 region
END:VEVENT
BEGIN:VEVENT
UID:lhbwhM5rCesyp+QpJPXUVYbXoQA=@hols.ca
SUMMARY:Saint Patrick’s Day (NL)
DTSTAMP:20260224T213542Z
DTSTART;VALUE=DATE:20260317
DTEND;VALUE=DATE:20260318
DESCRIPTION:This is not a national holiday\\; it may not be observed in your
	 region
END:VEVENT
BEGIN:VEVENT
UID:OfPV7E6iPUB8aM+ACNThNNJgmaU=@hols.ca
SUMMARY:Good Friday
DTSTAMP:20260224T213542Z
DTSTART;VALUE=DATE:20260403
DTEND;VALUE=DATE:20260404
DESCRIPTION:National holiday
END:VEVENT
BEGIN:VEVENT
UID:FOmWfc04KOtVxza2ZWxV14y51m4=@hols.ca
SUMMARY:Easter Monday (Federal)
DTSTAMP:20260224T213542Z
DTSTART;VALUE=DATE:20260406
DTEND;VALUE=DATE:20260407
DESCRIPTION:This is not a national holiday\\; it may not be observed in your
	 region
END:VEVENT
BEGIN:VEVENT
UID:ilDryVJZ3Hg2N6B0z/fdxKsqX5k=@hols.ca
SUMMARY:Saint George’s Day (NL)
DTSTAMP:20260224T213542Z
DTSTART;VALUE=DATE:20260423
DTEND;VALUE=DATE:20260424
DESCRIPTION:This is not a national holiday\\; it may not be observed in your
	 region
END:VEVENT
BEGIN:VEVENT
UID:JFCAbkPIc/JO4ARlU+8A3A3/ByQ=@hols.ca
SUMMARY:National Patriots’ Day (QC)
DTSTAMP:20260224T213542Z
DTSTART;VALUE=DATE:20260518
DTEND;VALUE=DATE:20260519
DESCRIPTION:This is not a national holiday\\; it may not be observed in your
	 region
END:VEVENT
BEGIN:VEVENT
UID:aGZ/lAdXqSPwjGcpMjulCsJ5xqU=@hols.ca
SUMMARY:Victoria Day (AB\\, BC\\, MB\\, NT\\, NU\\, ON\\, SK\\, YT\\, Federal)
DTSTAMP:20260224T213542Z
DTSTART;VALUE=DATE:20260518
DTEND;VALUE=DATE:20260519
DESCRIPTION:This is not a national holiday\\; it may not be observed in your
	 region
END:VEVENT
BEGIN:VEVENT
UID:9wnegABM/9BjPlr+suSKOSI9k+M=@hols.ca
SUMMARY:National Indigenous Peoples Day (NT\\, YT)
DTSTAMP:20260224T213542Z
DTSTART;VALUE=DATE:20260621
DTEND;VALUE=DATE:20260622
DESCRIPTION:This is not a national holiday\\; it may not be observed in your
	 region
END:VEVENT
BEGIN:VEVENT
UID:0xCjMQMz6eatO9HJC2ZXz8ugMtg=@hols.ca
SUMMARY:Saint-Jean-Baptiste Day (QC)
DTSTAMP:20260224T213542Z
DTSTART;VALUE=DATE:20260624
DTEND;VALUE=DATE:20260625
DESCRIPTION:This is not a national holiday\\; it may not be observed in your
	 region
END:VEVENT
BEGIN:VEVENT
UID:RHJOO3D2rhRZeS3L7sHfxx5+tKc=@hols.ca
SUMMARY:Discovery Day (NL)
DTSTAMP:20260224T213542Z
DTSTART;VALUE=DATE:20260624
DTEND;VALUE=DATE:20260625
DESCRIPTION:This is not a national holiday\\; it may not be observed in your
	 region
END:VEVENT
BEGIN:VEVENT
UID:YLvAadcDKjgJ67RweLxTpfOJGLA=@hols.ca
SUMMARY:Canada Day
DTSTAMP:20260224T213542Z
DTSTART;VALUE=DATE:20260701
DTEND;VALUE=DATE:20260702
DESCRIPTION:National holiday
END:VEVENT
BEGIN:VEVENT
UID:0yz1QyYsBfY9QA9wmFotY9n5wYY=@hols.ca
SUMMARY:Nunavut Day (NU)
DTSTAMP:20260224T213542Z
DTSTART;VALUE=DATE:20260709
DTEND;VALUE=DATE:20260710
DESCRIPTION:This is not a national holiday\\; it may not be observed in your
	 region
END:VEVENT
BEGIN:VEVENT
UID:zU2TSR34JcRG/GiRHEvf+8JerMY=@hols.ca
SUMMARY:Orangemen’s Day (NL)
DTSTAMP:20260224T213542Z
DTSTART;VALUE=DATE:20260712
DTEND;VALUE=DATE:20260713
DESCRIPTION:This is not a national holiday\\; it may not be observed in your
	 region
END:VEVENT
BEGIN:VEVENT
UID:TGCKCE40VjexbDh9A553FNPL2nw=@hols.ca
SUMMARY:Civic Holiday (NT\\, NU\\, Federal)
DTSTAMP:20260224T213542Z
DTSTART;VALUE=DATE:20260803
DTEND;VALUE=DATE:20260804
DESCRIPTION:This is not a national holiday\\; it may not be observed in your
	 region
END:VEVENT
BEGIN:VEVENT
UID:UP5+EJhwkq+8dF7Ilh5tQuVhiQk=@hols.ca
SUMMARY:British Columbia Day (BC)
DTSTAMP:20260224T213542Z
DTSTART;VALUE=DATE:20260803
DTEND;VALUE=DATE:20260804
DESCRIPTION:This is not a national holiday\\; it may not be observed in your
	 region
END:VEVENT
BEGIN:VEVENT
UID:cdv9xfoq6L4Hc4j2gGG/CnGq1MM=@hols.ca
SUMMARY:New Brunswick Day (NB)
DTSTAMP:20260224T213542Z
DTSTART;VALUE=DATE:20260803
DTEND;VALUE=DATE:20260804
DESCRIPTION:This is not a national holiday\\; it may not be observed in your
	 region
END:VEVENT
BEGIN:VEVENT
UID:7hfIlKyzAO8QzhVQ/widgavj8eo=@hols.ca
SUMMARY:Saskatchewan Day (SK)
DTSTAMP:20260224T213542Z
DTSTART;VALUE=DATE:20260803
DTEND;VALUE=DATE:20260804
DESCRIPTION:This is not a national holiday\\; it may not be observed in your
	 region
END:VEVENT
BEGIN:VEVENT
UID:9PgsJ5UqB6U8ICdBfBSWSh/EIP0=@hols.ca
SUMMARY:Regatta Day (NL)
DTSTAMP:20260224T213542Z
DTSTART;VALUE=DATE:20260805
DTEND;VALUE=DATE:20260806
DESCRIPTION:This is not a national holiday\\; it may not be observed in your
	 region
END:VEVENT
BEGIN:VEVENT
UID:3T5ESxV3zuYSxEf4Xq7Y29tRRSg=@hols.ca
SUMMARY:Discovery Day (YT)
DTSTAMP:20260224T213542Z
DTSTART;VALUE=DATE:20260817
DTEND;VALUE=DATE:20260818
DESCRIPTION:This is not a national holiday\\; it may not be observed in your
	 region
END:VEVENT
BEGIN:VEVENT
UID:Z+FhvuEueaqkF8xsRQXQy1/bIyQ=@hols.ca
SUMMARY:Labour Day
DTSTAMP:20260224T213542Z
DTSTART;VALUE=DATE:20260907
DTEND;VALUE=DATE:20260908
DESCRIPTION:National holiday
END:VEVENT
BEGIN:VEVENT
UID:3UYzIChotmJP7QPw7vuUltWULqA=@hols.ca
SUMMARY:National Day for Truth and Reconciliation (BC\\, NT\\, PE\\, YT\\, Fede
	ral)
DTSTAMP:20260224T213542Z
DTSTART;VALUE=DATE:20260930
DTEND;VALUE=DATE:20261001
DESCRIPTION:This is not a national holiday\\; it may not be observed in your
	 region
END:VEVENT
BEGIN:VEVENT
UID:tQcD8PTsNFbS8leGx9YeGbJmz1M=@hols.ca
SUMMARY:Orange Shirt Day (MB)
DTSTAMP:20260224T213542Z
DTSTART;VALUE=DATE:20260930
DTEND;VALUE=DATE:20261001
DESCRIPTION:This is not a national holiday\\; it may not be observed in your
	 region
END:VEVENT
BEGIN:VEVENT
UID:b7W2bTjrU39dYx9ITGaTb7UmVYU=@hols.ca
SUMMARY:Thanksgiving (AB\\, BC\\, MB\\, NT\\, NU\\, ON\\, QC\\, SK\\, YT\\, Federal)
DTSTAMP:20260224T213542Z
DTSTART;VALUE=DATE:20261012
DTEND;VALUE=DATE:20261013
DESCRIPTION:This is not a national holiday\\; it may not be observed in your
	 region
END:VEVENT
BEGIN:VEVENT
UID:CfekWpUvT2id9QGy0gSzW7LB6d0=@hols.ca
SUMMARY:Remembrance Day (AB\\, BC\\, NB\\, NL\\, NT\\, NU\\, PE\\, SK\\, YT\\, Feder
	al)
DTSTAMP:20260224T213542Z
DTSTART;VALUE=DATE:20261111
DTEND;VALUE=DATE:20261112
DESCRIPTION:This is not a national holiday\\; it may not be observed in your
	 region
END:VEVENT
BEGIN:VEVENT
UID:E/b85rpSP/bLx0YUdsLn3bcKBD4=@hols.ca
SUMMARY:Christmas Day
DTSTAMP:20260224T213542Z
DTSTART;VALUE=DATE:20261225
DTEND;VALUE=DATE:20261226
DESCRIPTION:National holiday
END:VEVENT
BEGIN:VEVENT
UID:tadQqxe58rQXLFYlRGy+NUiezxY=@hols.ca
SUMMARY:Boxing Day (NL\\, ON\\, Federal)
DTSTAMP:20260224T213542Z
DTSTART;VALUE=DATE:20261226
DTEND;VALUE=DATE:20261227
DESCRIPTION:This is not a national holiday\\; it may not be observed in your
	 region
END:VEVENT
END:VCALENDAR`;

let pressureLog    = [];
let forecastData   = null;
// forecastDates[i] = 'YYYY-MM-DD' for column i
let forecastDates  = [];

/* ── UTILS ── */
function degToCompass(d) {
  if (d==null) return '—';
  return ['N','NNE','NE','ENE','E','ESE','SE','SSE','S','SSW','SW','WSW','W','WNW','NW','NNW'][Math.round(d/22.5)%16];
}
function fmt(v,d=1){ return (v==null||isNaN(v))? '—': Number(v).toFixed(d); }
function ordinal(n) {
  const s=['th','st','nd','rd'], v=n%100;
  return n+(s[(v-20)%10]||s[v]||s[0]);
}
function wmoIcon(code) {
  if (code==null) return '🌤';
  if ([95,96,99].includes(code))                          return '⛈';
  if ([71,73,75,77,85,86].includes(code))                 return '❄';
  if ([51,53,55,61,63,65,66,67,80,81,82].includes(code)) return '🌧';
  if ([45,48].includes(code))                             return '🌫';
  if ([2,3].includes(code))                               return '☁';
  if ([1].includes(code))                                 return '⛅';
  if ([0].includes(code))                                 return '☀';
  return '🌤';
}

/* ── MOON ── */
function getMoonPhase(date) {
  const k = new Date('2025-01-29T00:00:00Z');
  return (((((date-k)/86400000)%29.53058867)+29.53058867)%29.53058867)/29.53058867;
}
function moonPhaseName(p) {
  if (p<0.0625||p>=0.9375) return 'New Moon';
  if (p<0.1875) return 'Wax Crescent';
  if (p<0.3125) return '1st Quarter';
  if (p<0.4375) return 'Wax Gibbous';
  if (p<0.5625) return 'Full Moon';
  if (p<0.6875) return 'Wan Gibbous';
  if (p<0.8125) return 'Last Quarter';
  return 'Wan Crescent';
}
// Accurate moon phase renderer (Northern Hemisphere)
// phase 0=new moon, 0.25=first quarter, 0.5=full moon, 0.75=last quarter
function drawMoonCanvas(phase) {
  const canvas = document.getElementById('moonCanvas');
  if (!canvas) return;
  // Use the size set by caller; canvas.width already set
  const size = canvas.width;
  const ctx = canvas.getContext('2d');
  ctx.clearRect(0, 0, size, size);
  const cx = size/2, cy = size/2, r = size/2 - 1.5;

  const LIT  = '#e8e8e8';  // illuminated surface
  const DARK = '#0f0f0f';  // shadow

  // Clip everything to disc
  ctx.save();
  ctx.beginPath(); ctx.arc(cx, cy, r, 0, Math.PI*2); ctx.clip();

  // --- Algorithm: two-semicircle + elliptical terminator ---
  // phase 0-0.5 (waxing):  lit side is RIGHT
  // phase 0.5-1 (waning):  lit side is LEFT
  // termRx = r * cos(2π*phase): positive for crescent, negative for gibbous, 0 for quarter

  const termRx = r * Math.cos(phase * 2 * Math.PI);

  if (phase <= 0.5) {
    // Fill entire disc dark first
    ctx.fillStyle = DARK; ctx.fillRect(0, 0, size, size);
    // Light up right half
    ctx.fillStyle = LIT;
    ctx.beginPath(); ctx.moveTo(cx, cy-r); ctx.arc(cx, cy, r, -Math.PI/2, Math.PI/2); ctx.closePath(); ctx.fill();
    if (termRx > 0.5) {
      // Crescent: cover right half with dark ellipse — leaves thin bright sliver on far right
      ctx.fillStyle = DARK;
      ctx.beginPath(); ctx.ellipse(cx, cy, termRx, r, 0, -Math.PI/2, Math.PI/2); ctx.closePath(); ctx.fill();
    } else if (termRx < -0.5) {
      // Gibbous: extend lit area into left half with a lit ellipse
      ctx.fillStyle = LIT;
      ctx.beginPath(); ctx.ellipse(cx, cy, -termRx, r, 0, Math.PI/2, -Math.PI/2); ctx.closePath(); ctx.fill();
    }
    // termRx ≈ 0 → first quarter, already correct (right half lit)
  } else {
    // Fill entire disc dark
    ctx.fillStyle = DARK; ctx.fillRect(0, 0, size, size);
    // Light up left half
    ctx.fillStyle = LIT;
    ctx.beginPath(); ctx.moveTo(cx, cy-r); ctx.arc(cx, cy, r, -Math.PI/2, Math.PI/2, true); ctx.closePath(); ctx.fill();
    if (termRx < -0.5) {
      // Gibbous: extend lit area into right half
      ctx.fillStyle = LIT;
      ctx.beginPath(); ctx.ellipse(cx, cy, -termRx, r, 0, -Math.PI/2, Math.PI/2); ctx.closePath(); ctx.fill();
    } else if (termRx > 0.5) {
      // Crescent: cover most of left half with dark ellipse — leaves thin bright sliver on far left
      ctx.fillStyle = DARK;
      ctx.beginPath(); ctx.ellipse(cx, cy, termRx, r, 0, Math.PI/2, -Math.PI/2); ctx.closePath(); ctx.fill();
    }
    // termRx ≈ 0 → last quarter, already correct (left half lit)
  }

  ctx.restore();
  // Subtle rim
  ctx.strokeStyle = '#444'; ctx.lineWidth = 0.8;
  ctx.beginPath(); ctx.arc(cx, cy, r, 0, Math.PI*2); ctx.stroke();
}
function updateMoon() {
  const phase = getMoonPhase(new Date());
  const canvas = document.getElementById('moonCanvas');
  if (canvas) { canvas.width = canvas.height = 80; }
  drawMoonCanvas(phase);
  const lbl = document.getElementById('moonLbl');
  if (lbl) lbl.textContent = moonPhaseName(phase);
}
/* ── DAYLIGHT ARC DIAL (inspired by Weather app's sun dial) ── */
function drawDaylightDial(sunriseIso, sunsetIso) {
  const canvas = document.getElementById('daylightCanvas');
  if (!canvas) return;
  const size = 130;
  canvas.width = canvas.height = size;
  const ctx = canvas.getContext('2d');
  const cx = size/2, cy = size/2, r = size/2 - 5;

  function minsFromISO(isoStr) {
    if (!isoStr) return null;
    const d = new Date(isoStr);
    return d.getHours()*60 + d.getMinutes();
  }
  // Inverted: noon (12h) at top, midnight at bottom
  function minsToAngle(mins) { return (mins/1440)*Math.PI*2 - 3*Math.PI/2; }

  const srM = minsFromISO(sunriseIso);
  const ssM = minsFromISO(sunsetIso);
  const srA = srM != null ? minsToAngle(srM) : minsToAngle(6*60);
  const ssA = ssM != null ? minsToAngle(ssM) : minsToAngle(20*60);
  const flA = minsToAngle((srM || 360) - 28);
  const llA = minsToAngle((ssM || 1200) + 28);
  const now = new Date();
  const nowM = now.getHours()*60 + now.getMinutes();
  const nowA = minsToAngle(nowM);
  const isDaytime = srM != null && ssM != null && nowM >= srM && nowM <= ssM;
  const isTwilight = !isDaytime && (
    (nowM >= (srM||360) - 28 && nowM < (srM||360)) ||
    (nowM > (ssM||1200) && nowM <= (ssM||1200) + 28)
  );

  // ── Background: night ──
  ctx.fillStyle = '#1a2740';
  ctx.beginPath(); ctx.arc(cx,cy,r,0,Math.PI*2); ctx.fill();

  // Stars
  ctx.fillStyle='rgba(255,255,255,0.35)';
  [[cx-r*.28,cy-r*.65],[cx+r*.45,cy-r*.48],[cx-r*.05,cy-r*.78],
   [cx+r*.2,cy-r*.3],[cx-r*.52,cy-r*.15],[cx+r*.1,cy-r*.55]].forEach(([sx,sy])=>{
    ctx.beginPath(); ctx.arc(sx,sy,1.1,0,Math.PI*2); ctx.fill();
  });

  // ── Twilight arc ──
  ctx.fillStyle = 'rgba(110,140,185,0.55)';
  ctx.beginPath(); ctx.moveTo(cx,cy); ctx.arc(cx,cy,r,flA,srA,false); ctx.closePath(); ctx.fill();
  ctx.beginPath(); ctx.moveTo(cx,cy); ctx.arc(cx,cy,r,ssA,llA,false); ctx.closePath(); ctx.fill();

  // ── Daylight arc ──
  ctx.fillStyle = '#e8bc3e';
  ctx.beginPath(); ctx.moveTo(cx,cy); ctx.arc(cx,cy,r,srA,ssA,false); ctx.closePath(); ctx.fill();

  // ── Inner dark face — smaller ratio = more sun arc visible ──
  const innerR = r * 0.44;
  ctx.fillStyle = '#07080f';
  ctx.beginPath(); ctx.arc(cx,cy,innerR,0,Math.PI*2); ctx.fill();

  // ── Rim labels ──
  ctx.font = 'bold 7px -apple-system,sans-serif';
  ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
  ctx.fillStyle = 'rgba(255,255,255,0.40)';
  [[cx,cy-r+8,'NN'],[cx,cy+r-8,'MN']].forEach(([x,y,t]) => ctx.fillText(t,x,y));
  ctx.font = '6.5px -apple-system,sans-serif';
  [[cx-r+9,cy,'6'],[cx+r-8,cy,'18']].forEach(([x,y,t]) => ctx.fillText(t,x,y));

  // ── Tick marks ──
  const tickLen = 5;
  function drawTick(angle, col) {
    const x1=cx+Math.cos(angle)*(r-1), y1=cy+Math.sin(angle)*(r-1);
    const x2=cx+Math.cos(angle)*(r-tickLen), y2=cy+Math.sin(angle)*(r-tickLen);
    ctx.strokeStyle=col; ctx.lineWidth=1.8;
    ctx.beginPath(); ctx.moveTo(x1,y1); ctx.lineTo(x2,y2); ctx.stroke();
  }
  drawTick(srA, '#ffe070');
  drawTick(ssA, '#ffe070');
  drawTick(flA, 'rgba(180,210,255,0.7)');
  drawTick(llA, 'rgba(180,210,255,0.7)');

  // ── Current time dot (larger, on the ring) ──
  const dotR = r * 0.74;
  const dotX = cx + Math.cos(nowA)*dotR, dotY = cy + Math.sin(nowA)*dotR;
  const glowR = 13;
  const grd = ctx.createRadialGradient(dotX,dotY,0,dotX,dotY,glowR);
  if (isDaytime) {
    grd.addColorStop(0,'rgba(255,240,80,0.95)'); grd.addColorStop(1,'rgba(255,200,0,0)');
  } else if (isTwilight) {
    grd.addColorStop(0,'rgba(160,190,240,0.9)'); grd.addColorStop(1,'rgba(80,130,200,0)');
  } else {
    grd.addColorStop(0,'rgba(120,160,240,0.85)'); grd.addColorStop(1,'rgba(60,100,200,0)');
  }
  ctx.fillStyle = grd; ctx.beginPath(); ctx.arc(dotX,dotY,glowR,0,Math.PI*2); ctx.fill();
  ctx.fillStyle = isDaytime?'#fff7b0':'#b0c8f8';
  ctx.beginPath(); ctx.arc(dotX,dotY,4.5,0,Math.PI*2); ctx.fill();

  // ── Current time label in the center ──
  const hh = String(now.getHours()).padStart(2,'0');
  const mm2 = String(now.getMinutes()).padStart(2,'0');
  ctx.font = `bold ${Math.round(innerR*0.52)}px -apple-system,sans-serif`;
  ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
  ctx.fillStyle = 'rgba(255,255,255,0.80)';
  ctx.fillText(`${hh}:${mm2}`, cx, cy);

  // ── Outer ring ──
  ctx.strokeStyle='rgba(255,255,255,0.15)'; ctx.lineWidth=1;
  ctx.beginPath(); ctx.arc(cx,cy,r,0,Math.PI*2); ctx.stroke();
}



/* ── PRESSURE TREND ── */
function calcTrend(hPa) {
  if(pressureLog.length<3) return 'steady';
  const old=pressureLog[Math.max(0,pressureLog.length-10)].v;
  const d=hPa-old;
  return d>0.5?'rising':d<-0.5?'falling':'steady';
}

/* ── OBSERVATIONS ── */
// Fetch JSON: try direct first, then same-origin PHP proxy on failure
async function fetchJSON(url) {
  try {
    const r = await fetch(url, {cache:'no-cache'});
    if (r.ok) return await r.json();
  } catch(e) { /* expected CORS block on direct attempt */ }
  // Proxy via ical-proxy.php (same origin, no CORS restrictions)
  try {
    const proxyUrl = CORS_PROXY + encodeURIComponent(url);
    const r2 = await fetch(proxyUrl, {credentials:'include'});
    if (!r2.ok) throw new Error(`Proxy HTTP ${r2.status}`);
    const text = await r2.text();
    // Guard against proxy returning an HTML error page instead of JSON
    if (text.trimStart()[0] !== '{' && text.trimStart()[0] !== '[') {
      throw new Error('Proxy returned non-JSON: ' + text.slice(0,80));
    }
    return JSON.parse(text);
  } catch(e) {
    console.error('fetchJSON proxy failed for', url, '—', e.message);
    throw e;
  }
}

// Apply observation data to the DOM
function applyObs({temp, heatIndex, dewpt, humidity,
                   pressure, windSpeed, windDir, windGust,
                   precipRate, precipTotal, precip24h, precip30d,
                   uv, solar,
                   indoorTemp, indoorHum,
                   epoch, now: useNow}) {
  const d = useNow ? new Date() : new Date(epoch*1000);
  document.getElementById('obsTime').textContent =
    d.toLocaleTimeString('en-CA',{hour:'2-digit',minute:'2-digit'}) + ' · ' +
    d.toLocaleDateString('en-CA',{weekday:'short',month:'short',day:'numeric'});

  const set = (id,v,dec=1) => { if(v!=null){ const el=document.getElementById(id); if(el) el.textContent = typeof v==='string'?v:fmt(v,dec); } };
  set('tempC',      temp);
  set('heatIndex',  heatIndex);
  set('dewpt',      dewpt);
  set('humidity',   humidity, 0);
  set('uv',         uv, 0);
  set('solar',      solar, 0);
  set('windSpeed',  windSpeed, 0);
  set('windDir',    windDir   != null ? degToCompass(windDir) : null);
  set('windGust',   windGust, 0);
  // Precip: default to 0 if null so display never shows '—'
  const safeNum = (v, dec=1) => (v != null ? fmt(parseFloat(v), dec) : '0');
  const setEl = (id,v) => { const e=document.getElementById(id); if(e) e.textContent=v; };
  setEl('precipRate', safeNum(precipRate, 2));
  setEl('precip24h',  safeNum(precip24h, 1));
  setEl('precip30d',  safeNum(precip30d, 1));
  setEl('precipTotal', safeNum(precipTotal, 1));
  set('indoorTempC',indoorTemp);
  set('indoorHum',  indoorHum, 0);

  if(pressure != null) {
    const hPa = parseFloat(pressure);
    document.getElementById('pressure').textContent = fmt(hPa,1);
    pressureLog.push({t: epoch ?? Date.now()/1000, v: hPa});
    if(pressureLog.length>300) pressureLog.shift();
    const trend = calcTrend(hPa);
    document.getElementById('trendPill').textContent =
      trend==='rising'?'↑ Rising': trend==='falling'?'↓ Falling':'→ Steady';
  }
}

// Discovered MAC address is cached so we only look it up once per session
let _ecoMAC = (ECO_MAC !== 'YOUR_STATION_MAC') ? ECO_MAC : null;

async function getEcoMAC() {
  if (_ecoMAC) return _ecoMAC;
  // EcoWitt device list API — returns all stations on the account
  const listUrl = `https://api.ecowitt.net/api/v3/device/list?application_key=${ECO_APP_KEY}&api_key=${ECO_API_KEY}`;
  const proxyUrl = CORS_PROXY + encodeURIComponent(listUrl);
  const res = await fetch(proxyUrl, {cache:'no-cache', credentials:'include'});
  const text = await res.text();
  console.log('EcoWitt device list raw:', text.slice(0,300));
  const json = JSON.parse(text);
  if (json.code !== 0) throw new Error('Device list error: ' + json.msg);
  // Accept various response shapes
  const list = json.data?.list ?? json.data?.devices ?? json.data ?? [];
  const arr  = Array.isArray(list) ? list : Object.values(list);
  if (!arr.length) throw new Error('No devices found on account');
  _ecoMAC = arr[0].mac ?? arr[0].device_info?.mac ?? arr[0].id;
  console.log('Discovered EcoWitt MAC:', _ecoMAC);
  return _ecoMAC;
}

async function loadObs() {
  // ── Try EcoWitt ──────────────────────────────────────────────────
  try {
    const mac = await getEcoMAC();
    const ecoUrl = `https://api.ecowitt.net/api/v3/device/real_time?application_key=${ECO_APP_KEY}&api_key=${ECO_API_KEY}&mac=${mac}&call_back=all&temp_unitid=1&pressure_unitid=3&wind_speed_unitid=7&rainfall_unitid=12&solar_irradiance_unitid=16`;
    const proxyUrl = CORS_PROXY + encodeURIComponent(ecoUrl);
    // Retry once on 5xx (transient upstream errors like 502 Bad Gateway)
    let res = await fetch(proxyUrl, {cache:'no-cache', credentials:'include'});
    if (!res.ok && res.status >= 500) {
      console.debug('EcoWitt ' + res.status + ' — retrying in 2s');
      await new Promise(r => setTimeout(r, 2000));
      res = await fetch(proxyUrl, {cache:'no-cache', credentials:'include'});
    }
    if (!res.ok) throw new Error('EcoWitt HTTP ' + res.status);
    const text = await res.text();
    console.log('EcoWitt real_time raw:', text.slice(0, 300));
    const json = JSON.parse(text);
    if (json.code !== 0 || !json.data) throw new Error('EcoWitt API: ' + (json.msg || 'code ' + json.code));
    const d = json.data;
    const _ecoParams = {
      temp:        d.outdoor?.temperature?.value,
      heatIndex:   d.outdoor?.feels_like?.value,
      dewpt:       d.outdoor?.dew_point?.value,
      humidity:    d.outdoor?.humidity?.value,
      indoorTemp:  d.indoor?.temperature?.value,
      indoorHum:   d.indoor?.humidity?.value,
      pressure:    d.pressure?.relative?.value,
      windSpeed:   d.wind?.wind_speed?.value,
      windDir:     d.wind?.wind_direction?.value,
      windGust:    d.wind?.wind_gust?.value,
      precipRate:  d.rainfall?.rain_rate?.value,
      precipTotal: d.rainfall?.daily?.value,
      precip24h:   d.rainfall?.daily?.value,
      precip30d:   d.rainfall?.weekly?.value,
      uv:          d.solar_and_uvi?.uvi?.value,
      solar:       d.solar_and_uvi?.solar?.value,
      epoch:       Math.floor(Date.now()/1000),
    };
    applyObs({..._ecoParams, now: true});
    saveCache('obs', _ecoParams); // cache normalized params for fallback
    markFresh('obs');
    document.getElementById('statusText').textContent = 'EcoWitt';
    return; // success — done
  } catch(e) {
    console.warn('EcoWitt failed:', e.message, '— falling back to Open-Meteo current');
  }

  // ── Fallback: Open-Meteo current conditions ───────────────────────
  // Uses the hourly current_weather endpoint — free, no key, no CORS
  try {
    const now_url = `https://api.open-meteo.com/v1/forecast?latitude=${LAT}&longitude=${LON}` +
      `&current_weather=true` +
      `&hourly=relativehumidity_2m,dewpoint_2m,surface_pressure,windspeed_10m,winddirection_10m,windgusts_10m,precipitation,uv_index,direct_radiation` +
      `&timezone=America%2FVancouver&forecast_days=1`;
    const res  = await fetch(now_url);
    if (!res.ok) throw new Error('Open-Meteo current HTTP ' + res.status);
    const data = await res.json();
    const cw   = data.current_weather;
    // Match the closest hourly index to current time
    const times = data.hourly?.time ?? [];
    const nowISO = new Date().toISOString().slice(0,13); // 'YYYY-MM-DDTHH'
    let hi = times.findIndex(t => t.startsWith(nowISO));
    if (hi < 0) hi = 0;
    const h = data.hourly;
    const _omParams = {
      temp:        cw?.temperature,
      heatIndex:   cw?.temperature,
      dewpt:       h?.dewpoint_2m?.[hi],
      humidity:    h?.relativehumidity_2m?.[hi],
      pressure:    h?.surface_pressure?.[hi],
      windSpeed:   cw?.windspeed,
      windDir:     cw?.winddirection,
      windGust:    h?.windgusts_10m?.[hi],
      precipRate:  h?.precipitation?.[hi],
      precipTotal: h?.precipitation?.[hi],
      precip24h:   null,
      precip30d:   null,
      uv:          h?.uv_index?.[hi],
      solar:       h?.direct_radiation?.[hi],
      indoorTemp:  null,
      indoorHum:   null,
      epoch:       Math.floor(Date.now()/1000),
    };
    applyObs({..._omParams, now: true});
    saveCache('obs', _omParams); // cache normalized params
    markFresh('obs');
    document.getElementById('statusText').textContent = 'Open-Meteo';
  } catch(e) {
    console.error('Open-Meteo current failed:', e.message);
    throw e;
  }
}

/* ── FORECAST ── */
// applyForecastData: takes an Open-Meteo API response object and renders
// everything — day labels, hero panel, forecast strip, calendar scaffolding,
// chart.  Called from loadForecast() (live) and from init (cached).
function applyForecastData(data) {
  forecastData = data;
  const fcSrcEl = document.getElementById('fcSource');
  if (fcSrcEl) fcSrcEl.textContent = '⛅ open-meteo.com';

  const daily = data.daily;
  const days  = Math.min((daily.time||[]).length, 16);  // max from free-tier API
  const DAYS  = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
  const wmoDesc = code => {
    if(code==null) return '';
    if([95,96,99].includes(code)) return 'Thunderstorm';
    if([71,73,75,77,85,86].includes(code)) return 'Snow';
    if([66,67].includes(code)) return 'Freezing Rain';
    if([61,63,65,80,81,82].includes(code)) return 'Rain';
    if([51,53,55].includes(code)) return 'Drizzle';
    if([45,48].includes(code)) return 'Fog';
    if([3].includes(code)) return 'Overcast';
    if([2].includes(code)) return 'Partly Cloudy';
    if([1].includes(code)) return 'Mostly Clear';
    if([0].includes(code)) return 'Clear';
    return '';
  };
  const fmtSunTime = iso => {
    if(!iso) return '—';
    const d = new Date(iso);
    return d.toLocaleTimeString('en-CA',{hour:'2-digit',minute:'2-digit',hour12:false});
  };

  forecastDates = [];
  for(let i=0;i<days;i++) forecastDates.push(daily.time[i]);

  // ── Day-label strip (Row B) ───────────────────────────────────
  const labelStrip = document.getElementById('dayLabelStrip');
  if (labelStrip) {
    labelStrip.innerHTML = '';
    for(let i=0;i<7;i++){
      const dateStr=daily.time[i];
      const fcDate=new Date(dateStr+'T12:00:00');
      const dayOfWeek=fcDate.getDay();
      const isWeekend=dayOfWeek===0||dayOfWeek===6;
      const col=document.createElement('div');
      let cls='day-label-col';
      if(i===0) cls+=' today';
      if(isWeekend) cls+=' weekend';
      col.className=cls;
      col.innerHTML=`<div class="dl-name">${DAYS[dayOfWeek].slice(0,3).toUpperCase()}</div><div class="dl-date">${ordinal(fcDate.getDate())}</div>`;
      labelStrip.appendChild(col);
    }
  }

  // ── Hero today panel ──────────────────────────────────────────
  const hi0=daily.temperature_2m_max[0], lo0=daily.temperature_2m_min[0];
  const pop0=daily.precipitation_probability_max[0], mm0=daily.precipitation_sum[0];
  const code0=daily.weathercode[0];
  const el=id=>document.getElementById(id);
  if(el('todayIcon')) el('todayIcon').textContent=wmoIcon(code0);
  if(el('sunriseTime')) el('sunriseTime').textContent=fmtSunTime(daily.sunrise?.[0]);
  if(el('sunsetTime'))  el('sunsetTime').textContent=fmtSunTime(daily.sunset?.[0]);
  drawDaylightDial(daily.sunrise?.[0], daily.sunset?.[0]);
  if(daily.sunrise?.[0]&&daily.sunset?.[0]){
    const sr=new Date(daily.sunrise[0]),ss=new Date(daily.sunset[0]);
    const mins=Math.round((ss-sr)/60000);
    const dEl=el('daylightHrs');
    if(dEl) dEl.textContent=`Daylight ${Math.floor(mins/60)}h ${mins%60}m`;
  }
  if(el('todayHi')) el('todayHi').textContent=hi0!=null?Math.round(hi0)+'°':'—';
  if(el('todayLo')) el('todayLo').textContent=lo0!=null?Math.round(lo0)+'°':'—';
  let precipLine='';
  if(mm0!=null&&mm0>=0.5) precipLine=`${Number(mm0).toFixed(1)} mm`;
  else if(pop0!=null&&pop0>0) precipLine=`${pop0}% chance of precip`;
  if(el('todayPrecip')) el('todayPrecip').textContent=precipLine;
  if(el('todayDesc'))   el('todayDesc').textContent=wmoDesc(code0);

  // ── Forecast detail strip (Row D) ────────────────────────────
  const strip=el('fcStrip');
  if(strip){
    strip.innerHTML='';
    for(let i=0;i<7;i++){
      const hi=daily.temperature_2m_max[i], lo=daily.temperature_2m_min[i];
      const pop=daily.precipitation_probability_max[i], mm=daily.precipitation_sum[i];
      const icon=wmoIcon(daily.weathercode[i]);
      const hasPrecip=(mm!=null&&mm>=0.1)||(pop!=null&&pop>0);
      const precipHtml=hasPrecip?`
        <div class="fc-precip-side">
          ${mm!=null&&mm>=0.1?`<div class="fc-mm-big">${Number(mm).toFixed(1)}mm</div>`:''}
          ${pop!=null&&pop>0?`<div class="fc-pop-big">${pop}%</div>`:''}
        </div>`:'';
      const col=document.createElement('div');
      const dw2=new Date(forecastDates[i]+'T12:00:00').getDay();
      let fcCls='fc-col'; if(i===0) fcCls+=' today'; if(dw2===0||dw2===6) fcCls+=' weekend';
      col.className=fcCls;
      col.innerHTML=`<div class="fc-mid"><div class="fc-icon">${icon}</div>${precipHtml}</div><div class="fc-hilo"><span class="fc-hi">${hi!=null?Math.round(hi)+'°':'—'}</span><span class="fc-lo">${lo!=null?Math.round(lo)+'°':'—'}</span></div>`;
      strip.appendChild(col);
    }
  }

  // ── Calendar cell scaffolding (empty cells with correct IDs) ─
  const buildWeekCells = (stripId, hdrId, start, end) => {
    const calEl = el(stripId), hdrEl = el(hdrId);
    if (!calEl) return;
    if (hdrEl) hdrEl.innerHTML = '';
    calEl.innerHTML = '';
    for(let i=start;i<Math.min(end,forecastDates.length);i++){
      const dateStr=forecastDates[i];
      const fcDate=new Date(dateStr+'T12:00:00');
      const dayOfWeek=fcDate.getDay();
      const isWeekend=dayOfWeek===0||dayOfWeek===6;
      if(hdrEl){
        const hcol=document.createElement('div');
        let hcls='day-label-col'; if(isWeekend) hcls+=' weekend';
        hcol.className=hcls;
        hcol.innerHTML=`<div class="dl-name">${DAYS[dayOfWeek].slice(0,3).toUpperCase()}</div><div class="dl-date">${ordinal(fcDate.getDate())}</div>`;
        hdrEl.appendChild(hcol);
      }
      const cell=document.createElement('div');
      cell.className='cal-cell';
      cell.id='cal-'+dateStr;
      cell.innerHTML='<span class="cal-empty">—</span>';
      calEl.appendChild(cell);
    }
  };
  buildWeekCells('calStrip',  null,           0,  7);

  // Weeks 2 & 3: generate dates arithmetically from today — NOT from forecastDates.
  // This ensures exactly 7 cells per week regardless of API forecast-day limit,
  // and means these rows only show calendar/todo events (no weather icons).
  function buildCalWeek(stripId, hdrId, startDay, numDays) {
    const calEl = document.getElementById(stripId);
    const hdrEl = document.getElementById(hdrId);
    if (!calEl) return;
    if (hdrEl) hdrEl.innerHTML = '';
    calEl.innerHTML = '';
    const base = new Date(); base.setHours(0,0,0,0);
    for (let i = 0; i < numDays; i++) {
      const d = new Date(base); d.setDate(base.getDate() + startDay + i);
      const dayOfWeek = d.getDay();
      const isWeekend = dayOfWeek === 0 || dayOfWeek === 6;
      // ISO date string YYYY-MM-DD
      const dateStr = d.toLocaleDateString('sv-SE'); // sv-SE gives ISO format natively
      if (hdrEl) {
        const hcol = document.createElement('div');
        let hcls = 'day-label-col'; if (isWeekend) hcls += ' weekend';
        hcol.className = hcls;
        hcol.innerHTML = `<div class="dl-name">${DAYS[dayOfWeek].slice(0,3).toUpperCase()}</div><div class="dl-date">${ordinal(d.getDate())}</div>`;
        hdrEl.appendChild(hcol);
      }
      const cell = document.createElement('div');
      cell.className = 'cal-cell';
      cell.id = 'cal-' + dateStr;
      cell.innerHTML = '<span class="cal-empty">—</span>';
      calEl.appendChild(cell);
      // Register this date in forecastDates if not already present
      // so applyCalendarEvents can find the cell by id
      if (!forecastDates.includes(dateStr)) forecastDates.push(dateStr);
    }
  }

  buildCalWeek('calStrip2', 'cal2LabelStrip', 7,  7);
  buildCalWeek('calStrip3', 'cal3LabelStrip', 14, 7);
  // Ensure week-3 rows are visible
  const cal3hdrEl = document.getElementById('cal3LabelStrip');
  const cal3El    = document.getElementById('calStrip3');
  if (cal3hdrEl) cal3hdrEl.style.display = '';
  if (cal3El)    cal3El.style.display = '';

  requestAnimationFrame(()=>drawChart(daily,7));
}

// loadForecast: fetch → applyForecastData → save cache
// On error: surfaces a banner and optionally re-applies cache
async function loadForecast() {
  let res, data;
  try {
    res = await fetch(FORECAST_URL);
    if (!res.ok) throw new Error(`Open-Meteo HTTP ${res.status} — check forecast_days or API status`);
    data = await res.json();
    if (!data || !data.daily || !data.daily.time) throw new Error('Open-Meteo: unexpected response shape');
  } catch(e) {
    console.error('loadForecast failed:', e.message);
    // If we have a cache, re-apply it so the page shows something useful
    if (INIT_CACHE.forecast && INIT_CACHE.forecast.d) {
      console.warn('loadForecast: applying cached forecast data as fallback');
      applyForecastData(INIT_CACHE.forecast.d);
      markStale('forecast');
    } else {
      // No cache — show an error state in the forecast strip
      const strip = document.getElementById('fcStrip');
      if (strip) strip.innerHTML = `<div style="grid-column:1/8;text-align:center;padding:10px;color:#c00;font-size:11px">⚠ Forecast unavailable — ${e.message}</div>`;
      const lbl = document.getElementById('dayLabelStrip');
      if (lbl) lbl.innerHTML = `<div style="grid-column:1/8;text-align:center;padding:6px;color:#c00;font-size:10px">Forecast error</div>`;
    }
    markError('forecast'); throw e; // re-throw so Promise.allSettled records the failure
  }
  applyForecastData(data);
  saveCache('forecast', data);   // fire-and-forget cache write
  markFresh('forecast');
}

/* ── TO-DO LIST (reads from todo.php) ── */
const TODO_API = window.location.origin + '/todo-api.php';
let todoRawItems = []; // all raw items — used by loadCalendar for todo→cal injection

// Priority 1(critical)→5(someday): bg, text colour, left-stripe colour, short label
const PRIORITY_STYLE = {
  1: { bg:'#fee',    color:'#b00',    stripe:'#b00',    label:'P1' },
  2: { bg:'#fff3e0', color:'#d06000', stripe:'#d06000', label:'P2' },
  3: { bg:'#f0f0f0', color:'#555',    stripe:'#888',    label:'P3' },
  4: { bg:'#f5f5f5', color:'#999',    stripe:'#bbb',    label:'P4' },
  5: { bg:'#fafafa', color:'#bbb',    stripe:'#ddd',    label:'P5' },
};
function getPriorityStyle(p) {
  return PRIORITY_STYLE[parseInt(p)] || PRIORITY_STYLE[3];
}

const DONE_KEY   = 'kaslo_done_ids';
const DONE_AT_KEY = 'kaslo_done_at';   // id→timestamp map for 24h auto-purge

// Load done set, purging any entries older than 24 h
function loadDoneSet() {
  const raw   = JSON.parse(localStorage.getItem(DONE_KEY)   || '[]');
  const atMap = JSON.parse(localStorage.getItem(DONE_AT_KEY)|| '{}');
  const now   = Date.now();
  const keep  = raw.filter(id => {
    const t = atMap[id];
    return t && (now - t) < 24*60*60*1000; // keep if < 24 h old
  });
  // Clean up stale atMap entries
  const keepSet = new Set(keep);
  Object.keys(atMap).forEach(id => { if (!keepSet.has(id)) delete atMap[id]; });
  localStorage.setItem(DONE_KEY,    JSON.stringify(keep));
  localStorage.setItem(DONE_AT_KEY, JSON.stringify(atMap));
  return new Set(keep);
}

let doneSet = loadDoneSet();

function saveDone(id, nowDone) {
  const atMap = JSON.parse(localStorage.getItem(DONE_AT_KEY)||'{}');
  if (nowDone) { atMap[id] = Date.now(); }
  else         { delete atMap[id]; }
  localStorage.setItem(DONE_AT_KEY, JSON.stringify(atMap));
  localStorage.setItem(DONE_KEY,    JSON.stringify([...doneSet]));
}

function fmtDue(due) {
  if(!due) return '';
  // Compare as date strings to avoid any timezone/DST offset shifting the day
  const todayStr = new Date().toLocaleDateString('en-CA'); // 'YYYY-MM-DD' in local time
  if(due === todayStr) {
    return ' <span style="font-size:9px;color:#e07000">Today</span>';
  }
  const todayD = new Date(todayStr + 'T12:00:00');
  const dueD   = new Date(due      + 'T12:00:00');
  const diff = Math.round((dueD - todayD) / 86400000);
  const label = diff < 0  ? Math.abs(diff) + 'd overdue'
              : diff === 1 ? 'Tomorrow'
              : dueD.toLocaleDateString('en-CA',{month:'short',day:'numeric'});
  const color = diff < 0 ? '#c00' : diff === 1 ? '#e07000' : '#aaa';
  return ' <span style="font-size:9px;color:'+color+'">' + label + '</span>';
}

function renderTodoItems(listItems){
  const list = document.getElementById('todoList');
  list.innerHTML = '';
  if (!listItems.length) {
    list.innerHTML = '<div style="font-size:10px;color:#ccc;font-style:italic;padding:4px 0">All clear ✓</div>';
    return;
  }
  // Sort by priority (1=highest) — items with same priority keep insertion order
  const sorted = [...listItems].sort((a,b) => (parseInt(a.priority)||5) - (parseInt(b.priority)||5));
  sorted.forEach(item => {
    const done = doneSet.has(item.id) || item.done;
    const tags = Array.isArray(item.tags) && item.tags.length > 0 ? item.tags : null;
    // Build meta line: tags + due date in small grey text below the main label
    const tagParts = tags
      ? tags.map(t => `<span style="background:#222;color:#fff;font-size:7px;font-weight:700;padding:1px 4px;border-radius:1px;letter-spacing:0.5px">${escHtml(t)}</span>`).join(' ')
      : '';
    const duePart = item.due ? fmtDue(item.due) : '';
    const metaHtml = (tagParts || duePart) ? `<div class="todo-item-meta">${tagParts}${tagParts&&duePart?' ':''}${duePart}</div>` : '';
    // Notes/description field (if present)
    const notesHtml = item.notes ? `<div class="todo-item-meta">${escHtml(item.notes)}</div>` : '';
    const row = document.createElement('div');
    row.className = `todo-item${done ? ' done-item' : ''}`;
    if (done) row.style.opacity = '0.4';
    // No checkbox — just text + meta line below
    row.innerHTML = `<div class="todo-item-text${done?' todo-text done':''}">${escHtml(item.text)}</div>${metaHtml}${notesHtml}`;
    list.appendChild(row);
  });
}
function escHtml(s){ return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

function toggleDone(id, el){
  const nowDone = !doneSet.has(id);
  if (nowDone) doneSet.add(id); else doneSet.delete(id);
  saveDone(id, nowDone);
  const row = el.closest('.todo-item');
  el.classList.toggle('done');
  el.nextElementSibling.classList.toggle('done');
  row.style.opacity = nowDone ? '0.4' : '';
}

async function loadTodos(){
  try {
    // Try ?action=raw first (returns full unfiltered list for calendar injection)
    let allItems = null;
    const r = await fetch(TODO_API + '?t=' + Date.now(), {credentials:'include'});
    if (r.status === 401) throw new Error('session_expired');
    if (!r.ok) throw new Error('todo-api HTTP ' + r.status);
    const data = await r.json();
    if (!Array.isArray(data)) throw new Error('Unexpected response from todo-api');
    allItems = data;

    todoRawItems = allItems; // store ALL items for calendar injection

    // For the todo panel: filter out done, and recurring items (they go to calendar)
    const todayLocal = new Date().toLocaleDateString('en-CA'); // YYYY-MM-DD local
    const calDateSet = new Set(forecastDates);
    const displayItems = allItems.filter(item => {
      if (item.done) return false;
      if (item.recurWeekday != null && item.recurWeekday !== '') return false; // in calendar
      if (item.recurDay     != null && item.recurDay     !== '') return false; // in calendar
      if (item.due && calDateSet.has(item.due)) return false; // due-in-window → calendar
      return true;
    });

    renderTodoItems(displayItems);
  } catch(e){
    console.error('loadTodos failed:', e.message);
    const expired = e.message === 'session_expired';
    document.getElementById('todoList').innerHTML = expired
      ? '<div style="font-size:9px;color:#c00;font-style:italic"><a href="/login.php" style="color:#c00">Session expired — log in</a></div>'
      : '<div style="font-size:9px;color:#ccc;font-style:italic">List unavailable</div>';
  }
}

// loadTodos is called after forecast+calendar in the init block below
setInterval(async()=>{ doneSet = loadDoneSet(); await loadTodos(); await loadCalendar(); }, 5*60*1000); // refresh todos+calendar every 5 min
/* ── iCAL PARSER ── */

// Parse iCal DTSTART / DTEND value to 'YYYY-MM-DD' string
function icalDateToISO(val) {
  // All-day: 20250224  →  2025-02-24
  // DateTime: 20250224T153000Z or 20250224T153000
  const m=val.replace(/[TZ].*$/,'').match(/^(\d{4})(\d{2})(\d{2})$/);
  if(!m) return null;
  return `${m[1]}-${m[2]}-${m[3]}`;
}

// Parse a DTSTART with TZID or VALUE param — returns 'YYYY-MM-DD'
function parseDtstart(line) {
  // line e.g.: DTSTART;TZID=America/Vancouver:20250224T090000
  // or:        DTSTART:20250224
  const val=line.split(':').slice(1).join(':').trim();
  return icalDateToISO(val);
}

// Format a datetime value string to a human-readable time
function icalToTime(val) {
  // val like 20250224T150000Z or 20250224T150000
  const m=val.match(/T(\d{2})(\d{2})/);
  if(!m) return null;  // all-day, no time
  let h=parseInt(m[1]), mn=m[2];
  // If UTC, convert to local (simple offset for America/Vancouver = UTC-8 or -7)
  if(val.endsWith('Z')){
    const offset=new Date().getTimezoneOffset(); // minutes behind UTC
    const totalMin=h*60+parseInt(mn)-offset;
    h=Math.floor(((totalMin%1440)+1440)%1440/60);
    mn=String(Math.floor(((totalMin%1440)+1440)%60)).padStart(2,'0');
  }
  const ampm=h>=12?'pm':'am';
  return `${h%12||12}:${mn}${ampm}`;
}

// Unfold iCal lines (continued lines start with space/tab)
function unfoldIcal(text) {
  return text.replace(/\r\n[ \t]/g,'').replace(/\n[ \t]/g,'');
}

// Parse full iCal text → array of {date, endDate, summary, time, isMultiDay}
function parseIcal(text) {
  const events=[];
  const unfolded=unfoldIcal(text);
  const blocks=unfolded.split('BEGIN:VEVENT');
  for(let bi=1;bi<blocks.length;bi++){
    const block=blocks[bi];
    let summary='', dateStr=null, endDateStr=null, timeStr=null;

    const lines=block.split(/\r?\n/);
    for(const line of lines){
      const pu=line.toUpperCase();
      if(pu.startsWith('SUMMARY:')){
        summary=line.slice(line.indexOf(':')+1).trim();
        // Decode basic iCal text escaping
        summary=summary.replace(/\\n/g,' ').replace(/\\,/g,',').replace(/\\;/g,';').replace(/\\\\/g,'\\');
      }
      if(pu.startsWith('DTSTART')){
        const rawVal=line.split(':').slice(1).join(':').trim();
        dateStr=icalDateToISO(rawVal);
        timeStr=icalToTime(rawVal);
      }
      if(pu.startsWith('DTEND')||pu.startsWith('DUE')){
        const rawVal=line.split(':').slice(1).join(':').trim();
        let ed=icalDateToISO(rawVal);
        // For all-day events iCal DTEND is exclusive (day AFTER last day) — subtract 1
        if(ed && !icalToTime(rawVal)){
          const d=new Date(ed+'T12:00:00'); d.setDate(d.getDate()-1);
          ed=d.toLocaleDateString('en-CA');
        }
        endDateStr=ed;
      }
    }
    if(summary && dateStr){
      const isMultiDay = !!(endDateStr && endDateStr > dateStr);
      events.push({date:dateStr, endDate:endDateStr||dateStr, summary, time:timeStr, isMultiDay});
    }
  }
  // Sort by time within same date
  events.sort((a,b)=>(a.date+(a.time||''))>(b.date+(b.time||''))?1:-1);
  return events;
}

// Fetch a remote iCal: skip direct (iCloud blocks CORS), go straight to same-origin PHP proxy
async function fetchIcal(url) {
  // Direct attempt only if not iCloud (iCloud never sends CORS headers — skip to save console noise)
  const isIcloud = url.includes('caldav.icloud.com');
  if (!isIcloud) {
    try {
      const r = await fetch(url, {cache:'no-cache'});
      if(r.ok){ const t=await r.text(); if(t.includes('BEGIN:VCALENDAR')) return t; }
    } catch(e){}
  }
  // Proxy via same-origin PHP proxy
  const proxyUrl = CORS_PROXY + encodeURIComponent(url);
  try {
    const r = await fetch(proxyUrl, {credentials:'include'});
    if(r.ok){
      const t = await r.text();
      if(t.includes('BEGIN:VCALENDAR')){
        console.log('iCal loaded via proxy:', url.split('/').slice(2,3).join(''));
        return t;
      } else {
        console.warn('Proxy returned non-iCal content. Preview:', t.slice(0,200));
      }
    } else {
      console.warn('Proxy HTTP error', r.status, '—', proxyUrl);
    }
  } catch(e){
    console.error('Proxy fetch exception:', e.message);
  }
  return null;
}


/* ── HOLIDAY MAP BUILDER ── */
// Returns a Map of 'YYYY-MM-DD' → holiday name from embedded ICS.
// Fast — no network. Safe to call multiple times.
function buildHolidayMap() {
  const m = new Map();
  parseIcal(BC_HOLIDAYS_ICS).forEach(ev => m.set(ev.date, ev.summary));
  parseIcal(CA_HOLIDAYS_ICS).forEach(ev => { if(!m.has(ev.date)) m.set(ev.date, ev.summary); });
  return m;
}

/* ── MULTI-DAY ZONE RENDERER ── */
// Renders multi-day events as unbroken spanning bars in a CSS-grid zone element.
// Events don't need to appear in individual cells — the zone IS the bar.
function renderMultidayZone(zoneEl, weekDates, multiEvents) {
  zoneEl.innerHTML = '';
  const wFirst = weekDates[0], wLast = weekDates[weekDates.length-1];

  // Events that overlap this week
  const visible = multiEvents
    .filter(ev => ev.date <= wLast && ev.endDate >= wFirst)
    .map(ev => ({
      ...ev,
      startI: ev.date < wFirst ? 0 : weekDates.indexOf(ev.date),
      endI:   ev.endDate > wLast ? weekDates.length-1 : weekDates.indexOf(ev.endDate),
    }))
    .filter(ev => ev.startI >= 0 && ev.endI >= 0 && ev.startI <= weekDates.length-1);

  if (!visible.length) {
    zoneEl.className = zoneEl.className.replace('has-events','') + ' no-events';
    zoneEl.style.display = 'none';
    return;
  }
  // Greedy row assignment to prevent overlap
  visible.sort((a,b)=>a.startI-b.startI);
  const rowEnds = [];
  visible.forEach(ev => {
    let row = rowEnds.findIndex(end => end < ev.startI);
    if (row === -1) { row = rowEnds.length; rowEnds.push(ev.endI); }
    else rowEnds[row] = ev.endI;
    ev.row = row + 1; // 1-indexed CSS grid row
  });

  zoneEl.className = zoneEl.className.replace('no-events','') + ' has-events';
  zoneEl.style.display = '';

  visible.forEach(ev => {
    const bar = document.createElement('div');
    bar.className = 'md-bar';
    bar.style.gridColumn = `${ev.startI+1} / ${ev.endI+2}`;
    bar.style.gridRow = ev.row;
    bar.title = `${ev.summary} · ${ev.date} – ${ev.endDate}`;
    // Label: right-aligned span, overflows off the left edge
    const lbl = document.createElement('span');
    lbl.className = 'md-label';
    lbl.textContent = ev.summary;
    bar.appendChild(lbl);
    zoneEl.appendChild(bar);
  });
}

// applyCalendarEvents: atomically clear + populate all calendar cells.
// Takes a pre-fetched/pre-filtered allEvents array and a holidayDates Map.
// Safe to call multiple times — each call fully replaces the previous render.
function applyCalendarEvents(allEvents, holidayDates) {
  // 1. Clear cells and re-apply holiday styling
  forecastDates.forEach(d => {
    const cell = document.getElementById('cal-'+d);
    if(!cell) return;
    cell.innerHTML = '';
    if(holidayDates.has(d)){
      cell.classList.add('holiday');
      const lbl = document.createElement('div');
      lbl.className = 'cal-holiday-label';
      lbl.textContent = holidayDates.get(d);
      cell.appendChild(lbl);
    } else {
      cell.classList.remove('holiday');
    }
  });

  // 2. Separate single-day from multi-day
  const singleEvents = allEvents.filter(ev => !ev.isMultiDay);
  const multiEvents  = allEvents.filter(ev =>  ev.isMultiDay);

  // Deduplicate multi-day events (iCal recurrence can produce duplicates)
  const seenMulti = new Set();
  const deduped = multiEvents.filter(ev => {
    const k = ev.summary+'|'+ev.date;
    if(seenMulti.has(k)) return false;
    seenMulti.add(k); return true;
  });

  // 3. Single-day events → coloured-border pills in individual cells
  singleEvents.forEach(ev => {
    const cell = document.getElementById('cal-'+ev.date);
    if(!cell) return;
    const div = document.createElement('div');
    div.className = 'cal-event';
    div.style.borderLeftColor = CAL_COLORS[ev.calColor] || '#000';
    div.title = ev.summary + (ev.time ? ' · '+ev.time : '');
    div.innerHTML = ev.time
      ? `<span class="cal-event-time">${ev.time}</span>${ev.summary}`
      : ev.summary;
    cell.appendChild(div);
  });

  // 4. Multi-day events → spanning bars in zone strips
  const zone1 = document.getElementById('multidayZone1');
  const zone2 = document.getElementById('multidayZone2');
  const zone3 = document.getElementById('multidayZone3');
  if(zone1) renderMultidayZone(zone1, forecastDates.slice(0,7),   deduped);
  if(zone2) renderMultidayZone(zone2, forecastDates.slice(7,14),  deduped);
  if(zone3) renderMultidayZone(zone3, forecastDates.slice(14,21), deduped);

  // 5. Inject todo items into calendar cells
  const forecastDateSet14 = new Set(forecastDates);
  const injectTodoCell = (dateStr, item) => {
    const cell = document.getElementById('cal-' + dateStr);
    if (!cell) return;
    const empty = cell.querySelector('.cal-empty');
    if (empty) empty.remove();
    const div = document.createElement('div');
    div.className = 'cal-event cal-todo';
    div.style.cssText = 'background:#333;border-left-color:#111;color:#fff;font-weight:700;';
    div.innerHTML = '✓ ' + escHtml(item.text);
    div.title = item.text + (item.due ? ' · Due ' + item.due : '');
    const firstNonHoliday = Array.from(cell.children).find(c => !c.classList.contains('cal-holiday-label'));
    if (firstNonHoliday) cell.insertBefore(div, firstNonHoliday);
    else cell.appendChild(div);
  };

  (todoRawItems.length > 0 ? todoRawItems : []).forEach(item => {
    if (item.done) return;
    const rw = (item.recurWeekday != null && item.recurWeekday !== '') ? parseInt(item.recurWeekday) : null;
    const rd = (item.recurDay     != null && item.recurDay     !== '') ? parseInt(item.recurDay)     : null;
    if (rw !== null || rd !== null) {
      forecastDates.forEach(dateStr => {
        const dt = new Date(dateStr + 'T12:00:00');
        if ((rw !== null && rw === dt.getDay()) || (rd !== null && rd === dt.getDate()))
          injectTodoCell(dateStr, item);
      });
    } else if (item.due && forecastDateSet14.has(item.due)) {
      injectTodoCell(item.due, item);
    }
  });

  // 6. Fill any remaining empty cells with a quiet dash
  forecastDates.forEach(d => {
    const cell = document.getElementById('cal-'+d);
    if(!cell) return;
    const hasContent = Array.from(cell.children).some(c =>
      c.classList.contains('cal-event') || c.classList.contains('cal-holiday-label'));
    if(!hasContent && !cell.classList.contains('holiday'))
      cell.innerHTML = '<span class="cal-empty">—</span>';
  });
}

// filterEventsToWindow: apply forecastDates window filter to an events array.
// Re-filters cached events (which may have been filtered to an older window).
function filterEventsToWindow(events) {
  if (!forecastDates.length) return events;
  const winFirst = forecastDates[0];
  const winLast  = forecastDates[forecastDates.length-1];
  const fds = new Set(forecastDates);
  return events.filter(ev => {
    if(ev.isMultiDay) return ev.date <= winLast && ev.endDate >= winFirst;
    return fds.has(ev.date);
  });
}

async function loadCalendar() {
  // Guard: calendar cells need forecastDates to be populated first.
  // If forecast failed and no cache provided dates, bail — nothing to populate.
  if (!forecastDates.length) {
    console.warn('loadCalendar: forecastDates is empty — skipping (forecast may have failed)');
    return;
  }
  // Always rebuild holiday map (fast — embedded ICS, no network)
  const holidayDates = buildHolidayMap();

  // Fetch all remote iCal feeds in parallel — BEFORE clearing any cells
  const results = await Promise.allSettled(REMOTE_CALS.map(c => fetchIcal(c.url)));
  let allEvents = [];
  let anyLoaded = false;
  results.forEach((r,i) => {
    if(r.status==='fulfilled' && r.value){
      const evs = parseIcal(r.value).map(ev => ({...ev, calColor: REMOTE_CALS[i].color||'dark'}));
      console.log(`Calendar "${REMOTE_CALS[i].label}": ${evs.length} events parsed`);
      allEvents = allEvents.concat(evs);
      anyLoaded = true;
    } else {
      console.warn(`Calendar "${REMOTE_CALS[i].label}": failed —`, r.reason?.message ?? r.reason);
    }
  });

  if (anyLoaded) {
    // Fresh data arrived — filter to current window, apply, save cache
    allEvents = filterEventsToWindow(allEvents);
    allEvents.sort((a,b)=>(a.date+(a.time||''))>(b.date+(b.time||''))?1:-1);
    console.log(`Calendar: ${allEvents.length} events after window filter`);
    applyCalendarEvents(allEvents, holidayDates);
    saveCache('ical', allEvents);  // save parsed events array (not raw iCal text)
    markFresh('ical');
  } else {
    // ALL feeds failed.  If we have INIT_CACHE.ical, re-apply it so the
    // calendar cells (which loadForecast may have just cleared) show data.
    if (INIT_CACHE.ical && Array.isArray(INIT_CACHE.ical.d) && forecastDates.length) {
      console.warn('All iCal feeds failed — re-applying cached calendar events');
      const cachedEvs = filterEventsToWindow(INIT_CACHE.ical.d);
      applyCalendarEvents(cachedEvs, holidayDates);
      markStale('ical');
    } else {
      console.warn('All iCal feeds failed and no cache available — calendar empty');
      markError('ical');
    }
  }
}

function markCalendarEmpty(msg) {
  forecastDates.forEach(d => {
    const cell = document.getElementById('cal-'+d);
    if(cell) cell.innerHTML = `<span class="cal-loading">${msg}</span>`;
  });
}

/* ── CHART ── */
function drawChart(daily,days) {
  const canvas=document.getElementById('chart');
  const W=canvas.parentElement.getBoundingClientRect().width||940;
  const H=120;
  canvas.width=W; canvas.height=H;
  const ctx=canvas.getContext('2d');
  ctx.clearRect(0,0,W,H);
  if(!daily||days<2) return;

  const highs=[],lows=[],precips=[];
  for(let i=0;i<days;i++){
    highs.push(daily.temperature_2m_max[i]??null);
    lows.push(daily.temperature_2m_min[i]??null);
    precips.push(daily.precipitation_sum[i]??0);
  }

  const PT=18,PB=8,colW=W/days,cH=H-PT-PB;
  const allT=[...highs,...lows].filter(v=>v!=null);
  if(!allT.length) return;
  const tMin=Math.floor(Math.min(...allT)-2),tMax=Math.ceil(Math.max(...allT)+2),tRng=tMax-tMin||1;
  const pMax=Math.max(...precips,2),barMaxH=cH*0.28;
  const xPos=i=>colW*i+colW/2;
  const yTemp=v=>PT+(1-(v-tMin)/tRng)*cH;
  const fs=Math.max(8,W*0.013);
  const F=`-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif`;

  const step=tRng<=10?2:5;
  ctx.lineWidth=1;
  for(let t=Math.ceil(tMin/step)*step;t<=tMax;t+=step){ctx.strokeStyle='#f0f0f0';ctx.beginPath();ctx.moveTo(0,yTemp(t));ctx.lineTo(W,yTemp(t));ctx.stroke();}
  if(tMin<=0&&tMax>=0){ctx.strokeStyle='#ddd';ctx.setLineDash([3,3]);ctx.beginPath();ctx.moveTo(0,yTemp(0));ctx.lineTo(W,yTemp(0));ctx.stroke();ctx.setLineDash([]);}
  ctx.strokeStyle='#eee';
  for(let i=1;i<days;i++){ctx.beginPath();ctx.moveTo(colW*i,0);ctx.lineTo(colW*i,H);ctx.stroke();}

  const barW=Math.max(6,colW*0.26);
  for(let i=0;i<days;i++){
    const bH=(precips[i]/pMax)*barMaxH;
    if(bH>0.5){
      const x=xPos(i);ctx.fillStyle='#d8d8d8';ctx.fillRect(x-barW/2,H-PB-bH,barW,bH);
      if(precips[i]>=0.5){ctx.fillStyle='#aaa';ctx.font=`${Math.max(7,fs*0.76)}px ${F}`;ctx.textAlign='center';ctx.fillText(precips[i].toFixed(1),x,H-PB-bH-3);}
    }
  }
  ctx.fillStyle='#ccc';ctx.textAlign='right';ctx.font=`${Math.max(7,fs*0.76)}px ${F}`;ctx.fillText(pMax.toFixed(0)+'mm',W-3,H-PB-barMaxH+8);

  ctx.beginPath();let mv=false;
  for(let i=0;i<days;i++){if(highs[i]==null)continue;mv?ctx.lineTo(xPos(i),yTemp(highs[i])):ctx.moveTo(xPos(i),yTemp(highs[i]));mv=true;}
  for(let i=days-1;i>=0;i--){if(lows[i]==null)continue;ctx.lineTo(xPos(i),yTemp(lows[i]));}
  ctx.closePath();ctx.fillStyle='rgba(0,0,0,0.05)';ctx.fill();

  ctx.beginPath();ctx.strokeStyle='#000';ctx.lineWidth=2;let s=false;
  for(let i=0;i<days;i++){if(highs[i]==null)continue;if(!s){ctx.moveTo(xPos(i),yTemp(highs[i]));s=true;}else ctx.lineTo(xPos(i),yTemp(highs[i]));}
  ctx.stroke();

  ctx.beginPath();ctx.strokeStyle='#999';ctx.lineWidth=1.5;ctx.setLineDash([4,3]);s=false;
  for(let i=0;i<days;i++){if(lows[i]==null)continue;if(!s){ctx.moveTo(xPos(i),yTemp(lows[i]));s=true;}else ctx.lineTo(xPos(i),yTemp(lows[i]));}
  ctx.stroke();ctx.setLineDash([]);

  for(let i=0;i<days;i++){
    const x=xPos(i);
    if(highs[i]!=null){const y=yTemp(highs[i]);ctx.fillStyle='#000';ctx.beginPath();ctx.arc(x,y,3,0,Math.PI*2);ctx.fill();ctx.font=`700 ${fs}px ${F}`;ctx.textAlign='center';ctx.fillText(Math.round(highs[i])+'°',x,y-5);}
    if(lows[i]!=null){const y=yTemp(lows[i]);ctx.fillStyle='#999';ctx.beginPath();ctx.arc(x,y,2.5,0,Math.PI*2);ctx.fill();ctx.font=`${fs}px ${F}`;ctx.textAlign='center';ctx.fillText(Math.round(lows[i])+'°',x,y+11);}
  }

  ctx.textAlign='left';ctx.fillStyle='#ccc';ctx.font=`${Math.max(7,fs*0.82)}px ${F}`;
  for(let t=Math.ceil(tMin/step)*step;t<=tMax;t+=step)ctx.fillText(t+'°',3,yTemp(t)+3);
  ctx.font=`${Math.max(7,fs*0.85)}px ${F}`;
  ctx.fillStyle='#000';ctx.fillText('— Hi',4,13);
  ctx.fillStyle='#999';ctx.fillText('- Lo',38,13);
  ctx.fillStyle='#ccc';ctx.fillText('▮ Rain',70,13);
}

window.addEventListener('resize',()=>{
  if(forecastData) {
    requestAnimationFrame(()=>drawChart(forecastData.daily,7));
    drawDaylightDial(forecastData.daily.sunrise?.[0], forecastData.daily.sunset?.[0]);
  }
  updateMoon();
});

async function refresh() {
  try {
    await loadObs();  // loadObs() calls markFresh('obs') internally on success
    document.getElementById('liveDot').className='dot';
    document.getElementById('statusText').textContent='Live';
  } catch(e) {
    console.error('refresh error:', e.message);
    document.getElementById('liveDot').className='dot off';
    document.getElementById('statusText').textContent='Offline';
    // If obs fetch fails and we're showing fresh obs, mark stale
    if (INIT_CACHE.obs && !_stale.has('obs')) {
      markStale('obs');
    }
  }
}

let countdown=60;
setInterval(()=>{
  countdown--;
  document.getElementById('nextRefresh').textContent=countdown;
  if(countdown<=0){countdown=60;refresh();}
},1000);

(async()=>{
  // ── PHASE 1: Apply cached data immediately ─────────────────────────────
  // Each block is independent; a bad/absent cache entry is silently skipped.
  if (INIT_CACHE.forecast) {
    try {
      applyForecastData(INIT_CACHE.forecast.d);
      markStale('forecast');
    } catch(e) { console.warn('Cache forecast apply failed:', e.message); }
  }
  if (INIT_CACHE.obs) {
    try {
      // Restore with the actual cached timestamp so the header shows the
      // observation time, not the current time.
      applyObs({...INIT_CACHE.obs.d, now:false, epoch: INIT_CACHE.obs.ts});
      markStale('obs');
    } catch(e) { console.warn('Cache obs apply failed:', e.message); }
  }
  updateMoon();
  // Apply cached calendar events only if we already have forecastDates from the
  // forecast cache above.
  const _initHol = buildHolidayMap();
  if (INIT_CACHE.ical && Array.isArray(INIT_CACHE.ical.d) && forecastDates.length) {
    try {
      applyCalendarEvents(filterEventsToWindow(INIT_CACHE.ical.d), _initHol);
      markStale('ical');
    } catch(e) { console.warn('Cache ical apply failed:', e.message); }
  }

  // ── PHASE 2: Fetch fresh data ──────────────────────────────────────────
  // Observations and forecast load in parallel; calendar loads after forecast
  // (it needs forecastDates to be set by applyForecastData).
  await Promise.allSettled([refresh(), loadForecast()]);
  updateMoon(); // redraw moon after potential resize from forecast layout change
  // If forecast failed but cache already gave us forecastDates, calendar can still load.
  // If forecastDates is still empty here, loadCalendar will bail safely.
  await loadTodos();
  await loadCalendar();
})();
</script>
</body>
</html>