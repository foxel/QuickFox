<?php

// Simple InUSE Check
if ( !defined('QF_STARTED') )
        die('Hacking attempt');

$error='';
$action = Get_Request('script', 2);
if (!empty($action))
{
  $ntz = Get_Request('new_tz', 2, 'f');
  $nstyle = Get_Request('new_style', 2, 's');
  $redir = $QF_User->cuser['lasturl'];

  if($QF_User->uid)
      $QF_DBase->sql_doupdate('{DBKEY}users', Array('timezone' => $ntz, 'style' => $nstyle), Array('id' => $QF_User->uid ) );
  elseif($QF_User->guest['gid'])
      $QF_DBase->sql_doupdate('{DBKEY}guests', Array('gtimezone' => $ntz, 'gstyle' => $nstyle), Array('gid' => $QF_User->guest['gid'] ) );
  else $error=$lang['QSETS_CANT_APPLY'];

  if (!$error) {
          $QF_DBase->sql_query($query);
          $rresult=$lang['QSETS_APPLIED'];
          $QF_Config['def_tz']=$ntz;
          $QF_Config['style']=$nstyle;
  }

  Set_Result($error, $rresult, $redir);
}
?>