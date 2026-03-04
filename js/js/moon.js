// Moon phase calculation and canvas rendering

/* ── MOON ── */
function getMoonPhase(date) {
  const k = new Date('2025-01-29T00:00:00Z');
  return (((((date-k)/86400000)%29.53058867)+29.53058867)%29.53058867)/29.53058867;
}
function moonPhaseName(p) {
  if (p<0.0625||p>=0.9375) return 'New Moon';
  if (p<0.1875) return 'Wax Crescent';
  if (p<0.3125) return '1st Quarter';
  if (p<0.4375) return 'Wax Gibbous';
  if (p<0.5625) return 'Full Moon';
  if (p<0.6875) return 'Wan Gibbous';
  if (p<0.8125) return 'Last Quarter';
  return 'Wan Crescent';
}
// Accurate moon phase renderer (Northern Hemisphere)
// phase 0=new moon, 0.25=first quarter, 0.5=full moon, 0.75=last quarter
function drawMoonCanvas(phase, canvas) {
  if (!canvas) canvas = document.getElementById('moonCanvas');
  if (!canvas) return;
  // Use the size set by caller; canvas.width already set
  const size = canvas.width;
  const ctx = canvas.getContext('2d');
  ctx.clearRect(0, 0, size, size);
  const cx = size/2, cy = size/2, r = size/2 - 1.5;

  const LIT  = '#e8e8e8';  // illuminated surface
  const DARK = '#0f0f0f';  // shadow

  // Clip everything to disc
  ctx.save();
  ctx.beginPath(); ctx.arc(cx, cy, r, 0, Math.PI*2); ctx.clip();

  // --- Algorithm: two-semicircle + elliptical terminator ---
  // phase 0-0.5 (waxing):  lit side is RIGHT
  // phase 0.5-1 (waning):  lit side is LEFT
  // termRx = r * cos(2π*phase): positive for crescent, negative for gibbous, 0 for quarter

  const termRx = r * Math.cos(phase * 2 * Math.PI);

  if (phase <= 0.5) {
    // Fill entire disc dark first
    ctx.fillStyle = DARK; ctx.fillRect(0, 0, size, size);
    // Light up right half
    ctx.fillStyle = LIT;
    ctx.beginPath(); ctx.moveTo(cx, cy-r); ctx.arc(cx, cy, r, -Math.PI/2, Math.PI/2); ctx.closePath(); ctx.fill();
    if (termRx > 0.5) {
      // Crescent: cover right half with dark ellipse — leaves thin bright sliver on far right
      ctx.fillStyle = DARK;
      ctx.beginPath(); ctx.ellipse(cx, cy, termRx, r, 0, -Math.PI/2, Math.PI/2); ctx.closePath(); ctx.fill();
    } else if (termRx < -0.5) {
      // Gibbous: extend lit area into left half with a lit ellipse
      ctx.fillStyle = LIT;
      ctx.beginPath(); ctx.ellipse(cx, cy, -termRx, r, 0, Math.PI/2, -Math.PI/2); ctx.closePath(); ctx.fill();
    }
    // termRx ≈ 0 → first quarter, already correct (right half lit)
  } else {
    // Fill entire disc dark
    ctx.fillStyle = DARK; ctx.fillRect(0, 0, size, size);
    // Light up left half
    ctx.fillStyle = LIT;
    ctx.beginPath(); ctx.moveTo(cx, cy-r); ctx.arc(cx, cy, r, -Math.PI/2, Math.PI/2, true); ctx.closePath(); ctx.fill();
    if (termRx < -0.5) {
      // Gibbous: extend lit area into right half
      ctx.fillStyle = LIT;
      ctx.beginPath(); ctx.ellipse(cx, cy, -termRx, r, 0, -Math.PI/2, Math.PI/2); ctx.closePath(); ctx.fill();
    } else if (termRx > 0.5) {
      // Crescent: cover most of left half with dark ellipse — leaves thin bright sliver on far left
      ctx.fillStyle = DARK;
      ctx.beginPath(); ctx.ellipse(cx, cy, termRx, r, 0, Math.PI/2, -Math.PI/2); ctx.closePath(); ctx.fill();
    }
    // termRx ≈ 0 → last quarter, already correct (left half lit)
  }

  ctx.restore();
  // Subtle rim
  ctx.strokeStyle = '#444'; ctx.lineWidth = 0.8;
  ctx.beginPath(); ctx.arc(cx, cy, r, 0, Math.PI*2); ctx.stroke();
}
function updateMoon() {
  const phase = getMoonPhase(new Date());
  // Main (desktop) moon
  const canvas = document.getElementById('moonCanvas');
  if (canvas) { canvas.width = canvas.height = 80; }
  drawMoonCanvas(phase, canvas);
  const lbl = document.getElementById('moonLbl');
  if (lbl) lbl.textContent = moonPhaseName(phase);
  // (No mobile moon canvas in simplified mobile layout)
}
