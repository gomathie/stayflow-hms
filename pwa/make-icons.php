<?php
function sb_icon($size, $path)
{
    $im = imagecreatetruecolor($size, $size);
    imagealphablending($im, false);
    imagesavealpha($im, true);
    imagefilledrectangle($im, 0, 0, $size - 1, $size - 1, imagecolorallocate($im, 18, 53, 44));
    imagealphablending($im, true);
    $gold = imagecolorallocate($im, 185, 138, 47);
    $pad = (int) round($size * 0.22);
    $mid = (int) round($size / 2);
    $roofY = (int) round($size * 0.46);
    imagefilledpolygon($im, array($mid, $pad, $size - $pad, $roofY, $pad, $roofY), 3, $gold);
    imagefilledrectangle($im, (int) round($size * 0.34), $roofY - (int) round($size * 0.02), (int) round($size * 0.66), (int) round($size * 0.76), $gold);
    $door = imagecolorallocate($im, 18, 53, 44);
    imagefilledrectangle($im, (int) round($size * 0.44), (int) round($size * 0.56), (int) round($size * 0.56), (int) round($size * 0.76), $door);
    imagepng($im, $path, 9);
}
sb_icon(180, __DIR__.'/icon-180.png');
sb_icon(192, __DIR__.'/icon-192.png');
sb_icon(512, __DIR__.'/icon-512.png');
echo "ok\n";
