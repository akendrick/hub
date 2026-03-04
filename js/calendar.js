// Calendar — iCal parser, fetch, holiday map, event rendering, mobile cal

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
  // Mobile: always force-hide zone2/3 regardless of has-events inline style set by renderMultidayZone
  if (window.innerWidth <= 600) {
    if (zone2) zone2.style.display = 'none';
    if (zone3) zone3.style.display = 'none';
  }

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

  // 7. Rebuild mobile vertical calendar from the freshly-populated desktop cells
  buildMobileCal();
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

// ── buildMobileCal — vertical calendar for mobile (week 1 only) ──────────
// Reads the already-populated #cal-YYYY-MM-DD cells and renders a vertical
// list into #mobileCal. Called after applyCalendarEvents.
function buildMobileCal() {
  const wrap = document.getElementById('mobileCal');
  if (!wrap) return;  // PHP stripped this element (not authed) — nothing to do
  wrap.innerHTML = '';
  const today = new Date(); today.setHours(0,0,0,0);
  const DAYS = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
  for (let i = 0; i < 7; i++) {
    const d = new Date(today); d.setDate(today.getDate() + i);
    const dateStr = d.toLocaleDateString('sv-SE');
    const dayOfWeek = d.getDay();
    const isWeekend = dayOfWeek === 0 || dayOfWeek === 6;
    const isToday   = i === 0;
    const dow  = DAYS[dayOfWeek].slice(0,3).toUpperCase();
    const date = d.getDate();

    const row = document.createElement('div');
    row.className = 'mob-cal-day';

    // Day label column
    const lbl = document.createElement('div');
    lbl.className = 'mob-cal-label' + (isWeekend?' weekend':'') + (isToday?' today':'');
    lbl.innerHTML = `<div class="mob-cal-dow">${dow}</div><div class="mob-cal-date">${date}</div>`;
    row.appendChild(lbl);

    // Events column — clone content from the desktop cell if it exists
    const eventsCol = document.createElement('div');
    eventsCol.className = 'mob-cal-events';
    const srcCell = document.getElementById('cal-' + dateStr);
    if (srcCell) {
      // Clone all children except .cal-empty placeholders
      Array.from(srcCell.children).forEach(child => {
        if (!child.classList.contains('cal-empty')) {
          eventsCol.appendChild(child.cloneNode(true));
        }
      });
    }
    if (!eventsCol.children.length) {
      const empty = document.createElement('span');
      empty.className = 'cal-empty'; empty.textContent = '—';
      eventsCol.appendChild(empty);
    }
    row.appendChild(eventsCol);
    wrap.appendChild(row);
  }
}

window.addEventListener('resize',()=>{
  if(forecastData) {
    requestAnimationFrame(()=>drawChart(forecastData.daily,7));
    drawDaylightDial(forecastData.daily.sunrise?.[0], forecastData.daily.sunset?.[0]);
    // Redraw mobile dial in case canvas was resized
    if(window.innerWidth <= 600) {
      drawDaylightDial(forecastData.daily.sunrise?.[0], forecastData.daily.sunset?.[0], 'mobileDaylightCanvas');
    }
  }
  updateMoon();
});

