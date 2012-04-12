<?php

// Simple InUSE Check
if ( !defined('QF_STARTED') )
        die('Hacking attempt');

$error = '';
$action = Get_Request('action', 2);

$apply_conf = Array();

Load_Language('admin');

if ($QF_Session->Get('is_admin')!=1 || !$QF_User->admin) {
    $error = $lang['ERR_ADMIN_ONLY'];
}

elseif ($action=='common_config') {
    $site_name = Get_Request('site_name', 2, 'ht', 255);
    if (strlen($site_name)<3)
        $error .= '<LI>'.$lang['CONFIG_COMMON_SITENAME_ERR'];
    $apply_conf[]=Array(
        'parent' => '',
        'name'   => 'site_name',
        'value'  => $site_name,
    );

    $site_mail = Get_Request('site_mail', 2, 's');
    if (!preg_match('/'.EMAIL_MASK.'/i',$site_mail))
        $error .= '<LI>'.$lang['CONFIG_COMMON_SITEMAIL_ERR'];
    $apply_conf[]=Array(
        'parent' => '',
        'name'   => 'site_mail',
        'value'  => $site_mail,
    );

    $site_style = Get_Request('site_style', 2, 's');
    if (!$styles[$site_style]['name'] && $site_style!='')
        $error .= '<LI>'.$lang['CONFIG_COMMON_SITE_STYLE_ERR'];
    else
        $apply_conf[]=Array(
            'parent' => '',
            'name'   => 'def_style',
            'value'  => $site_style,
        );

    $site_csssep = (Get_Request('force_css_separate', 2)=='ON') ? 1 : 0;
    $apply_conf[]=Array(
        'parent' => '',
        'name'   => 'force_css_separate',
        'value'  => $site_csssep,
    );

    $site_gzip = (Get_Request('site_gzip', 2)=='ON') ? 1 : 0;
    $apply_conf[]=Array(
        'parent' => '',
        'name'   => 'GZIP',
        'value'  => $site_gzip,
    );

    $site_smtp = (Get_Request('site_smtp', 2)=='ON') ? 1 : 0;
    $apply_conf[]=Array(
        'parent' => '',
        'name'   => 'sendmail_smtp',
        'value'  => $site_smtp,
    );

    $site_guests = (Get_Request('site_guests', 2)=='ON') ? 1 : 0;
    $apply_conf[]=Array(
        'parent' => '',
        'name'   => 'enable_guests',
        'value'  => $site_guests,
    );

    $site_spiders = (Get_Request('site_spiders', 2)=='ON') ? 1 : 0;
    $apply_conf[]=Array(
        'parent' => '',
        'name'   => 'enable_spiders',
        'value'  => $site_spiders,
    );

    $site_no_spiders = (Get_Request('site_no_spiders', 2)=='ON') ? 1 : 0;
    $apply_conf[]=Array(
        'parent' => '',
        'name'   => 'restrict_spiders',
        'value'  => $site_no_spiders,
    );

    $register_need_approve = (Get_Request('register_need_approve', 2) == 'ON') ? 1 : 0;
    $apply_conf[] = Array(
        'parent' => '',
        'name'   => 'register_need_approve',
        'value'  => $register_need_approve,
    );

    $uinfo_rights = Get_Request('uinfo_rights', 2, 'i');
    if ($uinfo_rights>=0 && $uinfo_rights <= $QF_User->level)
        $apply_conf[]=Array(
            'parent' => '',
            'name'   => 'uinfo_acc_lvl',
            'value'  => $uinfo_rights,
        );
    else
        $error .= '<LI>'.$lang['CONFIG_COMMON_SITE_UINFOACC_ERR'];

    $site_use_spcode = (Get_Request('site_use_spcode', 2)=='ON') ? 1 : 0;
    $apply_conf[]=Array(
        'parent' => '',
        'name'   => 'use_spcode',
        'value'  => $site_use_spcode,
    );

    $def_tz = Get_Request('def_tz', 2, 'f');
    if ($def_tz>=-12 && $def_tz<=12)
        $apply_conf[]=Array(
            'parent' => '',
            'name'   => 'def_tz',
            'value'  => $def_tz,
        );
    else
        $error .= '<LI>'.$lang['CONFIG_COMMON_SITE_TZ_ERR'];

    $date_format = Get_Request('date_format', 2, 'ht');
    if (strlen($date_format)<3 && $date_format!='')
        $error .= '<LI>'.$lang['CONFIG_COMMON_DATEFORM_ERR'];
    else
        $apply_conf[]=Array(
            'parent' => '',
            'name'   => 'def_date_format',
            'value'  => $date_format,
        );

    $time_format = Get_Request('time_format', 2, 'ht');
    if (strlen($time_format)<3 && $time_format!='')
        $error .= '<LI>'.$lang['CONFIG_COMMON_TIMEFORM_ERR'];
    else
        $apply_conf[]=Array(
            'parent' => '',
            'name'   => 'def_time_format',
            'value'  => $time_format,
        );

    $time_correct = Get_Request('time_correct', 2, 'i');
    if ($time_correct==0) $time_correct='';
    $apply_conf[]=Array(
        'parent' => '',
        'name'   => 'date_corr_mins',
        'value'  => $time_correct,
    );

    $file_rights = Get_Request('file_rights', 2, 'i');
    if ($file_rights>=0 && $file_rights <= $QF_User->level)
        $apply_conf[]=Array(
            'parent' => '',
            'name'   => 'post_files_rights',
            'value'  => $file_rights,
        );
    else
        $error .= '<LI>'.$lang['CONFIG_COMMON_FILES_PRIGHTS_ERR'];

    $file_noattc = (Get_Request('file_noattc', 2)=='ON') ? 1 : 0;
    $apply_conf[]=Array(
        'parent' => 'files',
        'name'   => 'no_attc',
        'value'  => $file_noattc,
    );

    $file_msize = Get_Request('file_msize', 2, 'i');
    if ($file_msize>=512 && $file_msize<=102400)
        $apply_conf[]=Array(
            'parent' => '',
            'name'   => 'post_file_size',
            'value'  => $file_msize*1024,
        );
    elseif ($file_msize>0)
        $error .= '<LI>'.$lang['CONFIG_COMMON_FILES_PRIGHTS_ERR'];
    else
        $apply_conf[]=Array(
            'parent' => '',
            'name'   => 'post_file_size',
            'value'  => '',
        );

    $th_width = Get_Request('th_width', 2, 'i');
    if ($th_width>=80 && $th_width<=200)
        $apply_conf[]=Array(
            'parent' => '',
            'name'   => 'thumb_width',
            'value'  => $th_width,
        );
    elseif ($th_width>0)
        $error .= '<LI>'.$lang['CONFIG_COMMON_FILES_TWIDTH_ERR'];
    else
        $apply_conf[]=Array(
            'parent' => '',
            'name'   => 'thumb_width',
            'value'  => '',
        );

    $th_height = Get_Request('th_height', 2, 'i');
    if ($th_height>=80 && $th_height<=200)
        $apply_conf[]=Array(
            'parent' => '',
            'name'   => 'thumb_height',
            'value'  => $th_height,
        );
    elseif ($th_height>0)
        $error .= '<LI>'.$lang['CONFIG_COMMON_FILES_THEIGHT_ERR'];
    else
        $apply_conf[]=Array(
            'parent' => '',
            'name'   => 'thumb_height',
            'value'  => '',
        );

    $redir_url = 'index.php?st=mycabinet&amp;job=config';
}

elseif ($action=='visual_config') {

    $site_logo = Get_Request('site_logo', 2, 's');
    if ($site_logo && (!qf_str_is_url($site_logo) || !file_exists($site_logo)))
        $error .= '<LI>'.$lang['CONFIG_VISUAL_SITELOGO_ERR'];
    else
        $apply_conf[]=Array(
            'parent' => '',
            'name'   => 'site_logo',
            'value'  => $site_logo,
        );

    $menu_hide_home = (Get_Request('menu_hide_home', 2)=='ON') ? 1 : 0;
    $apply_conf[]=Array(
        'parent' => 'std_menu',
        'name'   => 'hide_home',
        'value'  => $menu_hide_home,
    );

    $menu_hide_gbook = (Get_Request('menu_hide_gbook', 2)=='ON') ? 1 : 0;
    $apply_conf[]=Array(
        'parent' => 'std_menu',
        'name'   => 'hide_gbook',
        'value'  => $menu_hide_gbook,
    );

    $menu_hide_forum = (Get_Request('menu_hide_forum', 2)=='ON') ? 1 : 0;
    $apply_conf[]=Array(
        'parent' => 'std_menu',
        'name'   => 'hide_forum',
        'value'  => $menu_hide_forum,
    );

    $menu_hide_users = (Get_Request('menu_hide_users', 2)=='ON') ? 1 : 0;
    $apply_conf[]=Array(
        'parent' => 'std_menu',
        'name'   => 'hide_users',
        'value'  => $menu_hide_users,
    );

    $menu_items = Get_Request('menu_add_buttons', 2, 's');
    if ($menu_items)
    {
        $butts = explode("\n", $menu_items);
        $menu_items = '';
        foreach ($butts as $butt)
        {
            $butt = explode(' ', $butt, 2);
            $burl = trim($butt[0]);
            $bcpt = trim($butt[1]);
            if (count($butt)>1 && qf_str_is_url($burl))
                $menu_items.=$burl.' '.$bcpt."\n";
        }
        $apply_conf[]=Array(
            'parent' => 'std_menu',
            'name'   => 'add_buttons',
            'value'  => $menu_items,
        );
    }

    $adv_data = Get_Request('adv_data', 2, 's');
    $apply_conf[]=Array(
        'parent' => '',
        'name'   => 'adv_data',
        'value'  => $adv_data,
    );

    $adv_data = Get_Request('bott_adv_data', 2, 's');
    $apply_conf[]=Array(
        'parent' => '',
        'name'   => 'bottom_adv_data',
        'value'  => $adv_data,
    );

    $redir_url = 'index.php?st=mycabinet&amp;job=vis_config';
}

elseif ($action=='forum_config') {

    $root_name = Get_Request('root_name', 2, 'ht');
    if (strlen($root_name)<3 && $root_name!='')
        $error .= '<LI>'.$lang['CONFIG_FORUM_ROOTNAME_ERR'];
    else
        $apply_conf[]=Array(
            'parent' => 'forum',
            'name'   => 'root_name',
            'value'  => $root_name,
        );

    $page_posts = Get_Request('page_posts', 2, 'i');
    if ($page_posts>=5 && $page_posts<=30)
        $apply_conf[]=Array(
            'parent' => 'forum',
            'name'   => 'posts_per_page',
            'value'  => $page_posts,
        );
    elseif ($page_posts==-1)
        $apply_conf[]=Array(
            'parent' => 'forum',
            'name'   => 'posts_per_page',
            'value'  => '',
        );
    else
        $error .= '<LI>'.$lang['CONFIG_FORUM_PAGEPOSTS_ERR'];

    $mess_lock = Get_Request('mess_lock', 2, 'i');
    if ($mess_lock>=0 && $mess_lock<=10)
        $apply_conf[]=Array(
            'parent' => 'forum',
            'name'   => 'mess_lock_time',
            'value'  => $mess_lock,
        );
    elseif ($mess_lock==-1)
        $apply_conf[]=Array(
            'parent' => 'forum',
            'name'   => 'mess_lock_time',
            'value'  => '',
        );
    else
        $error .= '<LI>'.$lang['CONFIG_FORUM_MESSLOCK_ERR'];

    $guest_book = Get_Request('guest_book', 2, 'i');
    $gbquery='SELECT t.*, s.acc_group FROM {DBKEY}topics t
        LEFT JOIN {DBKEY}sections s ON (s.id=t.parent)
        WHERE t.postrights=0 AND t.deleted=0 AND t.locked=0 AND t.id='.$guest_book;
    $result=$QF_DBase->sql_query($gbquery);
    if ($result)
        $gtopic=$QF_DBase->sql_fetchrow($result);
    if ((!$gtopic['id'] || intval($topic['acc_group'])>0) && $guest_book>0)
        $error .= '<LI>'.$lang['CONFIG_FORUM_GBOOK_ERR'];
    else
        $apply_conf[]=Array(
            'parent' => 'forum',
            'name'   => 'guest_book',
            'value'  => $guest_book,
        );

    $post_files = Get_Request('post_files', 2, 'i');
    if ($post_files>=0 && $post_files<=5)
        $apply_conf[]=Array(
            'parent' => 'forum',
            'name'   => 'post_upl_files',
            'value'  => $post_files,
        );
    elseif ($post_files==-1)
        $apply_conf[]=Array(
            'parent' => 'forum',
            'name'   => 'post_upl_files',
            'value'  => '',
        );
    else
        $error .= '<LI>'.$lang['CONFIG_FORUM_MESSLOCK_ERR'];

    $redir_url = 'index.php?st=mycabinet&amp;job=for_config';
}
else
    $error = $lang['ERR_NO_ACTION'];

if (!$error) {
    // Initial Default config values
    require 'kernel/QF_def_conf.php';

    foreach($apply_conf as $conf) {
        $par_conf = ($conf['parent']) ? $def_config[$conf['parent']] : $def_config;

        if (strlen($conf['value'])==0 || $par_conf[$conf['name']]==$conf['value'])
            $QF_DBase->sql_dodelete('{DBKEY}config', Array('parent' => $conf['parent'], 'name' => $conf['name']) );
        else
            $QF_DBase->sql_doinsert('{DBKEY}config', Array('parent' => $conf['parent'], 'name' => $conf['name'], 'value' => $conf['value']), true );

    }

    $rresult = $lang['CONFIG_SAVED'];
}

  Set_Result($error, $rresult, $redir_url);

?>
