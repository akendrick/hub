# Plugin 1: Kaslo Weather
**Strategy:** Polling · **Remove Bleed Margin:** ✅

## Polling URLs
```
https://api.ecowitt.net/api/v3/device/real_time?application_key=C6FD389063D6A82CC7A68532000A5962&api_key=1c2c26a5-a293-4f82-a69a-9e77b6447344&mac=E0:5A:1B:21:11:57&call_back=all&temp_unitid=1&pressure_unitid=3&wind_speed_unitid=7&rainfall_unitid=12&solar_irradiance_unitid=16
https://api.open-meteo.com/v1/forecast?latitude=49.912&longitude=-116.908&current=temperature_2m,apparent_temperature,wind_speed_10m,wind_direction_10m,wind_gusts_10m,relative_humidity_2m,surface_pressure,uv_index,weather_code&daily=temperature_2m_max,temperature_2m_min,precipitation_sum,precipitation_probability_max,weather_code,sunrise,sunset&wind_speed_unit=kmh&timezone=America%2FVancouver&forecast_days=7
```
- `IDX_0` → EcoWitt live station
- `IDX_1` → Open-Meteo forecast

---

## Markup

```html
##{% comment %} ── Wind compass ── ##{% endcomment %}
##{% assign _wd = IDX_0.data.wind.wind_direction.value | plus: 0 %}
##{% if _wd < 23 or _wd >= 338 %}##{% assign wdir = "N" %}
##{% elsif _wd < 68  %}##{% assign wdir = "NE" %}
##{% elsif _wd < 113 %}##{% assign wdir = "E"  %}
##{% elsif _wd < 158 %}##{% assign wdir = "SE" %}
##{% elsif _wd < 203 %}##{% assign wdir = "S"  %}
##{% elsif _wd < 248 %}##{% assign wdir = "SW" %}
##{% elsif _wd < 293 %}##{% assign wdir = "W"  %}
##{% else             %}##{% assign wdir = "NW" %}##{% endif %}

##{% comment %} ── Current WMO → label ── ##{% endcomment %}
##{% assign _wc = IDX_1.current.weather_code | plus: 0 %}
##{% if _wc == 0 %}##{% assign wxlbl = "Clear" %}
##{% elsif _wc == 1 %}##{% assign wxlbl = "Mostly Clear" %}
##{% elsif _wc == 2 %}##{% assign wxlbl = "Partly Cloudy" %}
##{% elsif _wc == 3 %}##{% assign wxlbl = "Overcast" %}
##{% elsif _wc == 45 or _wc == 48 %}##{% assign wxlbl = "Fog" %}
##{% elsif _wc >= 51 and _wc <= 55 %}##{% assign wxlbl = "Drizzle" %}
##{% elsif _wc >= 61 and _wc <= 65 %}##{% assign wxlbl = "Rain" %}
##{% elsif _wc >= 71 and _wc <= 75 %}##{% assign wxlbl = "Snow" %}
##{% elsif _wc >= 80 and _wc <= 82 %}##{% assign wxlbl = "Rain Showers" %}
##{% elsif _wc == 85 or _wc == 86  %}##{% assign wxlbl = "Snow Showers" %}
##{% elsif _wc >= 95 %}##{% assign wxlbl = "Thunderstorm" %}
##{% else %}##{% assign wxlbl = "—" %}##{% endif %}

##{% assign srtime = IDX_1.daily.sunrise[0] | split: "T" | last %}
##{% assign sstime = IDX_1.daily.sunset[0]  | split: "T" | last %}

<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html,body{width:800px;height:480px;overflow:hidden;background:#fff;color:#000}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Arial,sans-serif;font-size:12px;display:flex;flex-direction:column}

.hdr{display:flex;justify-content:space-between;align-items:center;padding:0 12px;height:30px;border-bottom:2px solid #000;flex-shrink:0}
.hdr-title{font-weight:700;font-size:13px;letter-spacing:.04em}
.hdr-sub{font-size:11px;color:#666}
.hdr-time{font-size:11px;color:#555}

/* Two-column body */
.body{display:grid;grid-template-columns:300px 1fr;flex:1;min-height:0}
.col{overflow:hidden}
.col+.col{border-left:1px solid #bbb}

/* LEFT: hero + 4 stats */
.col-left{display:flex;flex-direction:column;padding:10px 14px}
.hero-row{display:flex;align-items:flex-end;gap:12px;margin-bottom:4px}
.big-temp{font-size:80px;font-weight:200;line-height:1}
.big-temp sup{font-size:28px;vertical-align:.65em;font-weight:300}
.hero-right{padding-bottom:8px}
.wx-cond{font-size:16px;font-weight:700;line-height:1.2}
.wx-feels{font-size:12px;color:#555;margin-top:2px}
.hi-lo{font-size:13px;font-weight:600;margin-top:3px}

.precip-row{font-size:11px;color:#666;margin-bottom:8px}

/* 2×2 stat tiles */
.stats{display:grid;grid-template-columns:1fr 1fr;gap:0;border:1px solid #e0e0e0;margin-top:auto}
.st{padding:7px 10px;border-right:1px solid #e0e0e0;border-bottom:1px solid #e0e0e0}
.st:nth-child(2n){border-right:none}
.st:nth-child(3),.st:nth-child(4){border-bottom:none}
.st-lbl{font-size:8px;text-transform:uppercase;letter-spacing:.09em;color:#999;margin-bottom:2px}
.st-val{font-size:20px;font-weight:700;line-height:1.15}
.st-unit{font-size:10px;font-weight:400}
.st-sub{font-size:10px;color:#666;margin-top:1px}

/* RIGHT: 7-day forecast */
.fc{display:grid;grid-template-columns:repeat(7,1fr);height:100%}
.fd{display:flex;flex-direction:column;align-items:center;justify-content:center;padding:8px 3px;border-right:1px solid #ebebeb;gap:3px}
.fd:last-child{border-right:none}
.fd-name{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.04em}
.fd-cond{font-size:9px;color:#444;text-align:center;line-height:1.25}
.fd-pop{font-size:9px;color:#888}
.fd-hi{font-size:16px;font-weight:700}
.fd-lo{font-size:13px;color:#777}
.fd-mm{font-size:9px;color:#aaa}

/* Footer */
.ftr{display:flex;justify-content:space-between;align-items:center;padding:0 14px;height:30px;border-top:1px solid #bbb;flex-shrink:0;font-size:11px}
.ftr-l{font-weight:600}
.ftr-r{color:#555}
</style>

<div class="hdr">
  <div class="hdr-title">IKASLO6 &middot; Kaslo BC</div>
  <div class="hdr-sub">##{{ IDX_0.data.outdoor.temperature.value }}&deg;C station</div>
  <div class="hdr-time">##{{ trmnl.user.time | date: "%H:%M &middot; %a %b %-d" }}</div>
</div>

<div class="body">

  <div class="col col-left">
    <div class="hero-row">
      <div class="big-temp">##{{ IDX_0.data.outdoor.temperature.value | split:"." | first }}<sup>&deg;</sup></div>
      <div class="hero-right">
        <div class="wx-cond">##{{ wxlbl }}</div>
        <div class="wx-feels">Feels ##{{ IDX_0.data.outdoor.feels_like.value | split:"." | first }}&deg;</div>
        <div class="hi-lo">Hi ##{{ IDX_1.daily.temperature_2m_max[0] | round }}&deg; &middot; Lo ##{{ IDX_1.daily.temperature_2m_min[0] | round }}&deg;</div>
      </div>
    </div>

    <div class="precip-row">
      Rain today &nbsp;<strong>##{{ IDX_0.data.rainfall.daily.value }}mm</strong>
      &ensp;&bull;&ensp; Rate <strong>##{{ IDX_0.data.rainfall.rain_rate.value }}mm/h</strong>
      &ensp;&bull;&ensp; Week <strong>##{{ IDX_0.data.rainfall.weekly.value }}mm</strong>
    </div>

    <div class="stats">
      <div class="st">
        <div class="st-lbl">Wind</div>
        <div class="st-val">##{{ IDX_0.data.wind.wind_speed.value | split:"." | first }}<span class="st-unit"> km/h</span></div>
        <div class="st-sub">##{{ wdir }} &bull; gust ##{{ IDX_0.data.wind.wind_gust.value | split:"." | first }}</div>
      </div>
      <div class="st">
        <div class="st-lbl">Pressure</div>
        <div class="st-val" style="font-size:16px">##{{ IDX_0.data.pressure.relative.value | split:"." | first }}<span class="st-unit"> hPa</span></div>
        <div class="st-sub">&nbsp;</div>
      </div>
      <div class="st">
        <div class="st-lbl">Humidity / Dew</div>
        <div class="st-val">##{{ IDX_0.data.outdoor.humidity.value }}<span class="st-unit">%</span></div>
        <div class="st-sub">Dew ##{{ IDX_0.data.outdoor.dew_point.value | split:"." | first }}&deg;</div>
      </div>
      <div class="st">
        <div class="st-lbl">UV / Solar</div>
        <div class="st-val">##{{ IDX_0.data.solar_and_uvi.uvi.value }}</div>
        <div class="st-sub">##{{ IDX_0.data.solar_and_uvi.solar.value | split:"." | first }} W/m&sup2;</div>
      </div>
    </div>
  </div>

  <!-- 7-Day Forecast -->
  <div class="col">
    <div class="fc">
      ##{% for i in (0..6) %}
        ##{% assign _fc = IDX_1.daily.weather_code[i] | plus: 0 %}
        ##{% if _fc == 0 %}##{% assign fc = "Clear" %}
        ##{% elsif _fc == 1 %}##{% assign fc = "Mostly Clr" %}
        ##{% elsif _fc == 2 %}##{% assign fc = "Pt Cloudy" %}
        ##{% elsif _fc == 3 %}##{% assign fc = "Overcast" %}
        ##{% elsif _fc == 45 or _fc == 48 %}##{% assign fc = "Fog" %}
        ##{% elsif _fc >= 51 and _fc <= 55 %}##{% assign fc = "Drizzle" %}
        ##{% elsif _fc >= 61 and _fc <= 65 %}##{% assign fc = "Rain" %}
        ##{% elsif _fc >= 71 and _fc <= 75 %}##{% assign fc = "Snow" %}
        ##{% elsif _fc >= 80 and _fc <= 82 %}##{% assign fc = "Showers" %}
        ##{% elsif _fc == 85 or _fc == 86 %}##{% assign fc = "Snw Shwrs" %}
        ##{% elsif _fc >= 95 %}##{% assign fc = "T-Storm" %}
        ##{% else %}##{% assign fc = "—" %}##{% endif %}
        <div class="fd">
          <div class="fd-name">##{{ IDX_1.daily.time[i] | date: "%a" }}</div>
          <div class="fd-cond">##{{ fc }}</div>
          ##{% assign _pop = IDX_1.daily.precipitation_probability_max[i] | plus: 0 %}
          ##{% if _pop > 10 %}<div class="fd-pop">##{{ _pop }}%</div>##{% endif %}
          <div class="fd-hi">##{{ IDX_1.daily.temperature_2m_max[i] | round }}&deg;</div>
          <div class="fd-lo">##{{ IDX_1.daily.temperature_2m_min[i] | round }}&deg;</div>
          ##{% assign _mm = IDX_1.daily.precipitation_sum[i] | plus: 0 %}
          ##{% if _mm > 0.1 %}<div class="fd-mm">##{{ IDX_1.daily.precipitation_sum[i] | round: 1 }}mm</div>##{% endif %}
        </div>
      ##{% endfor %}
    </div>
  </div>

</div>

<div class="ftr">
  <div class="ftr-l">Indoor: ##{{ IDX_0.data.indoor.temperature.value | split:"." | first }}&deg; &bull; ##{{ IDX_0.data.indoor.humidity.value }}% hum</div>
  <div class="ftr-r">&uarr;##{{ srtime }} &nbsp;&darr;##{{ sstime }}</div>
</div>
```
