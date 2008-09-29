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

// this is the image
$image=imagecreatetruecolor(96,18);
// setting up colors
$fon=imagecolorallocate($image,rand(200,255),rand(200,255),rand(200,255));
imagefill($image,10,10,$fon);
imageantialias($image,true);

$colors=Array();
for ($i=0;$i<8;$i++) {	$colors[$i]=imagecolorallocate($image,rand(50,150),rand(50,150),rand(50,150));
}

if (is_array($sess)) {
	$newcode=substr(md5(uniqid($sid)),0,8);

   	$QF_DBase->sql_doupdate('{DBKEY}sessions', Array( 'spamcode' => $newcode, 'spctime' => $timer->time), Array( 'sid' => $sess['sid']) );

    for ($i=0;$i<8;$i++) {
		imagetext($image,3,6+12*$i,9,substr($newcode,$i,1),$colors[$i],True);
	}


}
else
	imagetext($image,2,48,9,'ERROR',$colors[0],True);


header('Content-Type: image/png');
header('Cache-control: no-cache, no-store');
imagetruecolortopalette($image,true,16);
ob_end_clean();
imagepng($image);
?>