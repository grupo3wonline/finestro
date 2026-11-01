<?php

/**
 * Throwaway generator (FEAT-004) for the static brand images in public/:
 *   og-image.png (1200x630), apple-touch-icon.png (180), icon-192.png, icon-512.png,
 *   favicon.ico (16/32/48, added in the final integration step).
 * Run once from the project root: php .agents/tasks/phase-7-final/tools/make-images.php [all|favicon]
 * Not application code. Requires ext-gd (+ FreeType for TTF text).
 */

declare(strict_types=1);

$root = dirname(__DIR__, 4);
$public = $root.DIRECTORY_SEPARATOR.'public';

$fontBold = firstExisting(['C:/Windows/Fonts/segoeuib.ttf', 'C:/Windows/Fonts/arialbd.ttf']);
$fontRegular = firstExisting(['C:/Windows/Fonts/segoeui.ttf', 'C:/Windows/Fonts/arial.ttf']) ?? $fontBold;
$hasTtf = function_exists('imagettftext') && $fontBold !== null;

// Brand: primary-600 #039855 -> primary-900 #054f31
$from = [0x03, 0x98, 0x55];
$to = [0x05, 0x4F, 0x31];

const SCALE = 2; // supersampling factor for smooth edges

/** @param list<string> $paths */
function firstExisting(array $paths): ?string
{
    foreach ($paths as $path) {
        if (is_file($path)) {
            return $path;
        }
    }

    return null;
}

/** Diagonal gradient: one anti-diagonal line per x+y value covers every pixel. */
function gradient(GdImage $img, array $from, array $to): void
{
    $w = imagesx($img);
    $h = imagesy($img);
    $max = $w + $h - 2;

    for ($k = 0; $k <= $max; $k++) {
        $t = $k / $max;
        $color = imagecolorallocate(
            $img,
            (int) round($from[0] + ($to[0] - $from[0]) * $t),
            (int) round($from[1] + ($to[1] - $from[1]) * $t),
            (int) round($from[2] + ($to[2] - $from[2]) * $t),
        );
        imageline($img, min($k, $w - 1), max(0, $k - ($w - 1)), max(0, $k - ($h - 1)), min($k, $h - 1), $color);
    }
}

function roundedRect(GdImage $img, int $x, int $y, int $w, int $h, int $r, int $color): void
{
    imagefilledrectangle($img, $x + $r, $y, $x + $w - $r, $y + $h, $color);
    imagefilledrectangle($img, $x, $y + $r, $x + $w, $y + $h - $r, $color);
    foreach ([[$x + $r, $y + $r], [$x + $w - $r, $y + $r], [$x + $r, $y + $h - $r], [$x + $w - $r, $y + $h - $r]] as [$cx, $cy]) {
        imagefilledellipse($img, $cx, $cy, $r * 2, $r * 2, $color);
    }
}

/**
 * Chart-line mark from favicon.svg (64x64 viewBox) drawn into a box.
 * Thick polyline = filled circles at joints + thick segments.
 */
function chartMark(GdImage $img, int $x, int $y, int $size, int $color): void
{
    $points = [[18, 44], [26, 32], [34, 38], [46, 20]];
    $u = $size / 64;
    $stroke = (int) max(2, round(4 * $u));
    imagesetthickness($img, $stroke);

    $scaled = array_map(static fn (array $p): array => [(int) round($x + $p[0] * $u), (int) round($y + $p[1] * $u)], $points);

    for ($i = 0; $i < count($scaled) - 1; $i++) {
        imageline($img, $scaled[$i][0], $scaled[$i][1], $scaled[$i + 1][0], $scaled[$i + 1][1], $color);
    }
    // Round caps/joins: GD thick lines are square-ended, so cover joints with discs.
    $join = (int) round($stroke * 1.1);
    foreach ($scaled as [$px, $py]) {
        imagefilledellipse($img, $px, $py, $join, $join, $color);
    }
    imagesetthickness($img, 1);

    $dot = (int) round(6 * $u + $stroke * 0.5);
    imagefilledellipse($img, $scaled[3][0], $scaled[3][1], $dot, $dot, $color);
}

function downsample(GdImage $big, int $w, int $h): GdImage
{
    $out = imagecreatetruecolor($w, $h);
    imagecopyresampled($out, $big, 0, 0, 0, 0, $w, $h, imagesx($big), imagesy($big));

    return $out;
}

function save(GdImage $img, string $path): void
{
    imagepng($img, $path, 9);
    [$w, $h] = getimagesize($path);
    echo basename($path)." {$w}x{$h} ".filesize($path)." bytes\n";
}

function text(GdImage $img, bool $hasTtf, ?string $font, float $size, int $x, int $baseline, int $color, string $text): void
{
    if ($hasTtf && $font !== null) {
        imagettftext($img, $size, 0, $x, $baseline, $color, $font, $text);

        return;
    }

    imagestring($img, 5, $x, $baseline - 16, $text, $color);
}

/**
 * favicon.ico: same tile as favicon.svg (rounded 14/64 corners, transparent
 * outside), one PNG-compressed 32-bit entry per size (supported by every
 * current browser and Windows Vista+).
 *
 * @param list<int> $sizes
 */
function favicon(array $sizes, array $from, array $to, string $path): void
{
    $entries = [];
    foreach ($sizes as $px) {
        $s = $px * 8;
        $big = imagecreatetruecolor($s, $s);
        gradient($big, $from, $to);
        chartMark($big, 0, 0, $s, imagecolorallocate($big, 255, 255, 255));

        // Clear everything outside the rounded rectangle.
        imagealphablending($big, false);
        imagesavealpha($big, true);
        $clear = imagecolorallocatealpha($big, 0, 0, 0, 127);
        $r = $s * 14 / 64;
        for ($y = 0; $y < $s; $y++) {
            for ($x = 0; $x < $s; $x++) {
                $dx = max($r - $x - 0.5, $x + 0.5 - ($s - $r), 0);
                $dy = max($r - $y - 0.5, $y + 0.5 - ($s - $r), 0);
                if ($dx * $dx + $dy * $dy > $r * $r) {
                    imagesetpixel($big, $x, $y, $clear);
                }
            }
        }

        $small = imagecreatetruecolor($px, $px);
        imagealphablending($small, false);
        imagesavealpha($small, true);
        imagefill($small, 0, 0, imagecolorallocatealpha($small, 0, 0, 0, 127));
        imagecopyresampled($small, $big, 0, 0, 0, 0, $px, $px, $s, $s);

        ob_start();
        imagepng($small, null, 9);
        $entries[$px] = (string) ob_get_clean();
    }

    // ICONDIR + one ICONDIRENTRY per image, then the PNG payloads.
    $ico = pack('vvv', 0, 1, count($entries));
    $offset = 6 + 16 * count($entries);
    foreach ($entries as $px => $png) {
        $ico .= pack('CCCCvvVV', $px >= 256 ? 0 : $px, $px >= 256 ? 0 : $px, 0, 0, 1, 32, strlen($png), $offset);
        $offset += strlen($png);
    }
    $ico .= implode('', $entries);

    file_put_contents($path, $ico);
    echo basename($path).' '.implode('/', array_keys($entries)).' px '.strlen($ico)." bytes\n";
}

// Usage: php make-images.php [all|favicon] — "favicon" only rebuilds favicon.ico.
$only = $argv[1] ?? 'all';

favicon([16, 32, 48], $from, $to, $public.DIRECTORY_SEPARATOR.'favicon.ico');

if ($only === 'favicon') {
    exit(0);
}

// ---------------------------------------------------------------- icons
foreach (['apple-touch-icon.png' => 180, 'icon-192.png' => 192, 'icon-512.png' => 512] as $file => $px) {
    $s = $px * SCALE * 2;
    $big = imagecreatetruecolor($s, $s);
    gradient($big, $from, $to);
    $white = imagecolorallocate($big, 255, 255, 255);
    // The polyline spans x 18-46 / y 20-44 of the 64 viewBox (centre 32,32);
    // a 1.15x box keeps it ~50% wide, inside the maskable safe zone (central 80%).
    $mark = (int) round($s * 1.15);
    chartMark($big, (int) (($s - $mark) / 2), (int) (($s - $mark) / 2), $mark, $white);
    save(downsample($big, $px, $px), $public.DIRECTORY_SEPARATOR.$file);
}

// ---------------------------------------------------------- og-image.png
$w = 1200 * SCALE;
$h = 630 * SCALE;
$og = imagecreatetruecolor($w, $h);
imagealphablending($og, true);
gradient($og, $from, $to);

$white = imagecolorallocate($og, 255, 255, 255);
$soft = imagecolorallocatealpha($og, 255, 255, 255, 30);
$mint = imagecolorallocate($og, 0xD1, 0xFA, 0xDF);
$amber = imagecolorallocate($og, 0xFE, 0xC8, 0x4B);
$glow = imagecolorallocatealpha($og, 255, 255, 255, 118);
$green = imagecolorallocate($og, 0x03, 0x98, 0x55);

// Decorative circles (top-right / bottom-right)
imagefilledellipse($og, (int) ($w * 0.92), (int) ($h * 0.08), 900, 900, $glow);
imagefilledellipse($og, (int) ($w * 0.80), (int) ($h * 1.05), 700, 700, $glow);

$S = SCALE;
// Logo tile + wordmark
roundedRect($og, 96 * $S, 120 * $S, 128 * $S, 128 * $S, 28 * $S, $white);
chartMark($og, 96 * $S, 120 * $S, 128 * $S, $green);
text($og, $hasTtf, $fontBold, 78 * $S, 252 * $S, 220 * $S, $white, 'Finestro');

// Tagline + supporting line
text($og, $hasTtf, $fontBold, 40 * $S, 96 * $S, 350 * $S, $white, 'Gestão financeira pessoal inteligente');
text($og, $hasTtf, $fontRegular, 24 * $S, 96 * $S, 410 * $S, $mint, 'Carteiras, transações, metas e orçamentos em um só lugar');

// Divider + publisher
imagefilledrectangle($og, 96 * $S, 470 * $S, 176 * $S, 474 * $S, $amber);
text($og, $hasTtf, $fontRegular, 24 * $S, 96 * $S, 530 * $S, $soft, 'por Grupo 3W Online');

save(downsample($og, 1200, 630), $public.DIRECTORY_SEPARATOR.'og-image.png');
