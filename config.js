// kaslo-weather config — API keys, URLs, calendar feeds, embedded holiday ICS
// Edit this file to change data sources without touching any other JS.

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
