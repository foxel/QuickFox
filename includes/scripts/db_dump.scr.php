<?php

// Simple InUSE Check
if ( !defined('QF_STARTED') )
        die('Hacking attempt');


include 'kernel/core_sql.php';

Load_Language('admin');

$error = "";
$action = Get_Request('action', 2);
if (!empty($action))
{

    if ($QF_Session->Get('is_admin')!=1 || !$QF_User->admin) // если нет прав
        $error = $error."<LI>".$lang['ERR_ADMIN_ONLY']."\n";

    if (empty($error)) // если ошибок нет, обрабатываем сообщение
    {

        $dumper = new mysql_dumper();
        $sets=Array(
            'all_tables' => Get_Request('alltables', 2, 'b') ,
            'nostruct'   => Get_Request('nostruct', 2, 'b') ,
            'nocontent'  => Get_Request('nocontent', 2, 'b') ,
            );

        $dumper->dump_tables('QF_DBDump.sql', true, $sets);

        $filename = $dumper->filename;
        if (file_exists($filename)) {
            $file_info=pathinfo($filename);
            $file=substr('QuickFox-'.$timer->time.'.'.$file_info['extension'],0,28).'.qff';
            rename($filename, 'files/'.$file);

            $fid=md5($file);
            $caption='QuickFox DataBase dump file';

            if ($result = $QF_DBase->sql_doselect('{DBKEY}files', 'file', Array( 'filename' => $filename) ))
            {                while (list($del_dump) = $QF_DBase->sql_fetchrow($result, false))
                    unlink('files/'.$del_dump);
                $QF_DBase->sql_freeresult($result);
                $QF_DBase->sql_dodelete('{DBKEY}files', Array( 'filename' => $filename) );
            }

            $ins_data = Array(
                'id'       => $fid,
                'folder'   => 0,
                'user'     => 'QuickFox',
                'time'     => $timer->time,
                'file'     => $file,
                'filename' => $filename,
                'caption'  => $caption,
                'descr'    => $descr,
                'rights'   => $QF_User->level,
                );
            $QF_DBase->sql_doinsert('{DBKEY}files', $ins_data, true);

            $redir_url="index.php?st=getfile&amp;file=".$fid;
            $rresult=sprintf($lang['ADMCAB_DBDUMP_READY'],'<a href="'.$redir_url.'">','</a>');
        }
        else
            $error = 'File Error';
    }
}
else
    $error = $lang['ERR_NO_ACTION'];

Set_Result($error, $rresult, $redir_url);


?>