<?php

// Simple InUSE Check
if ( !defined('QF_STARTED') )
        die('Hacking attempt');

$error='';
$login=Get_Request('duserlogin', 2, 'ht');

$action = Get_Request('action', 2);

$result = $QF_DBase->sql_doselect('{DBKEY}users', '*', Array( 'nick' => $login, 'deleted' => 0) );
if (!empty($result))
    $cuser = $QF_DBase->sql_fetchrow($result);

      if (!$login) // No username
          $error .= "<LI>".$lang['ERR_NO_LOGIN']."\n";
      elseif (!$cuser) // No username
          $error .= "<LI>".sprintf($lang['ERR_USER_LOST'],$login)."\n";


if ($action=='get_code')
{

  $spcode = Get_Request('spamcode', 2, 'h');
  if (!$QF_User->uid)
      $QF_Session->CheckSpamCode($spcode);

  $dcode=md5(uniqid($timer->time.$user['nick']));


  if (empty($error)) // если ошибок нет, обрабатываем сообщение
    {


     $QF_DBase->sql_doupdate('{DBKEY}users', Array( 'passdropcode' => $dcode, 'new_acode' => '', 'new_email' => ''), Array ( 'id' => $cuser['id']) );

     $tmpl['nick']=$login;
     $tmpl['dcode']=$dcode;
     $tmpl['aurl']='http://'.$QF_Config["server_name"].$QF_Config['root'].'/index.php?st=register&job=drop_pass&dcode='.$dcode;
     $tmpl['surl']='http://'.$QF_Config["server_name"];
     $tmpl['sname']=$QF_Config['site_name'];

     $email = New mailer;
     $email->email_address($cuser['email']);
     $email->use_template('drop_pass');
     $email->assign_vars($tmpl);
     $email->send();

     $rresult=$lang['DROP_PASS_CODE_SENT'];
     $redir='index.php?st=register&amp;job=drop_pass';
  }

}

elseif ($action=='use_code')
{

  $dcode = Get_Request('drop_code', 2, 'h', 32);
  $npasssrc1 = Get_Request('nuserpass1', 2);
  $npass1 = md5($npasssrc1);
  $npasssrc2 = Get_Request('nuserpass2', 2);
  $npass2 = md5($npasssrc2);

      if (!$npass1 || !$npass2) // Wrongpass
      {
        $action = "";
        $error = $error."<LI>".$lang['ERR_NO_PASS']."\n";
      }
      elseif ($npass1!=$npass2) // Wrongpass
      {
        $action = "";
        $error = $error."<LI>".$lang['ERR_DIF_PASS']."\n";
      }

      if (!$cuser['passdropcode'])
      {
        $action = "";
        $error = $error."<LI>".$lang['ERR_NO_DROPCODE']."\n";
      }
      elseif ($dcode != $cuser['passdropcode'])
      {
        $action = "";
        $error = $error."<LI>".$lang['ERR_DCODE_LOST']."\n";
      }


if (empty($error)) // если ошибок нет, обрабатываем сообщение
  {
     $QF_DBase->sql_doupdate('{DBKEY}users', Array( 'pass' => $npass1, 'new_acode' => '', 'new_email' => ''), Array ( 'id' => $cuser['id']) );

     $tmpl['nick']=$login;
     $tmpl['pass']=$npasssrc1;
     $tmpl['surl']='http://'.$QF_Config["server_name"];
     $tmpl['sname']=$QF_Config['site_name'];

     $email = New mailer;
     $email->email_address($cuser['email']);
     $email->use_template('pass_changed');
     $email->assign_vars($tmpl);
     $email->send();

     $rresult=$lang['UCAB_PASS_CHANGED'];

     $redir='index.php';

  }
  else {  // If there are any error we'll clear email request
     $QF_DBase->sql_doupdate('{DBKEY}users', Array( 'passdropcode' => '', 'new_acode' => '', 'new_email' => ''), Array ( 'id' => $cuser['id']) );
     $error.='<br />'.$lang['ERR_DCODE_CLEARED'];
  }

}

else $rresult=$lang['ERR_NO_ACTION'];

Set_Result($error, $rresult, $redir);

?>