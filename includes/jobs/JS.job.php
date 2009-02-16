<?php

// Simple InUSE Check
if ( !defined('QF_STARTED') )
        die('Hacking attempt');

// GZIP Starting
$GZipped=False;
if ($QF_Config['GZIP'])
    StartGZIP();

// Visulizer

header('Content-Type: text/javascript', true);
header('Expires: '.date('r', $timer->time + 3600), true);

$scripts = Get_Request('scripts', 1, 'v');

$file = 'jscripts/'.$scripts.'.js';

if ($sfile=@fopen($file, 'r')) {
    $indata=fread($sfile,filesize($file));
    $indata=preg_replace('#\{L_(\w+)\}#e', '\$lang["\\1"]', $indata);
    print $indata;
    fclose($sfile);
}
else
    print '// no file found';

?>