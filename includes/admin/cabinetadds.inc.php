<?php

// Simple InUSE Check
if ( !defined('QF_STARTED') )
        die('Hacking attempt');

//
// cabinetadds.inc.php
// This File Adds administrative Menus and Windows to Personal cabinet
//

if (!$QF_User->admin)
    trigger_error('Hacking Attemt. Administration Script improper usage',E_ERROR);

Load_Language('admin');

LoadVisuals('admin');

$showdels=1;

$cabinet_adm_hide=false;

// if not logged in admin
if ($QF_Session->Get('is_admin') != 1) {    $cabinet_adm_hide=true;
}


//
// Common config
//
elseif ($job=='config') {
    $cabinet_caption = $lang['UCAB_ADMIN_CONFIG'];

    $fields=Array();

    $fields['script']=Array(
        'value' => 'adm_config',
        'type'  => 'hidden' );

    $fields['action']=Array(
        'value' => 'common_config',
        'type'  => 'hidden' );

    $fields['siteinfo']=Array(
        'type' => 'separator',
        'capt' => $lang['CONFIG_COMMON_COMMCONF'] );

    $fields['site_name']=Array(
        'value' => $QF_Config['site_name'],
        'type'  => 'text',
        'capt'  => $lang['CONFIG_COMMON_SITENAME'],
        'descr' => $lang['CONFIG_COMMON_SITENAME_MORE'] );

    $fields['site_mail']=Array(
        'value' => $QF_Config['site_mail'],
        'type'  => 'text',
        'capt'  => $lang['CONFIG_COMMON_SITEMAIL'],
        'descr' => $lang['CONFIG_COMMON_SITEMAIL_MORE'] );

    $fields['site_style']=Array(
        'type' => 'select',
        'capt'  => $lang['CONFIG_COMMON_SITE_STYLE'],
        'descr' => $lang['CONFIG_COMMON_SITE_STYLE_MORE'] );
    $fields['site_style']['subs']['-def-']=Array(
        'name'  => ' --- ',
        'value' => ' ' );
    foreach($styles as $ttst)
        $fields['site_style']['subs'][$ttst['name']]=Array(
            'name'     => $ttst['name'],
            'value'    => $ttst['id'],
            'selected' => ($QF_Config['def_style']==$ttst['id']) );

    $fields['force_css_separate']=Array(
        'value'   => 'ON',
        'type'    => 'checkbox',
        'checked' => ($QF_Config['force_css_separate']) ? '1' : '',
        'capt'    => $lang['CONFIG_COMMON_CSSSEP'],
        'descr'   => $lang['CONFIG_COMMON_CSSSEP_MORE'] );


    $fields['site_gzip']=Array(
        'value'   => 'ON',
        'type'    => 'checkbox',
        'checked' => ($QF_Config['GZIP']) ? '1' : '',
        'capt'    => $lang['CONFIG_COMMON_SITE_GZIP'],
        'descr'   => $lang['CONFIG_COMMON_SITE_GZIP_MORE'] );

    $fields['site_guests']=Array(
        'value'   => 'ON',
        'type'    => 'checkbox',
        'checked' => ($QF_Config['enable_guests']) ? '1' : '',
        'capt'    => $lang['CONFIG_COMMON_SITE_DOGUESTS'],
        'descr'   => $lang['CONFIG_COMMON_SITE_DOGUESTS_MORE'] );

    $fields['site_spiders']=Array(
        'value'   => 'ON',
        'type'    => 'checkbox',
        'checked' => ($QF_Config['enable_spiders']) ? '1' : '',
        'capt'    => $lang['CONFIG_COMMON_SITE_DOSPIDERS'],
        'descr'   => $lang['CONFIG_COMMON_SITE_DOSPIDERS_MORE'] );

    $fields['site_no_spiders']=Array(
        'value'   => 'ON',
        'type'    => 'checkbox',
        'checked' => ($QF_Config['restrict_spiders']) ? '1' : '',
        'capt'    => $lang['CONFIG_COMMON_SITE_NOSPIDERS'],
        'descr'   => $lang['CONFIG_COMMON_SITE_NOSPIDERS_MORE'] );

    $fields['uinfo_rights']=Array(
        'type'  => 'select',
        'capt'  => $lang['CONFIG_COMMON_SITE_UINFOACC'],
        'descr' => $lang['CONFIG_COMMON_SITE_UINFOACC_MORE'] );
    for($stt=0; $stt<= $QF_User->level; $stt++)
        $fields['uinfo_rights']['subs'][$stt]=Array(
    	    'name'     => $stt,
	        'value'    => $stt,
    	    'selected' => ($QF_Config['uinfo_acc_lvl']==$stt) );


    $fields['site_use_spcode']=Array(
        'value'   => 'ON',
        'type'    => 'checkbox',
        'checked' => ($QF_Config['use_spcode']) ? '1' : '',
        'capt'    => $lang['CONFIG_COMMON_SITE_NOSPAM'],
        'descr'   => $lang['CONFIG_COMMON_SITE_NOSPAM_MORE'] );

    $fields['timesets']=Array(
        'type' => 'separator',
        'capt' => $lang['CONFIG_COMMON_TIMECONF'] );

    $fields['def_tz']=Array(
        'type'  => 'select',
        'capt'  => $lang['CONFIG_COMMON_SITE_TZ'],
        'descr' => $lang['CONFIG_COMMON_SITE_TZ_MORE'] );
    if ($lang['tz'])
	    foreach($lang['tz'] as $ttz=>$ttzs)
    	    $fields['def_tz']['subs'][$ttz]=Array(
    	        'name'     => $ttzs,
	            'value'    => $ttz,
    	        'selected' => ($QF_Config['def_tz']==$ttz) );
    else
	    for($ttz=-12;$ttz<=13;$ttz++)
    	    $fields['def_tz']['subs'][$ttz]=Array(
    	        'name'     => $ttz,
	            'value'    => $ttz,
    	        'selected' => ($QF_Config['def_tz']==$ttz) );

    $fields['date_format']=Array(
        'value' => $QF_Config['def_date_format'],
        'type'  => 'text',
        'capt'  => $lang['CONFIG_COMMON_DATEFORM'],
        'descr' => $lang['CONFIG_COMMON_DATEFORM_MORE'] );

    $fields['time_format']=Array(
        'value' => $QF_Config['def_time_format'],
        'type'  => 'text',
        'capt'  => $lang['CONFIG_COMMON_TIMEFORM'],
        'descr' => $lang['CONFIG_COMMON_TIMEFORM_MORE'] );

    $fields['time_correct']=Array(
        'value' => $QF_Config['date_corr_mins'],
        'type'  => 'text',
        'capt'  => $lang['CONFIG_COMMON_TIMECORR'],
        'descr' => $lang['CONFIG_COMMON_TIMECORR_MORE'] );

    $fields['filesets']=Array(
        'type' => 'separator',
        'capt' => $lang['CONFIG_COMMON_FILESCONF'] );

    $fields['file_rights']=Array(
        'type'  => 'select',
        'capt'  => $lang['CONFIG_COMMON_FILES_PRIGHTS'],
        'descr' => $lang['CONFIG_COMMON_FILES_PRIGHTS_MORE'] );
    for($stt=0; $stt<= $QF_User->level;$stt++)
        $fields['file_rights']['subs'][$stt]=Array(
    	    'name'     => $stt,
	        'value'    => $stt,
    	    'selected' => ($QF_Config['post_files_rights']==$stt) );

    $fields['file_msize']=Array(
        'value' => round($QF_Config['post_file_size']/1024),
        'type'  => 'text',
        'capt'  => $lang['CONFIG_COMMON_FILES_MSIZE'],
        'descr' => $lang['CONFIG_COMMON_FILES_MSIZE_MORE'] );

    $fields['th_width']=Array(
        'value' => $QF_Config['thumb_width'],
        'type'  => 'text',
        'capt'  => $lang['CONFIG_COMMON_FILES_TWIDTH'],
        'descr' => $lang['CONFIG_COMMON_FILES_TWIDTH_MORE'] );

    $fields['th_height']=Array(
        'value' => $QF_Config['thumb_height'],
        'type'  => 'text',
        'capt'  => $lang['CONFIG_COMMON_FILES_THEIGHT'],
        'descr' => $lang['CONFIG_COMMON_FILES_THEIGHT_MORE'] );

    $fields['submbutt']=Array(
        'type' => 'separator',
        'capt' => ' --- ' );

    $fields['submit']=Array(
        'value' => $lang['BTN_ACCEPT'],
        'type'  => 'submit',
        'descr' => $lang['CONFIG_COMMON_ACCEPT'] );

    $cabinet_main_window.= Vis_Draw_Form($lang['UCAB_ADMIN_CONFIG'],'commconfigform','index.php',$lang['CONFIG_COMMON_REQUEST'],$fields);

}

//
// Visual config
//
elseif ($job=='vis_config') {
    $cabinet_caption = $lang['UCAB_ADMIN_CONFIG_VIS'];

    $fields=Array();

    $fields['script']=Array(
        'value' => 'adm_config',
        'type'  => 'hidden' );

    $fields['action']=Array(
        'value' => 'visual_config',
        'type'  => 'hidden' );

    $fields['siteinfo']=Array(
        'type' => 'separator',
        'capt' => $lang['CONFIG_VISUAL_FIRST'] );

    $fields['site_logo']=Array(
        'value' => $QF_Config['site_logo'],
        'type'  => 'text',
        'capt'  => $lang['CONFIG_VISUAL_SITELOGO'],
        'descr' => $lang['CONFIG_VISUAL_SITELOGO_MORE'] );

    $fields['menu_hide_home']=Array(
        'value'   => 'ON',
        'type'    => 'checkbox',
        'checked' => ($QF_Config['std_menu']['hide_home']) ? '1' : '',
        'capt'    => $lang['CONFIG_VISUAL_MHIDE_HOME'],
        'descr'   => $lang['CONFIG_VISUAL_MHIDE_HOME_MORE'] );

    $fields['menu_hide_gbook']=Array(
        'value'   => 'ON',
        'type'    => 'checkbox',
        'checked' => ($QF_Config['std_menu']['hide_gbook']) ? '1' : '',
        'capt'    => $lang['CONFIG_VISUAL_MHIDE_GBOOK'],
        'descr'   => $lang['CONFIG_VISUAL_MHIDE_GBOOK_MORE'] );

    $fields['menu_hide_forum']=Array(
        'value'   => 'ON',
        'type'    => 'checkbox',
        'checked' => ($QF_Config['std_menu']['hide_forum']) ? '1' : '',
        'capt'    => $lang['CONFIG_VISUAL_MHIDE_FORUM'],
        'descr'   => $lang['CONFIG_VISUAL_MHIDE_FORUM_MORE'] );

    $fields['menu_hide_users']=Array(
        'value'   => 'ON',
        'type'    => 'checkbox',
        'checked' => ($QF_Config['std_menu']['hide_users']) ? '1' : '',
        'capt'    => $lang['CONFIG_VISUAL_MHIDE_USERS'],
        'descr'   => $lang['CONFIG_VISUAL_MHIDE_USERS_MORE'] );

    $fields['menu_add_buttons']=Array(
        'value' => htmlspecialchars($QF_Config['std_menu']['add_buttons']),
        'type'  => 'textarea',
        'capt'  => $lang['CONFIG_VISUAL_MADD_BUTTS'],
        'descr' => $lang['CONFIG_VISUAL_MADD_BUTTS_MORE'] );

    $fields['advsinfo']=Array(
        'type' => 'separator',
        'capt' => $lang['CONFIG_VISUAL_ADVS'] );

    $fields['adv_data']=Array(
        'value' => htmlspecialchars($QF_Config['adv_data']),
        'type'  => 'textarea',
        'capt'  => $lang['CONFIG_VISUAL_ADVDATA'],
        'descr' => $lang['CONFIG_VISUAL_ADVDATA_MORE'] );

    $fields['bott_adv_data']=Array(
        'value' => htmlspecialchars($QF_Config['bottom_adv_data']),
        'type'  => 'textarea',
        'capt'  => $lang['CONFIG_VISUAL_BADVDATA'],
        'descr' => $lang['CONFIG_VISUAL_BADVDATA_MORE'] );

    $fields['submbutt']=Array(
        'type' => 'separator',
        'capt' => ' --- ' );

    $fields['submit']=Array(
        'value' => $lang['BTN_ACCEPT'],
        'type'  => 'submit',
        'descr' => $lang['CONFIG_VISUAL_ACCEPT'] );

    $cabinet_main_window.= Vis_Draw_Form($lang['UCAB_ADMIN_CONFIG_VIS'],'commconfigform','index.php',$lang['CONFIG_VISUAL_REQUEST'],$fields);

}

//
// Access Groups
//
elseif ($job=='acc_groups') {    $cabinet_caption = $lang['UCAB_ADMIN_ACCGROUPS'];

    Glob_Request('edit_grp show_grp drop_grp');
    $ptmpl = Array();

    $query = 'SELECT ag.*, COUNT(al.user_id) AS users FROM {DBKEY}acc_groups ag LEFT JOIN {DBKEY}acc_links al ON (al.group_id = ag.id) LEFT JOIN {DBKEY}users us ON (us.id = al.user_id AND us.deleted = 0) GROUP BY ag.id ';
    $result = $QF_DBase->sql_query($query);
    if ($result) {        $ttmpl = Array(
            'rows'      => '',
            'formstart' => '<form action="index.php" method="post">',
            'formend'   => '</form>',
            );

        while ($acc_gr = $QF_DBase->sql_fetchrow($result) ) {            $gtmpl= Array(
                'id'    => $acc_gr['id'],
                'name'  => $acc_gr['name'],
                'descr' => $acc_gr['descr'],
                'users' => $acc_gr['users'],
                'link'  => 'index.php?st=mycabinet&amp;job=acc_groups&amp;show_grp='.$acc_gr['id'],
                );

            if ($show_grp == $acc_gr['id']) {
                $edit_grp = null;
                $group_to_show = $acc_gr;
            }

            if ($drop_grp == $acc_gr['id']) {                $do_del = Get_Request('do', 1, 'b');
                if ($do_del) {
                    $QF_DBase->sql_query('DELETE FROM {DBKEY}acc_groups WHERE id = '.$drop_grp );
                    $QF_DBase->sql_query('DELETE FROM {DBKEY}acc_links WHERE group_id = '.$drop_grp );
                    $acc_gr['del'] = true;
                    $edit_grp = null;
                }
                else
                    $ptmpl['grp_request'] = Vis_Draw_Table($lang['ADMCAB_ACCGRP_GR_DELCAPT'],
                        sprintf($lang['ADMCAB_ACCGRP_GR_DELETE'], $acc_gr['name'], 'index.php?st=mycabinet&amp;job=acc_groups&amp;drop_grp='.$acc_gr['id'].'&amp;do=1'),
                        true );
            }

            if ($edit_grp == $acc_gr['id']) {                $ttmpl['rows'].= Visual('ACCESS_GROUPS_TBLROW_CHFORM', $gtmpl);
            }
            elseif (!$acc_gr['del']) {
                $gtmpl['controls'] = '<a href="index.php?st=mycabinet&amp;job=acc_groups&amp;edit_grp='.$acc_gr['id'].'">'.$Vis['BTN_EDIT'].'</a>
                    <a href="index.php?st=mycabinet&amp;job=acc_groups&amp;drop_grp='.$acc_gr['id'].'">'.$Vis['BTN_DROP'].'</a>';
                $ttmpl['rows'].= Visual('ACCESS_GROUPS_TBLROW', $gtmpl);
            }

        }

        if ($edit_grp == 'cr_new') {
            $gtmpl= Array(
                'id'       => '--',
                'name'     => '',
                'descr'    => '',
                );

            $ttmpl['rows'].= Visual('ACCESS_GROUPS_TBLROW_CHFORM', $gtmpl);
        }
        else
            $ttmpl['rows'].= Visual('ACCESS_GROUPS_TBLROW_NEW', Array( 'link' => 'index.php?st=mycabinet&amp;job=acc_groups&amp;edit_grp=cr_new') );

        $ptmpl['groups_list'] = Visual('ACCESS_GROUPS_TABLE', $ttmpl);

        $QF_DBase->sql_freeresult($result);

        if ($group_to_show>0) {            $query = 'SELECT l.*, u.nick AS user_nick FROM {DBKEY}acc_links l JOIN {DBKEY}users u ON (u.id = l.user_id) WHERE l.group_id = '.$group_to_show['id'].' AND u.deleted = 0 ORDER BY l.user_id';
            $result = $QF_DBase->sql_query($query);
            if ($result) {
                $gtmpl=Array(
                    'formstart' => '<form action="index.php" method="post">',
                    'formend'   => '</form>',
                    'controls'  => Visual('ACCESS_GROUP_USERS_CTRL', Array ('gr_id' => $group_to_show['id']) ),
                    'rows'      => '',
                    'gr_name'   => $group_to_show['name'],
                    );
                while ($gr_link = $QF_DBase->sql_fetchrow($result))
                {                    $rtmpl=Array(
                        'name'    => $gr_link['user_nick'],
                        't_given' => create_date('',$gr_link['time_given']),
                        't_drop'  => (isset($gr_link['drop_after'])) ? create_date('',$gr_link['drop_after']) : $lang['ADMCAB_ACCGRP_US_GOTPERM'],
                        'control' => '<input type="checkbox" name="us_list[]" value="'.$gr_link['user_id'].'" />',
                        'link'    => 'index.php?st=info&amp;infouser='.$gr_link['user_id'],
                        );

                    $gtmpl['rows'].= Visual('ACCESS_GROUP_USLIST_ROW', $rtmpl);
                }

                $ptmpl['group_users'] = Visual('ACCESS_GROUP_USLIST', $gtmpl);

                $gtmpl = Array(
                    'formstart' => '<form action="index.php" method="post">',
                    'formend'   => '</form>',
                    'gr_id'     => $group_to_show['id'],
                    'gr_name'   => $group_to_show['name'],
                    );

                $ptmpl['group_add_users'] = Visual('ACCESS_GROUP_ADDUSERS_FORM', $gtmpl);
            }

        }
    }

    $cabinet_main_window = Vis_Draw_Table($lang['UCAB_ADMIN_ACCGROUPS'], Visual('ACCESS_GROUPS_PAGE', $ptmpl) );
}

//
// Forum config
//
elseif ($job=='for_config') {
    $cabinet_caption = $lang['UCAB_ADMIN_FOR_CONFIG'];

    $forconfig = $QF_Config['forum'];

    $fields=Array();

    $fields['script']=Array(
        'value' => 'adm_config',
        'type'  => 'hidden' );

    $fields['action']=Array(
        'value' => 'forum_config',
        'type'  => 'hidden' );

    $fields['root_name']=Array(
        'value' => $forconfig['root_name'],
        'type'  => 'text',
        'capt'  => $lang['CONFIG_FORUM_ROOTNAME'],
        'descr' => $lang['CONFIG_FORUM_ROOTNAME_MORE'] );

    $fields['page_posts']=Array(
        'type'  => 'select',
        'capt'  => $lang['CONFIG_FORUM_PAGEPOSTS'],
        'descr' => $lang['CONFIG_FORUM_PAGEPOSTS_MORE'] );
   	$fields['page_posts']['subs'][-1]=Array(
   	    'name'     => $lang['DEFAULT'],
        'value'    => -1 );
    for($ttz=5;$ttz<=30;$ttz++)
   	    $fields['page_posts']['subs'][$ttz]=Array(
   	        'name'     => $ttz,
            'value'    => $ttz,
   	        'selected' => ($forconfig['posts_per_page']==$ttz) );

    $fields['mess_lock']=Array(
        'type'  => 'select',
        'capt'  => $lang['CONFIG_FORUM_MESSLOCK'],
        'descr' => $lang['CONFIG_FORUM_MESSLOCK_MORE'] );
   	$fields['mess_lock']['subs'][-1]=Array(
   	    'name'     => $lang['DEFAULT'],
        'value'    => -1 );
    for($ttz=0;$ttz<=10;$ttz++)
   	    $fields['mess_lock']['subs'][$ttz]=Array(
   	        'name'     => $ttz,
            'value'    => $ttz,
   	        'selected' => ($forconfig['mess_lock_time']==$ttz) );

    $fields['guest_book']=Array(
	    'type'  => 'select',
	    'capt'  => $lang['CONFIG_FORUM_GBOOK'],
	    'params' => 'style="width: 130px;"',
	    'descr' => $lang['CONFIG_FORUM_GBOOK_MORE'] );
    $fields['guest_book']['subs'][0]=Array(
	    'name'     => ' --- ',
        'params'   => 'style="width: auto;"',
        'value'    => 0  );

    $gbquery='SELECT t.*, s.acc_group FROM {DBKEY}topics t LEFT JOIN {DBKEY}sections s ON (s.id=t.parent) WHERE t.postrights=0 AND t.deleted=0 AND t.locked=0';
    $result=$QF_DBase->sql_query($gbquery);
    if ($result) {
	    while ($topic=$QF_DBase->sql_fetchrow($result))
	   	    if (intval($topic['acc_group'])==0)
	   	        $fields['guest_book']['subs'][$topic['id']]=Array(
	   	            'name'     => STrim($topic['name'],30),
    	            'params'   => 'title = "'.$topic['name'].'" style="width: auto;"',
    	            'value'    => $topic['id'],
	       	        'selected' => ($forconfig['guest_book']==$topic['id']) );
    }

    $fields['post_files']=Array(
        'type'  => 'select',
        'capt'  => $lang['CONFIG_FORUM_POSTFILES'],
        'descr' => $lang['CONFIG_FORUM_POSTFILES_MORE'] );
   	$fields['post_files']['subs'][-1]=Array(
   	    'name'     => $lang['DEFAULT'],
        'value'    => -1 );
    for($ttz=0;$ttz<=5;$ttz++)
   	    $fields['post_files']['subs'][$ttz]=Array(
   	        'name'     => $ttz,
            'value'    => $ttz,
   	        'selected' => ($forconfig['post_upl_files']==$ttz) );

    $fields['submit']=Array(
        'value' => $lang['BTN_ACCEPT'],
        'type'  => 'submit',
        'descr' => $lang['CONFIG_FORUM_ACCEPT'] );

    $cabinet_main_window = Vis_Draw_Form($lang['UCAB_ADMIN_FOR_CONFIG'],'commconfigform','index.php',$lang['CONFIG_FORUM_REQUEST'],$fields);

}

//
// Visit Statistics
//
elseif ($job=='vis_stat') {
    $cabinet_caption = $lang['UCAB_ADMIN_VISSTAT'];

    $visstats='';

    //loading all the users
    $ulist->load('', True);
    $ulist->timesort();

    $toprint='';
    $query='SELECT * FROM {DBKEY}sessions ORDER BY lastused DESC ';
    $result=$QF_DBase->sql_query($query);
    if ($result) {
       while ( $sess = $QF_DBase->sql_fetchrow($result) ) {
       $ssdata=unserialize($sess['vars']);
       $suser=$ulist->get($ssdata['QF_uid']);
       $tmpl=Array(
       'sess_ip'        => $sess['ip'],
       'sess_starttime' => create_date('',$sess['starttime']),
       'sess_lastused'  => create_date('',$sess['lastused']),
       'sess_clicks'    => $sess['clicks'] );
       if ($suser) $tmpl['sess_user']='<a href="index.php?st=info&amp;infouser='.$suser['id'].'">'.$suser['nick'].'</a>';
       $toprint.=Visual('VSTAT_VSESS_TBLROW', $tmpl);
       }
       $toprint=Visual('VSTAT_VSESS_TABLE', Array('rows' => $toprint) );
    }
    $visstats.= Vis_Draw_Panel($toprint,$lang['ADMCAB_VISSTAT_SESSION'], "100%");

    $toprint='';
    $query='SELECT *, SUM(views) as views, MAX(lastseen) as lastseen FROM {DBKEY}guests GROUP BY gcode ORDER BY lastseen DESC ';
    $result=$QF_DBase->sql_query($query);
    if ($result) {
       while ( $guest = $QF_DBase->sql_fetchrow($result) ) {
       $tmpl=Array(
       'g_gid'      => $guest['gid'],
       'g_nick'     => $guest['gnick'],
       'g_uagent'   => $guest['guser_agent'],
       'g_lastip'   => $guest['lastip'],
       'g_lasturl'  => $guest['lasturl'],
       'g_lastseen' => create_date('',$guest['lastseen']),
       'g_isguest'  => ($guest['gcode']==$guest['gid']),
       'g_views'    => $guest['views'] );

       $toprint.=Visual('VSTAT_VGUESTS_TBLROW', $tmpl);
       }
       $toprint=Visual('VSTAT_VGUESTS_TABLE', Array('rows' => $toprint) );
    }
    $visstats.= Vis_Draw_Panel($toprint,$lang['ADMCAB_VISSTAT_GUESTS'], "100%");

    $toprint='';
    $query='SELECT s.name, s.agent_mask, ss.lastseen, ss.visits FROM {DBKEY}spiders_stats ss JOIN {DBKEY}spiders s ON(s.id = ss.id) ORDER BY ss.lastseen DESC ';
    $result=$QF_DBase->sql_query($query);
    if ($result) {
       while ( $spdr = $QF_DBase->sql_fetchrow($result) ) {
       $tmpl=Array(
       'spd_name'       => $spdr['name'],
       'spd_mask'       => $spdr['agent_mask'],
       'spd_lastseen'   => create_date('',$spdr['lastseen']),
       'spd_visits'     => $spdr['visits'],
       );

       $toprint.=Visual('VSTAT_VSPIDERS_TBLROW', $tmpl);
       }
       $toprint=Visual('VSTAT_VSPIDERS_TABLE', Array('rows' => $toprint) );
    }
    $visstats.= Vis_Draw_Panel($toprint,$lang['ADMCAB_VISSTAT_SPIDERS'], "100%");

    $toprint='';
    $LastCount=0;
    Foreach($ulist->users as $suser) {
    $tmpl=Array(
    'u_id'       => $suser['id'],
    'u_avatar'   => Vis_Gen_Avatar($suser),
    'u_nick'     => '<a href="index.php?st=info&amp;infouser='.$suser['id'].'">'.$suser['nick'].'</a>',
    'u_lastip'   => $suser['lastip'],
    'u_lasturl'  => $suser['lasturl'],
    'u_lastseen' => create_date('',$suser['lastseen']) );
    $toprint.=Visual('VSTAT_VUSERS_TBLROW', $tmpl);
    $LastCount++;
    }
    $toprint=Visual('VSTAT_VUSERS_TABLE', Array('rows' => $toprint) );

    $visstats.= Vis_Draw_Panel($toprint,$lang['ADMCAB_VISSTAT_USERS'], "100%", True);

    $cabinet_main_window = Vis_Draw_Table($lang['UCAB_ADMIN_VISSTAT'], $visstats);
}

//
// Sections Control
//

elseif ($job=='sections') {
    $cabinet_caption = $lang['UCAB_ADMIN_SECTIONS'];
    include 'includes/forum_core.php';
    $QF_Forum = new qf_forum();

    $section = Get_Request('section', 1, 's');;

    if ($section=='new')
        $cursect=Array('name'=>'', 'parent'=>0, 'minrights'=>0, 'postrights'=>1);
    elseif ($section)
        $cursect=$QF_Forum->ForumTree[IntVal($section)];

    if (is_array($cursect)) {
        $tmpl=Array(
            'sect'        => $cursect['id'],
            'capt'        => $cursect['name'],
            'descr'       => $cursect['descr'],
            'rr_options'  => '',
            'pr_options'  => '',
            'par_options' => '',
            'par_hint'    => Vis_Draw_Hint($lang['SECTION_EDIT_HINT']),
            );

        if ($section=='new')
            $tmpl['sect_new']='new';
        if ($cursect['deleted'])
            $tmpl['deleted']='1';

        for($stt=1; $stt <= $QF_User->level; $stt++){
            $tmpl['rr_options'].='<option value="'.$stt.'"'.
                (($cursect['minrights']==$stt) ? " SELECTED" : '').
                 '>'.$stt.'</option>';
            $tmpl['pr_options'].='<option value="'.$stt.'"'.
                (($cursect['postrights']==$stt) ? " SELECTED" : '').
                '>'.$stt.'</option>';
        }

        $query='SELECT * FROM {DBKEY}acc_groups';
        $result=$QF_DBase->sql_query($query);
        if ($result)
            while ($agr = $QF_DBase->sql_fetchrow($result))
                $tmpl['acc_g_options'].='<option value="'.$agr['id'].'" '.
                    (($cursect['acc_group']==$agr['id']) ? ' SELECTED':'').
                    '>'.$agr['name'].'</option>';
        $QF_DBase->sql_freeresult($result);

        $stree = $QF_Forum->ForumTree;
        Unset ($stree[$section]);
        $stree=$QF_Forum->GenForumTree($stree);
        foreach ($stree as $ss)
            $tmpl['par_options'].='<option value="'.$ss['id'].'" '.
                (($cursect['parent']==$ss['id']) ? ' SELECTED':'').
                '>'.$ss['pref'].$ss['name'].' ('.$ss['minrights'].')</option>';

        $content=Visual('FOR_ADM_EDIT_SECT', $tmpl);
        $cabinet_main_window.= Vis_Draw_Table( (($section=='new') ? $lang['ADMCAB_SECTION_NEW_CAPT'] : $lang['ADMCAB_SECTION_EDIT_CAPT']), $content).'<br />';
    }

    $content='';
    foreach ($QF_Forum->ForumTree as $sect) if ($sect['level']>0){
        $tmpl=Array(
            'labels'    => '<a name="s'.$sect['id'].'"></a>',
            'caption'   => '<a href="index.php?st=section&amp;section='.$sect['id'].'">'.$sect['name'].'</a>',
            'descr'     => $QF_Parser->parse_mess($sect['descr']),
            'sects'     => $sect['sects'],
            'themes'    => $sect['themes'],
            'posts'     => $sect['posts'],
            'rights'    => Vis_Gen_Rights($sect['minrights'],' '),
            'acc_group' => $sect['acc_group_name'],
            'acc_gr_lk' => 'index.php?st=mycabinet&amp;job=acc_groups&amp;show_grp='.$sect['acc_group'],
            'imgs'      => $Vis['FOR_CROSS'].str_repeat($Vis['FOR_ARROW'],$sect['level']-1),
            'edit'      => '<a href="index.php?st=mycabinet&amp;job=sections&amp;section='.$sect['id'].'">'.$Vis['BTN_EDIT'].'</a>',
            );

        if ($sect['deleted']) $tmpl['deleted']='1';

        $content.=Visual('FOR_ADM_SECT_ROW', $tmpl);
    }
    $content=Visual('FOR_ADM_SECT_TBL', Array('rows' => $content, 'sect_new_url' => 'index.php?st=mycabinet&amp;job=sections&amp;section=new') );
    $cabinet_main_window.= Vis_Draw_Table($lang['UCAB_ADMIN_SECTIONS'], $content).'<br />';

    $cabinet_main_window.= Vis_Draw_Table($lang['ADMCAB_SECTION_SYNC'], $Vis['FOR_ADM_SYNC']).'<br />';
}

//
// Reserv DBase
//

elseif ($job=='db_reserv') {
    $cabinet_caption = $lang['UCAB_ADMIN_DBRESERV'];
    $fields=Array();

    $fields['script']=Array(
        'value' => 'db_dump',
        'type'  => 'hidden' );

    $fields['action']=Array(
        'value' => 'dump',
        'type'  => 'hidden' );

    $fields['siteinfo']=Array(
        'type' => 'separator',
        'capt' => $QF_DBase->srv_info() );

    $fields['nocontent']=Array(
        'value' => 'ON',
        'type'  => 'checkbox',
        'capt'  => $lang['ADMCAB_DBDUMP_NOCONTENT'],
        'descr' => $lang['ADMCAB_DBDUMP_NOCONTENT_MORE'] );

    $fields['nostruct']=Array(
        'value' => 'ON',
        'type'  => 'checkbox',
        'capt'  => $lang['ADMCAB_DBDUMP_NOSTRUCT'],
        'descr' => $lang['ADMCAB_DBDUMP_NOSTRUCT_MORE'] );

    $fields['alltables']=Array(
        'value' => 'ON',
        'type'  => 'checkbox',
        'capt'  => $lang['ADMCAB_DBDUMP_ALLTABLES'],
        'descr' => $lang['ADMCAB_DBDUMP_ALLTABLES_MORE'] );

//    $fields['SCTuse']=Array(
//        'value' => 'ON',
//        'type'  => 'checkbox',
//        'capt'  => $lang['ADMCAB_DBDUMP_SCTUSE'],
//        'descr' => $lang['ADMCAB_DBDUMP_SCTUSE_MORE'] );
//    if (mysql_get_server_info()>='4.1') $fields['SCTuse']['checked']='true';

    $fields['submit']=Array(
        'value' => $lang['BTN_GO'],
        'type'  =>'submit',
        'descr' => $lang['ADMCAB_DBDUMP_ACCEPT'] );

    $cabinet_main_window.= Vis_Draw_Form($lang['UCAB_ADMIN_DBRESERV'],'dbreservform','index.php',$lang['ADMCAB_DBDUMP_REQUEST'],$fields);

}

else {
    $cabinet_adm_hide=true;

}

// Admin's Menu
if ($QF_Session->Get('is_admin') == 1) {
    $mrows='';
    $mrows.=Visual('MYCABINET_MROW', Array( 'capt' => $lang['UCAB_ADMIN_VISSTAT'], 'link' => '?st=mycabinet&amp;job=vis_stat', 'selected' => (($job=='vis_stat') ? 'True' : '') ));
    $mrows.=Visual('MYCABINET_MROW', Array( 'capt' => $lang['UCAB_ADMIN_CONFIG'], 'link' => '?st=mycabinet&amp;job=config', 'selected' => (($job=='config') ? 'True' : '') ));
    $mrows.=Visual('MYCABINET_MROW', Array( 'capt' => $lang['UCAB_ADMIN_CONFIG_VIS'], 'link' => '?st=mycabinet&amp;job=vis_config', 'selected' => (($job=='vis_config') ? 'True' : '') ));
    $mrows.=Visual('MYCABINET_MROW', Array( 'capt' => $lang['UCAB_ADMIN_ACCGROUPS'], 'link' => '?st=mycabinet&amp;job=acc_groups', 'selected' => (($job=='acc_groups') ? 'True' : '') ));
    $content=Visual('MYCABINET_MTABLE', Array('rows' => $mrows) );
    $cabinet_adm_menu_tower.= Vis_Draw_panel($content,$lang['UCAB_ADMIN_QF_COMMON'],'200',$cabinet_adm_hide);

    $mrows='';
    $mrows.=Visual('MYCABINET_MROW', Array( 'capt' => $lang['UCAB_ADMIN_FOR_CONFIG'], 'link' => '?st=mycabinet&amp;job=for_config', 'selected' => (($job=='for_config') ? 'True' : '') ));
    $mrows.=Visual('MYCABINET_MROW', Array( 'capt' => $lang['UCAB_ADMIN_SECTIONS'], 'link' => '?st=mycabinet&amp;job=sections', 'selected' => (($job=='sections') ? 'True' : '') ));
    $content=Visual('MYCABINET_MTABLE', Array('rows' => $mrows) );
    $cabinet_adm_menu_tower.= Vis_Draw_panel($content,$lang['UCAB_ADMIN_QF_FORUM'],'200',$cabinet_adm_hide);

    $mrows='';
    $mrows.=Visual('MYCABINET_MROW', Array( 'capt' => $lang['UCAB_ADMIN_DBRESERV'], 'link' => '?st=mycabinet&amp;job=db_reserv', 'selected' => (($job=='db_reserv') ? 'True' : '') ));
    if ($QF_User->uid==1) {
        $mrows.=Visual('MYCABINET_MROW', Array( 'capt' => $lang['UCAB_ADMIN_DIRECTSQL'], 'link' => '?st=directSQL' ));
        $mrows.=Visual('MYCABINET_MROW', Array( 'capt' => $lang['UCAB_ADMIN_IPB_CONV'], 'link' => '?st=IPB_convert' ));
    }
    $content=Visual('MYCABINET_MTABLE', Array('rows' => $mrows) );
    $cabinet_adm_menu_tower.= Vis_Draw_panel($content,$lang['UCAB_ADMIN_UTILITIES'],'200',true);
}
else {
    $content=$Vis['ADM_LOGIN_MESS'];
    $cabinet_adm_menu_tower.= Vis_Draw_panel($content,$lang['UCAB_MADMIN_CAPT'],'200',true);
}

if (!$cabinet_adm_hide) $cabinet_menu_hide=true;

?>