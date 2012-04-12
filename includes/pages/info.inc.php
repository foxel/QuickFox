<?php

// Simple InUSE Check
if ( !defined('QF_STARTED') )
        die('Hacking attempt');

$infouser=Get_Request('infouser', 1, 'i');
if (($acc_lev = $QF_Config['uinfo_acc_lvl']) && ($QF_User->level < $acc_lev))
{
    print Vis_Err_String($lang['ERR_LOWLEVEL']);
}
elseIf ($infouser)
{

    $result = $QF_DBase->sql_doselect('{DBKEY}users', '*', Array( 'id' => $infouser) );
    if (!empty($result))
        $iuser = $QF_DBase->sql_fetchrow($result);
    if (!empty($iuser) && ((!$iuser['deleted'] && $iuser['approved']) || $QF_User->admin))
    {

        if (empty($iuser['timezone'])) $iuser['timezone']=$QF_Config['def_tz'];
        if ($iuser['id']==1) $iuser['admin']=1;
        if (!$iuser['active'])
        {
            $iuser['modlevel'] = 0;
            $iuser['admin']    = 0;
        }

        $Page_SubTitle = sprintf($lang['INFO_CAPT'],$iuser['nick']);
        $tmpl=Array(
            'unick'      => $iuser['nick'],
            'udescr'     => $iuser['descr'],
            'uabout'     => $QF_Parser->parse_mess($iuser['about']),
            'ucity'      => ($iuser['city']) ? $iuser['city'] : 'n/a',
            'urights'    => Vis_Gen_Rights($iuser['rights'],$lang['OUTCAST']),
            'uregtime'   => create_date('',$iuser['regtime']),
            'utime'      => create_date('',$timer->time,$iuser['timezone']),
            'ulastvisit' => create_date('',$iuser['lastseen']),
            'utimezone'  => (($iuser['timezone']) ? $lang['tz'][floatval($iuser['timezone'])] : False),
            'uavatar'    => Vis_Gen_Avatar($iuser, true),
            'uemail'     => Vis_Print_Email($iuser['email']),
            'adminshow'  => ($QF_User->admin) ? 1 : false,
            'uhomepage'  => ($iuser['homepage']) ? '<a href="'.$iuser['homepage'].'">'.$iuser['homepage'].'</a>' : 'n/a',
            'uicq'       => ($iuser['icq']) ? $iuser['icq'] : "n/a",
            'ulasturl'   => $iuser['lasturl'] );

        switch($iuser['sex'])
        {
            case 'M':
                $tmpl['usex'] = $lang['INFO_USEX_M'];
                break;
            case 'F':
                $tmpl['usex'] = $lang['INFO_USEX_F'];
                break;
        }

        if ($QF_User->admin) {
             $tmpl['ulastip'] = $iuser['lastip'];
             if ($iuser['lastip']) $tmpl['ulastdns'] = gethostbyaddr($iuser['lastip']);
        }

        if (!$iuser['approved']) $tmpl['urights'].=' ['.$lang['INFO_USER_NOT_APPROVED'].']';
        elseif (!$iuser['active']) $tmpl['urights'].=' ['.$lang['INFO_UINACTIVE'].']';
        elseif ($iuser['admin']) $tmpl['urights'].=' + '.$lang['ADMINISTRATOR'];
        elseif ($iuser['modlevel']) $tmpl['urights'].=' + '.sprintf($lang['INFO_MODLEVEL'],$iuser['modlevel']);

        $query='SELECT us.*, t.name as ltheme_name, t.minrights as ltheme_level, s.acc_group AS ltheme_acc, al.user_id AS ltheme_accu
            FROM {DBKEY}userstats us
            LEFT JOIN {DBKEY}topics t ON (us.lasttheme = t.id)
            LEFT JOIN {DBKEY}sections s ON (s.id = t.parent)
	        LEFT JOIN {DBKEY}acc_links al ON (s.acc_group = al.group_id AND al.user_id = '.$QF_User->uid.')
            WHERE us.user_id='.$iuser['id'];

        if ( $result=$QF_DBase->sql_query($query) )
            $iuserstats=$QF_DBase->sql_fetchrow($result);

        if (is_array($iuserstats)) {
            if ($QF_User->admin)
                $can_show_lasttheme = true;
            else
                $can_show_lasttheme = $iuserstats['ltheme_level'] <= $QF_User->level && (!$iuserstats['ltheme_acc'] || $iuserstats['ltheme_accu']);

            $tmpl['userstats'] = 1;
            $tmpl['usposts'] = intval($iuserstats['posts']);
            $tmpl['usthemes'] = intval($iuserstats['themes']);
            $tmpl['usfiles'] = intval($iuserstats['files']);
            $tmpl['uslposttime'] = ($iuserstats['lastposttime']) ? create_date('',$iuserstats['lastposttime']) : 'n/a';
            $tmpl['usltheme'] = ($iuserstats['lasttheme'] && $can_show_lasttheme) ? '<a href="index.php?st=branch&amp;branch='.$iuserstats['lasttheme'].'&amp;postshow='.$iuserstats['lastpost'].'#'.$iuserstats['lastpost'].'">'.$iuserstats['ltheme_name'].'</a>' : 'n/a';
        }

        if($iuser['rights']<$QF_User->wlevel && $QF_User->wlevel > 1){

        	$fields['script']['value']='chrights';
        	$fields['script']['type']='hidden';

        	$fields['chuserid']['value']=$infouser;
	        $fields['chuserid']['type']='hidden';
        	$fields['iuserlogin']['value']=$QF_User->uname;
        	$fields['iuserlogin']['type']='hidden';

        	$fields['nrights']['type']='select';
        	$fields['nrights']['capt']=$lang['INFO_SET_RIGHTS'];
        	$fields['nrights']['descr']=$lang['INFO_RIGHTS_MORE'];
        	$fields['nrights']['subs'][0]['value']=0;
        	$fields['nrights']['subs'][0]['name']=$lang['OUTCAST'];
        	for($stt=1;$stt<$QF_User->level;$stt++){
            	$fields['nrights']['subs'][$stt]['value']=$stt;
            	$fields['nrights']['subs'][$stt]['name']=$stt;
            	if($iuser['rights']==$stt) $fields['nrights']['subs'][$stt]['selected']=1;
        	}

        	if ($QF_User->admin) {
	        	$fields['nmodlevel']['type']='select';
	        	$fields['nmodlevel']['capt']=$lang['INFO_SET_MODLEVEL'];
	        	$fields['nmodlevel']['descr']=$lang['INFO_MODLEVEL_MORE'];
	        	$fields['nmodlevel']['subs'][0]['value']=0;
	        	$fields['nmodlevel']['subs'][0]['name']=$lang['NOT_MODERATOR'];
	        	for($stt=1;$stt<$QF_User->level;$stt++){
	            	$fields['nmodlevel']['subs'][$stt]['value']=$stt;
	            	$fields['nmodlevel']['subs'][$stt]['name']=$stt;
	            	if($iuser['modlevel']==$stt) $fields['nmodlevel']['subs'][$stt]['selected']=1;
        		}

                if (!$iuser['approved']) {
                    $fields['approved'] = Array(
                        'value'   => '1',
                        'type'    => 'checkbox',
                        'checked' => '',
                        'capt'    => $lang['INFO_SET_APPROVED'],
                        'descr'   => $lang['INFO_APPROVED_MORE']);
                }

                $fields['deluser']=Array(
                    'value'   => '1',
                    'type'    => 'checkbox',
                    'checked' => ($iuser['deleted']) ? '1' : '',
                    'capt'    => $lang['INFO_SET_DELUSER'],
                    'descr'   => $lang['INFO_DELUSER_MORE'] );

                $fields['inactive']=Array(
                    'value'   => '1',
                    'type'    => 'checkbox',
                    'checked' => ($iuser['active']) ? '' : '1',
                    'capt'    => $lang['INFO_SET_INACTIVE'],
                    'descr'   => $lang['INFO_INACTIVE_MORE'] );
        	}

	        $fields['motiv']['type']='textarea';
        	$fields['motiv']['capt']=$lang['INFO_SET_MOTIVATION'];

        	$fields['submit']['value']=$lang['BTN_ACCEPT'];
        	$fields['submit']['type']='submit';
        	$fields['submit']['descr']=$lang['INFO_SET_DO'];

        	$tmpl['chform'] = Vis_Draw_Panel(Vis_Draw_Form('', 'ChrightForm', 'index.php', sprintf($lang['INFO_CAN_CHANGE'], $QF_User->uname),$fields, '100%', 'noborder'),$lang['INFO_CHANGE'], '100%', true);

        }

        if($QF_User->uid!=$infouser) {
            $pmfrm= Array(
                'time'     => create_date("", $timer->time),
                'user'     => $QF_User->uname,
                'u_rights' => Vis_Gen_Rights($QF_User->level,' '),
                'u_descr'  => $QF_User->cuser['descr'],
                'avatar'   => Vis_Gen_Avatar($QF_User->cuser) );
            if (!$QF_User->uid) $pmfrm['u_descr']= $lang['GUEST'];

            $pmfrm['formstart']='<form name="newpm" action="index.php" method="post" enctype="multipart/form-data">';
            $pmfrm['formend']='</form>';

            $form=Array(
                'recip'    => $iuser['nick'],
                'fixrecip' => 'true',
                'formname' => 'newpm',
                'user'     => $QF_User->uname );

            if ($QF_User->uid)
                $form['fixuser']='true';

            $pmfrm['formbody']=Visual('PM_NEW_FORM', $form);
            $pmfrm['class']='noborder';


            $tmpl['pmform']=Vis_Draw_panel(Visual('POST_BODY', $pmfrm), $lang['WRITE_NEW_PM'], '100%', True);
        }

        $toprint = Visual('USERINFO_TABLE', $tmpl);
        $userinfo = Vis_Draw_Table('<b>'.$iuser['nick'].'</b>',$toprint,false,450);

    }
    else
    {
        $userinfo = Visual('ERR_STRING', Array('message'=>$lang['INFO_NO_INFO']));
        $Page_SubTitle = $lang['INFO_NOT_FOUND'];
    }

    $tmpl=Array(
        'UINFO' => $userinfo );
    print Visual('USERINFO_MAIN', $tmpl);
}
else
{
    //loading all the users
    $ulist->load('', True);
    $ulist->id_order();
    $lusers = $ulist->users;

    $stats = Array();
    $result=$QF_DBase->sql_doselect('{DBKEY}userstats');
    if (!empty($result))
        while ( $ustt = $QF_DBase->sql_fetchrow($result))
        {
            if (isset($lusers[$ustt['user_id']]))
                $lusers[$ustt['user_id']] += Array('s_posts' => $ustt['posts'], 's_themes' => $ustt['themes'], 's_files' => $ustt['files']);
        }
    $QF_DBase->sql_freeresult($result);

    $Page_SubTitle = $lang['INFO_ULIST'];

    $toprint='';
    if ($sortby = Get_Request('sortby', 1, 'v'))
    {
        switch ($sortby)
        {
            case 'nick':
                $lusers = qf_array_2dresort($lusers, 'nick');
                break;
            case 'level':
                $lusers = qf_array_2dresort($lusers, 'rights', true);
                $lusers = qf_array_2dresort($lusers, 'admin', true);
                $lusers = qf_array_2dresort($lusers, 'approved', true);
                break;
            case 'time':
                $lusers = qf_array_2dresort($lusers, 'lastseen', true);
                break;
            case 'posts':
                $lusers = qf_array_2dresort($lusers, 's_posts', true);
                break;
            case 'themes':
                $lusers = qf_array_2dresort($lusers, 's_themes', true);
                break;
            case 'files':
                $lusers = qf_array_2dresort($lusers, 's_files', true);
                break;
            default:
                // do nothing (ID sorting)
        }
    }

    Foreach($lusers as $iuser) {
        if (!$iuser['approved'] && !$QF_User->admin) {
            continue;
        }

        $row = Array(
            'u_id'    => $iuser['id'],
            'u_nick'  => '<a href="index.php?st=info&amp;infouser='.$iuser['id'].'">'.$iuser['nick'].'</a>',
            'u_avatar'=> Vis_Gen_Avatar($iuser),
            'u_level' => Vis_Gen_Rights($iuser['rights'],$lang['OUTCAST']).((!$iuser['approved']) ? " [NEW]" :((!$iuser['active'])?" [R/O]":(($iuser['admin'])?" +A":(($iuser['modlevel'])?" +M".$iuser['modlevel']:'')))),
            'u_lseen' => create_date('',$iuser['lastseen']),
            'u_posts' => intVal($iuser['s_posts']),
            'u_themes'=> intVal($iuser['s_themes']),
            'u_files' => intVal($iuser['s_files']),
            'u_email' => Vis_Print_Email($iuser['email']),
            'u_url'   => ($iuser['homepage']) ? '<a href="'.$iuser['homepage'].'">'.$iuser['homepage'].'</a>' : 'n/a' );

        $toprint.=Visual('USERINFO_USER_ROW', $row);
        $LastCount++;
    }

    $tmpl=Array(
        'content' => $toprint );
    $toprint = Visual('USERINFO_USERS_TABLE', $tmpl);
    print Visual('USERINFO_USERS', Array( 'ulist' => $toprint) );
}

?>