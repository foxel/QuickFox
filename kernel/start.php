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

$timer->Time_Log('Vis Started');

// Styles list
$result=$QF_DBase->sql_doselect('{DBKEY}styles', '*', '', 'ORDER BY name');
if ($result) while ($stylerec=$QF_DBase->sql_fetchrow($result)) {
     $styles[$stylerec['id']]=$stylerec;
}

//
// Common loadings
//
// Loading Common JS
Connect_JS('common');

//
//if we have banned user
//
if ($QF_Locked){
    // Load defaulsts
    $QF_Config['force_css_separate'] = false;
    LoadStyle();
    LoadVisuals();
    $tmpl = Array(
        'locked_for' => create_date('', $QF_Config['site_locked_for']) );
    $QF_Pagedata['caption']=$lang['SITE_LOCKED'];
    $QF_Pagedata['content']=Visual('NODATA_LOCKEDMESS', $tmpl);
    $QF_Pagedata['footstat'] = ($GZipped) ? $Vis['GZIP_FLAG'] : '';
    print Visual('GLOBAL_NODATAPAGE', $QF_Pagedata);
    QF_exit();
}
elseif ($QF_Banned){
    // Load defaulsts
    $QF_Config['force_css_separate'] = false;
    LoadStyle();
    LoadVisuals();
    $tmpl = Array(
        'ips'    => ($current_ban['last_ip'] > $current_ban['first_ip'])
            ? decode_ip($current_ban['first_ip']).' - '.decode_ip($current_ban['last_ip'])
            : decode_ip($current_ban['first_ip']) ,
        'reason' => $current_ban['reason'] );
    $QF_Pagedata['caption']=$lang['BANNED'];
    $QF_Pagedata['content']=Visual('NODATA_BANNEDMESS', $tmpl);
    $QF_Pagedata['footstat'] = ($GZipped) ? $Vis['GZIP_FLAG'] : '';
    print Visual('GLOBAL_NODATAPAGE', $QF_Pagedata);
    QF_exit();
}

$timer->Time_Log('Session before');

$QF_Session->Open_Session($QF_Fix_Session);
if (!$QF_Session->Get('script_token')) {
    $QF_Session->Set('script_token', md5(uniqid()));
}

$timer->Time_Log('Session started');

// Loging in the user or guest
$QF_User->login();

$timer->Time_Log('User logged');

if ($QF_User->is_spider && $QF_Config['restrict_spiders']) {
    // Load defaulsts
    $QF_Config['force_css_separate'] = false;
    LoadStyle();
    LoadVisuals();
    $tmpl = Array(
        'spider_name'   => $QF_User->spider['name'],
	    'spider_visits' => $QF_User->spider['visits'] );

    $QF_Pagedata['caption']=$lang['ERR_SPIDERS_RESTRICTED'];
    $QF_Pagedata['content']=Visual('NODATA_SPIDER_RESTRICTED', $tmpl);
    $QF_Pagedata['footstat'] = ($GZipped) ? $Vis['GZIP_FLAG'] : '';
    print Visual('GLOBAL_NODATAPAGE', $QF_Pagedata);
    QF_exit();
}

// Running Scripts
if(!empty($QF_Script)) {
    $scr_file = 'includes/scripts/'.$QF_Script.'.scr.php';
    $gotScriptToken = Get_Request('script_token');
    $realScriptToken = $QF_Session->Get('script_token');
    if (!$realScriptToken || $realScriptToken != $gotScriptToken) {
        Set_Result($lang['ERR_SCRIPT_TOKEN'], '', '/');
    } else {
        // resetting script_token to new value
        $QF_Session->Set('script_token', md5(uniqid()));
        if (file_exists($scr_file)) {
            Ignore_User_Abort (True);
            include($scr_file);
            $QF_User->login();;
        }
    }
}

// Loading setted style
$curstyle=$styles[$QF_Config['style']];
if ($curstyle['name']) {
    $QF_Config['visual']=$curstyle['visual'];
    $QF_Config['CSS']=$curstyle['CSS'];
}

// Loading CSS data
LoadStyle();

$timer->Time_Log('Started1');
// Loading setted visual

LoadVisuals();

$timer->Time_Log('Started');

?>
