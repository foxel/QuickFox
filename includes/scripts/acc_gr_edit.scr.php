<?php

// Simple InUSE Check
if ( !defined('QF_STARTED') )
        die('Hacking attempt');

$error = '';
$action = Get_Request('action', 2);
$gr_id = Get_Request('gr_id', 2);

$create_group = ($gr_id=='--');
$gr_id = intval($gr_id);

$acc_group = Array();

Load_Language('admin');

$result = $QF_DBase->sql_doselect('{DBKEY}acc_groups', '*', Array( 'id' => $gr_id ) );
if ($result) {
    $acc_group = $QF_DBase->sql_fetchrow($result);
    $QF_DBase->sql_freeresult($result);
}

if ($QF_Session->Get('is_admin')!=1 || !$QF_User->admin) {
    $error = $lang['ERR_ADMIN_ONLY'];
}

elseif ($action=='gr_edit') {
    $gr_name = Get_Request('gr_name', 2, 'ht', 32);
    $gr_descr = Get_Request('gr_descr', 2, 'ht', 255);

    $result = $QF_DBase->sql_doselect('{DBKEY}acc_groups', '*', Array( 'name' => $gr_name), ' AND id != '.$gr_id );
    if ($result) {
        $dup_group = $QF_DBase->sql_fetchrow($result);
        $QF_DBase->sql_freeresult($result);
    }

    if (!$create_group && !$acc_group['id'])
        $error.= '<LI>'.$lang['ADMCAB_ACCGRP_ERR_NOGROUP']."\n";
    elseif (empty($gr_name))
        $error.= '<LI>'.$lang['ADMCAB_ACCGRP_ERR_NONAME']."\n";
    elseif ($dup_group['id'])
        $error.= '<LI>'.$lang['ADMCAB_ACCGRP_ERR_NAMEDUP']."\n";

    else
    {        $upd_data = Array(
            'id'    => ($create_group) ? '' : $gr_id,
            'name'  => $gr_name,
            'descr' => $gr_descr,
            );
        $QF_DBase->sql_doinsert('{DBKEY}acc_groups', $upd_data, true);

        if ($create_group)
            $gr_id = intval($QF_DBase->sql_nextid());

        $rresult = ($create_group)
            ? sprintf($lang['ADMCAB_ACCGRP_GR_ADDED'], $gr_name)
            : sprintf($lang['ADMCAB_ACCGRP_GR_EDITED'], $gr_name);

        $redir_url = 'index.php?st=mycabinet&amp;job=acc_groups&amp;show_grp='.$gr_id;
    }

}
elseif ($action=='us_add') {

    if (!$acc_group['id'])
        $error.= '<LI>'.$lang['ADMCAB_ACCGRP_ERR_NOGROUP']."\n";
    else {
        $add_users = Get_Request('add_users', 2);
        $add_users = explode (',', $add_users);
        foreach ($add_users as $num=>$name)
            $add_users[$num] = trim($name);
        $sel_users = '"'.implode('", "', $add_users).'"';

        $todo = Get_Request('todo', 2);
        if ($todo == 'set_month')
            $drop_time = $timer->time + 30*24*3600;
        elseif ($todo == 'set_perm')
            $drop_time = null;
        else
            $drop_time = $timer->time + 30*24*3600;

        $ulist->load(' WHERE u.nick IN ('.$sel_users.')', true);

        $added = 0;

        foreach ($add_users as $uname) {            $uname = trim($uname);
            $user = $ulist->by_nick($uname);
            if ( $user['id']>0 ) {
                $QF_DBase->sql_doinsert('{DBKEY}acc_links', Array('user_id' => $user['id'], 'group_id' => $gr_id, 'time_given' => $timer->time, 'drop_after' => $drop_time), true );
                $added++;
            }
        }

        $rresult = sprintf($lang['ADMCAB_ACCGRP_US_ADDED'], $acc_group['name'], $added);

        $redir_url = 'index.php?st=mycabinet&amp;job=acc_groups&amp;show_grp='.$gr_id;
    }

}

elseif ($action=='us_edit') {

    $edit_users = Get_Request('us_list', 2);
    if (!$acc_group['id'])
        $error.= '<LI>'.$lang['ADMCAB_ACCGRP_ERR_NOGROUP']."\n";
    elseif (!is_array($edit_users))
        $error.= '<LI>'.$lang['ERR_QUERY']."\n";
    else {
        foreach ($edit_users as $num=>$id)
            $edit_users[$num] = intval($id);

        $sel_users = implode(', ', $edit_users);

        $todo = Get_Request('todo', 2);
        if ($todo == 'set_month')
            $drop_time = $timer->time + 30*24*3600;
        elseif ($todo == 'set_perm')
            $drop_time = 'NULL';
        else
            $drop_time = $timer->time + 30*24*3600;

        if ($todo == 'drop')
            $query = 'DELETE FROM {DBKEY}acc_links WHERE user_id IN ('.$sel_users.') AND group_id = '.$gr_id;
        else
            $query = 'UPDATE {DBKEY}acc_links SET drop_after = '.$drop_time.' WHERE user_id IN ('.$sel_users.') AND group_id = '.$gr_id;

        $QF_DBase->sql_query($query);

        $rresult = sprintf($lang['ADMCAB_ACCGRP_US_EDITED'], $acc_group['name'], count($edit_users) );

        $redir_url = 'index.php?st=mycabinet&amp;job=acc_groups&amp;show_grp='.$gr_id;
    }

}
else
    $error = $lang['ERR_NO_ACTION'];

if (!$error)
    $QF_Session->Cache_Drop_List('forumstat forumsects', true);

Set_Result($error, $rresult, $redir_url);

?>