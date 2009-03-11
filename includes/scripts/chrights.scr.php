<?php

// Simple InUSE Check
if ( !defined('QF_STARTED') )
        die('Hacking attempt');

$iuser=Get_Request('iuserlogin', 2, 'ht', 16);
$chuser=Get_Request('chuserid', 2, 'i', 16);
$motiv=Get_Request('motiv', 2, 'ht');
$nrights=Get_Request('nrights', 2, 'i');
$nmodlevel=Get_Request('nmodlevel', 2, 'i');
$ndeluser= Get_Request('deluser', 2, 'b') ? 1 : 0;
$nactive= Get_Request('inactive', 2, 'b') ? 0 : 1;
$error="";

$result = $QF_DBase->sql_doselect('{DBKEY}users', '*', Array( 'id' => $chuser ) );
if ($result)
    $ndata = $QF_DBase->sql_fetchrow($result);

if (is_array($ndata) && $ndata['deleted'] && !$QF_User->admin)
    $ndata = null;

if (!empty($ndata))
{

  if($QF_User->uname==$iuser && $QF_User->wlevel > $ndata['rights'])
  {

    if (!$QF_User->admin)
    {
        $nmodlevel = $ndata['modlevel'];
        $ndeluser = $ndata['deleted'];
        $nactive  = $ndata['active'];
    }

    if ($nmodlevel > $nrights)
        $nmodlevel = $nrights;
    if ($ndeluser)
        $nmodlevel = $nrights = 0;

    $QF_DBase->sql_doupdate('{DBKEY}users', Array( 'rights' => $nrights, 'modlevel' => $nmodlevel, 'deleted' => $ndeluser, 'active' => $nactive), Array( 'id' => $chuser ) );

    if (!$ndeluser || !$ndata['deleted'])
    {        $tmpl['nick']=$ndata['nick'];
        $tmpl['anick']=$iuser;
        $tmpl['nrights']=($nrights>0) ? $nrights : $lang['OUTCAST'];
        $tmpl['nmodlevel']=($nmodlevel>0) ? $nmodlevel : $lang['NOT_MODERATOR'];
        $tmpl['motivation']=$motiv;
        $tmpl['surl']='http://'.$QF_Config["server_name"];
        $tmpl['sname']=$QF_Config['site_name'];

        $email = New mailer;
        $email->email_address($ndata['email']);
        if ($ndeluser)
            $email->use_template('us_deleted');
        else
            $email->use_template('chrights');
        $email->assign_vars($tmpl);
        $email->send();
    }

    $rresult=sprintf($lang['INFO_RIGHTS_CHANGED'],$ndata['nick']);
    $redir_url='index.php?st=info&amp;infouser='.$chuser;

  }
  else $error=$lang['ERR_LOWLEVEL'];
}
else $error=sprintf($lang['ERR_USER_LOST'],$chuser);

  Set_Result($error, $rresult, $redir_url);
?>