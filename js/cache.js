// Cache layer — server-side JSON cache read/write + stale banner

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
