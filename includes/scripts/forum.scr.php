<?php
// Simple InUSE Check
if ( !defined('QF_STARTED') )
        die('Hacking attempt');

include 'includes/forum_scr.php';

$action = Get_Request('action', 2);
if (!empty($action))
{    $forum = New qf_forum_upd();

    $forum->preload_data();

    if ($action=='newpost')
        $forum->post_message();
    elseif ($action=='editpost')
        $forum->edit_message();
    elseif ($action=='newbranch')
        $forum->add_branch();
    elseif ($action=='editbranch')
        $forum->edit_branch();
    else
        Set_Result('', $lang['ERR_NO_ACTION'], '');

  Set_Result($forum->error, $forum->result, $forum->redir);
}
else
    Set_Result('', $lang['ERR_NO_ACTION'], '');



?>