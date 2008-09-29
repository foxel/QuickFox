<?php

// Simple InUSE Check
if ( !defined('QF_STARTED') )
        die('Hacking attempt');

Glob_Request('rid');

$result=$QF_DBase->sql_doselect('{DBKEY}results', '*', Array( 'id' => $rid) );
if ($result) $curres=$QF_DBase->sql_fetchrow($result);
if ($curres) {
  if (empty($curres['error'])) {
    $Page_SubTitle = $lang['ERR_NO_ERR'];
      if ($curres['redirect']) {

        $furl=$QF_Session->AddSID(GetFullUrl($curres['redirect'], true));

        Add_META('http-equiv="refresh" content="5; url='.$furl.'"');
        $curres['result'].='<hr>'.sprintf($lang['REDIRECT_YOU'],'<a href="'.$curres['redirect'].'">','</a>');
      }
    Print Vis_Draw_Table('<b>['.create_date('',$curres['time'],'',true).']</b> '. $lang['ERR_NO_ERR'],$curres['result']);

  }
  else
  {
    $Page_SubTitle = $lang['ERR'];
    Print Vis_Draw_Table('<b>['.create_date('',$curres['time'],'',true).']</b> '. $lang['ERRS_DONE'],$curres['error'],true);
  }
}
$query='DELETE FROM {DBKEY}results WHERE time<'.($timer->time - 24*3600);
$QF_DBase->sql_query($query);
?>