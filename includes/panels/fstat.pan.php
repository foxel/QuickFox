<?php

// Simple InUSE Check
if ( !defined('QF_STARTED') )
        die('Hacking attempt');

$forumstat_themes = $QF_Session->Cache_Get('forumstat');
if (is_null($forumstat_themes)) {
    $forumstat_themes = Array();
    $forumstat_posters = Array();
    $query = 'SELECT t.id, t.name, t.lasttime, u.id AS lastposter_id, t.lastposter, u.nick AS lastposter_nick
            FROM {DBKEY}topics t
            LEFT JOIN {DBKEY}users u ON (u.id = t.lastposter_id AND u.deleted = 0)
            LEFT JOIN {DBKEY}sections s ON (s.id = t.parent)';
    if ($QF_User->uid>0 && !$QF_User->admin)
        $query.= ' LEFT JOIN {DBKEY}acc_links al ON (s.acc_group=al.group_id AND al.user_id = '.$QF_User->uid.')
            WHERE t.minrights<='.$QF_User->level.' AND t.deleted = 0
            AND (al.user_id = '.$QF_User->uid.' || s.acc_group = 0 || t.parent = 0)';
    elseif (!$QF_User->admin)
        $query.= ' WHERE t.minrights = 0 AND t.deleted = 0 AND (s.acc_group = 0 || t.parent = 0)';
    else
        $query.= ' WHERE t.deleted = 0';

    $query.= ' ORDER BY t.lasttime DESC LIMIT 0, 10';
    if ( $result = $QF_DBase->sql_query($query) ) {        while ( $subtheme = $QF_DBase->sql_fetchrow($result))            $forumstat_themes[] = $subtheme;

        $QF_DBase->sql_freeresult($result);
    }
    $QF_Session->Cache_Add('forumstat', $forumstat_themes);
}

$toprint='';
foreach ( $forumstat_themes as $subtheme )
{
    $puser=$ulist->get($subtheme['lastposter_id'],$uli);
    $tmpl=Array(
        'id'      => $subtheme['id'],
        'caption' => $subtheme['name'],
        'time'    => create_date("", $subtheme['lasttime']) );
    $tmpl['user']=($subtheme['lastposter_id']) ? '<a href="index.php?st=info&amp;infouser='.$subtheme['lastposter_id'].'">'.$subtheme['lastposter_nick'].'</a>'
     : $subtheme['lastposter'];

    $toprint.=Visual('FORUMSTAT_ROW', $tmpl);
}
$tbl=Array( 'rows' => $toprint );

print Vis_Draw_panel(Visual('FORUMSTAT_TABLE',$tbl),$lang['FOR_LAST10_CAPT']);
?>
