<?php

// Simple InUSE Check
if ( !defined('QF_STARTED') )
        die('Hacking attempt');

$tmpl = Array(
    'tz_options' => '',
    'st_options' => '' );

if ($lang['tz'])
    foreach($lang['tz'] as $ttz=>$ttzs){
        $tmpl['tz_options'].='<option value="'.$ttz.'"';
        $tmpl['tz_options'].=($QF_Config['tz']==$ttz) ? ' SELECTED' : '';
        $tmpl['tz_options'].='>'.$ttzs.'</option>';
    }
else
    for($ttz=-12;$ttz<=13;$ttz++){
        $tmpl['tz_options'].='<option value="'.$ttz.'"';
        $tmpl['tz_options'].=($QF_Config['tz']==$ttz) ? ' SELECTED' : '';
        $tmpl['tz_options'].='>'.$ttz.'</option>';
    }

$setted_style = ($QF_User->uid) ? $QF_User->cuser['style'] : $QF_User->guest['gstyle'];

foreach($styles as $ttst) {
    $tmpl['st_options'].='<option value="'.$ttst['id'].'"';
    $tmpl['st_options'].=($setted_style==$ttst['id']) ? ' SELECTED' : '';
    $tmpl['st_options'].='>'.$ttst['name'].'</option>';
}


print Vis_Draw_panel(Visual('QUICKSETS_PANEL', $tmpl) ,$lang['QSETS_CAPT'],'',true);

?>