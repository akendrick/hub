// Shared utility functions

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

function escHtml(s){ return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
