<?php
// Gera assets/icons/share.png (1200x630), a prévia padrão para redes sociais.
// Uso: php scripts/make-share-image.php [fonte-negrito.ttf] [fonte-normal.ttf]
declare(strict_types=1);

$bold = $argv[1] ?? 'C:/Windows/Fonts/segoeuib.ttf';
$regular = $argv[2] ?? 'C:/Windows/Fonts/segoeui.ttf';
foreach ([$bold, $regular] as $f) {
    if (!is_file($f)) {
        fwrite(STDERR, "Fonte não encontrada: $f\n");
        exit(1);
    }
}
$W = 1200;
$H = 630;
$im = imagecreatetruecolor($W, $H);
imagesavealpha($im, true);
$bg = imagecolorallocate($im, 17, 18, 20);
$white = imagecolorallocate($im, 255, 255, 255);
$muted = imagecolorallocate($im, 170, 174, 182);
$blue = imagecolorallocate($im, 59, 130, 246);
$card = imagecolorallocate($im, 32, 34, 38);
imagefill($im, 0, 0, $bg);

// Ícone do app à esquerda
$icon = imagecreatefrompng(dirname(__DIR__) . '/assets/icons/icon-512.png');
imagecopyresampled($im, $icon, 90, 165, 0, 0, 300, 300, 512, 512);

// Textos
imagettftext($im, 92, 0, 450, 300, $white, $bold, 'Urbs');
imagettftext($im, 34, 0, 454, 365, $muted, $regular, 'Participação cidadã');
$steps = ['Aponte', 'Proponha', 'Compare'];
$x = 454;
foreach ($steps as $i => $s) {
    $box = imagettfbbox(26, 0, $bold, $s);
    $w = $box[2] - $box[0] + 44;
    imagefilledrectangle($im, $x, 410, $x + $w, 466, $card);
    imagettftext($im, 26, 0, $x + 22, 449, $white, $bold, $s);
    $x += $w + 18;
}
imagefilledrectangle($im, 0, $H - 10, $W, $H, $blue);
imagepng($im, dirname(__DIR__) . '/assets/icons/share.png', 9);
echo "ok assets/icons/share.png\n";
