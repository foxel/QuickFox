<?php

// Simple InUSE Check
if ( !defined('QF_STARTED') )
        die('Hacking attempt');

//
// Preinit
//
// additional forum functions are represented in separated file
include 'includes/forum_core.php';
$QF_Forum = new qf_forum();

$topic_id = Get_Request('branch', 1, 's');

if ($topic_id == 'gtree' && $forconfig['guest_book']>0)
    $topic_id = $forconfig['guest_book'];
else
    $topic_id = intval($topic_id);

$t_shownew   = Get_Request('shownew', 1, 'b');
$t_subscribe = Get_Request('subscribe', 1);
$t_page      = Get_Request('page', 1, 's');

$t_editpost  = Get_Request('editpost', 1, 'i');
$t_edittheme = Get_Request('edittheme', 1, 'b');
$t_moderate  = Get_Request('moderate', 1, 'b');

$t_postfind  = Get_Request('postfind', 1, 'i');
$t_postdel   = Get_Request('postdel', 1, 'i');
$t_postshow  = Get_Request('postshow', 1, 'i');

$t_history   = Get_Request('history', 1, 'i');

if ($t_editpost) $t_history=$t_editpost;

$BranchPrint=Array();

//
// loading branch data
//
if(!$topic_id && !$t_postfind)
    $BranchPrint['content']= Vis_Err_String($lang['ERR_THEME_NOTSET']);
else
{
    if ($t_postdel>0) {
        include_once ('includes/forum_scr.php');
        $forum_upd = New qf_forum_upd();
        $forum_upd->del_message ($t_postdel, $topic_id);
    }

    $query=($topic_id) ? 'SELECT * FROM {DBKEY}topics WHERE id = '.$topic_id
        : 'SELECT t.* FROM {DBKEY}topics t, {DBKEY}posts p WHERE t.id = p.theme AND p.id = '.$t_postfind;
    if ($result = $QF_DBase->sql_query($query)) {
        $topic = $QF_DBase->sql_fetchrow($result);
        $QF_DBase->sql_freeresult($result);
    }

    if (!is_array($topic))
        $BranchPrint['content']= Vis_Err_String($lang['ERR_THEME_LOST']);
    else {
        $QF_Forum->CurSection = $QF_Forum->ForumTree[$topic['parent']];
        $cur_sect = &$QF_Forum->CurSection;
        $topic_id = $topic['id'];
    }
}

if (is_array($topic))
{
    // Curuser permission calc
    if (!$cur_sect['curuser_access'])
        $t_curaccess = 0;
    elseif ($QF_User->uid) {
        if ($QF_User->admin || ($QF_User->cuser['modlevel']>=$topic['postrights'] && $QF_User->cuser['modlevel']>0))
            $t_curaccess = 3;
        elseif ($QF_User->wlevel >= $topic['postrights'] && !$topic['locked'])
            $t_curaccess = 2;
        elseif ($QF_User->level >= $topic['minrights'])
            $t_curaccess = 1;
        else
            $t_curaccess = 0;
    }
    elseif ($topic['minrights']>0)
        $t_curaccess = 0;
    elseif ($topic['postrights']>0 || $topic['locked'])
        $t_curaccess = 1;
    else
        $t_curaccess = 2;

    // ---

    // Related Vars
    if ($topic['deleted']) {        $t_edittheme = true;
        $t_moderate  = false;
        $t_editpost  = 0;
        $t_postdel   = 0;
    }

    $t_posts_per_page = $forconfig['posts_per_page'];

    if ($topic['id'] == $forconfig['guest_book'])
        $t_reverssorting=1;

    if ($t_curaccess<3) {        $t_moderate = false;
        $t_postdel  = 0;
    }
    if ($t_moderate==1) {        $t_edittheme = 0;
        $t_editpost  = 0;
        $t_postdel   = 0;
    }
    elseif ($t_postdel>0) {        $t_editpost = 0;
        $t_postshow = $t_postdel;
    }
    elseif ($t_editpost>0) {        $t_postshow = $t_editpost;
    }
    elseif ($t_postfind>0) {        $t_postshow = $t_postfind;
    }

    // ---

    if (($topic['deleted']==1 && $t_curaccess < 3) || ($t_curaccess < 1))
        $BranchPrint['content']= Vis_Err_String($lang['FOR_THEME_UNAVAILABLE']);
    else
    {
        if ($topic['minrights'] <= 2 && !$cur_sect['acc_group_name'])
            $QF_Pagedata['META'].= "\n".'<link rel="alternate" type="application/rss+xml" title="'.htmlspecialchars(sprintf($lang['RSS_TITLE_TOPIC_MSGS'], $QF_Config['site_name'], $topic['name'])).'" href="index.php?sr=RSS&amp;topic='.$topic_id.'" >';

        $Page_SubTitle = $topic['name'];

        $BranchPrint['caption'] = $topic['name'];

        if (!$topic['special']) {
            $BranchPrint['forumer'] = $QF_Forum->getparline($cur_sect['id']);
        }

        // Calculating postreads data
        $query = 'SELECT r.*, COUNT(p.id) AS readposts
            FROM {DBKEY}reads r
            LEFT JOIN {DBKEY}posts p ON (p.id <= r.lastread AND p.theme = '.$topic_id.' AND p.deleted = 0)
            WHERE r.theme = '.$topic_id.' AND r.user_id = '.$QF_User->uid.'
            GROUP BY r.theme';
        if ($result = $QF_DBase->sql_query($query)) {
            $readp = $QF_DBase->sql_fetchrow($result);
            $QF_DBase->sql_freeresult($result);
        }
        else
            $readp = null;

        if ($readp['user_id'] != $QF_User->uid)
        {            $query = 'SELECT MAX(p.id) as lastread, COUNT(p.id) AS readposts
                FROM {DBKEY}posts p
                WHERE p.time < '.$QF_User->cuser['regtime'].' AND p.theme = '.$topic_id.' AND p.deleted = 0';
            if ($result = $QF_DBase->sql_query($query)) {
                $readp = $QF_DBase->sql_fetchrow($result);
                $readp['active'] = ($readp['lastread'] == $topic['MaxID']) ? 1 : 0;
                $QF_DBase->sql_freeresult($result);
            }
        }

        $t_lastread   = intval($readp['lastread']);
        $t_posts_read = intval($readp['readposts']);
        $t_readed     = ($readp['active']) ? true : false;

        if (is_null($t_subscribe))
            $t_subscribe=$readp['subscribe'];
        else
            $t_subscribe=intval($t_subscribe);

        $t_postsnum = $topic['posts'];

        // calculating post num to show if there is no setted page
        if ($t_shownew && !$QF_User->uid)
            $t_seekpost_no = $t_postsnum;
        elseif ($t_postshow>0) {
            $query = 'SELECT COUNT(p.id) AS seekpost FROM {DBKEY}posts p WHERE p.theme = '.$topic_id.'
                AND p.id <= '.$t_postshow.' AND p.deleted = 0 ';
            if ($result = $QF_DBase->sql_query($query)) {
                $seekp = $QF_DBase->sql_fetchrow($result);
                $QF_DBase->sql_freeresult($result);
            }
            else
                $seekp = null;
            $t_seekpost_no = $seekp['seekpost'];
        }
        elseif ($t_shownew==1) {
            $t_seekpost_no = ($t_lastread) ? ($t_posts_read+1) : 0;
        }
        else
            $t_seekpost_no = null;
        // ---

        // Loading History Data
        if ($t_history) {
            $query = 'SELECT * FROM {DBKEY}parchive WHERE id = '.$t_history;
            if ($result = $QF_DBase->sql_query($query)) {                $t_archive = $QF_DBase->sql_fetchrow($result);
                $QF_DBase->sql_freeresult($result);
            }
            if (!is_array($t_archive))
                $t_history = null;
        }
        // ---

        // total pages count
        $t_pages = floor(($t_postsnum - 1) / $t_posts_per_page) + 1;

        if ($t_pages < 1)
            $t_pages = 1;

        // Calculating what page to show
        if ($t_seekpost_no) {
            $t_seekpost_no = ($t_seekpost_no>$t_postsnum) ? $t_postsnum : $t_seekpost_no;
            $t_seekpost_no = ($t_reverssorting) ? ($t_postsnum - $t_seekpost_no + 1) : $t_seekpost_no;
            $t_page = floor(($t_seekpost_no-1)/$t_posts_per_page) + 1;
        }
        elseif ($t_page=='last')
            $t_page = ($t_reverssorting) ? 0 : $t_pages;
        else
            $t_page = intval($t_page);

        if($t_page < 1)
            $t_page = 1;
        elseif($t_page > $t_pages)
            $t_page = $t_pages;

        // first post to show
        if ($t_reverssorting)
            $t_seek = ($t_pages - $t_page)*$t_posts_per_page;
        else
            $t_seek = ($t_page - 1)*$t_posts_per_page;

        if ($t_seek < 0)
            $t_seek = 0;

        $Base_Link = $topic_inc_url.'&amp;branch='.$topic_id.'&amp;page='.$t_page;


        if (!$topic['deleted'])
        {
            if ($t_pages>=10)
            {
                $draw_pages='|';
                $pp=False;
                for($stt=1; $stt<=$t_pages; $stt++)
                {
                    if ($stt==1 || $stt==$t_pages || Abs($stt-$t_page)<3) {
                        if ($stt!=$t_page)
                            $draw_pages.=' <a class="pglink" href="'.$topic_inc_url.'&amp;branch='.$topic_id.'&amp;page='.$stt.'#theme" title="'.$lang['PAGE_NO'].' '.$stt.'">'.$stt.'</a> |';
                        else
                            $draw_pages.='<b>['.$stt.']</b>|';
                        $pp=True;
                    }
                    elseif ($pp)
                    {
                        $draw_pages.=' ... |';
                        $pp=False;
                    }
                }
            }
            elseif ($t_pages>1)
            {
                $draw_pages='|';
                for($stt=1; $stt<=$t_pages; $stt++)
                {
                    if ($stt != $t_page)
                        $draw_pages.=' <a class="pglink" href="'.$topic_inc_url.'&amp;branch='.$topic_id.'&amp;page='.$stt.'#theme" title="'.$lang['PAGE_NO'].' '.$stt.'">'.$stt.'</a> |';
                    else
                        $draw_pages.='<b>['.$stt.']</b>|';
                }
            }
            else
                $draw_pages = '';

            $BranchPrint['pages']=$draw_pages;



            // Loading posts
            $query = 'SELECT p.*, ch.ch_text AS parsed_post FROM {DBKEY}posts p
                    LEFT JOIN {DBKEY}posts_cache ch ON (ch.ch_id = p.id)
                    WHERE p.theme = '.$topic_id.' AND p.deleted = 0
                    ORDER BY p.id ASC LIMIT '.$t_seek.', '.($t_posts_per_page + 1);
            $presult = $QF_DBase->sql_query($query);

            $theme_posts = Array();
            $pusers_list = Array();
            $atts        = Array();

            $sel_deleted_query = 'SELECT * FROM {DBKEY}posts p WHERE p.theme = '.$topic_id.' AND p.deleted = 1 ';

            //$postshow

            if (!empty($presult))
            {
                $t_posts_loaded = $QF_DBase->sql_numrows($presult);
                $t_lastpost = min($t_posts_loaded, $t_posts_per_page);
                if ($QF_User->uid)
                    $t_unr_post = ($t_readed) ? $t_lastpost : ($t_posts_read - $t_seek + 1);
                else
                    $t_unr_post = $t_lastpost;

                $counter     = 0;
                $postid_list = Array();

                while ( $post = $QF_DBase->sql_fetchrow($presult))
                {                    if ($counter == 0)
                        $sel_deleted_query.=' AND p.id > '.$post['id'];

                    $counter++;

                    if ($counter > $t_posts_per_page)
                        $sel_deleted_query.=' AND p.id < '.$post['id'];
                    else
                    {
                        $post['labels']='';
                        $post['posted_pos']=$t_seek + $counter;

		    	        if ($counter == 1)
        		    	    $post['labels'].='<a name="first"></a>';
		            	elseif ($counter == $t_lastpost)
		    	            $post['labels'].='<a name="last"></a>';

                        if ( $counter == $t_unr_post )
                            $post['labels'].='<a name="unread"></a>';

                        $postid_list[]=$post['id'];
                        $pusers_list[]=$post['author_id'];

                        $theme_posts[]=$post;
                    }
                }
                $QF_DBase->sql_freeresult($presult);

                // let's add deleted posts
                if ($t_curaccess>=3)
                {                    $dresult = $QF_DBase->sql_query($sel_deleted_query);
                    if ($dresult) {                        while ( $post = $QF_DBase->sql_fetchrow($dresult))
                        {
                            $post['labels']='';

                            $postid_list[]=$post['id'];
                            $pusers_list[]=$post['author_id'];

                            $theme_posts[]=$post;
                        }
                        $QF_DBase->sql_freeresult($dresult);
                    }

                    function branch_posts_sort ($a, $b)
                    {
                        return ( $a['id'] < $b['id'] ) ? -1 : 1;
                    }

                    uasort($theme_posts, 'branch_posts_sort');
                }

                $postid_list = implode(', ',$postid_list); // creating list string for attaches query

                if ($postid_list) {                    $attquery = 'SELECT * FROM {DBKEY}files WHERE att_to IN ('.$postid_list.') ORDER BY file';
                    $attresult = $QF_DBase->sql_query($attquery);
                    if ($attresult) {
                        While ($attach = $QF_DBase->sql_fetchrow($attresult))
                        {                            $atts[$attach['att_to']][]=$attach;
                        }
                        $QF_DBase->sql_freeresult($attresult);
                    }
                }

            }

            // Loading related users
            if (count($pusers_list)) {
                $pusers_list = implode(', ',Array_Unique($pusers_list)); // creating list string for attaches query

                $ulist->load(' WHERE u.id IN ('.$pusers_list.')');
            }
            // ---


            if (count($theme_posts)) {
                $t_MAXID = 0;
                $dont_cache_posts = $QF_Config['cache']['visuals_nocache'];
                Foreach ($theme_posts as $post)
                {

                    $MB = Array();
                    $showthisone = ($t_editpost==$post['id'] || $t_postshow==$post['id']);

                    $puser = $ulist->get($post['author_id']);

                    if ($post['deleted'] && !$showthisone && !$t_showdels) {                        $tmpl=Array(
                            'showlink' => $Base_Link.'&amp;postshow='.$post['id'].'#'.$post['id'],
	                        'labels'   => '<a name="'.$post['id'].'"></a>'.$post['labels'],
	                        'flags'    => ($post['id']>$t_lastread && $QF_User->uid) ? $Vis['NEW_FLAG'].' ' :'' ,
        	                'time'     => create_date("", $post['time']) ,
	                        'user'     => ($puser) ? '<a href="index.php?st=info&amp;infouser='.$puser['id'].'">'.$puser['nick'].'</a>' : $post['author'] ,
                            );

                        if ($post['ctime']>$post['time'] && !empty($post['changer'])) {
	                        $tmpl['postedited']=sprintf($lang['FOR_POST_DELETED'],$post['changer']).' '.create_date('', $post['ctime']);
        	            }

	                    $curpost_data =  Visual('DEL_POST_MINIINFO', $tmpl);
                    }
                    else {

        	            $Can_Edit_Post = ($t_curaccess>=3) || ($QF_User->uid == $post['author_id'] && $QF_User->uid);

	                    if ($t_editpost == $post['id'])
	                        $Open_Edit_Post = $Can_Edit_Post;
        	            else
	                        $Open_Edit_Post = False;

	                    if ($post['id']>$t_MAXID)
        	                $t_MAXID = $post['id'];

        	            $tmpl=Array(
	                        'post_link' => ($post['posted_pos']) ? '<a href="'.$topic_inc_url.'&amp;branch='.$topic_id.'&amp;postshow='.$post['id'].'#'.$post['id'].'">#'.$post['posted_pos'].'</a>' : '',
	                        'labels'    => '<a name="'.$post['id'].'"></a>'.$post['labels'],
        		            'avatar'    => Vis_Gen_Avatar($puser),
	                        'u_descr'   => ($puser) ?  $puser['descr'] : $lang['GUEST'],
	                        'u_rights'  => Vis_Gen_Rights($puser['rights'],' '),
        	                'u_status'  => ($puser['modlevel']>=$topic['postrights'] && $puser['modlevel']>0 && $puser['active']) ? $lang['MODERATOR'] : '' ,
	                        'flags'     => ($post['id']>$t_lastread && $QF_User->uid) ? $Vis['NEW_FLAG'].' ' :'' ,
	                        'time'      => create_date('', $post['time']) ,
        	                'user'      => ($puser) ? '<a href="index.php?st=info&amp;infouser='.$puser['id'].'">'.$puser['nick'].'</a>' : $post['author']
	                        );


        	            if ($post['deleted']) $tmpl['class']='deleted';
	                    //If ($puser['admin']) $tmpl['u_status']=$lang['ADMINISTRATOR'];

        	            if ($Open_Edit_Post) {
	                        $tmpl['formstart']='<form name="editmsg" action="index.php" method="post" enctype="multipart/form-data">';
	                        $tmpl['formend']='</form>';
        	                $form=Array(
	                            'st'      => $QF_Inc,
	                            'page'    => $t_page,
	                            'id'      => $post['id'],
        	                    'tid'     => $topic['id'],
	                            'user'    => $QF_User->uname,
	                            'text'    => $post['text'],
                                'formname' => 'editmsg',
	                            );

        	                if ($post['deleted'])
	                            $form['delmessage']='checked';
	                        if ($t_curaccess>=3 && (!$post['ctime'] || $post['changer'] == $QF_User->uname))
	                            $form['canhide']='true';

        	                $form['unattach']='';
	                        if (is_array($atts[$post['id']]))
	                            foreach ($atts[$post['id']] as $att)
	                                {
                	                    $Can_Edit_Att = ($QF_User->cuser['modlevel']>=$att['rights'] && $t_curaccess>=3) || ($QF_User->uid==$att['user_id'] && $QF_User->uid);

        	                            $atmpl=Array(
	                                        'fid'  => $att['id'],
	                                        'url'  => 'index.php?st=getfile&amp;file='.$att['id'],
	                                        'src'  => 'index.php?sr=thumb&amp;fid='.$att['id'],
	                                        'capt' => $att['caption'],
        	                                'size' => round($att['size']/1024,2).' Kb.',
	                                        );


        	                            if ($Can_Edit_Att)
	                                        $form['unattach'].=Visual('POST_EDIT_UNATTACH', $atmpl);
	                                }

        	                $content='';
	                        if ($QF_User->wlevel >= $QF_Config['post_files_rights'])
	                        {
	                            for( $fdx=1; $fdx<=$forconfig['post_upl_files']; $fdx++)
        	                        $content.=Visual('FILE_ROW', Array( 'id' => $fdx ));

	                            if ($forconfig['post_upl_files']>0)
	                                $form['files']=Vis_Draw_panel('<table class="border fullwidth noborder">'.$content.'</table>', $lang['FOR_POST_FILES'],'99%',True);
        	                }


                            if (($t_history==$post['id']) && $Can_Edit_Post)
                                $form['history']=Vis_Draw_Panel('<div class="genmed autoscroll">'.For_Parse_History($t_archive['content'],True).'</div>',$lang['FOR_POST_HISTORY'],'99%',true,true);

        	                $tmpl['formbody']= Visual('POST_EDIT_FORM', $form);

	                        $MB['editorbutts']=Visual('FOR_EDIT_BUTTS',
	                            Array (
        	                        'formname' => 'editmsg',
	                                'cancel_url' => $Base_Link.'#'.$post['id']) );

        	            }
	                    else
	                    {
        	                if ($dont_cache_posts)
	                            $parsed_post = $QF_Parser->parse_mess($post['text']);
                            else {
	                            $parsed_post = $post['parsed_post'];

    	                        if (empty($parsed_post)) {	                                $parsed_post = $QF_Parser->parse_mess($post['text'], 1);
        	                        $QF_DBase->sql_doinsert('{DBKEY}posts_cache', Array( 'ch_id' => $post['id'], 'ch_text' => $parsed_post, 'ch_stored' => $timer->time), true );
	                            }

	                            $parsed_post = $QF_Parser->parse_mess($parsed_post, 2);
        	                }

	                        $tmpl['postbody'] = $parsed_post.'<br />&nbsp;';

                            if (($t_history==$post['id']) && $Can_Edit_Post)
        	                    $tmpl['postbody'].=Vis_Draw_Panel('<span class="genmed">'.For_Parse_History($t_archive['content']).'</span>',$lang['FOR_POST_HISTORY'],'99%',true,true);

	                        if ($post['ctime']>$post['time'] && !empty($post['changer'])) {
	                            $tmpl['postedited']=(!$post['deleted']) ? sprintf($lang['FOR_POST_EDITED'],$post['changer']).' '.create_date('', $post['ctime']) :
	                                '<span class="red">'.sprintf($lang['FOR_POST_DELETED'],$post['changer']).' '.create_date('', $post['ctime']).'</span>';
        	                }

        	                $tmpl['attaches']='';
	                        if (is_array($atts[$post['id']]))
	                            foreach ($atts[$post['id']] as $att)
        	                        {        	                            $capt = $att['caption'];
        	                            if (strlen($capt)>30)
                                            $capt = substr($capt, 0, 18).'...'.substr($capt, -7);

	                                    $atmpl=Array(
	                                        'url'  => 'index.php?st=getfile&amp;file='.$att['id'],
	                                        'src'  => 'index.php?sr=thumb&amp;fid='.$att['id'],
	                                        'capt' => $capt,
        	                                'size' => round($att['size']/1024,2).' Kb.',
	                                        'dloads' => intVal($att['dloads']) );
	                                    $tmpl['attaches'].=Visual('POST_ATTACH', $atmpl);
        	                        }
	                    }

        	            if ($Can_Edit_Post) {
	                        $MB['content']='<a href="'.$Base_Link.'&amp;editpost='.$post['id'].'#'.$post['id'].'"> '.$Vis['BTN_EDIT'].' </a>';
	                        if ($t_curaccess>=3)
    	                        $MB['content'].='  <a href="'.$Base_Link.'&amp;postdel='.$post['id'].'#'.$post['id'].'"> '.$Vis['BTN_DROP'].' </a>';
	                        $tmpl['modblock']=Visual('POST_MOD_BLOCK', $MB);
	                    }

        	            $curpost_data=  Visual('POST_BODY', $tmpl);
	                }

        	        if ($t_reverssorting)
	                    $BranchPrint['content']=$curpost_data.$BranchPrint['content'];
                    else
	                    $BranchPrint['content'].=$curpost_data;
                }

            }

            if ($QF_User->uid) {
                if($t_subscribe!=$readp['subscribe'] || !$readp['active'] || $t_MAXID>$t_lastread) {

                    $t_lastread = max(intval($t_MAXID),intval($t_lastread));

                    $t_readed = ($t_lastread>=$topic['MaxID']) ? 1 : 0;

                    $ins_data = Array(
                        'user_id'   => $QF_User->uid,
                        'theme'     => $topic['id'],
                        'lastread'  => $t_lastread,
                        'active'    => $t_readed,
                        'subscribe' => intval($t_subscribe),
                        );
                    $QF_DBase->sql_doinsert('{DBKEY}reads', $ins_data, true);

                    if ($t_readed!=$readp['active']) {                        if ($result = $QF_DBase->sql_doselect('{DBKEY}reads', 'theme', Array( 'active' => 0, 'subscribe' => 1, 'user_id' => $QF_User->uid) ) ) {                            $cuser_has_unrsubs = ($QF_DBase->sql_numrows($result)>0) ? 1 : 0;
                            if ($QF_User->cuser['hasnewsubscr']!=$cuser_has_unrsubs) {
                                $QF_User->cuser['hasnewsubscr'] = $cuser_has_unrsubs;
                                $QF_DBase->sql_doupdate('{DBKEY}users', Array('hasnewsubscr' => $cuser_has_unrsubs), Array('id' => $QF_User->uid) );                            }
                            $QF_DBase->sql_freeresult($result);
                        }
                        if ($t_readed) {
                            $need_id = $topic['parent'];
                            $forum_sects = &$QF_Forum->ForumTree;
                            $sect = end($forum_sects);
                            do {
                                if ($sect['id'] == $need_id) {
                                    $need_id = $sect['parent'];
                                    $forum_sects[$sect['id']]['unreads']--;
                                }
                            } while ($sect = prev($forum_sects));

                            $QF_Session->Cache_Add('forumsects', $QF_Forum->ForumTree);
                        }
                    }

                }

                $BranchPrint['footcontr'] = (!$t_subscribe) ? '<a href="'.$Base_Link.'&amp;subscribe=1#last">'.$lang['FOR_SUBSCRIBE'].'</a>' :
                    '<a href="'.$Base_Link.'&amp;subscribe=0#last">'.$lang['FOR_UNSUBSCRIBE'].'</a>';

            }


            // Posting form
            if($topic['locked'])
                $BranchPrint['newpost']= Vis_Err_String($lang['FOR_THEME_CLOSED']);
            elseif (!$t_readed && $QF_User->uid)
                $BranchPrint['newpost']= Vis_Err_String($lang['FOR_NOANSW_NEW']);
            if (($t_curaccess>=2) && ($t_readed || !$QF_User->uid)) {
                $tmpl= Array(
                    'time'      => create_date('', $timer->time),
                    'user'      => $QF_User->uname,
                    'u_rights'  => Vis_Gen_Rights($QF_User->level,' '),
                    'u_descr'   => $QF_User->cuser['descr'],
                    'avatar'    => Vis_Gen_Avatar($QF_User->cuser, true),
                    'formstart' => '<form name="newmess" action="index.php" method="post" enctype="multipart/form-data">',
                    'formend'   => '</form>',
                    );

                if (!$QF_User->uid) $tmpl['u_descr']= $lang['GUEST'];


                $form=Array(
                    'st'       => $QF_Inc,
                    'page'     => $page,
                    'tid'      => $topic['id'],
                    'formname' => 'newmess',
                    'user'     => $QF_User->uname,
                    );

                if ($QF_User->uid && !$t_subscribe)
                    $form['subscribe'] = 'true';

                $content='';
                if ($QF_User->wlevel >= $QF_Config['post_files_rights'])
                {
                    for( $fdx=1; $fdx<=$forconfig['post_upl_files']; $fdx++)
                        $content.=Visual('FILE_ROW', Array( 'id' => $fdx ));

                    if ($forconfig['post_upl_files']>0)
                        $form['files']=Vis_Draw_panel('<table class="border fullwidth noborder">'.$content.'</table>', $lang['FOR_POST_FILES'],'99%',True);
                }

                if ($QF_User->uid)
                    $form['fixuser']='true';

                $tmpl['formbody']=Visual('POST_NEW_FORM', $form);


                $BranchPrint['newpost'].=Vis_Draw_Fliper(Visual('POST_BODY', $tmpl), $lang['FOR_POST_FORM'], '100%', True);

            }
        }
        else
        {
            $BranchPrint['content']= Vis_Err_String($lang['FOR_THEME_DELETED']);
        }

        //
        // Drawing Theme
        //
        $ulist->load(' WHERE u.id = '.$topic['author_id']);

        unset ($MB);
        $toprint='';
        $Can_Edit_Theme = (($t_curaccess>=3) || ($t_curaccess>=2 && $QF_User->uid == $topic['author_id'] && $QF_User->uid)) && ($topic['special']==0 || $QF_User->admin);
        $Open_Edit_Theme = $Can_Edit_Theme && $t_edittheme;

        $puser=$ulist->get($topic['author_id']);

        $tmpl=Array(
            'labels'   => '<a name="theme"></a>',
            'caption'  => $lang['FOR_THEME_DESC'],
            'image'    => Vis_Gen_TIcon($topic['locked']),
            'header'   => create_date("", $topic['time']),
            'u_descr'  => $puser['descr'],
            'u_rights' => Vis_Gen_Rights($puser['rights'],' '),
            'avatar'   => Vis_Gen_Avatar($puser),
            );

        $tmpl['user'] = ($puser) ? '<a href="index.php?st=info&amp;infouser='.$puser['id'].'">'.$puser['nick'].'</a>' :
            str_replace('[|]','',$topic['author']);

        if ($Open_Edit_Theme) {
            $tmpl['formstart']='<form name="edittheme" action="index.php" method="post">';
            $tmpl['formend']='</form>';
            $form=Array(
                'st'      => $QF_Inc,
                'page'    => $t_page,
                'id'      => $topic['id'],
                'user'    => $QF_User->uname,
                'caption' => $topic['name'],
                'descr'   => $topic['descr'],
                'formname' => 'edittheme',
                'mrights_options' => '',
                'prights_options' => '',
                'moderate' => ($t_curaccess>=3) ? 1 : null,
                );

            if ($topic['pinned']) $form['pintheme']='checked';
            if ($topic['deleted']) $form['deltheme']='checked';
            if ($topic['locked']) $form['locktheme']='checked';

            for($stt=$cur_sect['minrights']; $stt <= $QF_User->wlevel; $stt++){
                $form['mrights_options'].='<option value="'.$stt.'" '.(($topic['minrights']==$stt) ? " SELECTED" : ''). '>'.(($stt>0) ? $stt : $lang['FOR_ALL']).'</option>';
                $form['prights_options'].='<option value="'.$stt.'" '.(($topic['postrights']==$stt) ? " SELECTED" : ''). '>'.(($stt>0) ? $stt : $lang['FOR_ALL']).'</option>';
            }

            $form['rights_hint']= Vis_Draw_Hint($lang['THEME_EDIT_HINT']);
            foreach ($QF_Forum->ForumTree as $ss)
                if ($ss['curuser_access'] && !$ss['deleted'])
                    $form['psect_options'].='<option value="'.$ss['id'].'" '.(($topic['parent']==$ss['id']) ? ' SELECTED':'').'>'.$ss['pref'].$ss['name'].' ('.$ss['minrights'].')</option>';


            $tmpl['body']=Visual('THEME_EDIT_FORM', $form);

            $MB['editorbutts']=Visual('FOR_EDIT_BUTTS',
                Array (
                    'formname' => 'edittheme',
                    'cancel_url' => $Base_Link.'#theme'));

        }
        else
        {
            $cont=Array(
                'descr'      => $QF_Parser->parse_mess($topic['descr']),
                'minrights'  => Vis_Gen_Rights($topic['minrights']),
                'postrights' => Vis_Gen_Rights($topic['postrights']),
                'posts'      => $topic['posts'],
                'curpage'    => $t_page,
                'totalpages' => $t_pages,
                'lasttime'   => create_date('', $topic['lasttime']) );

            $cont['subscribe']=($t_subscribe) ? $Vis['SUBSCR_FLAG'].' <a href="'.$Base_Link.'&amp;subscribe=0#theme">'.$lang['FOR_UNSUBSCRIBE'].'</a>' : '' ;
            if ($topic['minrights'] <= 2 && !$cur_sect['acc_group_name'])
                $cont['subscribe'].= ' <a href="index.php?sr=RSS&amp;topic='.$topic_id.'">[RSS]</a>';

            $tmpl['body']=Visual('THEME_READ_BODY', $cont);

            //$MB['editorbutts']=($showdels) ? '<a href="'.$Base_Link.'&amp;showdels=0#theme">'.$lang['HIDE_DELS'].'</a>'
            // : (($curaccess>=3) ? '<a href="'.$Base_Link.'&amp;showdels=1#theme">'.$lang['SHOW_DELS'].'</a>' : '');
        }

        if ($Can_Edit_Theme) {
            $MB['content']='<a href="'.$Base_Link.'&amp;edittheme=1#theme"> '.$Vis['BTN_EDIT'].' </a>';
            $tmpl['modblock']=Visual('THEME_MOD_BLOCK', $MB);
        }

        $BranchPrint['theme']= Vis_Draw_Fliper(Visual('THEME_BODY', $tmpl),$lang['FOR_THEME_DESC'],'80%');

    }

}

$QF_Forum->Window = Visual('BRANCH_MAIN', $BranchPrint);
if ($topic['id'] == $forconfig['guest_book']) {
    $tmpl=Array(
        'content'   => $QF_Forum->Draw_Forum(),
        'tree_capt' => $topic['name'] );
    print Visual('TREE_MAIN', $tmpl);
}
else
    print $QF_Forum->Draw_Forum();;

?>