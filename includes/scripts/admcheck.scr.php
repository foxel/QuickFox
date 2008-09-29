<?php

// Simple InUSE Check
if ( !defined('QF_STARTED') )
        die('Hacking attempt');

$error='';
$pass=md5(Get_Request('userpass', 2));


      if (!$QF_User->uid) // No username
      {
        $action = '';
        $error .= '<LI>'.$lang['ERR_NOT_LOGED_IN']."\n";
      }
      elseif (!$QF_User->admin) // No username
      {
        $action = '';
        $error .= '<LI>'.$lang['ERR_ADMIN_ONLY']."\n";
      }
      elseif ($pass!=$QF_User->cuser['pass']) // Wrongpass
      {
        $action = '';
        $error .= '<LI>'.$lang['ERR_PASS_LOST']."\n";
      }


if (empty($error))
{   $QF_Session->Set('is_admin', 1);

   $rresult=sprintf($lang['ADM_AUTHORIZED'],$QF_User->uname);
   $redir='index.php?st=mycabinet&amp;job=vis_stat';
}

unset($autokey);
Set_Result($error, $rresult, $redir);
?>