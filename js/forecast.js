// Forecast — Open-Meteo fetch, DOM population, hi/lo chart scaffold

let forecastDates  = [];

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
  // Mobile: draw dial + mirror icon/desc to mobile hero strip
  drawDaylightDial(daily.sunrise?.[0], daily.sunset?.[0], 'mobileDaylightCanvas');
  if(daily.sunrise?.[0]&&daily.sunset?.[0]){
    const sr=new Date(daily.sunrise[0]),ss=new Date(daily.sunset[0]);
    const mins=Math.round((ss-sr)/60000);
    const dHrsText=`${Math.floor(mins/60)}h ${mins%60}m`;
    const dEl=el('daylightHrs'); if(dEl) dEl.textContent='Daylight '+dHrsText;
    const mDylHrs=el('mobileDaylightHrs'); if(mDylHrs) mDylHrs.textContent=dHrsText;
  }
  if(el('todayHi')) el('todayHi').textContent=hi0!=null?Math.round(hi0)+'°':'—';
  if(el('todayLo')) el('todayLo').textContent=lo0!=null?Math.round(lo0)+'°':'—';
  let precipLine='';
  if(mm0!=null&&mm0>=0.5) precipLine=`${Number(mm0).toFixed(1)} mm`;
  else if(pop0!=null&&pop0>0) precipLine=`${pop0}% chance of precip`;
  if(el('todayPrecip')) el('todayPrecip').textContent=precipLine;
  const desc0=wmoDesc(code0);
  if(el('todayDesc'))        el('todayDesc').textContent=desc0;
  if(el('mobileTodayIcon'))  el('mobileTodayIcon').textContent=wmoIcon(code0);
  if(el('mobileTodayDesc'))  el('mobileTodayDesc').textContent=desc0;

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

