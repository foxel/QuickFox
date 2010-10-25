<?php

// Simple InUSE Check
if ( !defined('QF_STARTED') )
        die('Hacking attempt');
if (!$QF_User->is_spider && $QF_User->uid && ($mess = Get_Request('newmess', 2, 's')) && $QF_User->wlevel)
{
    $new_mess = Array(
        'author' => $QF_User->uname,
        'author_id' => $QF_User->uid,
        'text'   => nl2br(htmlspecialchars(substr($mess, 0, 2048))),
        'time' => time() );

    if (($msglvl = Get_Request('messlevel', 2, 'i')) && $msglvl > 0 && $msglvl <= $QF_User->level )
        $new_mess['acc_lv'] = $msglvl;

    $QF_DBase->sql_doinsert('{DBKEY}minichats', $new_mess);

    Set_Result('', 'OK', 'index.php');
}
else
    Set_Result('MChat Error', '', 'index.php');

?>