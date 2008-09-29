<?php

// Simple InUSE Check
if ( !defined('QF_STARTED') )
        die('Hacking attempt');

// GZIP Starting
$GZipped=False;
if ($QF_Config['GZIP'])
    StartGZIP();

// Visulizer
include 'kernel/visualizer.php';

header('Content-Type: text/css', true);

$css_style=Get_Request('style', 1);
$css_part=$_GET['part'];
    $CSSFile = ($part) ? $part.'.ecss' : 'main.ecss';
    if (!file_exists('styles/'.$css_style.'/'.$CSSFile))
        $css_style='qf_def';
    $CSSFile = 'styles/'.$css_style.'/'.$CSSFile;
    $QF_Config['CSS_imgs_dir']='styles/'.$css_style.'/imgs';
print Combine_ECSS($CSSFile);

?>