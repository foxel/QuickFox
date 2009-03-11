<?php

// Simple InUSE Check
if ( !defined('QF_STARTED') )
        die('Hacking attempt');

if ( !defined('FORUM_CORE_LOADED') )
        include 'includes/forum_core.php';

Class qf_forum_upd
{
    var $curpost, $curtheme, $cursect, $parchive;
    var $uname, $uid, $ucode, $urights, $spcode;
    var $pfiles = Array();
    var $t_mrights, $t_prights, $t_caption, $t_descr, $dupthemeid; //posted theme data
    var $message, $parsed_post, $mhash, $duppostid;  //posted message data
    var $loaded = False;
    var $error, $result, $redir;   // This must be checked after usage
    var $time;
    var $do_cache = true;

    Function qf_forum_upd()
    {        global $QF_Session, $QF_Config;
        $this->time = time();
        $this->error = '';
        $this->loaded = False;
        $this->do_cache = !$QF_Config['cache']['visuals_nocache'];

        $QF_Session->Cache_Drop_List('forumstat forumsects', true);
    }

    Function preload_data()
    {        global $QF_Parser, $QF_Config, $QF_User, $lang, $QF_DBase, $QF_Session, $QF_Forum;

        $QF_Forum = new qf_forum();
        $this->error = '';

        // Preloading User Data
        $this->urights = $QF_User->level;
        $this->uname = Get_Request('qfuser', 2, 's', 16);
        $this->uid = $QF_User->uid ? $QF_User->uid : 0;
        $this->spcode = Get_Request('spamcode', 2, 'h', 8);

        $this->ucode = ($QF_User->uid) ? 'US'.$QF_User->uid : 'GS'.substr($QF_Session->SID,0,3);

        if ($result = $QF_DBase->sql_doselect('{DBKEY}users', 'id', Array('nick' => $this->uname, 'deleted' => 0) ))
            $userdata = $QF_DBase->sql_fetchrow($result);

        if (strlen($this->uname)<3) // если не введено имя
            $this->error .= '<LI>'.$lang['ERR_NO_LOGIN']."\n";
        elseif (!preg_match('/'.UNAME_MASK.'/i', $this->uname))
            $this->error .= '<LI>'.$lang['ERR_LOGIN_INCORRECT']."\n";
        elseif (preg_match("#Quick\W*?Fox#i", $this->uname))
            $this->error .= '<LI>'.$lang['ERR_SYSTEM_NICK']."\n";
        elseif ($this->uname!=$QF_User->uname) // имя не то
        {
            if ($QF_User->uid) {
                $this->error .= '<LI>'.$lang['ERR_DIF_NICKS']."\n";
            }
            elseif ($userdata) {
                $this->error .= '<LI>'.$lang['ERR_USED_NICK']."\n";
            }
            elseif ($QF_User->guest['gid']) {
                $QF_User->guest['gnick']=$this->uname;
                $QF_DBase->sql_doupdate('{DBKEY}guests', Array('gnick' => $this->uname), Array('gid' => $QF_User->guest['gid']) );
            }
        }

        if ($this->error) Return False;

        // preloading post
        $p_id = Get_Request('p_id', 2, 'i');
        if ($p_id) {
            if ($result = $QF_DBase->sql_doselect('{DBKEY}posts', '*', Array('id' => $p_id) ) )
                $this->curpost = $QF_DBase->sql_fetchrow($result);

            if ($result = $QF_DBase->sql_doselect('{DBKEY}parchive', '*', Array('id' => $p_id) ) )
                $this->parchive = $QF_DBase->sql_fetchrow($result);
        }

        // preloading branch
        $t_id = ($this->curpost['id']) ? $this->curpost['theme'] : Get_Request('t_id', 2, 'i');
        if ($t_id) {
            if ($result = $QF_DBase->sql_doselect('{DBKEY}topics', '*', Array('id' => $t_id) ) )
            {                $this->curtheme = $QF_DBase->sql_fetchrow($result);
                $QF_Forum->CurSection = $QF_Forum->ForumTree[$this->curtheme['parent']];
                $cur_sect = &$QF_Forum->CurSection;

                if (!$cur_sect['curuser_access'] || ($cur_sect['acc_group'] && !$QF_User->cuser['active']))
                    $this->curtheme['cu_access']=0;
                elseif($QF_User->admin || ($QF_User->cuser['modlevel']>=$this->curtheme['postrights'] && $QF_User->cuser['modlevel']>0))
                    $this->curtheme['cu_access']=3;
                elseif($QF_User->wlevel >= $this->curtheme['postrights'] && !$this->curtheme['locked'])
                    $this->curtheme['cu_access']=2;
                elseif($QF_User->level >= $this->curtheme['minrights'])
                    $this->curtheme['cu_access']=1;
                else
                    $this->curtheme['cu_access']=0;
            };
        }

        // preloading section
        $s_id = Get_Request('s_id', 2, 'i');
        if ($s_id) {
            if ($result = $QF_DBase->sql_doselect('{DBKEY}sections', '*', Array('id' => $s_id) ) )
            {
            	$this->cursect = $QF_DBase->sql_fetchrow($result);
                if($QF_User->admin || ($QF_User->cuser['modlevel']>=$this->cursect['postrights'] && $QF_User->cuser['modlevel']>0))
                    $this->cursect['cu_access']=3;
                elseif($QF_User->wlevel >= $this->cursect['postrights'] && !$this->cursect['locked'])
                    $this->cursect['cu_access']=2;
                elseif($QF_User->level >= $this->cursect['minrights'])
                    $this->cursect['cu_access']=1;
                else
                    $this->cursect['cu_access']=0;
            };
        }
        else $this->cursect=Array(
            'id' => 0,
            'minrights' => 0,
            'cu_access' => 2 );

        // preloading theme data
        $this->t_mrights = Get_Request('t_minrights', 2, 'i');
        $this->t_prights = Get_Request('t_postrights', 2, 'i');
        $this->t_caption = Get_Request('t_caption', 2, 'ht', 255);
        $this->t_descr   = Get_Request('t_descr', 2, 'ht', 4096);
        $this->t_descr   = $QF_Parser->prep_mess($this->t_descr);

        // preloading message
        $this->message = Get_Request('message', 2, 'ht');
        $this->message = $QF_Parser->prep_mess($this->message);
	    $this->parsed_post = $QF_Parser->parse_mess($this->message, 1);  //Preparsed post data
        $this->mhash = md5($this->message);

        $this->pfiles = Array();
        $fids = Array(); //for checkeng as unique

        if ($QF_User->wlevel >= $QF_Config['post_files_rights'])
        for( $fdx=1; $fdx<=$QF_Config['forum']['post_upl_files']; $fdx++) {
            $tmpname = $_FILES['file'.$fdx]['tmp_name'];
            $filename = $_FILES['file'.$fdx]['name'];
            $fsize=filesize($tmpname);
            $err = $_FILES['file'.$fdx]['error'];
            if (is_uploaded_file($tmpname) && $fsize == $_FILES['file'.$fdx]['size'] && !$err) {                $file_info=pathinfo($filename);
                $filename=preg_replace('#^.*[\\\/]#', '', $filename);
                $file=substr($this->ucode.'-'.$this->time.$fdx.'.'.$file_info['extension'],0,28).'.qff';
                $uni_name=implode('-', Array($this->ucode, $filename, $fsize) );
                $fid=md5($uni_name);

                if ($fsize>$QF_Config['post_file_size'])
                	$this->error .= '<LI>'.$filename.' - '.$lang['ERR_FILE_TOOBIG'].' '.
                		sprintf($lang['FILE_MAX_UPLSIZE'],round($QF_Config['post_file_size']/1024,2))."\n";
                elseif (!in_array($fid, $fids) )
                {
                    $caption=Get_Request('file'.$fdx.'capt', 2, 'ht', 255);
                    if (!$caption) $caption = trim(preg_replace('#(?<=\S)\.\w+$#', '', strtr($filename, '_', ' ')));

                    $this->pfiles[$fdx] = Array(
                        'tmpname'  => $tmpname,
                        'id'       => $fid,
                        'file'     => $file,
                        'filename' => $filename,
                        'caption'  => $caption,
                        'size'     => $fsize,
                        'descr'    => '' );

                    $fids[]=$fid;
                }
            }
            elseif ($filename)
              	$this->error .= '<LI>'.$filename.' - '.$lang['ERR_FILE_SRVERROR']."\n";
        }

        if (count($fids)>0) {            $fids = '"'.implode('", "', $fids).'"';
            if ($result = $QF_DBase->sql_doselect('{DBKEY}files', 'id', 'WHERE id IN ('.$fids.')' ) )
            {
                $fids = Array();
                while (list($fid) = $QF_DBase->sql_fetchrow($result, false))
                    $fids[] = $fid;
                $QF_DBase->sql_freeresult($result);

                foreach ($this->pfiles as $pfile)
                    if (in_array($pfile['id'], $fids) )
                      	$this->error .= '<LI>'.$pfile['filename'].' - '.$lang['ERR_FILE_UPL_DUP']."\n";
            }
        }

        if (connection_aborted())
            $this->error .= '<LI>Client Connection aborted - partial data might been sent'."\n";

        if ($this->error)
            Return False;


        $this->dupthemeid = 0;
        if ($result = $QF_DBase->sql_doselect('{DBKEY}topics', '*', Array('name' => $this->t_caption, 'parent' => $s_id, 'deleted' => 0) ) )
        {            $foundtheme = $QF_DBase->sql_fetchrow($result);
            if (is_array($foundtheme))
                $this->dupthemeid = $foundtheme['id'];
        };

        $this->duppostid = 0;
        if ($result = $QF_DBase->sql_doselect('{DBKEY}posts', '*', Array('hash' => $this->mhash, 'deleted' => 0) ) )
        {            $foundpost = $QF_DBase->sql_fetchrow($result);
            if (is_array($foundpost))
                $this->duppostid = $foundpost['id'];
        };


        $this->loaded = True;
    }

    function append_files($msgid=0)
    {        global $QF_Config, $lang, $QF_DBase;

        if ($this->error) Return False;

        if (!$this->loaded) $this->preload_data();

        foreach($this->pfiles as $file) {            $res = move_uploaded_file($file['tmpname'], 'files/'.$file['file']);
            if ($res == false)
                $this->error .= '<LI>'.$lang['ERR_FILE_SYSTEM']."\n";
            else {
                $QF_DBase->sql_doinsert('{DBKEY}files', Array(
                        'id'       => $file['id'],
                        'folder'   => 1,
                        'att_to'   => intval($msgid),
                        'user'     => $this->uname,
                        'user_id'  => $this->uid,
                        'time'     => $this->time,
                        'file'     => $file['file'],
                        'filename' => $file['filename'],
                        'size'     => $file['size'],
                        'caption'  => $file['caption'],
                        'descr'    => $file['descr'],
                        'rights'   => $this->curtheme['minrights'],
                        ) );
            }
        }

    }

    function post_message()
    {        global $QF_Config, $QF_Session, $lang, $QF_DBase, $QF_User, $QF_Parser;

        if ($this->error) Return False;

        if (!$this->loaded) $this->preload_data();

        If (empty($this->message)) {            $this->error .= '<LI>'.$lang['ERR_NO_MESS']."\n";
        }
        elseIf ($this->duppostid>0) {
            $this->error .= '<LI>'.$lang['ERR_POST_DUPLICATE']."\n";
        }
        If (empty($this->curtheme)) {
            $this->error .= '<LI>'.$lang['ERR_THEME_LOST']."\n";
        }
        elseIf ($this->curtheme['cu_access']<2) {
            $this->error .= '<LI>'.$lang['ERR_THEME_LOWLEVEL']."\n";
        }

        if ($this->error) Return False;

		if (!$QF_User->uid) $QF_Session->CheckSpamCode($this->spcode);

        if ( $result = $QF_DBase->sql_doselect('{DBKEY}posts', '*', Array ('theme' => $this->curtheme['id']), 'ORDER BY id DESC LIMIT 1' ) ) {            $this->curpost = $QF_DBase->sql_fetchrow($result);
            $QF_DBase->sql_freeresult($result);
        };

        if ($this->curpost['author']==$this->uname && $this->curpost['author_id']==$this->uid && !$this->curpost['changer'] && $this->curpost['time'] >= ($this->time-$QF_Config['forum']['mess_lock_time']*60))
        {            $this->message = $this->curpost['text']."\n\n".$this->message;
            $this->mhash = md5($this->message);
            $this->parsed_post = $QF_Parser->parse_mess($this->message, 1);
            $QF_DBase->sql_doupdate('{DBKEY}posts', Array('text' => $this->message, 'hash' => $this->mhash, 'time' => $this->time), Array( 'id' => $this->curpost['id']) );
        }
        else
        {
            $ins_data = Array(
                'theme'     => $this->curtheme['id'],
                'author_id' => $this->uid,
                'author'    => $this->uname,
                'text'      => $this->message,
                'hash'      => $this->mhash,
                'time'      => $this->time,
                );
            $QF_DBase->sql_doinsert('{DBKEY}posts', $ins_data);

            $new_id = intval($QF_DBase->sql_nextid());

            if ( $result = $QF_DBase->sql_doselect('{DBKEY}posts', '*', Array ('id' => $new_id) ) )
            {                $this->curpost = $QF_DBase->sql_fetchrow($result);
                $QF_DBase->sql_freeresult($result);
            }
        }


        $QF_DBase->sql_doinsert('{DBKEY}posts_cache', Array('ch_id' => $this->curpost['id'], 'ch_text' => $this->parsed_post, 'ch_stored' => $this->time), true );


        $this->append_files($this->curpost['id']);

        if ($this->error) Return False;

        $tsuscribe = Get_Request('tsubscribe', 2, 'b');

        if ($tsuscribe && $this->uid)
            $QF_DBase->sql_doupdate('{DBKEY}reads', Array('subscribe' => 1), Array('theme' => $this->curtheme['id'], 'user_id' => $this->uid) );

        $this->upd_theme_data($this->curtheme['id']);
        $this->upd_sect_data($this->curtheme['parent']);
        $this->upd_userstats($this->uid);


        $this->redir = 'index.php?st=branch&amp;branch='.$this->curtheme['id'].'&amp;shownew=1#unread';
        $this->result=sprintf($lang['FOR_POST_ADDED'],$this->curtheme['name'],'<a href="'.$this->redir.'">','</a>');

        //Уведомления
        $email = New mailer;
        $email->use_template('branchreply');
        $tmpl['anick']=(($this->uid) ? '' : $lang['GUEST'].' ').$this->uname;
        $tmpl['branchname']=$this->curtheme['name'];
        $tmpl['aurl']=GetFullUrl($this->redir);
        $tmpl['surl']='http://'.$QF_Config["server_name"];
        $tmpl['sname']=$QF_Config['site_name'];

        $query='SELECT u.* FROM {DBKEY}users u JOIN {DBKEY}reads r ON (r.user_id=u.id)
        where r.theme = '.$this->curtheme['id'].' AND r.subscribe = 1 AND r.active=1
        AND u.rights > '.$this->curtheme['minrights'].' AND u.deleted = 0 AND u.id!= '.$QF_User->uid;
        $result = $QF_DBase->sql_query($query);

        $uids = Array(); //Subscribed
        $send = False;
        if ( $result ) {            while ( $data = $QF_DBase->sql_fetchrow($result))
            {
                $uemail = trim($data['email']);
                if (!empty($uemail) && $data['subscrtype'] == 1) {                    //$tmpl['nick']=$data['nick'];
                    $email->bcc($uemail);
                    $send = True;
                }
                $uids[] = $data['id'];
            }
        };

        $email->assign_vars($tmpl);
        if ($send) $email->send();

        if (count($uids)>0)
            $QF_DBase->sql_doupdate('{DBKEY}users', Array( 'hasnewsubscr' => 1), 'WHERE id IN ('.implode(', ', $uids).')' );

        $QF_DBase->sql_doupdate('{DBKEY}reads', Array( 'active' => 0), 'WHERE theme = '.$this->curtheme['id'].' AND user_id!= '.$QF_User->uid);

    }

    function edit_message()
    {
        global $QF_Config, $lang, $QF_DBase, $QF_User;

        if ($this->error) Return False;

        if (!$this->loaded) $this->preload_data();

        If (empty($this->message)) {
            $this->error .= '<LI>'.$lang['ERR_NO_MESS']."\n";
        }
        elseIf ($this->duppostid>0 && $this->duppostid != $this->curpost['id']) {
            $this->error .= '<LI>'.$lang['ERR_POST_DUPLICATE']."\n";
        }
        elseIf (empty($this->curpost)) {
            $this->error .= '<LI>'.$lang['ERR_POST_LOST']."\n";
        }
        If (empty($this->curtheme)) {
            $this->error .= '<LI>'.$lang['ERR_THEME_LOST']."\n";
        }
        elseIf (!$this->uid) {
            $this->error .= '<LI>'.$lang['ERR_LOWLEVEL']."\n";
        }
        elseIf ($this->curtheme['cu_access']<2) {
            $this->error .= '<LI>'.$lang['ERR_THEME_LOWLEVEL']."\n";
        }
        elseIf ($this->curtheme['cu_access']<3 && $this->uid != $this->curpost['author_id']) {
            $this->error .= '<LI>'.$lang['ERR_LOWLEVEL']."\n";
        }

        if ($this->error) Return False;

        $this->append_files($this->curpost['id']);

        if ($this->error) Return False;

        $deleted = Get_Request('p_del', 2, 'b');
        $hideedit = Get_Request('p_hedit', 2, 'b');
        $unattach = Get_Request('unattach', 2);

        if (is_array($unattach)) {            $unatt_list=Array();
            foreach ($unattach as $id)
                $unatt_list[]='"'.addslashes($id).'"';
            $mod = ($this->curtheme['cu_access']>=3) ? ' OR rights <= '.$QF_User->cuser['modlevel'] : '';
            if (count($unatt_list)>0)
            {
                //$QF_DBase->sql_doupdate('{DBKEY}files', Array('att_to' => 0),  'WHERE att_to = '.$this->curpost['id'].' AND id IN ('.implode(',', $unatt_list).') AND (user_id = '.$QF_User->uid.' '.$mod.')' );
                $QF_DBase->sql_dodelete('{DBKEY}files', 'WHERE att_to = '.$this->curpost['id'].' AND id IN ('.implode(',', $unatt_list).') AND (user_id = '.$QF_User->uid.' '.$mod.')' );
            }

        }

        if ($this->curtheme['cu_access']<3) $hideedit=0;

        if (!$this->curpost['changer'] && $this->curpost['time'] >= ($this->time-$QF_Config['forum']['mess_lock_time']*60) && $this->uid == $this->curpost['author_id'])
            $hideedit = true;

        elseif ($this->curpost['changer'] && $this->curpost['changer'] != $this->uname)
            $hideedit = false;

        if ($hideedit && $deleted==$this->curpost['deleted'])
        {            $QF_DBase->sql_doupdate('{DBKEY}posts', Array('text' => $this->message, 'hash' => $this->mhash), Array( 'id' => $this->curpost['id']) );
        }
        elseif ($this->mhash != $this->curpost['hash'] || $deleted!=$this->curpost['deleted']) {
            $upd_data = Array(
                'text' => $this->message,
                'hash' => $this->mhash,
                'ctime' => $this->time,
                'changer' => $this->uname,
                'deleted' => intval($deleted),
                );
            $QF_DBase->sql_doupdate('{DBKEY}posts', $upd_data,  Array( 'id' => $this->curpost['id']) );

            if ($this->mhash != $this->curpost['hash']) {                $oldtime = ($this->curpost['ctime']) ? $this->curpost['ctime'] : $this->curpost['time'];
                $oldchanger = ($this->curpost['changer']) ? $this->curpost['changer'] : $this->curpost['author'];
                if (!empty($this->parchive))
                    $arch=$this->parchive['content'];
                else
                    $arch = '';

                $arch.="\n<{[time=".$oldtime."] - ".$oldchanger."}>\n";
                $arch.=$this->curpost['text']."\n";

                $QF_DBase->sql_doinsert('{DBKEY}parchive', Array( 'id' => $this->curpost['id'], 'content' => $arch), true);
            }
        }

        $QF_DBase->sql_doinsert('{DBKEY}posts_cache', Array('ch_id' => $this->curpost['id'], 'ch_text' => $this->parsed_post, 'ch_stored' => $this->time), true );


        $this->upd_theme_data($this->curtheme['id']);
        $this->upd_sect_data($this->curtheme['parent']);
        $this->upd_userstats($this->curpost['author_id']);

        $this->redir = 'index.php?st=branch&amp;branch='.$this->curtheme['id'].'&amp;postshow='.$this->curpost['id'].'#'.$this->curpost['id'];
        $this->result=sprintf($lang['FOR_POST_MODED'],$this->curtheme['name'],'<a href="'.$this->redir.'">','</a>');

    }

    function del_message($p_id, $t_id=0)
    {        global $QF_Config, $lang, $QF_DBase, $QF_User, $timer;

        if ( $result = $QF_DBase->sql_doselect('{DBKEY}posts', 'id, theme', Array( 'id' => $p_id) ) )        	$this->curpost = $QF_DBase->sql_fetchrow($result);

        if (empty($this->curpost))
            return False;

        if ($t_id==0)
            $t_id = $this->curpost['theme'];

        if ($t_id!=$this->curpost['theme'])
            return False;

        if ( $result = $QF_DBase->sql_doselect('{DBKEY}topics', '*', Array( 'id' => $t_id) ) )
        {            $this->curtheme = $QF_DBase->sql_fetchrow($result);
            if($QF_User->admin || ($QF_User->cuser['modlevel']>=$this->curtheme['postrights'] && $QF_User->cuser['modlevel']>0))
                $this->curtheme['cu_access']=3;
            else
                return False;
        };

        $QF_DBase->sql_doupdate('{DBKEY}posts', Array( 'changer' => $QF_User->uname, 'ctime' => $timer->time, 'deleted' => 1), Array( 'id' => $p_id) );

        $this->upd_theme_data($this->curtheme['id']);
        $this->upd_sect_data($this->curtheme['parent']);
        $this->upd_userstats($this->curpost['author_id']);

        return True;
    }

    function add_branch()
    {        global $QF_Config, $QF_Session, $lang, $QF_DBase, $QF_User;

        if ($this->error) Return False;

        if (!$this->loaded) $this->preload_data();

        If (strlen($this->t_caption)<3) {
            $this->error .= '<LI>'.$lang['ERR_NO_CAPT']."\n";
        }
        elseIf ($this->dupthemeid>0) {
            $this->error .= '<LI>'.$lang['ERR_THEME_DUPLICATE']."\n";
        }
        If (empty($this->message)) {
            $this->error .= '<LI>'.$lang['ERR_NO_MESS']."\n";
        }
        elseIf ($this->duppostid>0) {
            $this->error .= '<LI>'.$lang['ERR_POST_DUPLICATE']."\n";
        }
        If (empty($this->cursect)) {
            $this->error .= '<LI>'.$lang['ERR_NO_SECT']."\n";
        }
        elseIf ($this->cursect['cu_access']<2) {
            $this->error .= '<LI>'.$lang['ERR_SECT_LOWLEVEL']."\n";
        }

        if ($this->error) Return False;

		if (!$QF_User->uid) $QF_Session->CheckSpamCode($this->spcode);

        $this->append_files();

        if ($this->error) Return False;

        $this->t_mrights = Max($this->t_mrights, intval($this->cursect['minrights']));
        $this->t_prights = Max($this->t_prights, $this->t_mrights);

        $ins_data = Array(
            'parent'     => $this->cursect['id'],
            'name'       => $this->t_caption,
            'descr'      => $this->t_descr,
            'author_id'  => $this->uid,
            'author'     => $this->uname,
            'minrights'  => $this->t_mrights,
            'postrights' => $this->t_prights,
            'time'       => $this->time,
            'lasttime'   => $this->time,
            'lastposter' => $this->uname,
            'lastposter_id' => $this->uid,
            );
        $QF_DBase->sql_doinsert('{DBKEY}topics', $ins_data);

        $new_id = intval($QF_DBase->sql_nextid());

        if ( $result = $QF_DBase->sql_doselect('{DBKEY}topics', '*', Array( 'id' => $new_id) ) )
            $this->curtheme = $QF_DBase->sql_fetchrow($result);

        If (empty($this->curtheme)) {
            $this->error .= '<LI>'.$lang['ERR_THEME_CRERR']."\n";
        }

        if ($this->error) Return False;

        $ins_data = Array(
            'theme'     => $this->curtheme['id'],
            'author_id' => $this->uid,
            'author'    => $this->uname,
            'text'      => $this->message,
            'hash'      => $this->mhash,
            'time'      => $this->time,
            );
        $QF_DBase->sql_doinsert('{DBKEY}posts', $ins_data);

        $new_id = intval($QF_DBase->sql_nextid());

        if ( $result = $QF_DBase->sql_doselect('{DBKEY}posts', '*', Array ('id' => $new_id) ) )
        {
            $this->curpost = $QF_DBase->sql_fetchrow($result);
            $QF_DBase->sql_freeresult($result);
        }

        $new_id = intval($QF_DBase->sql_nextid());

        $QF_DBase->sql_doinsert('{DBKEY}posts_cache', Array('ch_id' => $this->curpost['id'], 'ch_text' => $this->parsed_post, 'ch_stored' => $this->time), true );

        $QF_DBase->sql_doinsert('{DBKEY}reads', Array( 'user_id' => $this->uid, 'theme' => $this->curtheme['id'], 'lastread' => $this->curpost['id'], 'active' => 1, 'subscribe' => 1), true );

        $this->upd_theme_data($this->curtheme['id']);
        $this->upd_sect_data($this->curtheme['parent']);
        $this->upd_userstats($this->uid);


        $this->redir = 'index.php?st=branch&amp;branch='.$this->curtheme['id'].'&amp;shownew=1#unread';
        $this->result=sprintf($lang['FOR_THEME_ADDED'],$this->curtheme['name'],'<a href="'.$this->redir.'">','</a>');

    }


    function edit_branch()
    {        global $QF_Config, $lang, $QF_DBase, $QF_User;

        if ($this->error) Return False;

        if (!$this->loaded) $this->preload_data();

        If (!$this->uid) {
            $this->error .= '<LI>'.$lang['ERR_LOWLEVEL']."\n";
        }
        elseIf (strlen($this->t_caption)<3) {
            $this->error .= '<LI>'.$lang['ERR_NO_CAPT']."\n";
        }
        elseIf ($this->dupthemeid>0 && $this->dupthemeid != $this->curtheme['id']) {
            $this->error .= '<LI>'.$lang['ERR_THEME_DUPLICATE']."\n";
        }
        elseIf (empty($this->curtheme)) {
            $this->error .= '<LI>'.$lang['ERR_THEME_LOST']."\n";
        }
        elseIf ($this->curtheme['cu_access']<3) {
            $this->error .= '<LI>'.$lang['ERR_LOWLEVEL']."\n";
        }
        If (empty($this->cursect)) {
            $this->error .= '<LI>'.$lang['ERR_NO_SECT']."\n";
        }
        elseIf ($this->cursect['cu_access']<2) {
            $this->error .= '<LI>'.$lang['ERR_SECT_LOWLEVEL']."\n";
        }

        if ($this->error) Return False;

        list($deleted, $locked, $pinned) = Get_Request_Multi('t_del t_lock t_pin', 2, 'b');

        $this->t_mrights = Max($this->t_mrights, intval($this->cursect['minrights']));
        $this->t_prights = Max($this->t_prights, $this->t_mrights);

        $upd_data = Array(
            'parent'     => $this->cursect['id'],
            'name'       => $this->t_caption,
            'descr'      => $this->t_descr,
            'minrights'  => $this->t_mrights,
            'postrights' => $this->t_prights,
            'locked'     => intval($locked),
            'deleted'    => intval($deleted),
            'pinned'     => intval($pinned),
            );
        $QF_DBase->sql_doupdate('{DBKEY}topics', $upd_data, Array ('id' => $this->curtheme['id']) );

        $this->upd_theme_data($this->curtheme['id']);
        $this->upd_sect_data($this->curtheme['parent']);
        $this->upd_sect_data($this->cursect['id']);
        $this->upd_userstats($this->uid);


        $this->redir = 'index.php?st=branch&amp;branch='.$this->curtheme['id'].'&amp;shownew=1#theme';
        $this->result=sprintf($lang['FOR_THEME_MODED'],$this->curtheme['name'],'<a href="'.$this->redir.'">','</a>');
    }

    //
    // Stats Forum Script Functions
    //

    function upd_theme_data($id)
    {        global $QF_Config, $lang, $QF_DBase;

        $id = intval($id);
        if (!$id) return False;

        if ( $result = $QF_DBase->sql_doselect('{DBKEY}topics', '*', Array( 'id' => $id ) ) )
            $curtheme = $QF_DBase->sql_fetchrow($result);

        if (!$curtheme['id']) return false;

        $query='SELECT count(id) as count, MAX(id) as MaxID FROM {DBKEY}posts WHERE deleted=0 AND theme = '.$id;
        if ( $result = $QF_DBase->sql_query($query) )
            $ts = $QF_DBase->sql_fetchrow($result);
        if (!$ts['count'])
            $ts=Array(
                'count' => 0,
                'MaxID' => 0 );


        if ( $result = $QF_DBase->sql_doselect('{DBKEY}posts', '*', Array( 'theme' => $id, 'deleted' => 0), 'ORDER BY time DESC LIMIT 1') )
            $lpost = $QF_DBase->sql_fetchrow($result);
        else
            unset($lpost);

        $upd_data = Array(
            'posts' => $ts['count'],
            'MaxID' => $ts['MaxID'],
            );

        if (is_array($lpost)) {
            $upd_data['lastposter'] = $lpost['author'];
            $upd_data['lastposter_id'] = $lpost['author_id'];
            $upd_data['lasttime'] = $lpost['time'];
        }
        else {            $upd_data['lastposter'] = $curtheme['author'];
            $upd_data['lastposter_id'] = $curtheme['author_id'];
            $upd_data['lasttime'] = $curtheme['time'];
        }
        $QF_DBase->sql_doupdate ('{DBKEY}topics', $upd_data, Array( 'id' => $id ) );

    }

    function upd_sect_data($id, $noparent=Array() ) //$noparent - Array with sections that can't be parent of $id section
    {        global $QF_Config, $lang, $QF_DBase;

        $id = intval($id);
        if (!$id) return False;

        if ( $result = $QF_DBase->sql_doselect('{DBKEY}sections', '*', Array( 'id' => $id ) ) )
            $cursect = $QF_DBase->sql_fetchrow($result);

        if (!$cursect['id']) return false;

        if ($cursect['parent']) {
            $noparent[]=$cursect['id'];
            if (in_array($cursect['parent'], $noparent))
                $QF_DBase->sql_doupdate('{DBKEY}sections', Array( 'parent' => 0 ), Array( 'id' => $id ) );
            else
                $this->upd_sect_data($cursect['parent'], $noparent);
        }
    }

    function upd_userstats($id)
    {        global $QF_Config, $lang, $QF_DBase;

        $id = intval($id);
        if (!$id) return False;

        $suserstats = Array();
        if ( $result = $QF_DBase->sql_doselect('{DBKEY}users', '*', Array( 'id' => $id, 'deleted' => 0 ) ) )
            $suser = $QF_DBase->sql_fetchrow($result);

        if (!$suser['id']) return False;
        $nick = $suser['nick'];

        $query='SELECT count(id) as posts , MAX(time) as lastposttime FROM {DBKEY}posts WHERE author_id='.$id.' AND deleted=0 GROUP BY author_id';
        if ( $result = $QF_DBase->sql_query( $query ) )
            $rdata=$QF_DBase->sql_fetchrow($result);

        $suserstats['posts']=intval($rdata['posts']);
        $suserstats['lastposttime']=intval($rdata['lastposttime']);

        $query='SELECT count(id) as files FROM {DBKEY}files WHERE user_id='.$id;
        if ( $result=$QF_DBase->sql_query( $query ) )
            $rdata=$QF_DBase->sql_fetchrow($result);

        $suserstats['files']=intval($rdata['files']);

        $query='SELECT count(id) as themes FROM {DBKEY}topics WHERE author_id='.$id.' AND deleted=0';
        if ( $result=$QF_DBase->sql_query( $query ) )
            $rdata=$QF_DBase->sql_fetchrow($result);

        $suserstats['themes']=intval($rdata['themes']);

        $query='SELECT theme as lasttheme, id as lastpost FROM {DBKEY}posts WHERE author_id='.$id.' AND deleted=0 ORDER BY time DESC LIMIT 1';
        if ( $result=$QF_DBase->sql_query( $query ) )
            $rdata=$QF_DBase->sql_fetchrow($result);

        $suserstats['lasttheme']=intval($rdata['lasttheme']);
        $suserstats['lastpost']=intval($rdata['lastpost']);

        $upd_data = Array(
            'user_id'      => $id,
            'posts'        => $suserstats['posts'],
            'themes'       => $suserstats['themes'],
            'files'        => $suserstats['files'],
            'lastposttime' => $suserstats['lastposttime'],
            'lasttheme'    => $suserstats['lasttheme'],
            'lastpost'     => $suserstats['lastpost'],
            );
        $QF_DBase->sql_doinsert( '{DBKEY}userstats', $upd_data, true );
    }

    function upd_all_sections()
    {        global $QF_Config, $lang, $QF_DBase;

        $query='SELECT s.id, s.name, COUNT(ss.id) AS scount FROM {DBKEY}sections s
                LEFT JOIN {DBKEY}sections ss ON(ss.parent = s.id)
                GROUP BY s.id';

        if ( $result = $QF_DBase->sql_query( $query ) )
            while($sdata=$QF_DBase->sql_fetchrow($result))
                if ($sdata['id'] && $sdata['scount']==0)
                    $this->upd_sect_data($sdata['id']);
    }

    function rebuild_forum_rights()
    {        global $QF_Config, $lang, $QF_DBase, $QF_Session, $QF_Forum;

	    $QF_Forum = new qf_forum();
        $tree = $QF_Forum->ForumTree;   // Generating tree
        unset ($tree[0]);

	    // Combining Sections Rights
	    foreach ($tree as $sect) {
	        $sn=$sect['id'];
	        if (empty($sect['acc_group_name']))
	            $tree[$sn]['acc_group']=0;

	        if ($sect['parent']>0) {
	            $psect = &$tree[$sect['parent']];
	            if ($sect['minrights']<$psect['minrights'])
	                $tree[$sn]['minrights']=$psect['minrights'];

	            if ($psect['acc_group']>0)
	            {
	                $tree[$sn]['acc_group']=$psect['acc_group'];
	                $tree[$sn]['acc_group_name']=$psect['acc_group_name'];
	            }
	        }
	        if ($tree[$sn]['postrights']<$tree[$sn]['minrights'])
	            $tree[$sn]['postrights']=$tree[$sn]['minrights'];
	    }

	    // Writing Sections rights & correcting topics rights
	    foreach ($tree as $sn=>$sect) {
	        $mrights=intval($sect['minrights']);
	        $prights=intval($sect['postrights']);
	        $acc_group=intval($sect['acc_group']);

	        $query='UPDATE {DBKEY}sections SET
	        minrights = '.$mrights.',
	        postrights = '.$prights.',
	        acc_group = '.$acc_group.'
	        WHERE id = '.$sn;
	        $QF_DBase->sql_query($query);

	        $query='UPDATE {DBKEY}topics SET
	        minrights = '.$mrights.'
	        WHERE minrights < '.$mrights.' AND parent = '.$sn;
	        $QF_DBase->sql_query($query);

	    }

        $query='UPDATE {DBKEY}topics SET
        postrights = minrights
        WHERE postrights < minrights';
        $QF_DBase->sql_query($query);

        $QF_Session->Cache_Drop_List('forumstat forumsects', true);
    }

    function rebuild_forum_stats()
    {        global $QF_Config, $lang, $QF_DBase;

        if ( $result = $QF_DBase->sql_doselect('{DBKEY}topics') )
            while($tdata=$QF_DBase->sql_fetchrow($result))
                if ($tdata['id'])
                    $this->upd_theme_data($tdata['id']);

        $this->upd_all_sections();

        if ( $result = $QF_DBase->sql_doselect('{DBKEY}users') )
            while($udata=$QF_DBase->sql_fetchrow($result))
                if ($udata['id'])
                    $this->upd_userstats($udata['id']);

    }
}

?>