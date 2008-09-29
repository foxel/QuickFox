<?php
// Simple InUSE Check
if ( !defined('QF_STARTED') )
        die('Hacking attempt');

// GZIP Starting
$GZipped=False;
if ($QF_Config['GZIP'])
    StartGZIP();

include 'kernel/visualizer.php';

// Styles list
$result=$QF_DBase->sql_doselect('{DBKEY}styles', '*', '', 'ORDER BY name');
if ($result) while ($stylerec=$QF_DBase->sql_fetchrow($result)) {
     $styles[$stylerec['id']]=$stylerec;
}

$QF_Session->Open_Session(true);
if (!$QF_Session->loaded)
    QF_exit('AJAX Session Fault');

// Loging in the user or guest
$QF_User->login();

if ($QF_User->is_spider)
    QF_exit('Spiders can\'t use any AJAX features');

// Loading setted style
$curstyle=$styles[$QF_Config['style']];
if ($curstyle['name']) {
    $QF_Config['visual']=$curstyle['visual'];
    $QF_Config['CSS']=$curstyle['CSS'];
}

Header('Content-Type: text/html; charset=windows-1251');

include 'kernel/core_charset.php';
define('AJAX_UTF_RECHAR', true);
$QF_CharConv = new qf_charconv();

$QF_AJAX_Class = Get_Request('class', 2, 's');
$file = 'includes/ajax/'.$QF_AJAX_Class.'.ajx.php';
if (file_exists($file))
    include $file;
else
    print 'AJAX Error: There is no such action class!';

?>