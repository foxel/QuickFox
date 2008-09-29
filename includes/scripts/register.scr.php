<?php

// Simple InUSE Check
if ( !defined('QF_STARTED') )
        die('Hacking attempt');

$error='';
$action = Get_Request('script', 2);
if (!empty($action))
{
  $nuser = Get_Request('nuserlogin', 2, 'ht', 16);
  $npasssrc1 = Get_Request('nuserpass1', 2);
  $npass1 = md5($npasssrc1);
  $npasssrc2 = Get_Request('nuserpass2', 2);
  $npass2 = md5($npasssrc2);
  $ndescr = Get_Request('nuinfo', 2, 'ht');
  $nemail = Get_Request('nemail', 2, 'ht', 36); // обрабатываем e-mail
  $spcode = Get_Request('spamcode', 2, 'h');

  if (!preg_match('/'.EMAIL_MASK.'/i', $nemail))
  {
   $nemail="";
  }
  $acode=md5(rand(1000,1000000));

  $result = $QF_DBase->sql_doselect('{DBKEY}users', '*', Array( 'nick' => $nuser) );
  if (!empty($result))
      $ouser = $QF_DBase->sql_fetchrow($result);

  $result = $QF_DBase->sql_doselect('{DBKEY}regs', '*', Array( 'nick' => $nuser) );
  if (!empty($result))
      $ruser = $QF_DBase->sql_fetchrow($result);

//Lets check the data (this is a second step - first step is in javascript)
      if (strlen($nuser)<3) // No username
      {
        $action = "";
        $error = $error."<LI>".$lang['ERR_NO_LOGIN']."\n";
      }
      elseif (!preg_match('/'.UNAME_MASK.'/i', $nuser))
      {
        $action = "";
        $error = $error."<LI>".$lang['ERR_LOGIN_INCORRECT']."\n";
      }
      elseif ($ouser['id']) // User nick is used
      {
        $action = "";
        $error = $error."<LI>".$lang['ERR_USED_NICK']."\n";
      }
      elseif ($ruser['nick']) // User nick is used
      {
        if ($ruser['pass']==$npass1) {            $QF_DBase->sql_dodelete('{DBKEY}regs', Array('nick' => $nuser) );
        }
        else {            $action = "";
            $error = $error."<LI>".$lang['ERR_USED_NICK']."\n";
        }
      }
      if (preg_match("#Quick\W*?Fox#i", $nuser))
      {
        $action = "";
        $error = $error."<LI>".$lang['ERR_SYSTEM_NICK']."\n";
      }

      if (!$nemail) // Wrong e-mail
      {
        $action = "";
        $error = $error."<LI>".$lang['ERR_NO_EMAIL']."\n";
      }
      if (!$npass1 || !$npass2) // Wrongpass
      {
        $action = "";
        $error = $error."<LI>".$lang['ERR_NO_PASS']."\n";
      }
      if ($npass1!=$npass2) // Wrongpass
      {
        $action = "";
        $error = $error."<LI>".$lang['ERR_DIF_PASS']."\n";
      }

      if (!$QF_User->uid)
          $QF_Session->CheckSpamCode($spcode);

// no erreors - let's go
  if (empty($error)) // если ошибок нет, обрабатываем сообщение
  {
    $ins_data = Array(
        'nick'  => $nuser,
        'time'  => $timer->time,
        'pass'  => $npass1,
        'email' => $nemail,
        'descr' => $ndescr,
        'acode' => $acode,
        );
    $QF_DBase->sql_doinsert('{DBKEY}regs', $ins_data);

    $tmpl['nick']=$nuser;
    $tmpl['pass']=$npasssrc1;
    $tmpl['acode']=$acode;
    $tmpl['aurl']=GetFullUrl('index.php?st=register&job=activate&acode='.$acode);
    $tmpl['surl']='http://'.$QF_Config["server_name"];
    $tmpl['sname']=$QF_Config['site_name'];

    $email = New mailer;
    $email->email_address($nemail);
    $email->use_template('activate');
    $email->assign_vars($tmpl);
    $email->send();

    //if (!@mail($nemail,$QF_Config['site_name'].': '.$Capt_Register,$msg,'From: '.$QF_Config['site_name'].' <'.$QF_Config['site_mail'].'>')) $error.="<LI>".$Err_ErrMail."\n";

    if (!$error) {$rresult=$lang['REG_DONE'];$redir_url="index.php?st=register&amp;job=activate";};

  }
  Set_Result($error, $rresult, $redir_url);

}

?>