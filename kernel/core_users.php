<?php

// Simple InUSE Check
if ( !defined('QF_STARTED') )
        die('Hacking attempt');

if ( defined('CORE_USERS_LOADED') )
        die('Scripting error');

define('CORE_USERS_LOADED', True);

class qf_curuser
{
    var $uid = 0;
    var $uname = '';
    var $level = 0;
    var $wlevel = 0;
    var $admin = 0;
    var $cuser = Array();
    var $guest = Array();
    var $spider = Array();
    var $is_spider = false;

    function qf_curuser()
    {
        Global $QF_User;
        if (defined('QF_CURUSER_CREATED'))
            trigger_error('Duplicate curuser creation!', 256);

        define('QF_CURUSER_CREATED', true);

        $QF_User = $this;
    }

    function login()
    {
        global $QF_Session, $QF_Client;
        global $QF_DBase, $lang, $QF_Config, $timer;

        $this->uid = 0;
        $this->uname = '';
        $this->level = 0;
        $this->wlevel = 0;
        $this->cuser = Array();
        $this->guest = Array();
        $this->spider = Array();
        $this->is_spider = false;

        $session_ids = $QF_Session->SID;

        $uid = $QF_Session->Get('QF_uid');

        // if it is logged session
        if(!empty($uid)) {
            if ( $result = $QF_DBase->sql_doselect('{DBKEY}users', '*', Array( 'id' => $uid, 'deleted' => 0) ) )
            {
                $curuser = $QF_DBase->sql_fetchrow($result);
                $uid = $curuser['id'];
                $QF_DBase->sql_freeresult($result);
            }

            if (!$uid)
                $QF_Session->Cache_Clear();
        }

        // if we have autologin entrance
        elseif ($autokey = Get_Request('QF_Login', 3, 'h', 32)) {
            if ($result = $QF_DBase->sql_doselect('{DBKEY}users', '*', 'WHERE autologin = "'.$autokey.'" AND lastseen > '.($timer->time - 86400*30).' AND deleted = 0' ) )
            {
                $curuser = $QF_DBase->sql_fetchrow($result);
                $QF_DBase->sql_freeresult($result);
            }
            if ($curuser) {
                $uid = $curuser['id'];
                $QF_Session->Set('QF_uid', $uid);
                $QF_Session->Cache_Clear();
                $curuser['sessid']=$session_ids;
                $QF_DBase->sql_dodelete('{DBKEY}acc_links', 'WHERE user_id = '.$uid.' AND NOT ISNULL(drop_after) AND drop_after < '.$timer->time );
            }
            else {
                $error=sprintf($lang['ERR_AUTOLOGIN_FAILED'],$login);
                setcookie('QF_Login');
                Set_Result($error,'');
            }
        }

        if ($uid) {
            if ($curuser['sessid']!=$session_ids) {
                $error = sprintf($lang['ERR_ANOTHER_SESSION'],$curuser['nick']);
                $QF_Session->Drop('QF_user QF_uid');
                $QF_Session->Cache_Clear();
                unset ($curuser);
                setcookie('QF_Login');
                $QF_DBase->sql_doupdate('{DBKEY}users', Array( 'sessid' => '', 'autologin' => '', 'lastip' => $QF_Client['ipaddr']), Array( 'id' => $uid) );
                Set_Result($error,'');
            }

            // Cahnges only for main scripts
            // Any Job Scripts (Previewers and other) Must Set $QF_Session->fix to prevent any changes
            If (!$QF_Session->fix) {
            // Logout
	            if (Get_Request('logout', 1, 'b')) {
                    unset($curuser);
                    setcookie('QF_Login');
                    $QF_Session->Cache_Clear();
                    $session_ids='';
	                $autokey='';
    	        }

                // Dinamic Changes
	            if ($curuser['autologin']) {
    	            $autokey=md5($timer->time.$uid);
	                setcookie('QF_Login', $autokey, $timer->time + 86400*30, '/');
	            }
    	        else {
	                $autokey='';
	                setcookie('QF_Login');
	            }

    	        $curuser['lastip']=$QF_Client['ipaddr'];
                $QF_DBase->sql_doupdate('{DBKEY}users', Array( 'lastseen' => $timer->time, 'lasturl' => $QF_Client['request'], 'sessid' => $session_ids, 'autologin' => $autokey, 'lastip' => $QF_Client['ipaddr']), Array( 'id' => $uid) );
            }
            else
                $QF_DBase->sql_doupdate('{DBKEY}users', Array( 'lastseen' => $timer->time, 'lastip' => $QF_Client['ipaddr']), Array( 'id' => $uid) );
        }

        if ($curuser['id']) {
            // Loadings
            if ($curuser['timezone']!='')
                $QF_Config['tz']=floatval($curuser['timezone']);
            if ($curuser['style']!=$QF_Config['style'] && $curuser['style'])
                $QF_Config['style']=trim($curuser['style']);
            if ($curuser['id']==1) $curuser['admin']=1;

            if ( $gid = $QF_Session->Get('QF_Guest') ) {
                $QF_DBase->sql_doupdate('{DBKEY}guests', Array( 'sessid' => ''), Array( 'gid' => $gid) );
                $QF_Session->Drop('QF_Guest');
            }

            if (!$curuser['approved']) {
                $curuser['rights']   = 0;
                $curuser['modlevel'] = 0;
                $curuser['admin']    = 0;
                $curuser['active']   = 0;
            } elseif (!$curuser['active']) {
                $curuser['modlevel'] = 0;
                $curuser['admin']    = 0;
            }

            $this->uid = $uid;
            $this->uname = $curuser['nick'];
            $this->level = ($curuser['admin']) ? 7 : $curuser['rights'];
            $this->wlevel = ($curuser['active']) ? $this->level : 0;
            $this->cuser = $curuser;
        }
        else {
            $QF_Session->Drop('QF_user QF_uid');
            unset ($curuser);

        	if ($QF_Config['enable_spiders']) {
	            $query='SELECT s.id, s.name, ss.visits FROM {DBKEY}spiders s
	                    LEFT JOIN {DBKEY}spiders_stats ss ON (ss.id = s.id)
	                    WHERE INSTR("'.addslashes($QF_Client['uagent']).'", agent_mask)>0
	                    ORDER BY LENGTH(agent_mask) DESC LIMIT 1';

        	    if ( $result = $QF_DBase->sql_query($query) ) {
		            $curspider = $QF_DBase->sql_fetchrow($result);
		            $QF_DBase->sql_freeresult($result);
    		    }
	            if ($curspider['name']) {
	                $ins_data=Array(
	                    'name'       => $curspider['name'],
	                    'time'       => $timer->time,
	                    'query'      => $QF_Client['request'],
	                    'user_agent' => $QF_Client['uagent'],
	                    'ip'         => $QF_Client['ipaddr'],
	                    );
	                $QF_DBase->sql_doinsert('{DBKEY}spiders_log', $ins_data);
	                $QF_DBase->sql_dodelete('{DBKEY}spiders_log', 'WHERE time < '.($timer->time - 86400));

    	            $upd_data = Array(
    	                'id'       => $curspider['id'],
    	                'lastseen' => $timer->time,
    	                'visits'   => ++$curspider['visits'],
    	                );
            	    $QF_DBase->sql_doinsert('{DBKEY}spiders_stats', $upd_data, true);

    	            $this->spider = $curspider;
    	            $this->is_spider = true;
    	        }
    	    }

	        if ($QF_Config['enable_guests'] && !$this->is_spider) {

        		if ( $gid = preg_replace('#[^0-9a-z]#', '', $QF_Session->Get('QF_Guest') ) ) {
		            $result = $QF_DBase->sql_doselect('{DBKEY}guests', '*', Array('gid' => $gid) );
        		    if ($result) {
		                $curguest = $QF_DBase->sql_fetchrow($result);
		                $QF_DBase->sql_freeresult($result);
		            }
        		}

		        if (!$curguest['gid']) {
		            $curguest=Array (
		                'gid' => md5(uniqid($timer->time.$QF_Client['ipaddr'])) );

        		    $ins_data = Array(
        		        'gid'         => $curguest['gid'],
        		        'sessid'      => $session_ids,
        		        'lastseen'    => $timer->time,
        		        'lasturl'     => $QF_Client['request'],
        		        'lastip'      => $QF_Client['ipaddr'],
        		        'guser_agent' => $QF_Client['uagent'],
        		        'gcode'       => $QF_Client['ipaddr'],
        		        'views'       => 1,
        		        );
		            $QF_DBase->sql_doinsert('{DBKEY}guests', $ins_data);
        		    setcookie('QF_Guest', $curguest['gid'], $timer->time + 86400, '/');
		        }
		        elseif(!$QF_Session->fix) {
        		    $curguest['views'] = intval($curguest['views'])+1;

        		    $upd_data = Array(
        		        'sessid'      => $session_ids,
        		        'lastseen'    => $timer->time,
        		        'lasturl'     => $QF_Client['request'],
        		        'lastip'      => $QF_Client['ipaddr'],
        		        'guser_agent' => $QF_Client['uagent'],
        		        'gcode'       => $curguest['gid'],
        		        'views'       => $curguest['views'],
        		        );
		            $QF_DBase->sql_doupdate('{DBKEY}guests', $upd_data, Array( 'gid' => $curguest['gid']) );

        		    $QF_DBase->sql_query($query);
		            setcookie('QF_Guest', $curguest['gid'], $timer->time + 86400*15, '/');
        		}

		        $QF_Session->Set('QF_Guest', $curguest['gid']);
        		if ($curguest['gtimezone']!='')
        		    $QF_Config['tz']=floatval($curguest['gtimezone']);
        		if ($curguest['gstyle']!=$QF_Config['style'] && $curguest['gstyle'])
		            $QF_Config['style']=trim($curguest['gstyle']);
        		$this->uname=$curguest['gnick'];
        		$QF_DBase->sql_dodelete('{DBKEY}guests', 'WHERE lastseen < '.($timer->time - 86400*15).' OR (lastseen < '.($timer->time-86400).' AND views <= 1)');

        		$this->guest = $curguest;
	        }
        }

        if ($curuser['admin'])
            $this->admin = true;
        else
            $QF_Session->Drop('is_admin');

        return true;

    }

}

//
// Userlist class to load users
//

Class UsersList {
    var $users   = Array();
    var $nick_id = Array();
    var $queries = Array();
    var $index   = 0;

    function load( $query, $doit=false )
    {
        $this->index++;
        $this->queries[$this->index]= Array(
            'query' => $query,
            'used'  => False );
        if ($doit)
            $this->doquery( $this->index );
        return $this->index;
    }

    Function get( $id, $qid=0 )
    {
        if (!$qid)
            $qid=$this->index;
        if (!$id)
            return null;
        elseif (empty($this->users[$id]) && !$this->queries[$qid]['used'])
            $this->doquery( $qid );

        return $this->users[$id];
    }

    Function by_nick( $nick, $qid=0 )
    {
        if (!$qid)
            $qid=$this->index;
        $nick = strtolower($nick);
        if (empty($this->nick_id[$nick]) && !$this->queries[$qid]['used'])
            $this->doquery( $qid );

        return $this->get($this->nick_id[$nick], $qid);
    }

    Function doquery( $qid )
    {
        Global $QF_DBase;

        $query='SELECT u.* FROM {DBKEY}users u '.$this->queries[$qid]['query'];
        $result=$QF_DBase->sql_query($query);
        if ($result) {
            while ( $user = $QF_DBase->sql_fetchrow($result))
            {
                if ($user['id']==1)
                    $user['admin']=1;
                if ($user['deleted'])
                    continue;
                if (!$user['approved'])
                {
                    $user['modlevel'] = 0;
                    $user['admin']    = 0;
                    $user['active']   = 0;
                }
                elseif (!$user['active'])
                {
                    $user['modlevel'] = 0;
                    $user['admin']    = 0;
                }

                $this->users[$user['id']] = $user;
                $this->nick_id[strtolower($user['nick'])] = $user['id'];
            }
            $QF_DBase->sql_freeresult($result);
        }
        $this->queries[$qid]['used']=true;
    }

    Function timesort ()
    {
        uasort($this->users, 'qf_ulist_timesort_func');
    }

    Function id_order ()
    {
        uasort($this->users, 'qf_ulist_idsort_func');
    }

}

Function qf_ulist_timesort_func($a, $b)
{
   if ( $a['lastseen'] == $b['lastseen'] )
       return 0;

   return ( $a['lastseen'] > $b['lastseen'] ) ? -1 : 1;
}

Function qf_ulist_idsort_func($a, $b)
{
   if ( $a['id'] == $b['id'] )
       return 0;

   return ( $a['id'] < $b['id'] ) ? -1 : 1;
}

?>