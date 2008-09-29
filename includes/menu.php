<?php

// Simple InUSE Check
if ( !defined('QF_STARTED') )
{
        die('Hacking attempt');
}

$menu_buttons='';
$menu_config = &$QF_Config['std_menu'];

if (!$menu_config['hide_home'])  $menu_buttons.=Visual('MAIN_MENU_BTN', Array('url'=>'/', 'caption'=>$lang['MMNU_HOME']));
if (!$menu_config['hide_gbook'] && $QF_Config['forum']['guest_book']>0) $menu_buttons.=Visual('MAIN_MENU_BTN', Array('url'=>'index.php?st=branch&amp;branch=gtree', 'caption'=>$lang['MMNU_GBOOK']));
//if (!$menu_config['hide_files']) $menu_buttons.=Visual('MAIN_MENU_BTN', Array('url'=>'index.php?st=files', 'caption'=>$lang['MMNU_FILES']));
if (!$menu_config['hide_forum']) $menu_buttons.=Visual('MAIN_MENU_BTN', Array('url'=>'index.php?st=section', 'caption'=>$lang['MMNU_FORUM']));
if ($menu_config['show_irclogs']) $menu_buttons.=Visual('MAIN_MENU_BTN', Array('url'=>'index.php?st=irclogs', 'caption'=>$lang['MMNU_IRCLOGS']));
if (!$menu_config['hide_users']) $menu_buttons.=Visual('MAIN_MENU_BTN', Array('url'=>'index.php?st=info', 'caption'=>$lang['MMNU_USERS']));

if (isset($menu_config['add_buttons']) && $menu_config['add_buttons'])
{    $butts = explode("\n", $menu_config['add_buttons']);
    foreach ($butts as $butt)
    {        $butt = explode(' ', $butt, 2);
        $burl = trim($butt[0]);
        $bcpt = trim($butt[1]);
        if (count($butt)>1 && qf_str_is_url($burl))
            $menu_buttons.=Visual('MAIN_MENU_BTN', Array('url'=>$burl, 'caption'=>$bcpt));
    }
}

print Visual('MAIN_MENU', Array('buttons' => $menu_buttons));
?>