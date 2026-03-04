// Observations — EcoWitt fetch, pressure trend, DOM apply


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
  // Mirror outdoor temp to mobile hero strip
  if(temp!=null){ const e=document.getElementById('mobileTempC'); if(e) e.textContent=fmt(temp,0); }

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

