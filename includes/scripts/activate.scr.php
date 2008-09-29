<?php

// Simple InUSE Check
if ( !defined('QF_STARTED') )
        die('Hacking attempt');

$error='';
$action = Get_Request('script', 2);
if (!empty($action))
{
  $nuser = Get_Request('auserlogin', 2, 's', 16);
  $npasssrc = Get_Request('auserpass', 2);
  $npass = md5($npasssrc);
  $nacode = Get_Request('activatecode', 2, 'h', 32);

  $result = $QF_DBase->sql_doselect('{DBKEY}users', '*', Array( 'nick' => $nuser) );
  if (!empty($result))
      $ouser = $QF_DBase->sql_fetchrow($result);

  $result = $QF_DBase->sql_doselect('{DBKEY}regs', '*', Array( 'nick' => $nuser) );
  if (!empty($result))      $ruser = $QF_DBase->sql_fetchrow($result);

//Lets check the data (this is a second step - first step is in javascript)
      if (!$nuser) // No username
      {
        $action = "";
        $error = $error."<LI>".$lang['ERR_NO_LOGIN']."\n";
      }
      if (!$ruser['nick']) // No username
      {
        $action = "";
        $error = $error."<LI>".sprintf($lang['ERR_USER_LOST'],$user)."\n";
      }
      elseif ($ouser['id']) // User nick is used
      {
        $action = "";
        $error = $error."<LI>".$lang['ERR_USED_NICK']."\n";
      }

      if (!$npass) // Wrongpass
      {
        $action = "";
        $error = $error."<LI>".$lang['ERR_NO_PASS']."\n";
      }
      if ($npass!=$ruser['pass']) // Wrongpass
      {
        $action = "";
        $error = $error."<LI>".$lang['ERR_PASS_LOST']."\n";
      }
      if ($nacode!=$ruser['acode']) // Wrongpass
      {
        $action = "";
        $error = $error."<LI>".$lang['ERR_ACODE_LOST']."\n";
      }


// no erreors - let's go
  if (empty($error)) // если ошибок нет, обрабатываем сообщение
  {
    $ins_data = Array(
        'nick'     => $ruser['nick'],
        'pass'     => $ruser['pass'],
        'email'    => $ruser['email'],
        'regtime'  => $timer->time,
        'lastseen' => $timer->time,
        'rights'   => 1,
        'about'    => $ruser['descr'],
        );
    $QF_DBase->sql_doinsert('{DBKEY}users', $ins_data);

    $new_id = intval($QF_DBase->sql_nextid());

    $QF_DBase->sql_doinsert('{DBKEY}userstats', Array( 'user_id' => $new_id ) );

    $QF_DBase->sql_dodelete('{DBKEY}regs', Array('nick' => $ruser['nick']) );


    //if (!@mail($nemail,$QF_Config['site_name'].': '.$Capt_Register,$msg,'From: '.$QF_Config['site_name'].' <'.$QF_Config['site_mail'].'>')) $error.="<LI>".$Err_ErrMail."\n";

    if (!$error) $rresult=$lang['REG_ACTIVE'];

  }
  Set_Result($error, $rresult);

}
?>