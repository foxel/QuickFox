<?php

// Simple InUSE Check
if ( !defined('QF_STARTED') )
        die('Hacking attempt');

$error = '';
$login = Get_Request('userlogin', 2, 'ht');
$pass  = md5(Get_Request('userpass', 2));
$useauto = Get_Request('autologin', 2, 'b');

if (isset($_SERVER['HTTP_REFERER'])
     && ($url_redir = trim($_SERVER['HTTP_REFERER']))
     && (strpos($url_redir, $QF_RootUrl) === 0))
{    $url_redir = substr($url_redir, strlen($QF_RootUrl));
    if (!$url_redir)
        $url_redir = 'index.php';
}
else
    $url_redir = 'index.php';

if (strstr($url_redir, 'showresult'))
    $url_redir = 'index.php';


  $result = $QF_DBase->sql_doselect('{DBKEY}users', '*', Array( 'nick' => $login, 'deleted' => 0) );
  if (!empty($result))
      $user = $QF_DBase->sql_fetchrow($result);

  $result = $QF_DBase->sql_doselect('{DBKEY}regs', '*', Array( 'nick' => $login) );
  if (!empty($result))
      $ruser = $QF_DBase->sql_fetchrow($result);

      if (!$login) // No username
      {
        $action = "";
        $error .= "<LI>".$lang['ERR_NO_LOGIN']."\n";
      }
      elseif (!$user) // No username
      {
        $action = "";
        if ($ruser['nick'])
        {          if ($pass==$ruser['pass'])
          {
            if (!$ruser['echecked'])
                $error = sprintf($lang['ERR_LOGIN_NOT_ECHECKED'],$login);
            else
                $error = sprintf($lang['ERR_LOGIN_NOT_ACHECKED'],$login);
          }
          else
              $error .= "<LI>".$lang['ERR_PASS_LOST']."\n";
        }
        else
            $error .= "<LI>".sprintf($lang['ERR_USER_LOST'],$login)."\n";
      }
      elseif (!$user['pass']) // Nopass set
      {
        $action = "";
        $error .= "<LI>".$lang['ERR_PASS_NULL']."\n";
      }
      elseif ($pass!=$user['pass']) // Wrongpass
      {
        $action = "";
        $error .= "<LI>".$lang['ERR_PASS_LOST']."\n";
      }


if (empty($error))
{   $curuser=$user;
   $uid = intval($curuser['id']);
   $QF_Session->Set('QF_user', $login);
   $QF_Session->Set('QF_uid', $uid);
   $QF_Session->Cache_Clear();
   $session_ids = $QF_Session->SID;
   $autokey='';
   if ($useauto)
       $autokey=md5(uniqid($timer->time.$curuser['id']));

   setcookie('QF_Login', $autokey, $timer->time + 86400*30, '/');

   if ($curuser['timezone']!='')
       $QF_Config['tz']=floatval($curuser['timezone']);

   $QF_DBase->sql_doupdate('{DBKEY}users', Array ('lastseen' => $timer->time, 'sessid' => $session_ids, 'autologin' => $autokey), Array('id' => $uid) );
   $QF_DBase->sql_dodelete('{DBKEY}acc_links', 'WHERE user_id = '.$uid.' AND NOT ISNULL(drop_after) AND drop_after < '.$timer->time );

   $rresult=sprintf($lang['AUTHORIZED'],$login,create_date('d M Y H:i', $user['lastseen']));
   $redir=($url_redir) ? $url_redir : 'index.php';

}

unset($autokey);
Set_Result($error, $rresult, $redir);
?>