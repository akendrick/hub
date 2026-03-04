// Daylight arc dial — SVG-style canvas rendering

/* ── DAYLIGHT ARC DIAL (inspired by Weather app's sun dial) ── */
function drawDaylightDial(sunriseIso, sunsetIso, canvasId) {
  const canvas = document.getElementById(canvasId || 'daylightCanvas');
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



