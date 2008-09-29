<?php

// Simple InUSE Check
if ( !defined('QF_STARTED') )
        die('Hacking attempt');

global $QF_Config;

if ($QF_User->uid) {
   $tmpl=Array(
       'form_row' => $Vis['USER_GREET_ROW'],
       'greet' => ((!empty($QF_User->cuser['greet'])) ? $QF_User->cuser['greet'] : $lang['DEFAULT_GREET']).' '.$QF_User->uname,
       'flags' => '',
       );

   if ($QF_User->cuser['hasnewpm'])
       $tmpl['flags'].='<a href="index.php?st=mycabinet&amp;job=pms" title="'.$lang['U_HAVE_NEW_PM'].'">'.$Vis['NEWPM_FLAG'].'</a><br />';
   if ($QF_User->cuser['hasnewsubscr'])
       $tmpl['flags'].='<a href="index.php?st=fsearch&amp;mode=1&amp;by_subscr=1" title="'.$lang['U_HAVE_NEW_SUBS'].'">'.$Vis['NEWSUBS_FLAG'].'</a><br />';
} else {
   $tmpl=Array(
   'form_row' => $Vis['USER_LOGIN_ROW'],
   'greet' => $lang['GUEST_GREET'] );
};

print Vis_draw_Panel(Visual('ENTER_PANEL', $tmpl),$lang['GREET']);


?>