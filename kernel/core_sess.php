<?php

// Simple InUSE Check
if ( !defined('QF_STARTED') )
        die('Hacking attempt');

if ( defined('CORE_SESSION_LOADED') )
        die('Scripting error');

define('CORE_SESSION_LOADED', True);

// ----------------------------------------------------------- \\
//            Quick Fox session management class  LION 2007    \\
// ----------------------------------------------------------- \\

class qf_session_cl
{    var $SID = '';
    var $sess_data = Array();
    var $sess_cache = Array();
    var $got_cache = Array();
    var $upd_cache = Array();
    var $drop_cache = Array();
    var $sp_code = '';
    var $sp_time = 0;
    var $clicks = 1;
    var $loaded = false;
    var $started = false;
    var $use_url = false;
    var $fix = false;

    function qf_session_cl()
    {        Global $QF_Session;
        if (defined('QF_SESSION_CREATED'))
            trigger_error('Duplicate session manager creation!', 256);

        define('QF_SESSION_CREATED', true);

        $QF_Session = $this;
    }

    function Open_Session($fix = false)
    {        global $QF_DBase;
        global $QF_Client;

        if ($this->started)
            return true;

        $this->fix = ($fix) ? true : false;

        $this->SID = Get_Request('QF_SID', 3, 'h', 32);

        if (!$this->SID) {
            $this->use_url = true;
            $this->SID = Get_Request('QF_SID', 0, 'h', 32);
        };

        if ($this->SID) {

            if ( $result = $QF_DBase->sql_doselect('{DBKEY}sessions', '*', Array('sid' => $this->SID, 'ip' => $QF_Client["ipaddr"]) ) ) {
                $sess = $QF_DBase->sql_fetchrow($result);
                $QF_DBase->sql_freeresult($result);
            }

            if (is_array($sess)) {
                $this->sess_data = unserialize($sess['vars']);

                $this->sp_code = $sess['spamcode'];
                $this->sp_time = $sess['spctime'];
                $this->clicks = $sess['clicks'] + 1;
                $this->loaded = true;
            }
            else
                $this->SID = md5( uniqid($QF_Client['ipaddr'].$QF_Client['time'], true) );
        }
        else
            $this->SID = md5( uniqid($QF_Client['ipaddr'].$QF_Client['time'], true) );

        setcookie('QF_SID',$this->SID,0,'/');

        ob_start('qf_session_handler');

        if (!$this->loaded)
            $this->Cache_Clear();

        $QF_Debug = Get_Request('debug', 1);
        if (isset($QF_Debug))
            $this->sess_data['QF_Debug'] = ($QF_Debug) ? 1 : NULL;

        $this->started = True;

        return true;

    }

    // session variables control
    function Get($query)
    {
        if (!$this->started)
            return false;

        $names = explode(' ', $query);
        if (count($names)>1) {
            $out = Array();
            foreach ($names as $name)
                $out[$name] = $this->sess_data[$name];
            return $out;
        }
        else
            return $this->sess_data[$query];
    }

    function Set($name, $val)
    {
        if (!$this->started)
            return false;

        return ($this->sess_data[$name] = $val);
    }

    function Drop($query)
    {
        if (!$this->started)
            return false;

        $names = explode(' ', $query);
        if (count($names)) {
            $out = Array();
            foreach ($names as $name)
                unset ($this->sess_data[$name]);
            return true;
        }
        else
            unset ($this->sess_data[$query]);
        return true;
    }

    // session cache control
    function Cache_Get($name)
    {        global $QF_DBase;
        if (!$this->started)
            return false;

        if (!in_array($name, $this->got_cache)) {
            if ( $result = $QF_DBase->sql_doselect('{DBKEY}sess_cache', 'ch_data', Array('sid' => $this->SID, 'ch_name' => $name) ) ) {
                if ($QF_DBase->sql_numrows($result)) {
                    list($tmp) = $QF_DBase->sql_fetchrow($result, false);
                    $this->sess_cache[$name] = unserialize($tmp);
                }
                $QF_DBase->sql_freeresult($result);
            }
            $this->got_cache[] = $name;
        }

        return $this->sess_cache[$name];
    }

    function Cache_Add($name, $value)
    {        if (!$this->started)
            return false;

        $this->sess_cache[$name] = $value;

        $this->upd_cache[] = $name;
    }

    function Cache_Drop($name, $global=false)
    {
        if (!$this->started)
            return false;

        $this->got_cache[] = $name;
        $this->sess_cache[$name] = null;

        if ($global)
            $this->drop_cache[] = $name;
        else
            $this->upd_cache[] = $name;
    }

    function Cache_Drop_List($list, $global=false)
    {
        if (!$this->started)
            return false;

        $names = explode(' ', $list);
        if (count($names)) {
            $out = Array();
            foreach ($names as $name)
                $this->Cache_Drop($name, $global);

            return true;
        }
        else
            return false;

    }

    function Cache_Clear()
    {        if (!$this->started || $this->fix)
            return false;

        global $QF_DBase, $timer;
        $this->sess_cache = Array();
        $this->drop_cache = Array();
        $this->upd_cache = Array();

        $QF_DBase->sql_dodelete('{DBKEY}sess_cache', Array('sid' => $this->SID) );

        return true;
    }


    function Cache_Do()
    {        if (!$this->started || $this->fix)
            return false;

        global $QF_DBase, $timer;
        $this->drop_cache = array_unique($this->drop_cache);
        $this->upd_cache = array_unique($this->upd_cache);

        foreach ($this->drop_cache as $name)            $QF_DBase->sql_dodelete('{DBKEY}sess_cache', Array('ch_name' => $name) );

        foreach ($this->upd_cache as $name) {
            $query = false;
            if (!$this->sess_cache[$name]) {
                if (!in_array($name, $this->drop_cache))
                    $QF_DBase->sql_dodelete('{DBKEY}sess_cache', Array('ch_name' => $name, 'sid' => $this->SID) );
            }
            else
                $QF_DBase->sql_doinsert('{DBKEY}sess_cache', Array('sid' => $this->SID, 'ch_name' => $name, 'ch_data' => serialize($this->sess_cache[$name]), 'ch_stored' => $timer->time), true );
        }

        $this->drop_cache = Array();
        $this->upd_cache = Array();
        return true;
    }

    function CheckSpamCode($code) {
        global $QF_DBase, $QF_Config, $timer, $lang;
        if (!$this->SID)
            return false;

        if (!$QF_Config['use_spcode'])
            return True;

       $QF_DBase->sql_doupdate('{DBKEY}sessions', Array('spamcode' => '', 'spctime' => 0), Array('sid' => $this->SID) );

       if (!$code)
           Set_Result($lang['ERR_SPAM_NOCODE'],'','');
       elseif ($this->sp_code != strtolower($code) || ($timer->time - $this->sp_time)>600)
           Set_Result($lang['ERR_SPAM_WRONG'],'','');

       return true;
    }


    function AddSID($url, $ampersand=false){
        $url=trim($url);

        if ( $this->use_url && !strstr($url, 'QF_SID=') && !stristr($url, 'javasript') )
        {            $insert = ( !strstr($url, '?') ) ? '?' : ($ampersand) ? '&amp;' : '&';
            $insert.= 'QF_SID='.$this->SID;

            $url= preg_replace('#(\#|$)#', $insert.'\\1', $url, 1);

        }

        return $url;
    }
}

function qf_session_handler($text)
{    Global $timer, $QF_Config, $lang, $GZipped, $QF_User, $QF_Client;
    Global $QF_DBase;
    Global $QF_Session;
    global $timer;

    if (!is_a($QF_Session, 'qf_session_cl'))
        return $text;
    elseif (!$QF_Session->started)
        return $text;
    elseif ($QF_Session->fix)
        return $text;


    if (!$QF_User->is_spider) {
        $QF_Session->Cache_Do();

        $data=serialize($QF_Session->sess_data);

        if ($QF_Session->loaded)
            $QF_DBase->sql_doupdate('{DBKEY}sessions', Array('vars' => $data, 'lastused' => $timer->time, 'clicks' => $QF_Session->clicks), Array('sid' => $QF_Session->SID) );
        else
            $QF_DBase->sql_doinsert('{DBKEY}sessions', Array('sid' => $QF_Session->SID, 'ip' => $QF_Client['ipaddr'], 'starttime' => $timer->time, 'vars' => $data, 'lastused' => $timer->time, 'clicks' => $QF_Session->clicks) );

        $QF_DBase->sql_query($query);
        $err=$QF_DBase->sql_error();
        if ($err['message']) trigger_error('Session_management: Can\'t set session record '.$err['message'], 256);

        $QF_DBase->sql_dodelete('{DBKEY}sessions', 'WHERE lastused < '.($timer->time-3600) );
        if ($QF_DBase->sql_affectedrows()) { //let's clear old session caches
            $QF_DBase->sql_dodelete('{DBKEY}sess_cache', 'WHERE ch_stored < '.($timer->time-3600) );
        }

        if ($QF_Session->use_url ) {
            function SID_Compose($vars)
            {                global $QF_Session;
                $url = $vars[3];
                if ( !strstr($url, 'QF_SID=') && !strstr($url, 'javascript') )
                {
                    $insert = ( !strstr($url, '?') ) ? '?' : '&amp;';
                    $insert.= 'QF_SID='.$QF_Session->SID;

                    $url= preg_replace('#(\#|$)#', $insert.'\\1', $url, 1);
                }

                return $vars[1].' = '.$vars[2].$url.$vars[2];
            }

            $text=preg_replace_callback('#(href)\s*=\s*(\"|\'|)([^\s<>\(\)]*)(\\2)#i', 'SID_Compose', $text);
            $text=preg_replace('#(<form .*?>)#i', "\\1\n".'<input type="hidden" name="QF_SID" value="'.$QF_Session->SID.'">', $text);
        }
    }

    if ($QF_Config['use_spcode'] && !$QF_User->uid) {
        $text=str_replace('{SPAMFIELD}', Visual('SPAM_FIELD'),$text);
        $text=str_replace('{SPAMIMG}', '<img src="index.php?sr=spamcode&amp;sid='.$QF_Session->SID.'&amp;rand='.rand(1000,10000).'" alt="SpamCode">', $text);
    }
    else {
        $text=str_replace('{SPAMFIELD}','',$text);
        $text=str_replace('{SPAMIMG}','',$text);
    }

    if ($timer) {

        $PHP_time = round($timer->Time_Spent(), 3);
        $SQL_time = round($QF_DBase->queries_time, 3);

        $pagestats = sprintf($lang['FOOT_STATS'], $PHP_time, $QF_DBase->num_queries, $SQL_time);

        if ($QF_Session->sess_data['QF_Debug'] && $QF_User->admin) {            $debug = '<b>Time Points:</b>';
            foreach($timer->events AS $event) {
                $debug.='<br />'.$event['name'].' => '.round($event['time'],5);
            }

            $debug.= '<br /><b>SQL History:</b><br /> => '.nl2br(htmlspecialchars(implode("\n => ", $QF_DBase->history)));
            $pagestats.= Vis_Draw_Panel($debug, 'Debug Info', '750', true);
        }
        $text=str_replace('<!-PageStats-!>', $pagestats, $text);
    }

    return $text;

}

//
// Adding SID to url
//

//

?>