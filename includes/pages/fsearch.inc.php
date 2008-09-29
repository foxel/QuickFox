<?php

// Simple InUSE Check
if ( !defined('QF_STARTED') )
        die('Hacking attempt');

// additional forum functions are rpresented in separated file
include 'includes/forum_core.php';
$QF_Forum = new qf_forum();

$find_mode = Get_Request('mode', 1, 'i');
$find_what = strtolower(Get_Request('string', 1, 'ht'));

if ($find_mode == 2 && strlen($find_what)<3)
    $find_mode = 0;

$acc_forums_list = Array(0);
foreach ($QF_Forum->ForumTree as $Forum)
{    if ($Forum['curuser_access'])
        $acc_forums_list[] = $Forum['id'];
}

$acc_forums_list = implode(', ', array_unique($acc_forums_list));

$SubTopics = Array();
$NeedUsers = Array();
$PIDs      = Array();

$SectPrint = Array();

switch ($find_mode)
{    case 1: // unread search
        if (!$QF_User->uid)
            break;
        $query = 'SELECT t.*, r.active AS is_read FROM {DBKEY}topics t
        LEFT JOIN {DBKEY}reads r ON (t.id=r.theme AND r.user_id='.$QF_User->uid.')
        WHERE t.deleted = 0 AND (r.active = 0 OR r.active IS NULL)
        AND t.parent IN ('.$acc_forums_list.') AND t.minrights<='.$QF_User->level;
        if (!Get_Request('fulllist', 1, 'b'))
        {
            $query.= ' AND t.lasttime >= '.$QF_User->cuser['regtime'];
            if (Get_Request('by_subscr', 1, 'b'))
                $query.= ' AND r.subscribe = 1';
        }

        $query.= ' ORDER BY t.lasttime DESC ';
        if ($result = $QF_DBase->sql_query($query)) {
            while ($topic = $QF_DBase->sql_fetchrow($result)) {
                $SubTopics[] = $topic;
                $NeedUsers[] = intval($topic['author_id']);
                $NeedUsers[] = intval($topic['lastposter_id']);
            }
            $QF_DBase->sql_freeresult($result);
        }
        $SectPrint['caption'] = $lang['FOR_SEARCH_UNREAD'];
        break;
    case 2: // string search
        $topics_list = Array();
        $prequery = 'SELECT MIN(id) AS pid, theme FROM {DBKEY}posts
            WHERE text LIKE "%'.addslashes($find_what).'%"
            GROUP BY theme';
        if ($result = $QF_DBase->sql_query($prequery)) {
            while ($post = $QF_DBase->sql_fetchrow($result)) {
                $topics_list[] = $post['theme'];
                $PIDs[$post['theme']] = $post['pid'];
            }
            $QF_DBase->sql_freeresult($result);
        }
        if (count($topics_list))
        {            $topics_list = implode(', ', $topics_list);
            $query = 'SELECT t.*, r.active AS is_read FROM {DBKEY}topics t
            LEFT JOIN {DBKEY}reads r ON (t.id=r.theme AND r.user_id='.$QF_User->uid.')
            WHERE t.deleted = 0 AND t.id IN ('.$topics_list.')
            AND t.parent IN ('.$acc_forums_list.') AND t.minrights<='.$QF_User->level.'
            ORDER BY t.lasttime DESC ';
            if ($result = $QF_DBase->sql_query($query)) {
                while ($topic = $QF_DBase->sql_fetchrow($result)) {
                    $SubTopics[] = $topic;
                    $NeedUsers[] = intval($topic['author_id']);
                    $NeedUsers[] = intval($topic['lastposter_id']);
                }
                $QF_DBase->sql_freeresult($result);
            }
            $SectPrint['caption'] = sprintf($lang['FOR_SEARCH_STRING'], $find_what) ;
            $SectPrint['search_str'] = $find_what;
        }
        break;
    default:
        $SectPrint['caption'] = $lang['ERR_NO_SEARCH_REQUEST'];
}

if (count($SubTopics)) {
    // prepare the list of users
    if (count($NeedUsers)) {
        $NeedUsers = Array_Unique($NeedUsers);
        $NeedUsers = implode(', ', $NeedUsers);
        $ulist->load(' WHERE u.id IN ('.$NeedUsers.')');
    }

    $SubThemes_List = '';
    foreach($SubTopics as $SubTopic)
    {
            $unread = (!$SubTopic['is_read'] && !$SubTopic['deleted'] && $QF_User->uid);

            $url = $topic_inc_url.'&amp;branch='.$SubTopic['id'];
            if ($pid = $PIDs[$SubTopic['id']])
                $url .= '&amp;postshow='.$pid.'#'.$pid;
            else
                $url .= '&amp;shownew=1#unread';

            $auser=$ulist->get($SubTopic['author_id']);
            $lpuser=$ulist->get($SubTopic['lastposter_id']);

            $tmpl = Array(
                'labels'  => '<a name="'.$SubTopic['id'].'"></a>',
                'caption' => $SubTopic['name'],
                'img'     => Vis_Gen_TIcon($SubTopic['locked'], $unread, $SubTopic['pinned'] ),
                'posts'   => $SubTopic['posts'],
                'rights'  => Vis_Gen_Rights($SubTopic['minrights']),
                'author'  => ($auser) ? '<a href="index.php?st=info&amp;infouser='.$auser['id'].'">'.$auser['nick'].'</a>' : $SubTopic['author'],
                'lastt'   => '<a href="'.$last_url.'">'.create_date('', $SubTopic['lasttime']).'</a>',
                'lastu'   => ($lpuser) ? '<a href="index.php?st=info&amp;infouser='.$lpuser['id'].'">'.$lpuser['nick'].'</a>' : $SubTopic['lastposter'],
                'deleted' => ($subtheme['deleted']) ? '1' : '',
                'url'     => $url,
                'flags'   => ($unread) ? $Vis['NEW_FLAG'] : '',
                );

            $SubThemes_List.=Visual('FOR_THEME_ROW', $tmpl);

    }

    $SectPrint['themes']= Vis_Draw_Fliper(Visual('FOR_THEMES', Array('content'=>$SubThemes_List)),$lang['FOR_THEMES']);
}
else
    $SectPrint['themes'] = Vis_Err_String($lang['ERR_NO_SEARCH_RESULT']);

$QF_Forum->Window = Visual('FSEARCH_MAIN', $SectPrint);

print $QF_Forum->Draw_Forum();
?>