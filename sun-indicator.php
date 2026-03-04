<?php
/**
 * Sun indicator — circular day/night graphic with sunrise/sunset wedge.
 *
 * Standalone PHP: computes today's sunrise, sunset, and civil twilight for
 * a given lat/lon, draws an SVG circle where the top = noon, left = sunrise,
 * right = sunset. The light wedge is daylight; the dark remainder is night.
 * Labels below: first light, sunrise, sunset, last light.
 *
 * Designed to be included in kaslo-weather.php once finalized.
 */

declare(strict_types=1);

// ── Config (match Kaslo when embedding) ─────────────────────────────────────
$LAT = 49.912;
$LON = -116.908;
$TZ  = 'America/Vancouver';

$date = new DateTimeImmutable('now', new DateTimeZone($TZ));
$ts   = $date->getTimestamp();

$sun = date_sun_info($ts, $LAT, $LON);

// All as DateTime in local TZ for display
$first_light = isset($sun['civil_twilight_begin']) ? date_create_from_format('U', (string) $sun['civil_twilight_begin'])->setTimezone(new DateTimeZone($TZ)) : null;
$sunrise     = isset($sun['sunrise'])              ? date_create_from_format('U', (string) $sun['sunrise'])->setTimezone(new DateTimeZone($TZ)) : null;
$sunset      = isset($sun['sunset'])               ? date_create_from_format('U', (string) $sun['sunset'])->setTimezone(new DateTimeZone($TZ)) : null;
$last_light  = isset($sun['civil_twilight_end'])   ? date_create_from_format('U', (string) $sun['civil_twilight_end'])->setTimezone(new DateTimeZone($TZ)) : null;

// Minutes from midnight (local) for wedge math — use sunrise/sunset for the daylight wedge
$midnight = $date->setTime(0, 0)->getTimestamp();
$min_from_midnight = static function ($dt) use ($midnight): ?int {
    if ($dt === null) return null;
    return (int) floor(($dt->getTimestamp() - $midnight) / 60);
};

$rise_min = $min_from_midnight($sunrise);
$set_min  = $min_from_midnight($sunset);

// Map time (minutes 0–1440) to angle: top = noon (90° in math), left = sunrise (180°), right = sunset (0°), bottom = midnight (270°)
// angle = 270 - (minutes / 1440) * 360  (so 0 min → 270°, 360 → 180°, 720 → 90°, 1080 → 0°)
$min_to_angle = static function (int $min): float {
    $min = $min % 1440;
    if ($min < 0) $min += 1440;
    return 270 - ($min / 1440) * 360;
};

$rise_angle = $rise_min !== null ? $min_to_angle($rise_min) : 180;
$set_angle  = $set_min !== null  ? $min_to_angle($set_min)  : 0;

// Daylight span (for asymmetric days we still draw wedge from rise to set)
$day_minutes = ($set_min !== null && $rise_min !== null && $set_min > $rise_min)
    ? $set_min - $rise_min
    : 0;
$day_fraction = $day_minutes / 1440;
$has_day_wedge = $day_minutes > 0;

// SVG geometry
$size = 200;
$cx   = $size / 2;
$cy   = $size / 2;
$r    = ($size / 2) - 6;

$angle_to_xy = static function (float $deg) use ($cx, $cy, $r): array {
    $rad = deg2rad($deg);
    return [
        $cx + $r * cos($rad),
        $cy - $r * sin($rad),
    ];
};

[$rise_x, $rise_y] = $angle_to_xy($rise_angle);
[$set_x, $set_y]   = $angle_to_xy($set_angle);

// Day wedge: arc from sunrise to sunset that passes through noon (90°). We use the clockwise arc; large-arc = 1 when that arc spans > 180°.
$cw_span = (($rise_angle - $set_angle) + 360) % 360;
$day_arc_sweep = 1;
$day_arc_large = $cw_span > 180 ? 1 : 0;

$fmt_time = static function (?DateTimeInterface $dt): string {
    if ($dt === null) return '—';
    return $dt->format('g:i A');
};

// Optional: emit scoped styles when not included (e.g. for standalone test page)
$standalone = !defined('SUN_INDICATOR_EMBEDDED');
?>
<?php if ($standalone): ?>
<style>
.sun-indicator { display: inline-flex; flex-direction: column; align-items: center; gap: 8px; padding: 12px; }
.sun-indicator-svg { display: block; }
.sun-indicator-labels { display: flex; flex-wrap: wrap; justify-content: center; gap: 10px 16px; font-size: 11px; color: #555; }
.sun-indicator-labels .sun-label { white-space: nowrap; }
</style>
<?php endif; ?>
<div class="sun-indicator" aria-label="Daylight indicator: <?php echo (int) round($day_fraction * 100); ?>% daylight">
  <svg class="sun-indicator-svg" viewBox="0 0 <?php echo $size; ?> <?php echo $size; ?>" width="<?php echo $size; ?>" height="<?php echo $size; ?>" role="img">
    <title>24-hour circle: left = sunrise, top = noon, right = sunset. Light = day, dark = night.</title>
    <!-- Night: full circle dark -->
    <circle cx="<?php echo $cx; ?>" cy="<?php echo $cy; ?>" r="<?php echo $r; ?>" fill="#2a2a2a" />
    <?php if ($has_day_wedge): ?>
    <!-- Day: wedge from sunrise to sunset through top -->
    <path
      d="M <?php echo $cx; ?>,<?php echo $cy; ?> L <?php echo round($rise_x, 2); ?>,<?php echo round($rise_y, 2); ?> A <?php echo $r; ?> <?php echo $r; ?> 0 <?php echo $day_arc_large; ?> <?php echo $day_arc_sweep; ?> <?php echo round($set_x, 2); ?> <?php echo round($set_y, 2); ?> Z"
      fill="#6b6b6b"
    />
    <?php endif; ?>
    <!-- Optional: thin circle outline -->
    <circle cx="<?php echo $cx; ?>" cy="<?php echo $cy; ?>" r="<?php echo $r; ?>" fill="none" stroke="#444" stroke-width="1" />
  </svg>
  <div class="sun-indicator-labels">
    <span class="sun-label first-light">First light <?php echo htmlspecialchars($fmt_time($first_light)); ?></span>
    <span class="sun-label sunrise">Sunrise <?php echo htmlspecialchars($fmt_time($sunrise)); ?></span>
    <span class="sun-label sunset">Sunset <?php echo htmlspecialchars($fmt_time($sunset)); ?></span>
    <span class="sun-label last-light">Last light <?php echo htmlspecialchars($fmt_time($last_light)); ?></span>
  </div>
</div>
