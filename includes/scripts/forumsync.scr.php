<?php

// Simple InUSE Check
if ( !defined('QF_STARTED') )
        die('Hacking attempt');


  $error = "";
  $action = Get_Request('action', 2);

Load_Language('admin');

if (!empty($action))
{

      if ($QF_Session->Get('is_admin')!=1 || !$QF_User->admin) // если нет прав
      {
        $action = "";
        $error = $error."<LI>".$lang['ERR_ADMIN_ONLY']."\n";
      }

if (empty($error)) // если ошибок нет, обрабатываем сообщение
{
    $timer->Time_Point();           // We'll count time
    $SStart_SQL  = $QF_DBase->num_queries; // We'll count queries

    include 'includes/forum_scr.php';
    $forum = New qf_forum_upd();
    $forum->rebuild_forum_stats();
    $forum->rebuild_forum_rights();

    $time_used = round($timer->Time_Point(), 3);
    $SQL_used  = $QF_DBase->num_queries - $SStart_SQL;

    $redir_url="index.php?st=mycabinet&amp;job=sections";
    $rresult=sprintf($lang['ADMCAB_FORUMSYNC_COMPLETE'], $time_used, $SQL_used);

}
Set_Result($error, $rresult, $redir_url);
}
?>