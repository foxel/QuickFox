<?php

// Simple InUSE Check
if ( !defined('QF_STARTED') )
        die('Hacking attempt');


include 'kernel/core_sql.php';

Load_Language('utils');

$error = "";
$action = Get_Request('action', 2);

if ($action == 'convert')
{


	function IPBC_unconvert_sql($sql="")
	{
		$sql = stripslashes($sql);

		$sql = preg_replace( "#<span style='.+?'>#is", "", $sql );
		$sql = str_replace( "</span>"                , "", $sql );
		$sql = preg_replace( "#\s*$#"                , "", $sql );

		return '[sql]'.$sql.'[/sql]';
	}

	function IPBC_unconvert_htm($html="")
	{
		$html = stripslashes($html);

		$html = preg_replace( "#<span style='.+?'>#is", "", $html );
		$html = str_replace( "</span>"                , "", $html );
		$html = preg_replace( "#\s*$#"                , "", $html );

		return '[html]'.$html.'[/html]';
	}

	function IPBC_fromDB_parse($txt="")
	{
		//-----------------------------------------
		// Clean up BR tags
		//-----------------------------------------

		$txt = str_replace( "<br>"  , "\n", $txt );
		$txt = str_replace( "<br />", "\n", $txt );

		# Make EMO_DIR safe so the ^> regex works
		$txt = str_replace( "<#EMO_DIR#>", "&lt;#EMO_DIR&gt;", $txt );

		# New emo
		$txt = preg_replace( "#<([^>]+?)emoid=\"(.+?)\"([^>]+?)".">#is", "\\2", $txt );

		# And convert it back again...
		$txt = str_replace( "&lt;#EMO_DIR&gt;", "<#EMO_DIR#>", $txt );

		# Legacy
		$txt = preg_replace( "#<!--emo&(.+?)-->.+?<!--endemo-->#", "\\1" , $txt );

		//-----------------------------------------
		// Clean up nbsp
		//-----------------------------------------

		$txt = str_replace( '&nbsp;&nbsp;&nbsp;&nbsp;', "\t", $txt );
		$txt = str_replace( '&nbsp;&nbsp;'            , "  ", $txt );

		if ( 1 )
		{
			//-----------------------------------------
			// SQL
			//-----------------------------------------

			$txt = preg_replace( "#<!--sql-->(.+?)<!--sql1-->(.+?)<!--sql2-->(.+?)<!--sql3-->#eis", "IPBC_unconvert_sql(\"\\2\")", $txt);

			//-----------------------------------------
			// HTML
			//-----------------------------------------

			$txt = preg_replace( "#<!--html-->(.+?)<!--html1-->(.+?)<!--html2-->(.+?)<!--html3-->#ise", "IPBC_unconvert_htm(\"\\2\")", $txt);

			//-----------------------------------------
			// Images / Flash
			//-----------------------------------------

			$txt = preg_replace( "#<img src=[\"'](\S+?)['\"].+?".">#"      , "\[img\]\\1\[/img\]"            , $txt );

			//-----------------------------------------
			// Email, URLs
			//-----------------------------------------

			$txt = preg_replace( "#<a href=[\"']mailto:(.+?)['\"]>(.+?)</a>#"                                   , "\[email=\\1\]\\2\[/email\]"   , $txt );
			$txt = preg_replace( "#<a href=[\"'](http://|https://|ftp://|news://)?(\S+?)['\"].+?".">(.+?)</a>#" , "\[url=\\1\\2\]\\3\[/url\]"  , $txt );

			//-----------------------------------------
			// Quote
			//-----------------------------------------

			$txt = preg_replace( "#<!--QuoteBegin-->(.+?)<!--QuoteEBegin-->#"                        , '[quote]'         , $txt );
			$txt = preg_replace( "#<!--QuoteBegin-{1,2}([^>]+?)\+([^>]+?)-->(.+?)<!--QuoteEBegin-->#", "[quote=\\1,\\2]" , $txt );
			$txt = preg_replace( "#<!--QuoteBegin-{1,2}([^>]+?)\+-->(.+?)<!--QuoteEBegin-->#"        , "[quote=\\1]"     , $txt );
			$txt = preg_replace( "#<!--QuoteEnd-->(.+?)<!--QuoteEEnd-->#"                            , '[/quote]'        , $txt );

			//-----------------------------------------
			// New quote
			//-----------------------------------------

			$txt = preg_replace( "#<!--quoteo([^>]+?)?-->(.+?)<!--quotec-->#si", '[quote]' , $txt );

			//-----------------------------------------
			// left, right, center
			//-----------------------------------------

			$txt = preg_replace( "#<div align=\"(left|right|center)\">(.+?)</div>#is"  , "[\\1]\\2[/\\1]", $txt );

			//-----------------------------------------
			// Ident => Block quote
			//-----------------------------------------

			while( preg_match( "#<blockquote>(.+?)</blockquote>#is" , $txt ) )
			{
				$txt = preg_replace( "#<blockquote>(.+?)</blockquote>#is"  , "[indent]\\1[/indent]", $txt );
			}

			//-----------------------------------------
			// CODE
			//-----------------------------------------

			$txt = preg_replace( "#<!--c1-->(.+?)<!--ec1-->#", '[code]' , $txt );
			$txt = preg_replace( "#<!--c2-->(.+?)<!--ec2-->#", '[/code]', $txt );

			//-----------------------------------------
			// Easy peasy
			//-----------------------------------------

			$txt = preg_replace( "#<i>(.+?)</i>#is"            , "\[i\]\\1\[/i\]"  , $txt );
			$txt = preg_replace( "#<b>(.+?)</b>#is"            , "\[b\]\\1\[/b\]"  , $txt );
			$txt = preg_replace( "#<strike>(.+?)</strike>#is"  , "\[s\]\\1\[/s\]"  , $txt );
			$txt = preg_replace( "#<u>(.+?)</u>#is"            , "\[u\]\\1\[/u\]"  , $txt );

			//-----------------------------------------
			// List headache
			//-----------------------------------------

			$txt = preg_replace( "#(\n){0,}<ul>#" , "\\1\[list\]"  , $txt );
			$txt = preg_replace( "#(\n){0,}<ol type='(a|A|i|I|1)'>#" , "\\1\[list=\\2\]\n"  , $txt );
			$txt = preg_replace( "#(\n){0,}<li>#" , "\n\[*\]"     , $txt );
			$txt = preg_replace( "#(\n){0,}</ul>(\n){0,}#", "\n\[/list\]\\2" , $txt );
			$txt = preg_replace( "#(\n){0,}</ol>(\n){0,}#", "\n\[/list\]\\2" , $txt );

			//-----------------------------------------
			// Opening style attributes
			//-----------------------------------------

			$txt = preg_replace( "#<!--sizeo:(.+?)-->(.+?)<!--/sizeo-->#"               , "[size=\\1]" , $txt );
			$txt = preg_replace( "#<!--coloro:(.+?)-->(.+?)<!--/coloro-->#"             , "[color=\\1]", $txt );
			$txt = preg_replace( "#<!--fonto:(.+?)-->(.+?)<!--/fonto-->#"               , "[font=\\1]" , $txt );
			$txt = preg_replace( "#<!--backgroundo:(.+?)-->(.+?)<!--/backgroundo-->#"   , "[background=\\1]" , $txt );

			//-----------------------------------------
			// Closing style attributes
			//-----------------------------------------

			$txt = preg_replace( "#<!--sizec-->(.+?)<!--/sizec-->#"            , "[/size]" , $txt );
			$txt = preg_replace( "#<!--colorc-->(.+?)<!--/colorc-->#"          , "[/color]", $txt );
			$txt = preg_replace( "#<!--fontc-->(.+?)<!--/fontc-->#"            , "[/font]" , $txt );
			$txt = preg_replace( "#<!--backgroundc-->(.+?)<!--/backgroundc-->#", "[/background]" , $txt );

			//-----------------------------------------
			// LEGACY SPAN TAGS
			//-----------------------------------------

			while ( preg_match( "#<span style=['\"]font-size:(.+?)pt;line-height:100%['\"]>(.+?)</span>#is", $txt ) )
			{
				$txt = preg_replace( "#<span style=['\"]font-size:(.+?)pt;line-height:100%['\"]>(.+?)</span>#is" , "\[size=\\1\]\\2\[/size\]", $txt );
			}

			while ( preg_match( "#<span style=['\"]color:(.+?)['\"]>(.+?)</span>#is", $txt ) )
			{
				$txt = preg_replace( "#<span style=['\"]color:(.+?)['\"]>(.+?)</span>#is"    , "\[color=\\1\]\\2\[/color\]", $txt );
			}

			while ( preg_match( "#<span style=['\"]font-family:(.+?)['\"]>(.+?)</span>#is", $txt ) )
			{
				$txt = preg_replace( "#<span style=['\"]font-family:(.+?)['\"]>(.+?)</span>#is", "\[font=\\1\]\\2\[/font\]", $txt );
			}

			while ( preg_match( "#<span style=['\"]background-color:(.+?)['\"]>(.+?)</span>#is", $txt ) )
			{
				$txt = preg_replace( "#<span style=['\"]background-color:(.+?)['\"]>(.+?)</span>#is", "\[background=\\1\]\\2\[/font\]", $txt );
			}

			# Legacy <strike>
			$txt = preg_replace( "#<s>(.+?)</s>#is"            , "\[s\]\\1\[/s\]"  , $txt );

			//-----------------------------------------
			// Tidy up the end quote stuff
			//-----------------------------------------

			$txt = preg_replace( "#(\[/QUOTE\])\s*?<br />\s*#si", "\\1\n", $txt );
			$txt = preg_replace( "#(\[/QUOTE\])\s*?<br>\s*#si"  , "\\1\n", $txt );

			$txt = preg_replace( "#<!--EDIT\|.+?\|.+?-->#" , "" , $txt );
			$txt = str_replace( "</li>", "", $txt );

			$txt = str_replace( "&#153;", "(tm)", $txt );
		}

		//-----------------------------------------
		// Parse html
		//-----------------------------------------

			$txt = str_replace( "&#39;", "'", $txt);

		return trim(stripslashes($txt));
	}


    $sel_db = Get_Request('database', 2);
    $prefix = Get_Request('prefix', 2);
    $u_mode = Get_Request('users_mode', 2, 'i');
    $root_par = Get_Request('set_parent', 2, 'i');
    if ($root_par<0)
        $root_par = 0;

    $append_attaches = Get_Request('append_attaches', 2, 'b');

    $f_pass = Get_Request('f_pass', 2);
    $fl_reads = Get_Request('f_read', 2);
    $fl_writes = Get_Request('f_write', 2);
    $fl_posts = Get_Request('f_post', 2);
    $fl_acc_gr = Get_Request('f_acc_gr', 2);

    if ($QF_Session->Get('is_admin')!=1 || !$QF_User->admin) // если нет прав
        $error = $error."<LI>".$lang['ERR_ADMIN_ONLY']."\n";

    if (empty($error)) // если ошибок нет, обрабатываем сообщение
    {
        $timer->Time_Point();           // We'll count time
        $SStart_SQL  = $QF_DBase->num_queries; // We'll count queries
        // stat vars
        $u_created = 0; // users created
        $g_created = 0; // acc_groups created
        $l_created = 0; // acc_links created
        $s_created = 0; // section created
        $s_changed = 0; // sections changed
        $t_created = 0; // topics created
        $p_created = 0; // posts created
        $f_created = 0; // files taken


        if (!$sel_db)
            $sel_db=$dbname;

        $users_create = ($u_mode >= 2);
        $users_load = ($u_mode >= 1);

        $IPB_users = Array();
        $ulist->load('', true);

        if ($users_load) {
            $uquery = 'SELECT * FROM '.$prefix.'members ORDER BY id';
            $result = $QF_DBase->sql_dbquery($sel_db, $uquery);
            if ($result) {
                while ($user = $QF_DBase->sql_fetchrow($result)) {                    $IPB_user = Array(
                        'nick'     => substr($user['name'], 0, 16),
                        'email'    => substr($user['email'], 0, 36),
                        'regtime'  => intval($user['joined']),
                        'lastseen' => intval($user['last_visit']),
                        'descr'    => STrim($user['title'], 128),
                        'groups'   => explode(',', $user['mgroup'].','.$user['mgroup_others']),
                        );

                    $my_user = $ulist->by_nick($IPB_user['nick']);

                    if ($my_user['id']>0)
                        $IPB_user['new_id'] = $my_user['id'];
                    elseif ($users_create && preg_match('/'.UNAME_MASK.'/i', $user['name'])) {
                        $QF_DBase->sql_doinsert('{DBKEY}users', Array('nick' => $IPB_user['nick'], 'pass' => '', 'email' => $IPB_user['email'], 'descr' => $IPB_user['descr'], 'regtime' => $IPB_user['regtime'], 'lastseen' => $IPB_user['lastseen'] ) );
                        $IPB_user['new_id'] = intval($QF_DBase->sql_nextid());
                        $u_created++;
                    }
                    else
                        $IPB_user['new_id'] = 0;

                    $IPB_users[$user['id']] = $IPB_user;
                }
                $QF_DBase->sql_freeresult($result);
            }
        }


        // let's load groups data and perform some manipulations
        $IPB_perms = Array();

        $gquery = 'SELECT * FROM '.$prefix.'groups ORDER BY g_id';
        $result = $QF_DBase->sql_dbquery($sel_db, $gquery);
        if ($result) {
            while ($group = $QF_DBase->sql_fetchrow($result)) {
                $gperm_ids = explode(',', $group['g_perm_id']);
                $g_users = Array();
                foreach ($IPB_users as $u_id=>$user)
                    if (in_array($group['g_id'], $user['groups']))
                        foreach ($gperm_ids as $p_id)
                            $IPB_perms[$p_id][] = $u_id;
            }
            $QF_DBase->sql_freeresult($result);
        }

        foreach ($IPB_perms as $p_id=>$p_data)
            $IPB_perms[$p_id] = array_unique($p_data);


        // Let's load forums

        $IPB_forums = Array();
        $IPB_f_groups = Array();
        $my_forums = Array();
        $my_groups = Array();

        // first load list of existent forums
        $result = $QF_DBase->sql_doselect('{DBKEY}acc_groups');
        if ($result) {
            while ($group = $QF_DBase->sql_fetchrow($result)) {
                $my_groups[strtolower($group['name'])] = $group;
            }
            $QF_DBase->sql_freeresult($result);
        }

        // first load list of existent forums
        $result = $QF_DBase->sql_doselect('{DBKEY}sections', 'id, name');
        if ($result) {
            while ($forum = $QF_DBase->sql_fetchrow($result)) {
                $my_forums[strtolower($forum['name'])] = $forum;
            }
            $QF_DBase->sql_freeresult($result);
        }

        // load list of new forums
        $fquery = 'SELECT * FROM '.$prefix.'forums ORDER BY position, id';
        $result = $QF_DBase->sql_dbquery($sel_db, $fquery);
        if ($result) {
            while ($forum = $QF_DBase->sql_fetchrow($result))
            if ($f_pass[$forum['id']]) {
                $IPB_forum = Array(
                    'name'   => STrim($forum['name'], 128),
                    'par'    => intval($forum['parent_id']),
                    'parent' => $root_par,
                    'descr'  => STrim($forum['description'], 255),
                    'perms'  => unserialize(stripslashes($forum['permission_array'])),
                    'l_read' => intval($fl_reads[$forum['id']]),
                    'l_write'=> intval($fl_writes[$forum['id']]),
                    'l_post' => intval($fl_posts[$forum['id']]),
                    );

                if ($IPB_forum['l_write'] < $IPB_forum['l_read'])
                    $IPB_forum['l_write'] = $IPB_forum['l_read'];
                if ($IPB_forum['l_post'] < $IPB_forum['l_read'])
                    $IPB_forum['l_post'] = $IPB_forum['l_read'];

                if ($IPB_forum['perms']['read_perms']!='*' && $fl_acc_gr[$forum['id']]) {
                    $f_perms = explode(',', $IPB_forum['perms']['read_perms']);
                    $IPB_f_groups[$forum['id']] = Array();
                    foreach ($f_perms as $p_id)
                        if (is_array($IPB_perms[$p_id]))
                            foreach ($IPB_perms[$p_id] as $u_id)
                                $IPB_f_groups[$forum['id']][] = $u_id;

                    $IPB_f_groups[$forum['id']] = array_unique($IPB_f_groups[$forum['id']]);
                    $grp_name = 'IPBC_'.$prefix.'_f'.$forum['id'];
                    $my_group = $my_groups[strtolower($grp_name)];
                    if ($my_group['id']>0)
                        $IPB_forum['acc_group']=$my_group['id'];
                    else {                        $QF_DBase->sql_doinsert('{DBKEY}acc_groups', Array( 'name' => $grp_name ) );
                        $IPB_forum['acc_group'] = intval($QF_DBase->sql_nextid());
                        $g_created++;
                    }
                }

                $my_forum = $my_forums[strtolower($IPB_forum['name'])];
                if ($my_forum['id']>0) {
                    $IPB_forum['new_id'] = $my_forum['id'];
                    $s_changed++;
                }
                else {
                    $QF_DBase->sql_doinsert('{DBKEY}sections', Array('name' => $IPB_forum['name'], 'descr' => $IPB_forum['descr'], 'minrights' => $IPB_forum['l_read'], 'postrights' => $IPB_forum['l_write'] ) );
                    $IPB_forum['new_id'] = intval($QF_DBase->sql_nextid());
                    $s_created++;
                }

                $IPB_forums[$forum['id']] = $IPB_forum;
            }
            $QF_DBase->sql_freeresult($result);
        }

        $acc_used = Array();
        foreach ($IPB_forums as $id=>$IPB_forum) {            $parent = intval($IPB_forums[$IPB_forum['par']]['new_id']);
            if ($parent>0)
                $IPB_forums[$id]['parent'] = $parent;

            if ($IPB_forums[$IPB_forum['par']]['acc_group']>0)
                $IPB_forums[$id]['acc_group'] = $IPB_forums[$IPB_forum['par']]['acc_group'];

            $acc_used[$IPB_forums[$id]['acc_group']] = 1;

            $QF_DBase->sql_doupdate('{DBKEY}sections', Array('parent' => $IPB_forums[$id]['parent'], 'acc_group' => intval($IPB_forums[$id]['acc_group']) ), Array('id' => $IPB_forum['new_id']) );
        }

        foreach ($IPB_f_groups as $id=>$g_users) {
            $my_id = intval($IPB_forums[$id]['acc_group']);
            $g_users = array_unique($g_users);
            foreach ($g_users as $u_id) {
                $my_uid = intval($IPB_users[$u_id]['new_id']);
                if ($my_uid >0) {
                    $QF_DBase->sql_doinsert('{DBKEY}acc_links', Array('user_id' => $my_uid, 'group_id' => $my_id, 'time_given' => $timer->time), true );
                    $l_created++;
                }
            }
        }

        // let's import topics
        $IPB_topics = Array();
        $my_topics = Array();

        // first load list of existent topics
        $result = $QF_DBase->sql_doselect('{DBKEY}topics', 'id, name');
        if ($result) {
            while ($topic = $QF_DBase->sql_fetchrow($result)) {
                $my_topics[strtolower($topic['name'])] = $topic;
            }
            $QF_DBase->sql_freeresult($result);
        }


        $tquery = 'SELECT * FROM '.$prefix.'topics ORDER BY tid';
        $result = $QF_DBase->sql_dbquery($sel_db, $tquery);
        if ($result) {
            while ($topic = $QF_DBase->sql_fetchrow($result))
            if ($f_pass[$topic['forum_id']])
            {                $IPB_topic = Array(
                    'name'  => STrim($topic['title'], 255),
                    'descr' => $topic['description'],
                    'time'  => intval($topic['start_date']),
                    'parent' => intval($IPB_forums[$topic['forum_id']]['new_id']),
                    'pinned' => ($topic['pinned']) ? 1 : 0,
                    'locked' => ($topic['state']=='closed') ? 1 : 0,
                    'l_read' => intval($IPB_forums[$topic['forum_id']]['l_read']),
                    'l_post' => intval($IPB_forums[$topic['forum_id']]['l_post']),
                    );
                $st_user = $IPB_users[$topic['starter_id']];

                if ($st_user['new_id']) {                    $IPB_topic['author_id'] = intval($st_user['new_id']);
                    $IPB_topic['author'] = $st_user['nick'];
                }
                else {
                    $IPB_topic['author_id'] = 0;
                    $IPB_topic['author'] = $topic['starter_name'];
                }

                if ($IPB_topic['parent']==0)
                    $IPB_topic['parent']==$root_par;

                $my_topic = $my_topics[strtolower($IPB_topic['name'])];
                if ($my_topic['id']) {
                    $IPB_topic['new_id'] = $my_topic['id'];
                }
                else {
                    $QF_DBase->sql_doinsert('{DBKEY}topics', Array('name' => $IPB_topic['name'], 'descr' => $IPB_topic['descr'], 'parent' => $IPB_topic['parent'],
                                         'author_id' => $IPB_topic['author_id'], 'author' => $IPB_topic['author'], 'time' => $IPB_topic['time'],
                                         'pinned' => $IPB_topic['pinned'], 'locked' => $IPB_topic['locked'], 'minrights' => $IPB_topic['l_read'], 'postrights' => $IPB_topic['l_post'] ) );
                    $IPB_topic['new_id'] = intval($QF_DBase->sql_nextid());
                    $t_created++;
                }

                $IPB_topics[$topic['tid']] = $IPB_topic;
            }
            $QF_DBase->sql_freeresult($result);
        }

        // let's import posts
        $IPB_pids = Array();
        $my_phashes = Array();

        // first load list of existent topics
        $result = $QF_DBase->sql_doselect('{DBKEY}posts', 'id, hash');
        if ($result) {
            while ($phash = $QF_DBase->sql_fetchrow($result)) {
                $my_phashes[$phash['id']] = $phash['hash'];
            }
            $QF_DBase->sql_freeresult($result);
        }


        $pquery = 'SELECT * FROM '.$prefix.'posts ORDER BY pid';
        $result = $QF_DBase->sql_dbquery($sel_db, $pquery);
        if ($result) {
            while ($post = $QF_DBase->sql_fetchrow($result))
            if ($IPB_topics[$post['topic_id']]['new_id'])
            {

                $text = IPBC_fromDB_parse($post['post']);
                $text = $QF_Parser->prep_mess($text);
                $hash = md5($text);

                $IPB_post = Array(
                    'text'  => $text,
                    'theme' => intval($IPB_topics[$post['topic_id']]['new_id']),
                    'time'  => intval($post['post_date']),
                    'hash'  => $hash,
                    );
                $st_user = $IPB_users[$post['author_id']];

                if ($st_user['new_id']) {
                    $IPB_post['author_id'] = intval($st_user['new_id']);
                    $IPB_post['author'] = $st_user['nick'];
                }
                else {
                    $IPB_post['author_id'] = 0;
                    $IPB_post['author'] = $post['author_name'];
                }

                $my_pid = array_search($IPB_post['hash'], $my_phashes);

                if (!$my_pid) {
                    $QF_DBase->sql_doinsert('{DBKEY}posts', Array('theme' => $IPB_post['theme'], 'author_id' => $IPB_post['author_id'], 'author' => $IPB_post['author'], 'text' => $IPB_post['text'], 'hash' => $IPB_post['hash'], 'time' => $IPB_post['time'] ) );
                    $IPB_post['new_id'] = intval($QF_DBase->sql_nextid());
                    $p_created++;

                    $IPB_pids[$post['pid']] = $IPB_post['new_id'];
                }
                else
                    $IPB_pids[$post['pid']] = $my_pid;

                $IPB_plevels[$post['pid']] = $IPB_topics[$post['topic_id']]['l_read'];

            }
            $QF_DBase->sql_freeresult($result);
        }

        // let's import uploads
        if ($append_attaches) {            $result = $QF_DBase->sql_dbquery($sel_db, 'SELECT conf_value FROM '.$prefix.'conf_settings WHERE conf_key = "upload_dir"' );
            if ($result)
                list($uploads_dir) = $QF_DBase->sql_fetchrow($result, false);

            if (file_exists($uploads_dir)) {                $uploads_dir = preg_replace('#(\/+|\\\+)$#', '', $uploads_dir).'/';

                $my_files = Array();
                // first load list of fids
                $result = $QF_DBase->sql_doselect('{DBKEY}files', 'id');
                if ($result) {
                    while ($file = $QF_DBase->sql_fetchrow($result)) {
                        $my_files[] = $file['id'];
                    }
                    $QF_DBase->sql_freeresult($result);
                }

                $fquery = 'SELECT * FROM '.$prefix.'attachments ORDER BY attach_id';
                $result = $QF_DBase->sql_dbquery($sel_db, $fquery);
                if ($result) {
                    while ($attach = $QF_DBase->sql_fetchrow($result))
                        if ($IPB_pids[$attach['attach_pid']]>0 && file_exists($uploads_dir.$attach['attach_location']) )
                        {                            $IPB_file = Array(
                                'att_to'   => intval($IPB_pids[$attach['attach_pid']]),
                                'time'     => intval($attach['attach_date']),
                                'filename' => STrim($attach['attach_file'], 255),
                                'size'     => intval($attach['attach_filesize']),

                                );

                            $st_user = $IPB_users[$attach['attach_member_id']];

                            if ($st_user['new_id']) {
                                $IPB_file['user_id'] = intval($st_user['new_id']);
                                $IPB_file['user'] = $st_user['nick'];
                            }
                            else {
                                $IPB_file['user_id'] = 0;
                                $IPB_file['user'] = 'IPB_import';
                            }

                            $ucode = 'US'.$IPB_file['user_id'];

                            $file_info=pathinfo($IPB_file['filename']);
                            $filename=$file_info['basename'];
                            $file=substr($ucode.'-'.$IPB_file['time'].$attach['attach_id'].'.'.$file_info['extension'],0,28).'.qff';
                            $uni_name=implode('-', Array($ucode, $filename, $IPB_file['size']) );
                            $fid=md5($uni_name);

                            if ( !in_array($fid, $my_files) )
                                if (copy($uploads_dir.$attach['attach_location'], 'files/'.$file))
                                {
                                    $ins_data = Array(
                                        'id'       => $fid,
                                        'folder'   => 1,
                                        'att_to'   => $IPB_file['att_to'],
                                        'user'     => $IPB_file['user'],
                                        'user_id'  => $IPB_file['user_id'],
                                        'time'     => $IPB_file['time'],
                                        'file'     => $file,
                                        'filename' => $filename,
                                        'size'     => $IPB_file['size'],
                                        'caption'  => $filename,
                                        'rights'   => intval($IPB_plevels[$attach['attach_pid']]),
                                        );
                                    $QF_DBase->sql_doinsert('{DBKEY}files', $ins_data);
                                    $f_created++;
                                }

                        }
                    $QF_DBase->sql_freeresult($result);
                }
            }
        }

        include 'includes/forum_scr.php';
        $forum = New qf_forum_upd();
        $forum->rebuild_forum_stats();
        $forum->rebuild_forum_rights();

        $time_used = round($timer->Time_Point(), 3);
        $SQL_used  = $QF_DBase->num_queries - $SStart_SQL;


        $rresult=sprintf($lang['UTILS_IPBC_ALL_DONE'],
            $time_used,
            $SQL_used,
            $u_created,
            $g_created,
            $l_created,
            $s_created,
            $s_changed,
            $t_created,
            $p_created,
            $f_created,
            'index.php?st=mycabinet' );
    }

}
else
    $error = $lang['ERR_NO_ACTION'];

Set_Result($error, $rresult);


?>