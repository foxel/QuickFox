<?

// Simple InUSE Check
if ( !defined('QF_STARTED') )
        die('Hacking attempt');

Glob_Request('job');

//
// Defining visual variables
//
$cabinet_menu_tower='';
$cabinet_adm_menu_tower='';
$cabinet_main_window='';
$cabinet_menu_hide=false;
$cabinet_caption='';

//
// Initialization
//
if ($QF_User->uid) {
$ucabuser=$QF_User->cuser;
if ($QF_User->admin) {
        Glob_Request('cabuser');
        $result = $QF_DBase->sql_doselect('{DBKEY}users', '*', Array( 'id' => $cabuser, 'deleted' => 0) );
        if ($result) $tuser = $QF_DBase->sql_fetchrow($result);
        if ($tuser['id']) $ucabuser=$tuser;
        }
if (empty($ucabuser['timezone'])) $ucabuser['timezone']=$QF_Config['def_tz'];

$Page_SubTitle = $lang['UCAB_CAPT'].' '.$ucabuser['nick'];


//
// Loading Administrative
//

if ($QF_User->admin) {
    include "includes/admin/cabinetadds.inc.php";
}

//
// Drawing Menu
//

// Profile caption
$tmpl=Array(
    'link'   => 'index.php?st=mycabinet'.(($cabuser) ? '&amp;cabuser='.$cabuser : ''),
    'nick'   => $ucabuser['nick'],
    'avatar' => Vis_Gen_Avatar($ucabuser, true),
    'rights' => Vis_Gen_Rights($ucabuser['rights'],$lang['OUTCAST']).(($ucabuser['admin'])?" +A":(($ucabuser['modlevel'])?" +M".$ucabuser['modlevel']:'')),
    'descr'  => $ucabuser['descr'],
    'email'  => $ucabuser['email']);

$content=Visual('MYCABINET_OVERVIEW', $tmpl);

$cabinet_menu_tower.= Vis_Draw_panel($content,$lang['UCAB_CAPT'],'200');

// Profile menu
$mrows='';
$mrows.=Visual('MYCABINET_MROW', Array( 'capt' => $lang['UCAB_MPROFILE_CSETS'], 'link' => '?st=mycabinet'.(($cabuser)?'&amp;cabuser='.$cabuser:'').'&amp;job=profile', 'selected' => (($job=='profile') ? 'True' : '') ));
$mrows.=Visual('MYCABINET_MROW', Array( 'capt' => $lang['UCAB_MPROFILE_CAVATAR'], 'link' => '?st=mycabinet'.(($cabuser)?'&amp;cabuser='.$cabuser:'').'&amp;job=avatar', 'selected' => (($job=='avatar') ? 'True' : '') ));
$mrows.=Visual('MYCABINET_MROW', Array( 'capt' => $lang['UCAB_MPROFILE_CPASS'], 'link' => '?st=mycabinet'.(($cabuser)?'&amp;cabuser='.$cabuser:'').'&amp;job=pass', 'selected' => (($job=='pass') ? 'True' : '') ));
$mrows.=Visual('MYCABINET_MROW', Array( 'capt' => $lang['UCAB_MPROFILE_CEMAIL'], 'link' => '?st=mycabinet'.(($cabuser)?'&amp;cabuser='.$cabuser:'').'&amp;job=email', 'selected' => (($job=='email') ? 'True' : '') ));
$content=Visual('MYCABINET_MTABLE', Array('rows' => $mrows) );

$cabinet_menu_tower.= Vis_Draw_panel($content,$lang['UCAB_MPROFILE_CAPT'],'200',$cabinet_menu_hide);

// PMs menu
$mrows='';
$mrows.=Visual('MYCABINET_MROW', Array( 'capt' => $lang['UCAB_MPMS_OPEN'], 'link' => '?st=mycabinet'.(($cabuser)?'&amp;cabuser='.$cabuser:'').'&amp;job=pms', 'selected' => (($job=='pms') ? 'True' : '') ));
$mrows.=Visual('MYCABINET_MROW', Array( 'capt' => $lang['UCAB_MPMS_LISTS'], 'link' => '?st=mycabinet'.(($cabuser)?'&amp;cabuser='.$cabuser:'').'&amp;job=plists', 'selected' => (($job=='plists') ? 'True' : '') ));
$content=Visual('MYCABINET_MTABLE', Array('rows' => $mrows) );

$cabinet_menu_tower.= Vis_Draw_panel($content,$lang['UCAB_MPMS_CAPT'],'200',$cabinet_menu_hide);


if ($job==''){
    $cabinet_main_window = '';
}

//
// Drawing Main Window
//

elseif ($job=='profile') {
    $cabinet_caption=$lang['UCAB_PROFILE_CAPT'];

    $fields['script']['value']='mycabinet';
    $fields['script']['type']='hidden';

    $fields['action']['value']='profile';
    $fields['action']['type']='hidden';

    $fields['qfuser']['value']=$ucabuser['nick'];
    $fields['qfuser']['type']='hidden';

    $fields['prfinfo']['type']='separator';
    $fields['prfinfo']['capt']=$lang['UCAB_PROFILE_PRFINFO'];

    $fields['sex']['type']='select';
    $fields['sex']['capt']=$lang['UCAB_PROFILE_SEX'];
    $fields['sex']['descr']=$lang['UCAB_PROFILE_SEX_MORE'];
        $fields['sex']['subs'][0] = Array('name' => '---', 'value' => ' ');
        $fields['sex']['subs'][1] = Array('name' => $lang['INFO_USEX_M'], 'value' => 'M', 'selected' => ($ucabuser['sex']=='M'));
        $fields['sex']['subs'][2] = Array('name' => $lang['INFO_USEX_F'], 'value' => 'F', 'selected' => ($ucabuser['sex']=='F'));

    $fields['location']['type']='text';
    $fields['location']['params']='maxlength=128';
    $fields['location']['capt']=$lang['UCAB_PROFILE_CITY'];
    $fields['location']['descr']=$lang['UCAB_PROFILE_CITY_MORE'];
    $fields['location']['value']=$ucabuser['city'];

    $fields['descr']['type']='text';
    $fields['descr']['params']='maxlength=128';
    $fields['descr']['capt']=$lang['UCAB_PROFILE_DESCR'];
    $fields['descr']['descr']=$lang['UCAB_PROFILE_DESCR_MORE'];
    $fields['descr']['value']=$ucabuser['descr'];

    $fields['icq']['type']='text';
    $fields['icq']['params']='maxlength=36';
    $fields['icq']['capt']=$lang['UCAB_PROFILE_ICQ'];
    $fields['icq']['descr']=$lang['UCAB_PROFILE_ICQ_MORE'];
    $fields['icq']['value']=$ucabuser['icq'];

    $fields['homepage']['type']='text';
    $fields['homepage']['params']='maxlength=50';
    $fields['homepage']['capt']=$lang['UCAB_PROFILE_HOMEPAGE'];
    $fields['homepage']['descr']=$lang['UCAB_PROFILE_HOMEPAGE_MORE'];
    $fields['homepage']['value']=$ucabuser['homepage'];

    $fields['about']['type']='textarea';
    $fields['about']['capt']=$lang['UCAB_PROFILE_ABOUT'];
    $fields['about']['value']=$ucabuser['about'];

    $fields['prfconf']['type']='separator';
    $fields['prfconf']['capt']=$lang['UCAB_PROFILE_PRFCONF'];

    $fields['greet']['type']='text';
    $fields['greet']['params']='maxlength=30';
    $fields['greet']['capt']=$lang['UCAB_PROFILE_GREET'];
    $fields['greet']['descr']=$lang['UCAB_PROFILE_GREET_MORE'];
    $fields['greet']['value']=$ucabuser['greet'];

    $fields['timezone']['type']='select';
    $fields['timezone']['capt']=$lang['UCAB_PROFILE_TZ'];
    $fields['timezone']['descr']=$lang['UCAB_PROFILE_TZ_MORE'];
    if ($lang['tz']) {
    foreach($lang['tz'] as $ttz=>$ttzs){
        $fields['timezone']['subs'][$ttz]['name']=$ttzs;
        $fields['timezone']['subs'][$ttz]['value']=$ttz;
        $fields['timezone']['subs'][$ttz]['selected']=($ucabuser['timezone']==$ttz);
    }}
    else {
    for($ttz=-12;$ttz<=13;$ttz++){
        $fields['timezone']['subs'][$ttz]['name']=$ttz;
        $fields['timezone']['subs'][$ttz]['value']=$ttz;
        $fields['timezone']['subs'][$ttz]['selected']=($ucabuser['timezone']==$ttz);
    }}

    $fields['ustyle']['type']='select';
    $fields['ustyle']['capt']=$lang['UCAB_PROFILE_STYLE'];
    $fields['ustyle']['descr']=$lang['UCAB_PROFILE_STYLE_MORE'];
    $fields['ustyle']['subs']['-def-']['name']=$lang['DEFAULT'];
    $fields['ustyle']['subs']['-def-']['value']=' ';
    foreach($styles as $ttst){
        $fields['ustyle']['subs'][$ttst['name']]['name']=$ttst['name'];
        $fields['ustyle']['subs'][$ttst['name']]['value']=$ttst['id'];
        $fields['ustyle']['subs'][$ttst['name']]['selected']=($ucabuser['style']==$ttst['id']);
    }

    $fields['noemailpm']['value']='ON';
    $fields['noemailpm']['type']='checkbox';
    $fields['noemailpm']['capt']=$lang['UCAB_PROFILE_NOEMAILPM'];
    $fields['noemailpm']['descr']=$lang['UCAB_PROFILE_NOEMAILPM_MORE'];
    $fields['noemailpm']['checked']=($ucabuser['noemailpm']);

    $fields['emailsubs']['value']='ON';
    $fields['emailsubs']['type']='checkbox';
    $fields['emailsubs']['capt']=$lang['UCAB_PROFILE_EMAILSUBS'];
    $fields['emailsubs']['descr']=$lang['UCAB_PROFILE_EMAILSUBS_MORE'];
    $fields['emailsubs']['checked']=($ucabuser['subscrtype']) ? 1 : 0;

    $fields['prfall']['type']='separator';
    $fields['prfall']['capt']=' --- ';

    $fields['submit']['value']=$lang['BTN_ACCEPT'];
    $fields['submit']['type']='submit';
    $fields['submit']['descr']=$lang['UCAB_PROFILE_ACCEPT'];

    $cabinet_main_window.= Vis_Draw_Form($lang['UCAB_PROFILE_CAPT'],'profileform','index.php',$lang['UCAB_PROFILE_REQUEST'],$fields);

    }

//
// Avatar Sets
//

elseif ($job=='avatar') {
    $cabinet_caption=$lang['UCAB_AVATAR_CAPT'];
    $content='';
    $tmpl=Array(
        'unick' => $ucabuser['nick'] );

    if ($ucabuser['avatar'])
        $tmpl['uavatar']=Vis_Gen_Avatar($ucabuser, true);
    else
        $tmpl['novatar']='true';

    $content=Visual('MYCABINET_CAVATAR', $tmpl);

    $cabinet_main_window.= Vis_Draw_Table($lang['UCAB_AVATAR_CAPT'],$content);

}

//
// E-Mail Sets
//

elseif ($job=='email') {
    $cabinet_caption=$lang['UCAB_EMAIL_CAPT'];
    $tmpl=Array(
        'unick'  => $ucabuser['nick'],
        'uemail' => $ucabuser['email'],
        'mail_mask' => EMAIL_MASK );

    $content = Visual('MYCABINET_CEMAIL_NEW', $tmpl);

    $cabinet_main_window.= Vis_Draw_Table($lang['UCAB_EMAIL_CAPT'],$content);

    $tmpl=Array(
        'unick'  => $ucabuser['nick'],
        'uacode' => Get_Request('acode', 1, 'h', 32 ),
        );

    $content = Visual('MYCABINET_CEMAIL_ACT', $tmpl);

    $cabinet_main_window.= Vis_Draw_Table($lang['UCAB_ACODE_CAPT'],$content);

    }

//
// Password Sets
//

elseif ($job=='pass') {
    $cabinet_caption=$lang['UCAB_PASS_CAPT'];

    $cabinet_main_window.= '<script language="JavaScript" type="text/javascript">
    errnopass = "'.$lang['ERR_NO_PASS'].'";
    errpasseq = "'.$lang['ERR_DIF_PASS'].'";

    function checkForm(form) {
            formErrors = "";

            if (form.nuserpass1.value.length < 3) {
                    formErrors += errnopass + "\n";
            }
            if (form.nuserpass2.value != form.nuserpass1.value) {
                    formErrors += errpasseq + "\n";
            }

            if (formErrors) {
                    alert(formErrors);
                    return false;
            } else {
                    formObj.submit.disabled = true;
                    return true;
            }
    }
    </script>';

    $fields['script']['value']='mycabinet';
    $fields['script']['type']='hidden';

    $fields['action']['value']='chpass';
    $fields['action']['type']='hidden';

    $fields['qfuser']['value']=$ucabuser['nick'];
    $fields['qfuser']['type']='hidden';

    $fields['auserpass']['type']='password';
    $fields['auserpass']['params']='maxlength=32';
    $fields['auserpass']['capt']=$lang['UCAB_OLDPASS'];
    $fields['auserpass']['descr']=$lang['UCAB_OLDPASS_MORE'];

    $fields['nuserpass']['type']='doubledpass';
    $fields['nuserpass']['params']='maxlength=32';
    $fields['nuserpass']['capt']=$lang['UCAB_NEWPASS'];
    $fields['nuserpass']['capt2']=$lang['REG_PASS_SHORT_R'];
    $fields['nuserpass']['descr']=$lang['UCAB_NEWPASS_MORE'].'<br />
    <b>'.sprintf($lang['MAX_SYMVOLS'],32).'</b>';

    $fields['submit']['value']=$lang['BTN_ACCEPT'];
    $fields['submit']['type']='submit';
    $fields['submit']['descr']=$lang['ACT_ACCEPT_MORE'];

    $cabinet_main_window.= Vis_Draw_Form($lang['UCAB_PASS_CAPT'],'chpassform','index.php',$lang['UCAB_PASS_REQUEST'],$fields,'','onsubmit="return checkForm(this)"');

    }

//
// PMS Sets
//

elseif ($job=='pms') {

    $cabinet_caption=$lang['UCAB_PMS_INFO'];

    $query=', {DBKEY}pms p WHERE (u.id=p.recipient_id OR u.id=p.author_id)';
    $ulist->load($query);
    $content='';
    Glob_Request('pm delpm');
    if ($delpm) {
           $QF_DBase->sql_doupdate('{DBKEY}pms', Array( 'deleted' => 1), Array( 'id' => $delpm, 'recipient_id' => $ucabuser['id']) );
    }
    if ($pm) {
           $query = 'SELECT * FROM {DBKEY}pms WHERE id='.$pm.' AND (author_id='.$ucabuser['id'].' OR recipient_id='.$ucabuser['id'].') AND deleted = 0';
           $result = $QF_DBase->sql_query($query);
           if ($result) $curpm = $QF_DBase->sql_fetchrow($result);
       if (is_array($curpm)) {
            $QF_DBase->sql_doupdate('{DBKEY}pms', Array( 'readed' => 1), Array( 'id' => $pm, 'recipient_id' => $ucabuser['id']) );

            $is_inpm=($curpm['recipient']==$ucabuser['nick']);
            if ($is_inpm) {
                $puser=$ulist->get($curpm['author_id']);
                $ruser=$ucabuser;
            }
            else {
                $puser=$ucabuser;
                $ruser=$ulist->get($curpm['recipient_id']);
            }
            $tmpl=Array(
                'time'     => (($curpm['readed'])?'':$Vis['UNR_FLAG'].' ').create_date("", $curpm['time']),
	            'user'     => ($puser) ? '<a href="index.php?st=info&amp;infouser='.$puser['id'].'">'.$puser['nick'].'</a>' : str_replace('[|]','',$curpm['author']),
	            'avatar'   => Vis_Gen_Avatar($puser),
	            'u_descr'  => ($puser) ?  $puser['descr'] : $lang['GUEST'],
	            'u_rights' => Vis_Gen_Rights($puser['rights'],' ') );

            $tmpl['formbody']=Visual('PM_READ_CONTENT', Array(
                                    'caption' => $curpm['theme'],
                                    'content' => $QF_Parser->parse_mess($curpm['text']) ) );

            if ($is_inpm) {
                $MB['content']='<a href="index.php?st=mycabinet&amp;job=pms&amp;delpm='.$curpm['id'].'"> '.$Vis['BTN_DROP'].' </a>';
                $tmpl['modblock']=Visual('POST_MOD_BLOCK', $MB);
                }

            $show_pm=Visual('MYCABINET_PMS_LINE', Array(
                'pmsno' => $pm,
                'from'  => ($puser) ? '<a href="index.php?st=info&amp;infouser='.$puser['id'].'">'.$puser['nick'].'</a>' : str_replace('[|]','',$curpm['author']),
                'to'    => ($ruser) ? '<a href="index.php?st=info&amp;infouser='.$ruser['id'].'">'.$ruser['nick'].'</a>' : str_replace('[|]','',$curpm['recipient']) )
            );
            $show_pm.=Visual('POST_BODY', $tmpl);
       }
       else $show_pm = Vis_Err_String($lang['UCAB_PMS_NOTFOUND'].' #'.$pm);
    }

    $tmpl=Array(
        'time'      => create_date("", $timer->time),
        'user'      => $ucabuser['nick'],
        'avatar'    => Vis_Gen_Avatar($ucabuser, true),
        'u_descr'   => $ucabuser['descr'],
        'u_rights'  => Vis_Gen_Rights($ucabuser['rights'],' '),
        'formstart' => '<form name="newpm" action="index.php" method="post" enctype="multipart/form-data">',
        'formend'   => '</form>',
    );


    //$tmpl['class']='noborder';

    $form=Array(
        'user'     => $ucabuser['nick'],
        'fixuser'  => 'true',
        'formname' => 'newpm' );

    if ($is_inpm && $puser['id']) {        $form['recip']=$curpm['author'];
        $np_theme = trim($curpm['theme']);
        if (preg_match('#^(?:Re\[(\d+)\]\s*|((?:Re\s)+))(.*)$#is', $np_theme, $np_parts))
        {            $recount = ($np_parts[1])
                        ? $np_parts[1] + 1
                        : (int) (strlen($np_parts[2])/3) + 1;
            $np_theme = 'Re['.($recount).'] '.$np_parts[3];
        }
        else
            $np_theme = 'Re '.$np_theme;
        $form['theme'] = $np_theme;
    }

    $tmpl['formbody']=Visual('PM_NEW_FORM', $form);
    $write_pm = Vis_Draw_Fliper(Visual('POST_BODY', $tmpl), $lang['WRITE_NEW_PM'], '100%', True);

    $result = $QF_DBase->sql_doselect('{DBKEY}pms', '*', Array( 'recipient_id' => $ucabuser['id'], 'deleted' => 0), ' ORDER BY time DESC');

    $hasnewpm=0; // let's reset curuser has new pm

    $pm_rows='';
    while ( $inpm = $QF_DBase->sql_fetchrow($result))
    {
        $puser=$ulist->get($inpm['author_id']);
        $tmpl=Array(
            'caption' => (($inpm['readed'])? '': $Vis['NEW_FLAG'].' ').'<a href="index.php?st=mycabinet&amp;job=pms'.(($cabuser)?'&amp;cabuser='.$cabuser:'').'&amp;pm='.$inpm['id'].'">'.$inpm['theme'].'</a>',
            'inpm'    => 'true',
            'date'    => create_date('', $inpm['time']),
            'user'    => ($puser) ? '<a href="index.php?st=info&amp;infouser='.$puser['id'].'">'.$puser['nick'].'</a>' : $inpm['author']
            );

        $pm_rows.=Visual('PM_LIST_ROW', $tmpl);

        if (!$inpm['readed'])
            $hasnewpm=1;
    }

    $pm_tbl = Visual('PM_LIST_BORDER', Array( 'rows' => $pm_rows ) );

    if ($ucabuser['noemailpm'])
        $pm_tbl.='<a href="?st=mycabinet&amp;job=profile#spprfconf"><i class="genmed red">'.$lang['UCAB_PMS_NOMAIL'].'</i></a>';

    $inpms=Vis_Draw_panel($pm_tbl,$lang['UCAB_PMS_INC'],"100%");

    if ($hasnewpm!=$ucabuser['hasnewpm']) {
       $QF_DBase->sql_doupdate('{DBKEY}users', Array( 'hasnewpm' => $hasnewpm), Array( 'id' => $ucabuser['id']) );
       if ($QF_User->uid==$ucabuser['id'])
           $QF_User->cuser['hasnewpm']=$hasnewpm;
    }

    $result = $QF_DBase->sql_doselect('{DBKEY}pms', '*', Array( 'author_id' => $ucabuser['id'], 'deleted' => 0), ' ORDER BY time DESC');

    $pm_rows='';
    while ( $outpm = $QF_DBase->sql_fetchrow($result))
    {
        $puser=$ulist->get($outpm['recipient_id']);
        $tmpl=Array(
            'caption' => (($outpm['readed'])? '': $Vis['UNR_FLAG'].' ').'<a href="index.php?st=mycabinet&amp;job=pms'.(($cabuser)?'&amp;cabuser='.$cabuser:'').'&amp;pm='.$outpm['id'].'">'.$outpm['theme'].'</a>',
            'date'    => create_date('', $outpm['time']),
            'user'    => (($puser) ? '<a href="index.php?st=info&amp;infouser='.$puser['id'].'">'.$puser['nick'].'</a>' : str_replace('[|]','',$outpm['author'])) );

        $pm_rows.=Visual('PM_LIST_ROW', $tmpl);
    }
    $pm_tbl = Visual('PM_LIST_BORDER', Array( 'rows' => $pm_rows ) );

    $oupms=Vis_Draw_panel($pm_tbl,$lang['UCAB_PMS_OUT'],"100%");

    $content = Visual('MYCABINET_PMS_TABLE', Array(
        'inpms'   => $inpms,
        'outpms'  => $oupms,
        'showpm'  => $show_pm,
        'writepm' => $write_pm ) );

    $cabinet_main_window.= Vis_Draw_Table($lang['UCAB_PMS_INFO'],$content);

}



//
// Overview
//
if ($cabinet_main_window==''){

    $cabinet_caption = $lang['UCAB_PROFILE_INFO'];

    $tmpl=Array(
        'unick'      => $ucabuser['nick'],
        'urights'    => Vis_Gen_Rights($ucabuser['rights'],$lang['OUTCAST']),
        'uregtime'   => create_date('',$ucabuser['regtime']),
        'utime'      => create_date('',$timer->time,$ucabuser['timezone']),
        'utimezone'  => (($ucabuser['timezone']) ? $lang['tz'][floatval($ucabuser['timezone'])] : False),
        'uemail'     => $ucabuser['email'],
        'ulastip'    => $ucabuser['lastip'],
        'ulastdns'   => gethostbyaddr($ucabuser['lastip']) );

    if ($ucabuser['admin']) $tmpl['urights'].=' + '.$lang['ADMINISTRATOR'];
    elseif ($ucabuser['modlevel']) $tmpl['urights'].=' + '.sprintf($lang['INFO_MODLEVEL'],$ucabuser['modlevel']);

    $content=Visual('MYCABINET_OVIEW_MAIN', $tmpl);

    $cabinet_main_window.= Vis_Draw_Table($lang['UCAB_PROFILE_INFO'],$content);


    $query='SELECT us.*, t.name as lastthemename FROM {DBKEY}userstats us, {DBKEY}topics t WHERE us.user_id='.$ucabuser['id'].' AND (us.lasttheme=t.id OR us.lasttheme=0)';
    $result=$QF_DBase->sql_query($query);
    if ($result) $ucabuserstats=$QF_DBase->sql_fetchrow($result);
    if (is_array($ucabuserstats)) {
    $tmpl=Array(
        'uposts'     => $ucabuserstats['posts'],
        'uthemes'    => $ucabuserstats['themes'],
        'ufiles'     => $ucabuserstats['files'],
        'ulposttime' => (($ucabuserstats['lastposttime']) ? create_date('',$ucabuserstats['lastposttime']) : 'n/a'),
        'ultheme'    => (($ucabuserstats['lasttheme']) ? '<a href="index.php?st=branch&amp;branch='.$ucabuserstats['lasttheme'].'">'.$ucabuserstats['lastthemename'].'</a>' : 'n/a') );

    $content=Visual('MYCABINET_OVIEW_STATS', $tmpl);
    } else $content=$lang['UCAB_STATS_ERR'];

    $cabinet_main_window.= Vis_Draw_Table($lang['UCAB_STATS_INFO'],$content);


    $tmpl=Array(
        'uinpms' => $lang['UCAB_PMS_ERR'],
        'uoutpms' => $lang['UCAB_PMS_ERR']);

    $query='SELECT COUNT(time) as pmscount, SUM(readed) as pmsreads FROM {DBKEY}pms
        WHERE recipient_id='.$ucabuser['id'].' AND deleted=0';
    $result=$QF_DBase->sql_query($query);
    if ($result) $pmsstats=$QF_DBase->sql_fetchrow($result);

    if (is_array($pmsstats)) {
       $tmpl['uinpms']=$pmsstats['pmscount'];
       if ($pmsstats['pmscount']>$pmsstats['pmsreads'] && $pmsstats['pmscount']) $tmpl['uinunr']=($pmsstats['pmscount']-$pmsstats['pmsreads']);
    }

    $query='SELECT COUNT(time) as pmscount, SUM(readed) as pmsreads FROM {DBKEY}pms
        WHERE author_id='.$ucabuser['id'].' AND deleted=0';
    $result=$QF_DBase->sql_query($query);
    if ($result) $pmsstats=$QF_DBase->sql_fetchrow($result);

    if (is_array($pmsstats)) {
       $tmpl['uoutpms']=$pmsstats['pmscount'];
       if ($pmsstats['pmscount']>$pmsstats['pmsreads'] && $pmsstats['pmscount']) $tmpl['uoutunr']=($pmsstats['pmscount']-$pmsstats['pmsreads']);
    }

    $content=Visual('MYCABINET_OVIEW_PMS', $tmpl);

    $cabinet_main_window.= Vis_Draw_Table($lang['UCAB_PMS_INFO'],$content);

}


//
// Profile Sets
//


$Page_SubTitle=$lang['UCAB_CAPT'].' '.$ucabuser['nick'];
if (!empty($cabinet_caption))
    $Page_SubTitle.=': '.$cabinet_caption;

$cabinet=Array (
'Window' => $cabinet_main_window,
'Caption' => $cabinet_caption,
'Menu' => $cabinet_menu_tower,
'Admin_Menu' => $cabinet_adm_menu_tower );

print Visual('MYCABINET_MAIN', $cabinet);

}
else print Vis_Err_String($lang['UCAB_CAPT_NOGUEST']);
?>