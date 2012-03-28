<?php
// Simple InUSE Check
if ( !defined('QF_STARTED') )
        die('Hacking attempt');

if ( defined('CORE_IMAGES_LOADED') )
        die('Scripting error');

define('CORE_IMAGES_LOADED', True);

function img_resize_to($srcfile, $destfile, $nw, $nh, $autoext=False)
{
    if (empty($destfile))
        $destfile=$srcfile;

    if (!extension_loaded('gd'))
        return False;

    $info=pathinfo($destfile);

    $size_img = getimagesize($srcfile);

    if (!$size_img)
        return false;

    $new_ratio=$nw/$nh;
    $src_ratio=$size_img[0]/$size_img[1];

    if ($src_ratio>$new_ratio) {
            $w=min($nw,$size_img[0]);
            $h=intval($w/$src_ratio);
    }
    else {
            $h=min($nh,$size_img[1]);
            $w=intval($h*$src_ratio);
    }

    // определим коэффициент сжатия изображения, которое будем генерить
    $ratio = $w/$h;

    // исходя из того какой тип имеет изображение
    // выбираем функцию создания
    switch ($size_img['mime'])
    {
        case 'image/jpeg':
            $src_img = imagecreatefromjpeg($srcfile);
            break;
        case 'image/gif':
            $src_img = imagecreatefromgif($srcfile);
            break;
        case 'image/png':
            $src_img = imagecreatefrompng($srcfile);
            break;
    }

    if($src_img) {

        ignore_user_abort(true);
        $dest_img = imagecreatetruecolor($w, $h);

        imagealphablending($dest_img,True);
        imagealphablending($dest_img, false);
        imagefilledrectangle($dest_img, 0, 0, $w-1, $h-1, 0x7FFFFFFF);

        imagecopyresampled($dest_img, $src_img, 0, 0, 0, 0, $w, $h, $size_img[0], $size_img[1]);

        imagesavealpha($dest_img,True);
        switch ($size_img['mime'])
        {
            case 'image/jpeg':
                if ($autoext)
                    $destfile.='.jpg';
                imagejpeg($dest_img,$destfile);
                break;
            default :
                if ($autoext)
                    $destfile.='.png';
                imagepng($dest_img,$destfile);
                break;
        }

        imagedestroy($dest_img);
        imagedestroy($src_img);

        return $destfile;
    }

    return False;
}

?>