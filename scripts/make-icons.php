<?php
// Gera os ícones PNG do PWA com GD. Uso: php scripts/make-icons.php
declare(strict_types=1);

function draw_icon(int $size, string $dest, bool $maskable = false): void
{
    $s = 4; // supersampling para bordas suaves
    $S = $size * $s;
    $im = imagecreatetruecolor($S, $S);
    imagesavealpha($im, true);
    imagealphablending($im, false);
    imagefill($im, 0, 0, imagecolorallocatealpha($im, 0, 0, 0, 127));
    imagealphablending($im, true);
    $bg = imagecolorallocate($im, 17, 18, 20);
    $white = imagecolorallocate($im, 255, 255, 255);
    $blue = imagecolorallocate($im, 59, 130, 246);

    if ($maskable) {
        imagefilledrectangle($im, 0, 0, $S, $S, $bg);
        $k = 0.72; // área segura
    } else {
        $r = (int)($S * 14 / 64);
        imagefilledrectangle($im, $r, 0, $S - $r, $S, $bg);
        imagefilledrectangle($im, 0, $r, $S, $S - $r, $bg);
        foreach ([[$r, $r], [$S - $r, $r], [$r, $S - $r], [$S - $r, $S - $r]] as [$cx, $cy]) {
            imagefilledellipse($im, $cx, $cy, $r * 2, $r * 2, $bg);
        }
        $k = 1.0;
    }
    $u = $S / 64 * $k;
    $ox = ($S - 64 * $u) / 2;
    $oy = ($S - 64 * $u) / 2;
    $P = fn(float $x, float $y) => [(int)round($ox + $x * $u), (int)round($oy + $y * $u)];

    // Pino: círculo + triângulo
    [$cx, $cy] = $P(32, 26.6);
    imagefilledellipse($im, $cx, $cy, (int)(30 * $u), (int)(30 * $u), $white);
    $tri = array_merge($P(18.6, 32), $P(45.4, 32), $P(32, 52));
    imagefilledpolygon($im, $tri, $white);
    imagefilledellipse($im, $cx, $cy, (int)(11 * $u), (int)(11 * $u), $bg);
    // Linha azul
    imagesetthickness($im, (int)(3 * $u));
    [$x1, $y1] = $P(20, 55);
    [$x2, $y2] = $P(44, 55);
    imagefilledrectangle($im, $x1, (int)($y1 - 1.5 * $u), $x2, (int)($y1 + 1.5 * $u), $blue);
    imagefilledellipse($im, $x1, $y1, (int)(3 * $u), (int)(3 * $u), $blue);
    imagefilledellipse($im, $x2, $y2, (int)(3 * $u), (int)(3 * $u), $blue);

    $out = imagecreatetruecolor($size, $size);
    imagesavealpha($out, true);
    imagealphablending($out, false);
    imagefill($out, 0, 0, imagecolorallocatealpha($out, 0, 0, 0, 127));
    imagecopyresampled($out, $im, 0, 0, 0, 0, $size, $size, $S, $S);
    imagepng($out, $dest);
    echo "ok $dest\n";
}

$dir = dirname(__DIR__) . '/assets/icons';
draw_icon(192, "$dir/icon-192.png");
draw_icon(512, "$dir/icon-512.png");
draw_icon(512, "$dir/icon-maskable-512.png", true);
