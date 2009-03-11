<?php

// Simple InUSE Check
if ( !defined('QF_STARTED') )
        die('Hacking attempt');

$error = "";
$action = Get_Request('action', 2);
if (!empty($action))
{
  $sect = Get_Request('section', 2);
  $sect = ($sect=='new') ? $sect : intVal($sect);
  $minrights = Get_Request('minrights', 2, 'i');
  $postrights = Get_Request('postrights', 2, 'i');
  $acc_group_id = Get_Request('acc_group', 2, 'i');
  $name = Get_Request('name', 2, 'ht', 128);
  $descr = Get_Request('descript', 2, 'ht', 255);
  $descr = $QF_Parser->prep_mess($descr); // обрабатываем сообщение
  $parent = Get_Request('parent', 2, 'i');

  $deleted = Get_Request('delsect', 2, 'b');
  $locked = Get_Request('locksect', 2, 'b');
  $pinned = Get_Request('pinsect', 2, 'b');

  $result = $QF_DBase->sql_doselect('{DBKEY}sections', '*', Array( 'id' => $parent ) );
  if (!empty($result)) {$psection = $QF_DBase->sql_fetchrow($result);};
  if ($minrights<intval($psection['minrights'])) $minrights=intval($psection['minrights']);
  if ($postrights<$minrights) $postrights=$minrights;

  $result = $QF_DBase->sql_doselect('{DBKEY}sections', '*', Array( 'id' => $sect) );
  if (!empty($result)) {$section = $QF_DBase->sql_fetchrow($result);};

  $result = $QF_DBase->sql_doselect('{DBKEY}acc_groups', '*', Array( 'id' => $acc_group_id) );
  if (!empty($result)) {$acc_group = $QF_DBase->sql_fetchrow($result);};

    $name = trim($name);
    $descr = trim($descr);

      if ($QF_Session->Get('is_admin')!=1 || !$QF_User->admin) // если нет прав
      {
        $action = "";
        $error .= "<LI>".$lang['ERR_ADMIN_ONLY']."\n";
      }
      if (empty($name)) // если не введено сообщение
      {
        $action = "";
        $error .= "<LI>".$lang['ERR_NO_CAPT']."\n";
      }
      if ($sect!='new' && !$section)
      {
        $action = "";
        $error .= "<LI>".$lang['ERR_NO_SECT']."\n";
      }
      if (!$psection && $parent)
      {
        $action = "";
        $error .= "<LI>".$lang['ERR_NO_PARENT']."\n";
      }
      if (!$acc_group && $acc_group_id)
      {
        $action = "";
        $error .= "<LI>".$lang['ERR_NO_ACC_GROUP']."\n";
      }
  else {
  }




if (empty($error)) // если ошибок нет, обрабатываем сообщение
  {
    $curtime=$timer->time;
    $descr = $QF_Parser->prep_mess($descr);

    if ($sect=='new') {
        $ins_data = Array(
            'parent'     => $parent,
            'name'       => $name,
            'descr'      => $descr,
            'minrights'  => $minrights,
            'postrights' => $postrights,
            'acc_group'  => $acc_group_id,
            );
        $QF_DBase->sql_doinsert('{DBKEY}sections', $ins_data);
    }
    else {
        $upd_data = Array(
            'parent'     => $parent,
            'name'       => $name,
            'descr'      => $descr,
            'minrights'  => $minrights,
            'postrights' => $postrights,
            'acc_group'  => $acc_group_id,
            'deleted'    => $deleted,
            );
        $QF_DBase->sql_doupdate('{DBKEY}sections', $upd_data, Array('id' => $sect) );
    }

    include 'includes/forum_scr.php';
    $forum = New qf_forum_upd();
    $forum->upd_all_sections();
    $forum->rebuild_forum_rights();

    $redir_url="index.php?st=mycabinet&amp;job=sections#s".$sect['id'];
    $rresult=($sect=='new') ?
    sprintf($lang['FOR_SECT_ADDED'],$name)
    : sprintf($lang['FOR_SECT_MODED'],$name);
  }
  Set_Result($error, $rresult, $redir_url);
  }

?>