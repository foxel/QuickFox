<?php

// Simple InUSE Check
if ( !defined('QF_STARTED') )
        die('Hacking attempt');

// QuickFox Core initialization
// Defining Core constants
define('EMAIL_MASK', '^[0-9a-zA-Z_\-\.]+@[0-9a-zA-Z_\-\.]+\.[a-zA-Z]{2,4}$');
define('UNAME_MASK', '^[0-9\w_\+\-=\(\)\[\] ]{3,16}$');

//
// Gets current time for page generetion time counter
//
Error_Reporting(E_ALL & ~E_NOTICE);
set_magic_quotes_runtime(0);

//Функция обработки ошибок на стадии инициализации. После инициализации юзается error_catcher
function init_err_parse($errno, $errstr, $errfile, $errline)
{
    global $debug;
    static $logfile;

    if (!$logfile)
        $logfile = fopen('init_err_log.txt','wb');

    if ($logfile && $errno!=8)
        fwrite($logfile,'Error '.$errno.'. '.$errstr.'<hr>File: '.$errfile.'. Line: '.$errline."\r\n");

    If ($debug) {
        if ($errno!=8&&$errno!=1024&&$errno!=2048)
        print "Error $errno. $errstr <hr>File: $errfile. Line: $errline<br />";
    }
}

function Cont_Measurer($page)
{    header('Content-Length: '.strlen($page));
    return $page;
}

ob_start('Cont_Measurer');

class QF_Timer
{	var $start_time;
	var $time;
	var $point_time;
	var $events = Array();

	function QF_Timer()
	{		$this->start_time = $this->Get_Time();
		$this->time = time();
		$this->point_time = $this->start_time;
	}

	function Get_Time()
	{        $time = explode(' ',microtime());
        $time = $time[1]+$time[0];
        return $time;
	}

    function Time_Point()
    {    	$time = $this->Get_Time();
    	$diff = $time - $this->point_time;
    	$this->point_time = $time;
    	return $diff;
    }

    function Time_Spent()
    {    	return ($this->Get_Time() - $this->start_time);
    }

    function Time_Log($event = '')
    {        $this->events[] = Array(
            'time' => $this->Time_Spent(),
            'name' => $event );
    }
}

$timer = New QF_Timer();


//Lets Set init_err_handler
set_error_handler('init_err_parse');
if (ini_get('safe_mode') == 0)
    set_time_limit(0);

require 'db/mysql4.php';

$QF_DBase = new qf_sql_base($QF_Dbase_Config, True, True);

if (!$QF_DBase->db_connect_id)
{
  echo( '<P>SQL Initialization Error.
         Please try later.</P>' );
  exit();
}

// Setting initial output buffer
ob_start();

// Init Vars
$QF_Job='';
$QF_Smiles=array();
$QF_Banned=False;
$QF_Locked=False;
$QF_Fix_Session=False;
$QF_Pagedata=Array();
$QF_Styles=Array();

// Loading Initial Modules and Data
require 'kernel/core_functs.php';

// Setting an error parser
set_error_handler('err_parse');

// Loading Users module
include 'kernel/core_users.php';
// Init curuser
$QF_User = new qf_curuser();
// init users list
$ulist= new UsersList;

// Loading Sessions module
include 'kernel/core_sess.php';
// Init session
$QF_Session = new qf_session_cl();

// Loading parser module
require 'kernel/core_parser.php';
// Init msg parser
$QF_Parser=new qf_parser();

//Constrs
$Forum_Root_Name='';


// Initial Default config values
require 'kernel/QF_def_conf.php';

$QF_Config=$def_config;


Unset($def_config);
// <-------

// Include script
$QF_Inc=Get_Request('st', 1, 'v');
// Job is a script for alternative output (preview, download...)
$QF_Job=Get_Request('sr', 1, 'v');
$QF_Script=Get_Request('script', 2, 'v');
// Get QF root dir and relative
$QF_SrvName = preg_replace('#^\/*?(.*)\/*$#', '\1', trim($_SERVER['SERVER_NAME']));
$QF_RootUrl = 'http://'.$QF_SrvName.'/';

$QF_Root = preg_replace('#\/+|\\\+#', '/', $_SERVER['PHP_SELF']);
$QF_Root = preg_replace('#^\/*([^\'\"]*)\/+([^\/]+)$#', '\\1', $QF_Root);
if ($QF_Root)
    $QF_RootUrl.= $QF_Root.'/';
$QF_RequestURI = preg_replace('#\/+|\\\+#', '/', '/'.$_SERVER['REQUEST_URI']);
$QF_RequestURI = preg_replace('#([\'\"])#s', '', $QF_RequestURI);
$QF_RequestURI = substr(preg_replace('#^\/*'.$QF_Root.'\/+#', '', $QF_RequestURI),0,255);

// Get client parameters
$QF_Client = Array(
    'time'    => $timer->start_time,
    'uagent'  => substr($_SERVER['HTTP_USER_AGENT'],0,255),
    'ipaddr'  => substr($_SERVER['REMOTE_ADDR'],0,16),
    'ip_hex'  => encode_ip($_SERVER['REMOTE_ADDR']),
    'referer' => substr($_SERVER['HTTP_REFERER'],0,255),
    'request' => $QF_RequestURI,
);

// Init Server settings
$QF_Config['server_name']=$_SERVER['SERVER_NAME'];
$QF_Config['server_port']=$_SERVER['SERVER_PORT'];
$QF_Config['root']=$QF_Root;

$result=$QF_DBase->sql_doselect('{DBKEY}config');
if ($result) {    while ( $setting = $QF_DBase->sql_fetchrow($result))
        if ( !empty($setting['name']) )
        {
            if (!empty($setting['parent']))
                $QF_Config[$setting['parent']][$setting['name']] = $setting['value'];
            else
                $QF_Config[$setting['name']] = $setting['value'];
        }

    $QF_DBase->sql_freeresult($result);
};

// Bancheck
$result=$QF_DBase->sql_doselect('{DBKEY}bans', '', 'WHERE "'.$QF_Client['ip_hex'].'" BETWEEN first_ip AND last_ip ');
if ($result) {    $current_ban = $QF_DBase->sql_fetchrow($result);
    $QF_DBase->sql_freeresult($result);

    if ($current_ban['ban_id']) {
        $QF_Banned = true;
        $QF_DBase->sql_doupdate('{DBKEY}bans', Array(
            'used' => $current_ban['used'] + 1,
            'lastused' => $timer->time ), ' WHERE ban_id='.$current_ban['ban_id']);
    }
}

// Are the site is locked?
if ($QF_Config['site_locked_for'])
{    $QF_Sid = Get_Request('QF_SID', 3, 'h');

    if ($QF_Config['site_locked_for']<$timer->start_time)        $QF_DBase->sql_dodelete('{DBKEY}config', 'WHERE parent="" AND name IN ("site_locked_for", "site_locker_sid") ');
    elseif ($QF_Config['site_locker_sid']!=$QF_Sid) {        $QF_Locked = true;
        $QF_Inc = '';
        $QF_Script = '';
        $QF_Job = '';
    }

    // can't be blocked more then for 1 hour
    if ($QF_Config['site_locked_for']>$timer->start_time+3600)
        $QF_DBase->sql_doupdate('{DBKEY}config', Array('value' => $timer->time+3600), Array( 'parent' => '', 'name' => 'site_locked_for') );

    unset($QF_sid);
}

// Applying DefConf
$QF_Config['tz']=$QF_Config['def_tz'];
$QF_Config['lang']=$QF_Config['def_lang'];
$QF_Config['style']=$QF_Config['def_style'];

$Forum_Root_Name=$QF_Config['forum']['root_name'];
$Page_Title=$QF_Config['site_name'];

$timer->Time_Log('Config loaded');


require 'kernel/core_email.php';

// Language
Load_Language();

if (empty($Forum_Root_Name)) $Forum_Root_Name = $lang['FOR_ROOT_NAME'];

// Closing Initial Buffer
$QF_InitBuffer=ob_get_clean();

$timer->Time_Log('Full Init');

?>
