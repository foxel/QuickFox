<?php

// Simple InUSE Check
if ( !defined('QF_STARTED') )
        die('Hacking attempt');

$error='';
$action = Get_Request('action', 2);
  $name = Get_Request('qfuser', 2, 'ht', 16); // обрабатываем ник
  $redir = 'index.php?st=mycabinet';

      if (!$QF_User->uid) // нет гостям
      {
        $action = "";
        $error = $error."<LI>".$lang['UCAB_CAPT_NOGUEST']."\n";
      }

      if ($name!=$QF_User->uname && !$QF_User->admin) // имя не то
      {
          $action = "";
          $error = $error."<LI>".$lang['ERR_DIF_NICKS']."\n";
      }

  $result = $QF_DBase->sql_doselect('{DBKEY}users', '*', Array( 'nick' => $name, 'deleted' => 0) );
  if (!empty($result))
      $cuser = $QF_DBase->sql_fetchrow($result);

      if (!$cuser['id'])
      {
        $action = "";
        $error = $error."<LI>".sprintf($lang['ERR_USER_LOST'],$name)."\n";
      }

if ($action=='profile')
{
  $location = Get_Request('location', 2, 'ht', 128);

  $descr = Get_Request('descr', 2, 'ht', 128);
  $sex   = Get_Request('sex', 2, 's', 1);

  $icq = Get_Request('icq', 2, 'ht', 36);

  $homepage = Get_Request('homepage', 2, 'ht', 50);
  $homepage = preg_replace('#^http:/+#i','',$homepage);
  $homepage = ($homepage) ? 'http://'.$homepage : '';

  $about = Get_Request('about', 2, 'ht'); // обрабатываем поле о себе

  $greet = Get_Request('greet', 2, 'ht', 30);

  $ntz = Get_Request('timezone', 2, 'f');
  $nstyle = Get_Request('ustyle', 2, 's');

  $noemailpm = Get_Request('noemailpm', 2, 'b');
  $emailsubs = Get_Request('emailsubs', 2, 'b');

if (empty($error)) // если ошибок нет, обрабатываем сообщение
  {

  if ($QF_User->uid) {
      $upd_data = Array(
          'sex'       => $sex,
          'city'      => $location,
          'descr'     => $descr,
          'icq'       => $icq,
          'homepage'  => $homepage,
          'about'     => $QF_Parser->prep_mess($about),
          'greet'     => $greet,
          'timezone'  => $ntz,
          'style'     => $nstyle,
          'noemailpm' => $noemailpm,
          'subscrtype'=> ($emailsubs) ? 1 : 0,
          );
      $QF_DBase->sql_doupdate('{DBKEY}users', $upd_data, Array('id' => $cuser['id'] ) );

      $rresult=$lang['UCAB_PROFILE_APPLIED'];
      $QF_Config['def_tz']=$ntz;
      $QF_Config['style']=$nstyle;
  }
  else $error=$lang['QSETS_CANT_APPLY'];

  }
}

elseif ($action=='newmail')
{

  $nemail = Get_Request('newmail', 2, 'ht', 36); // обрабатываем e-mail
  $acode=md5(uniqid($nemail));

      if (!preg_match("/[0-9a-z_]+@[0-9a-z_^\.]+\.[a-z]{2,3}/i", $nemail))
      {
        $action = "";
        $error = $error."<LI>".$lang['ERR_NO_EMAIL']."\n";
      }

if (empty($error)) // если ошибок нет, обрабатываем сообщение
  {


     $QF_DBase->sql_doupdate('{DBKEY}users', Array('new_email' => $nemail, 'new_acode' => $acode), Array('id' => $cuser['id'] ) );

     $tmpl['nick']=$name;
     $tmpl['acode']=$acode;
     $tmpl['aurl']='http://'.$QF_Config["server_name"].$QF_Config['root'].'/index.php?st=mycabinet&job=email&acode='.$acode;
     $tmpl['surl']='http://'.$QF_Config["server_name"];
     $tmpl['sname']=$QF_Config['site_name'];

     $email = New mailer;
     $email->email_address($nemail);
     $email->use_template('new_email');
     $email->assign_vars($tmpl);
     $email->send();

     $rresult=$lang['UCAB_EMAIL_SAVED'];

  }

}

elseif ($action=='actmail')
{

  $acode = Get_Request('nacode', 2, 'h', 32);
  $pass  = md5(Get_Request('userpass', 2));

      if (!$cuser['new_acode'] || !$cuser['new_email'])
      {
        $action = "";
        $error = $error."<LI>".$lang['ERR_NO_NEWMAIL']."\n";
      }
      elseif ($acode != $cuser['new_acode'])
      {
        $action = "";
        $error = $error."<LI>".$lang['ERR_ACODE_LOST']."\n";
      }
      if ($pass != $cuser['pass'])
      {
        $action = "";
        $error = $error."<LI>".$lang['ERR_PASS_LOST']."\n";
      }


if (empty($error)) // если ошибок нет, обрабатываем сообщение
  {
     $QF_DBase->sql_doupdate('{DBKEY}users', Array('email' => $cuser['new_email'], 'new_email' => '', 'new_acode' => ''), Array('id' => $cuser['id'] ) );

     $tmpl['nick']=$name;
     $tmpl['new_email']=$cuser['new_email'];
     $tmpl['surl']='http://'.$QF_Config["server_name"];
     $tmpl['sname']=$QF_Config['site_name'];

     $email = New mailer;
     $email->email_address($cuser['new_email']);
     $email->cc($cuser['email']);
     $email->use_template('email_changed');
     $email->assign_vars($tmpl);
     $email->send();

     $rresult=$lang['UCAB_EMAIL_CHANGED'];

     if ($name==$QF_User->uname)
         $redir="index.php?st=mycabinet&amp;job=email";

  }
else {  // If there are any error we'll clear email request
     $QF_DBase->sql_doupdate('{DBKEY}users', Array('new_email' => '', 'new_acode' => ''), Array('id' => $cuser['id'] ) );
     $error.='<br />'.$lang['ERR_NEWMAIL_CLEARED'];
  }

}

elseif ($action=='chpass')
{
  $npasssrc1 = Get_Request('nuserpass1', 2);
  $npass1 = md5($npasssrc1);
  $npasssrc2 = Get_Request('nuserpass2', 2);
  $npass2 = md5($npasssrc2);
  $npasssrc = Get_Request('auserpass', 2);
  $npass = md5($npasssrc);

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
      elseif (!$npass) // Wrongpass
      {
        $action = "";
        $error = $error."<LI>".$lang['ERR_NO_PASS']."\n";
      }
      elseif ($npass!=$QF_User->cuser['pass']) // Wrongpass
      {
        $action = "";
        $error = $error."<LI>".$lang['ERR_PASS_LOST']."\n";
      }

if (empty($error)) // если ошибок нет, обрабатываем сообщение
  {
     $QF_DBase->sql_doupdate('{DBKEY}users', Array('pass' => $npass1, 'new_email' => '', 'new_acode' => ''), Array('id' => $cuser['id'] ) );

     $tmpl['nick']=$name;
     $tmpl['pass']=$npasssrc1;
     $tmpl['surl']='http://'.$QF_Config["server_name"];
     $tmpl['sname']=$QF_Config['site_name'];

     $email = New mailer;
     $email->email_address($cuser['email']);
     $email->use_template('pass_changed');
     $email->assign_vars($tmpl);
     $email->send();

     $rresult=$lang['UCAB_PASS_CHANGED'];

  }

}

elseif ($action=='delavatar')
{

if (empty($error)) // если ошибок нет, обрабатываем сообщение
  {
     if ($cuser['avatar'] && file_exists($cuser['avatar']) )
         unlink($cuser['avatar']);

     $QF_DBase->sql_doupdate('{DBKEY}users', Array('avatar' => ''), Array('id' => $cuser['id'] ) );
     $rresult=$lang['UCAB_AVATAR_DELETED'];

  }

}

elseif ($action=='newavatar')
{

    $AVFile = $_FILES["newavatar"]["tmp_name"];
    $size_img = getimagesize($AVFile);

      if (!($_FILES["newavatar"]["size"]<153600))
      {
        $action = "";
        $error = $error."<LI>".$lang['ERR_FILE_TOOBIG']."\n";
      }

      if (!$size_img)
      {
        $action = "";
        $error = $error."<LI>".$lang['ERR_FILE_CORRUPT']."\n";
      }
      elseif(($size_img['mime']!='image/jpeg') && ($size_img['mime']!='image/gif') && ($size_img['mime']!='image/png'))
      {
        $action = "";
        $error = $error."<LI>".$lang['ERR_FILE_WRONGTYPE']."\n";
      }


if (empty($error)) // если ошибок нет, обрабатываем сообщение
  {
    $nw=64;
    $nh=96;
    $fname='avatars/US'.$cuser['id'].'-'.$timer->time;

    include 'kernel/core_images.php';

    $fname = img_resize_to($AVFile, $fname, $nw, $nh, True);
    if ($fname){
        $QF_DBase->sql_doupdate('{DBKEY}users', Array('avatar' => $fname), Array('id' => $cuser['id'] ) );
        $rresult=$lang['UCAB_AVATAR_UPLOADED'];
        if ($cuser['avatar'] && file_exists($cuser['avatar']) )
            unlink($cuser['avatar']);
    }
    else $error=$lang['UCAB_AVATAR_ERROR'];
  }
}

else $rresult=$lang['ERR_NO_ACTION'];

Set_Result($error, $rresult, $redir);

?>