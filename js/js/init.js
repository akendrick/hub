// Initialisation — refresh(), countdown timer, startup IIFE
// Depends on: all other JS modules being loaded first.

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
  // Calendar is public (iCal feeds are open URLs) — no auth needed
  await loadCalendar();
  // Todos require a session login
  if (IS_AUTHED) await loadTodos();
})();
