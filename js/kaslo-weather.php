<?php
// ── Auth + inline cache injection ─────────────────────────────────────────
require_once __DIR__ . '/auth.php';

// Page-render auth: session only.
// Remote devices use device-api.php with a long-lived API key instead.
$_kw_session_authed = auth_is_logged_in();
$_kw_is_authed      = $_kw_session_authed;

// ── Inline cache injection ─────────────────────────────────────────────────
$_KW_CACHE_DIR = __DIR__ . '/cache/';
function kw_read_cache(string $k): string {
    global $_KW_CACHE_DIR;
    $f = $_KW_CACHE_DIR . $k . '.json';
    if (!file_exists($f) || !is_readable($f)) return 'null';
    $raw = file_get_contents($f);
    if (!$raw || json_decode($raw) === null) return 'null';
    return json_encode([
        'ts' => filemtime($f),
        'age' => time() - filemtime($f),
        'd'  => json_decode($raw),
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
<link rel="stylesheet" href="/css/kaslo-weather.css">
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
<?php if($_kw_session_authed): ?>  <a href="/logout.php" class="hdr-logout" title="Log out">⎋ out</a><?php endif; ?>
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
        <div class="todo-list-inner" id="todoList">
          <div class="todo-col" id="todoColUrgent"></div>
          <div class="todo-col" id="todoColRest"></div>
        </div>
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

<!-- ══════════════════════════════════════════════════════
     MOBILE-ONLY LAYOUT (≤480px) — replaces entire desktop hero + rows
     Structure: [hero strip] → [todo] → [7-day vertical calendar]
     ══════════════════════════════════════════════════════ -->

<!-- ── MOBILE HERO: compact [temp] · [icon + desc] · [solar dial] ── -->
<div class="mobile-hero-strip" id="mobileHeroStrip">
  <div class="mob-temp-block">
    <span class="mob-temp-val" id="mobileTempC">—</span><span class="mob-temp-unit">°</span>
  </div>
  <div class="mob-icon-desc">
    <div class="mob-wx-icon" id="mobileTodayIcon">—</div>
    <div class="mob-wx-desc" id="mobileTodayDesc"></div>
  </div>
  <div class="mob-dial-block">
    <canvas id="mobileDaylightCanvas" width="88" height="88"></canvas>
    <div class="mob-dial-hrs" id="mobileDaylightHrs"></div>
  </div>
</div>

<!-- ── MOBILE TODO (always shown when authed; hidden by CSS on desktop) ── -->
<?php if($_kw_is_authed): ?>
<div class="mobile-todo-wrap" id="mobileTodoWrap">
  <div class="todo-header"><span>To Do</span><a href="/todo.php" target="_blank">✎</a></div>
  <div class="todo-list-inner" id="mobileTodoList"></div>
</div>
<?php endif; ?>

<!-- ── MOBILE CALENDAR: vertical 7-day (week 1 only, if authed) ── -->
<?php if($_kw_is_authed): ?>
<div class="mobile-cal-wrap" id="mobileCal"></div>
<?php endif; ?>


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
// ── Runtime constants injected by PHP ─────────────────────────────────────
// INIT_CACHE: server-side cache snapshot for instant first paint
const INIT_CACHE = {
  forecast: <?= $_kw_fc ?>,
  ical:     <?= $_kw_ic ?>,
  obs:      <?= $_kw_obs ?>,
};
// IS_AUTHED: true when the viewer has an active session login
const IS_AUTHED = <?= $_kw_is_authed ? 'true' : 'false' ?>;
</script>

<!-- ── JavaScript modules (load order matters — dependencies first) ── -->
<script src="/js/state.js"></script>
<script src="/js/config.js"></script>
<script src="/js/utils.js"></script>
<script src="/js/cache.js"></script>
<script src="/js/moon.js"></script>
<script src="/js/sun.js"></script>
<script src="/js/observations.js"></script>
<script src="/js/forecast.js"></script>
<script src="/js/todo.js"></script>
<script src="/js/calendar.js"></script>
<script src="/js/chart.js"></script>
<script src="/js/init.js"></script>
</body>
</html>
