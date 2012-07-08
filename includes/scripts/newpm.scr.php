<?php

// Simple InUSE Check
if ( !defined('QF_STARTED') )
        die('Hacking attempt');

  $error = "";
  $action = Get_Request('action', 2);
  if (!empty($action))
  {
  $name = Get_Request('qfuser', 2, 'ht', 16); // обрабатываем имя
  $theme = Get_Request('qftheme', 2, 'ht', 255);
  $recip = Get_Request('qfrec', 2, 'ht', 16); // обрабатываем имя
  $msgo = Get_Request('message', 2, 'ht');
  $msgo = $QF_Parser->prep_mess($msgo); // обрабатываем сообщение
  $spcode = Get_Request('spamcode', 2, 'h');

  $result = $QF_DBase->sql_doselect('{DBKEY}users', '*', Array( 'nick' => $name, 'deleted' => 0) );
  if (!empty($result))
      $cuser = $QF_DBase->sql_fetchrow($result);

  $result = $QF_DBase->sql_doselect('{DBKEY}users', '*', Array( 'nick' => $recip, 'deleted' => 0) );
  if (!empty($result))
      $ruser = $QF_DBase->sql_fetchrow($result);

      if (!$ruser['id'])
      {
        $action = "";
        $error = $error."<LI>".sprintf($lang['ERR_USER_LOST'],$recip)."\n";
      }

      if (!$theme)
      {
        $action = "";
        $error = $error."<LI>".$lang['ERR_NO_CAPT']."\n";
      }

      if (!$msgo)
      {
        $action = "";
        $error = $error."<LI>".$lang['ERR_NO_MESS']."\n";
      }

      if (strlen($name)<3) // если не введено имя
      {
        $action = "";
        $error = $error."<LI>".$lang['ERR_NO_LOGIN']."\n";
      }
      elseif (!preg_match('/'.UNAME_MASK.'/i', $name))
      {
        $action = "";
        $error = $error."<LI>".$lang['ERR_LOGIN_INCORRECT']."\n";
      }

      if ($name!=$QF_User->uname && !$QF_User->admin) // имя не то
      {
        if ($QF_User->uid > 0) {
          $action = "";
          $error = $error."<LI>".$lang['ERR_DIF_NICKS']."\n";
        }
        elseif ($cuser) {
          $action = "";
          $error = $error."<LI>".$lang['ERR_USED_NICK']."\n";
        }
        elseif ($QF_User->guest['gid']) {
           $QF_User->guest['gnick']=$name;
           $QF_DBase->sql_doupdate('{DBKEY}guests', Array('gnick' => $name), Array('gid' => $QF_User->guest['gid']) );
        }
      }

      if (($acc_lev = $QF_Config['uinfo_acc_lvl']) && ($QF_User->level < $acc_lev)) {
          $action = "";
          $error  = $error."<LI>".$lang['ERR_LOWLEVEL']."\n";
      }

      if (!$QF_User->uid)
          $QF_Session->CheckSpamCode($spcode);


if (empty($error)) // если ошибок нет, обрабатываем сообщение
  {
    // Parsing MSG
    $curtime=$timer->time;

    $uid=($QF_User->uid) ? $QF_User->uid : 0;

    $ins_data = Array(
        'time'         => $timer->time,
        'author'       => $name,
        'author_id'    => $uid,
        'recipient'    => $ruser['nick'],
        'recipient_id' => $ruser['id'],
        'theme'        => $theme,
        'text'         => $msgo,
        );
    $QF_DBase->sql_doinsert('{DBKEY}pms', $ins_data);

    $QF_DBase->sql_doupdate('{DBKEY}users', Array('hasnewpm' => 1), Array('id' => $ruser['id'] ) );

    //Уведомления
    if(!$ruser['noemailpm']) {
     $tmpl['anick']=(($uid) ? '' : $lang['GUEST'].' ').$name;
     $tmpl['nick']=$ruser['nick'];
     $tmpl['aurl']=GetFullUrl('index.php?st=mycabinet&job=pms');
     $tmpl['surl']='http://'.$QF_Config['server_name'];
     $tmpl['sname']=$QF_Config['site_name'];

     $email = New mailer;
     $email->email_address($ruser['email']);
     $email->use_template('new_pm');
     $email->assign_vars($tmpl);
     $email->send();
    }

     $rresult=$lang['UCAB_PMS_SENT'];
     $redir = ($QF_User->uid) ? 'index.php?st=mycabinet&amp;job=pms' : 'index.php?st=info&amp;infouser='.$ruser['id'];
  }
  Set_Result($error, $rresult, $redir);
}

?>
