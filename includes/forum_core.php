<?php

// Simple InUSE Check
if ( !defined('QF_STARTED') )
        die('Hacking attempt');

if ( defined('FORUM_CORE_LOADED') )
        die('Scripting error');

DEFINE ('FORUM_CORE_LOADED', TRUE);


// Gen Theme Icon
function Vis_Gen_TIcon($locked=false, $gotnew=false, $pinned=false, $url='')
{
    global $Vis;
    if (!empty($url)) {
        $in_a='<a href="'.$url.'" >';
        $out_a='</a>';
    }
    else {
        $in_a='';
        $out_a='';
    }
    return '<div style="position: relative">'.$in_a.(($locked) ? $Vis['IMG_BRANCH_LOCK'] : (
        ($gotnew) ? $Vis['IMG_BRANCH_NEW']
        : $Vis['IMG_BRANCH']
        )).$out_a.(($pinned) ? '<div style="position: absolute; left: 11px; top: -3px">'.$Vis['IMG_FOR_PINNED'].'</div>' : '').'</div>';
}

function For_Parse_History($text, $html=false)
{
        if ($html) return nl2br(preg_replace('#<\{\[time=([0-9\-]+)\] - ([\w\+\-\=\(\)\[\] ]+)\}>#e', '"[ <b>".create_date("","\\1")."</b> \\2 ] "', $text));
        else return ParseMSG(preg_replace('#<\{\[time=([0-9\-]+)\] - ([\w\+\-\=\(\)\[\] ]+)\}>#e', '"[b]".create_date("","\\1")."[/b] \\2"', $text));
}



class qf_forum
{
    var $ForumTree = Array();
    var $CurSection, $CurTopic;
    var $Window = '';
    var $base_url = '';

    function qf_forum()
    {
        Global $QF_User, $QF_Session, $QF_DBase;
        Global $QF_Config, $forconfig, $Forum_Root_Name;

        $forconfig = $QF_Config['forum'];
        LoadVisuals('forum');

        $this->base_url = 'index.php?st=section';

        $forum_sects = $QF_Session->Cache_Get('forumsects');

        if (is_null($forum_sects)) {

            $forum_sects = Array();

            $query='SELECT s.*, ag.name AS acc_group_name, ag.manager AS acc_group_man, al.user_id AS acc_user_id,
                SUM(t.posts) AS posts, COUNT(t.id) AS themes,
                SUBSTRING( MAX( CONCAT_WS(".", t.lasttime, t.id) ), 12) AS lasttheme
                FROM {DBKEY}sections s
                LEFT JOIN {DBKEY}topics t ON (t.parent = s.id AND t.minrights <= '.$QF_User->level.' AND t.deleted = 0)
                LEFT JOIN {DBKEY}acc_groups ag ON (s.acc_group=ag.id)
                LEFT JOIN {DBKEY}acc_links al ON (al.group_id=ag.id AND al.user_id = '.$QF_User->uid.')
                GROUP BY s.id ORDER BY s.order_id, s.id';

            if ( $result = $QF_DBase->sql_query($query) ) {
                $lthemes = Array();
                while ( $sect = $QF_DBase->sql_fetchrow($result))
                {
                    $sect['themes'] = intval($sect['themes']);
                    $sect['posts'] = intval($sect['posts']);
                    $sect['sects'] = 0;
                    $sect['unreads'] = 0;

                    $cur_access = ($sect['minrights'] <= $QF_User->level) || $QF_User->admin;
                    if (!empty($sect['acc_group_name'])) {
                         if (!$QF_User->uid)
                             $cur_access = false;
                         else
                             $cur_access = $cur_access && ($sect['acc_user_id']>0 || $sect['acc_group_man']==$QF_User->uid || $QF_User->admin);
                    }

                    $sect['curuser_access'] = $cur_access;
                    $forum_sects[$sect['id']] = $sect;
                    if (!empty($sect['lasttheme']))
                        $lthemes[] = $sect['lasttheme'];
                }
                $QF_DBase->sql_freeresult($result);

                $lthemes = array_unique($lthemes);
                if (count($lthemes)) {
                    $lthemes = implode(', ', $lthemes);
                    $query = 'SELECT t.id, t.parent, t.lasttime, u.id AS lastposter_id, u.nick AS lastposter_nick, t.lastposter, t.name
                        FROM {DBKEY}topics t
                        LEFT JOIN {DBKEY}users u ON (u.id = t.lastposter_id AND u.deleted = 0)
                        WHERE t.id IN ('.$lthemes.') GROUP BY t.parent';
                    if ( $result = $QF_DBase->sql_query($query) ) {
                        while ( $theme = $QF_DBase->sql_fetchrow($result))
                        {
                            $psect = &$forum_sects[$theme['parent']];
                            $psect['lasttime']=$theme['lasttime'];
                            $psect['lastposter']=$theme['lastposter'];
                            $psect['lastposter_id']=intval($theme['lastposter_id']);
                            $psect['lastthemename']=$theme['name'];
                            if ($theme['lastposter_id'])
                                $psect['lastposter']=$theme['lastposter_nick'];

                        }
                        $QF_DBase->sql_freeresult($result);
                    }
                }

                if ($QF_User->uid) {
                    // counting unreads
                    $query = 'SELECT t.parent, COUNT(t.id) as unreads
                        FROM {DBKEY}topics t
                        LEFT JOIN {DBKEY}reads r ON (r.theme = t.id AND r.user_id = '.$QF_User->uid.')
                        WHERE t.deleted = 0
                        AND (r.active = 0 OR r.active IS NULL)
                        AND t.minrights <= '.$QF_User->level.'
                        AND t.lasttime > '.$QF_User->cuser['regtime'].'
                        GROUP BY t.parent';
                    if ( $result = $QF_DBase->sql_query($query) ) {
                        while ( $ts = $QF_DBase->sql_fetchrow($result))
                        {
                            $psect = &$forum_sects[$ts['parent']];
                            $psect['unreads'] = $ts['unreads'];
                        }
                        $QF_DBase->sql_freeresult($result);
                    }
                }
            };

            $forum_sects[0]=Array('name' => $Forum_Root_Name,
                'minrights' => 0, 'postrights' => 5, 'id' => 0, 'curuser_access' => true);
            $forum_sects = $this->GenForumTree($forum_sects); // Global Forum Sections Tree

            $sect = end($forum_sects);
            do {
                if ($sect['curuser_access'] && !$sect['deleted'] && $sect['parent']) {
                    $psect = &$forum_sects[$sect['parent']];
                    $psect['sects']++;
                    $psect['themes']+= $sect['themes'];
                    $psect['posts']+= $sect['posts'];
                    $psect['unreads']+= $sect['unreads'];
                    if ($psect['lasttime'] < $sect['lasttime'] && $sect['lasttheme'] || !$psect['lasttheme']) {
                        $psect['lasttime'] = $sect['lasttime'];
                        $psect['lasttheme'] = $sect['lasttheme'];
                        $psect['lastposter'] = $sect['lastposter'];
                        $psect['lastposter_id'] = $sect['lastposter_id'];
                        $psect['lastthemename'] = $sect['lastthemename'];
                    }
                }
            } while ($sect = prev($forum_sects));

            $QF_Session->Cache_Add('forumsects', $forum_sects);
        }

        $this->ForumTree = $forum_sects;

        return true;
    }

    function Draw_Forum()
    {
        global $QF_Pagedata, $QF_Config, $lang;

        $QF_Pagedata['META'].= "\n".'<link rel="alternate" type="application/rss+xml" title="'.HTMLStrVal(sprintf($lang['RSS_TITLE_LAST_MSGS'], $QF_Config['site_name'])).'" href="index.php?sr=RSS" >';

        return Visual('FORUM_WINDOW', Array( 'fastjumper' => $this->FastJumper(), 'window' => $this->Window ) );
    }

    // compiles FastJumper
    function FastJumper() {
        Static $jumper;
        if (!$jumper) {
                  $tmpl=Array();
                  foreach ($this->ForumTree as $ss)
                      if ($ss['curuser_access'] && !$ss['deleted'])
                          $tmpl['jumper_options'].='<option value="'.$ss['id'].'" '.(($this->CurSection['id']==$ss['id']) ? ' SELECTED':'').'>'.$ss['pref'].$ss['name']."</option>\n";

                  $jumper=Visual('FORUM_JUMPER', $tmpl);
        }

        return $jumper;
    }

    function getparline($id, $used=Array())
    {
        $id = intval($id);

        $workspace = '';

        $sect = end($this->ForumTree);
        do {
            if ($sect['id']==$id && !is_null($sect['id'])) {
                $workspace = '<a href="'.$this->base_url.'&amp;section='.$sect['id'].'"><< '.$sect['name'].'</a> '.$workspace;
                $id = $sect['parent'];
            }
        } while ($sect = prev($this->ForumTree));

        return $workspace;
    }

    // Generates forum tree array
    function GenForumTree($sects, $rootsect=0)
    {
        global $Forum_Root_Name, $QF_User;
        $tree = Array();
        $sects[0]=Array('name' => $Forum_Root_Name,
            'minrights' => 0, 'postrights' => 5, 'id' => 0, 'curuser_access' => true);
        $sect = $sects[$rootsect];
        if (!empty($sect)) {
               $sect['level']=0;
               $tree[$sect['id']]=$sect;
               Unset($sects[$rootsect]);
               $this->AddChilds($sects, $tree, $rootsect);
        }
        Return $tree;
    }

    // a _GenForumTree_ subfunction
    function AddChilds(&$sects, &$tree, $rootsect, $pref=' + ', $level=0, $deleted=0)
    {

        $level++;
        foreach($sects as $sn=>$sect) {
            if ($sect['parent']==$rootsect && $sect['id']) {
                    if ($deleted)
                        $sect['deleted']=$deleted;
                    $sect['pref']=$pref;
                    $sect['level']=$level;
                    $tree[$sect['id']]=$sect;
                    Unset($sects[$sn]);
                    $this->AddChilds($sects, $tree, $sect['id'], $pref.' -> ', $level, $sect['deleted']);
            }
        }
        reset($sects);
    }
}

$sect_inc_url = 'index.php?st=section';
$topic_inc_url = 'index.php?st=branch';
Connect_JS('forum');
