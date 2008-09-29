<?php

// Simple InUSE Check
if ( !defined('QF_STARTED') )
        die('Hacking attempt');

//
// cabinetadds.inc.php
// This File Adds administrative Menus and Windows to Personal cabinet
//

include 'kernel/core_sql.php';

Load_Language('utils');

LoadVisuals('utils');

$Page_SubTitle = 'QuickFox IPB data conversion tool.';

if ($QF_User->uid!=1 || $QF_Session->Get('is_admin')!=1)
    print Vis_Err_String('Access denied!');

else
{

    Set_Adm_Lock(180);

    $sel_db = Get_Request('sel_db', 2);
    $sel_prefix = Get_Request('sel_prefix', 2);

    if (!$sel_db)
        $sel_db=$QF_DBase->database;

    //$DSQ_dbase = new

    $ptmpl = Array(
        'SQL_version'   => $QF_DBase->srv_info() ,
        'base_selected' => $sel_db ,
    );


    $ftmpl = Array(
        'dbs_options'  => '',
        'pfx_options' => '',
        );

    $dbs = $QF_DBase->sql_query('SHOW DATABASES');
    IF ($dbs) {
        while ($row=$QF_DBase->sql_fetchrow($dbs, false))
            $ftmpl['dbs_options'].='<option value="'.$row[0].'" '.(($row[0]==$sel_db) ? 'SELECTED' : '').'>'.$row[0].'</option>';
        $QF_DBase->sql_freeresult($dbs);
    }

    $tbls = $QF_DBase->sql_dbquery($sel_db, 'SHOW TABLES');
    IF ($tbls) {
        $rows = Array();
        while ($row=$QF_DBase->sql_fetchrow($tbls, false))
            $rows[]=$row[0];
        $QF_DBase->sql_freeresult($tbls);

        $keys = Array();
        foreach ($rows as $tbl) {            if (preg_match('#^(.+?)members_converge$#', $tbl, $match)) {                $key = $match[1];
                $kneedle = Array(
                    $key.'members',
                    $key.'forums',
                    $key.'groups',
                    $key.'topics',
                    $key.'posts',
                   // $key.'attachments'
                    );
                if (count(array_intersect($kneedle, $rows)) == count ($kneedle))
                    $keys[]=$key;
            }
        }

        if (!in_array($sel_prefix, $keys))
            $sel_prefix = $keys[0];

        foreach ($keys as $key)
            $ftmpl['pfx_options'].='<option value="'.$key.'" '.(($key==$sel_prefix) ? 'SELECTED' : '').'>'.$key.'</option>';
    }

    $ptmpl['selsrc_form'] = Visual('IPB_CONV_SELSRC_FORM', $ftmpl);

    if ($sel_prefix) {        include 'includes/forum_core.php';
        $QF_Forum = new qf_forum();

        $mtmpl = Array(
            'database' => $sel_db,
            'prefix'   => $sel_prefix,
            'forums'   => '',
            );

        $query='SELECT * FROM '.$sel_prefix.'forums ORDER BY position, id';
        $result=$QF_DBase->sql_dbquery($sel_db, $query);
        if ($result) {            $read_options = '';
            $write_options = '';
            for($stt=1; $stt <= $QF_User->level; $stt++){
                $read_options.='<option value="'.$stt.'">'.$stt.'</option>';
                $write_options.='<option value="'.$stt.'"'.(($stt==1) ? ' SELECTED' : '').'>'.$stt.'</option>';
            }

            $stree=$QF_Forum->ForumTree;
            foreach ($stree as $ss)
                $mtmpl['par_options'].='<option value="'.$ss['id'].'" >'.$ss['pref'].$ss['name'].' ('.$ss['minrights'].')</option>';

            while ($forum = $QF_DBase->sql_fetchrow($result)) {                $ftmpl = Array(
                    'id'            => $forum['id'],
                    'name'          => $forum['name'],
                    'read_options'  => $read_options,
                    'write_options' => $write_options,
                    'post_options' => $write_options,
                    );

                $mtmpl['forums'].= Visual('IPB_CONV_CONFIG_FORUM_ROW', $ftmpl);
            }
            $QF_DBase->sql_freeresult($result);
        }
        $ptmpl['mainframe'] = Visual('IPB_CONV_CONFIG_FORM', $mtmpl);
    }

    print Visual('IPB_CONV_MAINPAGE', $ptmpl);

}
?>