<?php
// Builds every Salisberg brand asset from salisberg-master.png.
// Run from the repo root with the repo mounted at /w:
//   docker run --rm --entrypoint php -v "$PWD:/w" <app image> /w/docker/branding/make.php
// Then bump BRANDING_VERSION in docker/entrypoint.sh so existing installs pick the files up.
$src = imagecreatefrompng(__DIR__.'/salisberg-master.png');
imagealphablending($src, false); imagesavealpha($src, true);
$LOGO = [101, 149, 1210, 644];   // x, y, w, h of the wordmark lockup
$ICON = [1403, 495, 313, 318];   // rounded-square mark

// Fit a crop into a w x h canvas. $bg null = transparent. $white = recolour dark ink to white.
function fit($src, $crop, $w, $h, $bg = null, $pad = 0, $align = 'center', $white = false) {
    $c = imagecreatetruecolor($w, $h);
    if ($bg === null) { imagealphablending($c, false); imagesavealpha($c, true);
        imagefill($c, 0, 0, imagecolorallocatealpha($c, 255, 255, 255, 127)); imagealphablending($c, true);
    } else { imagefill($c, 0, 0, imagecolorallocate($c, $bg[0], $bg[1], $bg[2])); }
    $s = min(($w - 2*$pad) / $crop[2], ($h - 2*$pad) / $crop[3]);
    $dw = (int)round($crop[2]*$s); $dh = (int)round($crop[3]*$s);
    $dx = $align === 'left' ? $pad : (int)(($w - $dw)/2); $dy = (int)(($h - $dh)/2);
    $piece = imagecrop($src, ['x'=>$crop[0],'y'=>$crop[1],'width'=>$crop[2],'height'=>$crop[3]]);
    imagealphablending($piece, false); imagesavealpha($piece, true);
    if ($white) {
        for ($y=0; $y<$crop[3]; $y++) for ($x=0; $x<$crop[2]; $x++) {
            $p = imagecolorat($piece, $x, $y); $a = ($p>>24)&0x7F; if ($a == 127) continue;
            $r=($p>>16)&255; $g=($p>>8)&255; $b=$p&255;
            if (max($r,$g,$b) < 110) imagesetpixel($piece, $x, $y, imagecolorallocatealpha($piece, 255, 255, 255, $a));
        }
    }
    imagecopyresampled($c, $piece, $dx, $dy, 0, 0, $dw, $dh, $crop[2], $crop[3]);
    return $c;
}
function ico($im, $sizes, $out) {
    $pngs = [];
    foreach ($sizes as $s) { $t = imagecreatetruecolor($s, $s); imagealphablending($t, false); imagesavealpha($t, true);
        imagecopyresampled($t, $im, 0, 0, 0, 0, $s, $s, imagesx($im), imagesy($im));
        ob_start(); imagepng($t, null, 9); $pngs[$s] = ob_get_clean(); }
    $data = pack('vvv', 0, 1, count($pngs)); $off = 6 + 16*count($pngs); $body = '';
    foreach ($pngs as $s => $p) { $data .= pack('CCCCvvVV', $s, $s, 0, 0, 1, 32, strlen($p), $off); $off += strlen($p); $body .= $p; }
    file_put_contents($out, $data.$body);
}
$W = [255,255,255];
imagejpeg(fit($src, $LOGO, 486, 260, $W, 4), '/w/img/logo.jpg', 92);
imagejpeg(fit($src, $LOGO, 320, 172, $W, 6), '/w/img/logo_mail.jpg', 92);
imagejpeg(fit($src, $LOGO, 320, 172, $W, 6), '/w/img/logo_invoice.jpg', 92);
$icon = fit($src, $ICON, 256, 256, null, 4);
ico($icon, [16, 32, 48, 64], '/w/img/favicon.ico');
imagepng(fit($src, $ICON, 30, 30, $W, 0), '/w/img/logo_stores.png');
imagegif(fit($src, $ICON, 30, 30, $W, 0), '/w/img/logo_stores.gif');
imagepng(fit($src, $LOGO, 369, 196, null, 0), '/w/img/qloapps@2x.png');                 // back office login, top
imagepng(fit($src, $ICON, 272, 272, null, 6), '/w/img/qloapps-login@2x.png');           // back office login, badge
imagepng(fit($src, $ICON, 272, 272, null, 6), '/w/img/qloapps-login-wink@2x.png');
imagepng(fit($src, $ICON, 250, 250, $W, 20), '/w/img/prestashop-avatar.png');           // default employee avatar
imagepng(fit($src, $LOGO, 448, 118, null, 16, 'left', true), '/w/admin/themes/default/img/qloapps-back-office-header.png'); // dark bar
foreach (['img/logo.jpg','img/logo_mail.jpg','img/favicon.ico','img/logo_stores.png','img/qloapps@2x.png','img/qloapps-login@2x.png','img/prestashop-avatar.png','admin/themes/default/img/qloapps-back-office-header.png'] as $f) { $s = getimagesize("/w/$f"); echo "$f {$s[0]}x{$s[1]} {$s['mime']} ".filesize("/w/$f")."b\n"; }
