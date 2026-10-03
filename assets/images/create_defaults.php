<?php
// Script untuk membuat gambar default
$dir = __DIR__;

// Default resep
$img = imagecreatetruecolor(800, 500);
$bg   = imagecolorallocate($img, 34, 34, 34);
$gold = imagecolorallocate($img, 201, 168, 76);
$gray = imagecolorallocate($img, 80, 80, 80);
imagefill($img, 0, 0, $bg);
imagefilledrectangle($img, 0, 0, 800, 500, $bg);
imagefilledellipse($img, 400, 230, 160, 160, $gray);
imagestring($img, 5, 358, 222, '   NO', $gold);
imagestring($img, 5, 350, 242, ' IMAGE', $gold);
imagejpeg($img, $dir . '/default-resep.jpg', 90);
imagedestroy($img);

// Default avatar
$img2 = imagecreatetruecolor(200, 200);
$bg2  = imagecolorallocate($img2, 34, 34, 34);
$gold2 = imagecolorallocate($img2, 201, 168, 76);
imagefill($img2, 0, 0, $bg2);
imagefilledellipse($img2, 100, 100, 190, 190, $gold2);
imagestring($img2, 5, 88, 93, 'U', $bg2);
imagejpeg($img2, $dir . '/default-avatar.png', 90);
imagedestroy($img2);

echo 'Gambar default berhasil dibuat!';
?>
