<?php
// Simple InUSE Check
if ( !defined('QF_STARTED') )
        die('Hacking attempt');

if (!extension_loaded('gd')) {
    $QF_DBase->sql_doinsert('{DBKEY}config', Array( 'parent' => '', 'name' => 'use_spcode', 'value' => 0 ), true);
    QF_exit();
}

function imagetext($image,$font,$x,$y,$text,$color,$center=false,$boxed=false,$boxcolor=0) {
         $zizeh=imagefontwidth($font)*strlen($text);
         $zizev=imagefontheight($font);
         if ($boxed) {
                 $zizeh=$zizeh+6; $zizev=$zizev+6;
                 imagefilledrectangle($image,$x,$y,$x+$zizeh,$y+$zizev,$boxcolor);
                 imagerectangle($image,$x,$y,$x+$zizeh,$y+$zizev,$color);
                 $x=$x+3; $y=$y+3;
                 };
         if ($center) {$x=$x-$zizeh/2; $y=$y-$zizev/2;};
         imagestring($image,$font,$x,$y,$text,$color);

         }

ob_start();

Glob_Request('sid');
$sessid=HTMLStrVal($sid);

$result=$QF_DBase->sql_doselect('{DBKEY}sessions', '*', Array( 'sid' => $sessid, 'ip' => $QF_Client['ipaddr']) );
if ($result) $sess = $QF_DBase->sql_fetchrow($result);

$width = 8*15 + 5;
$dest_img = imagecreatetruecolor($width, 25);
imagefilledrectangle($dest_img, 0, 0, $width-1, 24, imagecolorallocate($dest_img, rand(200, 255), rand(200, 255), rand(200, 255)));

if (is_array($sess)) {
	$newcode=substr(md5(uniqid($sid)),0,8);

   	$QF_DBase->sql_doupdate('{DBKEY}sessions', Array( 'spamcode' => $newcode, 'spctime' => $timer->time), Array( 'sid' => $sess['sid']) );

    $root_img = imagecreatefrompng('kernel/as_data.dat');

    $count = strlen($newcode);

    if (function_exists('imageantialias'))
        imageantialias($dest_img, true);

    for ($i = 0; $i < $count; $i++)
        imageline($dest_img, rand(-10, 0), rand(-30, 55), $width + rand(0, 10), rand(-30, 55), imagecolorallocate($dest_img, rand(100, 200), rand(100, 200), rand(100, 200)));

    for ($i = 0; $i < $count; $i++)
    {
        $id = (int) hexdec($newcode{$i}) + rand(0,3)*16;

        $sx = ($id%8) * 24;
        $sy = floor($id/8) * 32;

        $x = $i*15;
        $w = rand(15, 20);
        $h = rand(20, 25);
        $x+= rand(0, 20-$w);
        $y = rand(0, 25-$h);
        imagecopyresampled($dest_img, $root_img, $x, $y, $sx, $sy, $w, $h, 24, 32);
    }
    imagerectangle($dest_img, 0, 0, $width-1, 24, 0x6b7d87);


}
else
	imagetext($dest_img,2,48,9,'ERROR',$colors[0],True);


header('Content-Type: image/png');
header('Cache-control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
imagetruecolortopalette($dest_img,true,128);
ob_end_clean();
imagepng($dest_img);
?>