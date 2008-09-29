<?php

// Simple InUSE Check
if ( !defined('QF_STARTED') )
        die('Hacking attempt');

// additional forum functions are rpresented in separated file
include 'includes/forum_core.php';
$QF_Forum = new qf_forum();

$sect_id = Get_Request('section', 1, 'i');
$cur_sect_posts_num = 0;
$cur_sect_topics_num = 0;

$QF_Forum->CurSection = $QF_Forum->ForumTree[$sect_id];
$cur_sect = &$QF_Forum->CurSection;

$SectPrint = Array();

if (empty($cur_sect))
    $QF_Forum->Window = Vis_Err_String($lang['ERR_NO_SECT']);
elseif (!$cur_sect['curuser_access'])
    $QF_Forum->Window = Vis_Err_String($lang['ERR_SECT_NO_RIGHTS']);
else {

    $Page_SubTitle = $cur_sect['name'];
    $SectPrint['caption'] = $cur_sect['name'];

    if ($cur_sect['id']>0)
    {
        $psect=&$QF_Forum->ForumTree[$cur_sect['parent']];

        if (!$psect)
            $psect = Array('name' => $Forum_Root_Name,
                'minrights' => 0, 'postrights' => 5, 'id' => 0, 'curuser_access' => true);

        $SectPrint['forumer']=$QF_Forum->getparline($cur_sect['id']);
    }

    $Sects_List='';
    if (count($QF_Forum->ForumTree))
        foreach($QF_Forum->ForumTree as $subsect)
        {
            if ($subsect['parent'] == $sect_id && $subsect['id'] && ($showdels || !$subsect['deleted']) && $subsect['curuser_access']) {

                $cur_sect_posts_num += $subsect['posts'];
                $cur_sect_topics_num += $subsect['themes'];

                $tmpl=Array(
                    'labels'    => '<a name="s'.$subsect['id'].'"></a>',
                    'url'       => $sect_inc_url.'&amp;section='.$subsect['id'],
                    'caption'   => $subsect['name'],
                    'descr'     => $QF_Parser->parse_mess($subsect['descr']),
                    'sects'     => $subsect['sects'],
                    'themes'    => $subsect['themes'],
                    'posts'     => $subsect['posts'],
                    'rights'    => Vis_Gen_Rights($subsect['minrights']),
                    'acc_group' => $subsect['acc_group_name'],
                    'img'       => ($subsect['unreads']) ? $Vis['IMG_SECT_NEW'] : $Vis['IMG_SECT'] );

                if ($subsect['deleted'])
                    $tmpl['deleted']='1';
                if (($subsect['unreads']))
                    $tmpl['unreads'] = $subsect['unreads'];

                if ($subsect['lasttheme']>0) {
                    $tmpl['lastth']='<a href="'.$topic_inc_url.'&amp;branch='.$subsect['lasttheme'].'&amp;shownew=1#unread" title="'.$subsect['lastthemename'].'">'.STrim($subsect['lastthemename'],20).'</a>';
                    $tmpl['lastt']='<a href="'.$topic_inc_url.'&amp;branch='.$subsect['lasttheme'].'&amp;page=last#last">'.create_date('', $subsect['lasttime']).'</a>';

                    $tmpl['lastu']=($subsect['lastposter_id']) ? '<a href="index.php?st=info&amp;infouser='.$subsect['lastposter_id'].'">'.$subsect['lastposter'].'</a>'
                     : $subsect['lastposter'];
                }

                $Sects_List.=Visual('FOR_SECTION_ROW', $tmpl);
            }
        }
    if (!empty($Sects_List))
        $SectPrint['sections']=Vis_Draw_Fliper(Visual('FOR_SECTIONS', Array('content'=>$Sects_List)),$lang['FOR_SECTIONS']);

    $query = 'SELECT t.*, r.active AS is_read
        FROM {DBKEY}topics t
        LEFT JOIN {DBKEY}reads r ON (t.id=r.theme AND r.user_id='.$QF_User->uid.')
        WHERE t.parent = '.$sect_id.' AND t.special = 0 AND t.minrights<='.$QF_User->level;
    $query.=($showdels==0) ? ' AND t.deleted = 0' : '';
    $query.=' ORDER BY t.pinned DESC, t.lasttime DESC ';

    $SubTopics = Array();
    $NeedUsers = Array();
    if ($result = $QF_DBase->sql_query($query)) {
        while ($topic = $QF_DBase->sql_fetchrow($result)) {
            $SubTopics[] = $topic;
            $NeedUsers[] = intval($topic['author_id']);
            $NeedUsers[] = intval($topic['lastposter_id']);
        }
        $QF_DBase->sql_freeresult($result);
    }

    // prepare the list of users
    if (count($NeedUsers)) {
        $NeedUsers = Array_Unique($NeedUsers);
        $NeedUsers = implode(', ', $NeedUsers);
        $ulist->load(' WHERE u.id IN ('.$NeedUsers.')');
    }

    if (count($SubTopics)) {
        $SubThemes_List = '';
        foreach($SubTopics as $SubTopic)
        {
            $cur_sect_posts_num += $SubTopic['posts'];
            $cur_sect_topics_num ++;

            $unread = (!$SubTopic['is_read'] && !$SubTopic['deleted'] && $QF_User->uid);
            if ($unread && $SubTopic['lasttime'] < $QF_User->cuser['regtime'])
                $unread = false;

            $url = $topic_inc_url.'&amp;branch='.$SubTopic['id'];
            $new_url = $url.'&amp;shownew=1#unread';
            $last_url = $url.'&amp;page=last#last';

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

                );

            if ($unread) {
                $tmpl['flags'] = $Vis['NEW_FLAG'];
                $tmpl['url'] = $new_url;
            }
            elseif ($QF_User->uid)
                $tmpl['url'] = $last_url;
            else
                $tmpl['url'] = $url;

            $SubThemes_List.=Visual('FOR_THEME_ROW', $tmpl);

        }

        $SectPrint['themes']= Vis_Draw_Fliper(Visual('FOR_THEMES', Array('content'=>$SubThemes_List)),$lang['FOR_THEMES']);
    }


    if (!empty($cur_sect) && ($cur_sect['postrights']<=$QF_User->level)) {

        $tmpl=Array(
            'formstart' => '<form name="addtheme" action="index.php" method="post">',
            'formend'   => '</form>',
            'caption'   => $lang['FOR_NEW_THEME'].":",
            'image'     => Vis_Gen_TIcon(),
            'user'      => $QF_User->uname,
            'u_descr'   => $QF_User->cuser['descr'],
            'u_rights'  => Vis_Gen_Rights($QF_User->level,' '),
            'header'    => create_date("", $timer->time),
            'avatar'    => Vis_Gen_Avatar($QF_User->cuser, true)
            );


        $form=Array(
            'st'       => $QF_Inc,
            'page'     => $page,
            'sid'      => intval($cur_sect['id']),
            'formname' => 'addtheme',
            'user'     => $QF_User->uname,
            'mrights_options' => '',
            'prights_options' => '',
            );

        for ($stt=$cur_sect['minrights']; $stt<=$QF_User->level; $stt++) {
            $form['mrights_options'].='<option value="'.$stt.'" >'.(($stt>0) ? $stt : $lang['FOR_ALL']).'</option>';
            $form['prights_options'].='<option value="'.$stt.'" '.(($stt==1) ? 'SELECTED' : '').'>'.(($stt>0) ? $stt : $lang['FOR_ALL']).'</option>';
        }

        if ($QF_User->uid)
            $form['fixuser']='true';

        $tmpl['body']=Visual('THEME_NEW_FORM', $form);

        $SectPrint['newtheme']= Vis_Draw_Fliper(Visual('THEME_BODY', $tmpl),$lang['FOR_NEW_THEME'],'',True);
    }

    if ($sect_id == 0)
    {
        $query = 'SELECT SUM(posts), COUNT(id)
            FROM {DBKEY}topics WHERE deleted = 0';
        if ( $result = $QF_DBase->sql_query($query) )
            list($cur_sect_posts_num, $cur_sect_topics_num) = $QF_DBase->sql_fetchrow($result, false);
        $SectPrint['Sub_Line'] = sprintf($lang['FOR_ROOT_COUNTS'], $cur_sect_posts_num, $cur_sect_topics_num);
    }

    $QF_Forum->Window = Visual('SECT_MAIN', $SectPrint);
}

print $QF_Forum->Draw_Forum();
?>