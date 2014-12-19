<?php

// Simple InUSE Check
if ( !defined('QF_STARTED') )
        die('Hacking attempt');

if ( defined('CORE_FUNCITONS_LOADED') )
        die('Scripting error');

define('CORE_FUNCITONS_LOADED', True);

define('QF_RURL_MASK', '[\w_\#$%&~/.\-;:=,?@+\(\)\[\]]+');
define('QF_FURL_MASK', '(?>[0-9A-z]+://[0-9A-z_\-\.]+\.[A-z]{2,4})(?:\/'.QF_RURL_MASK.')?');

//
// Error messages parser
//
if (!defined('E_STRICT'))
    define('E_STRICT', 2048);

function err_parse($errno, $errstr, $errfile, $errline)
{
    global $debug, $QF_User, $lang;
    static $logfile;

    if (!$logfile) {        $logfile = fopen('err_log.txt','ab');
    }

    if ($logfile && ($errno & ~(E_NOTICE | E_USER_NOTICE | E_STRICT | E_DEPRECATED)))
        fwrite($logfile,date('[d M Y H:i]',time()).' - Error '.$errno.'. '.$errstr.'. File: '.$errfile.'. Line: '.$errline."\r\n");

    If ($debug && $QF_User->uid==1)
    {
        if ($errno & ~(E_NOTICE | E_USER_NOTICE | E_STRICT | E_DEPRECATED) )
            print '<table class="border error"><tr><td align="center">Error '.$errno.'. '.$errstr.'<hr />File: '.$errfile.'. Line: '.$errline.'</td></tr></table><br />';
    }

    elseif ($errno & ~(E_NOTICE | E_WARNING | E_USER_NOTICE | E_USER_WARNING | E_STRICT | E_DEPRECATED))
    {
        ob_clean();
        if (!$lang['ERR'])
            echo ('<html><head><title>Error</title></head><body><h1>There is an error!!!</h1>Maybe next time will be better</body></html>');
        else
            echo ('<html><head><title>'.$lang['ERR'].'</title></head><body><h1>'.$lang['ERR_CRIT_PAGE'].'</h1>'.$lang['ERR_CRIT_MESS'].'</body></html>');
        QF_exit();
    };

}


//
// returns request value
//
function Get_Request($var_name, $from=0, $type = '', $len = false)
{    global $_GET, $_POST, $_COOKIE, $_REQUEST, $QF_CharConv;

    static $cache = Array();

    if (!isset($cache[$from][$var_name]))
    {        switch ($from) {            case 1:
                $val = $_GET[$var_name];
                break;
            case 2:
                $val = $_POST[$var_name];
                break;
            case 3:
                $val = $_COOKIE[$var_name];
                break;
            default:
                $val = $_REQUEST[$var_name];
        }
        if (get_magic_quotes_gpc() && !is_numeric($val) && !is_null($val)) {            if (is_array($val))
                $val = Array_Unslash($val);
            else
                $val = stripslashes($val);
        }
        if (defined('AJAX_UTF_RECHAR'))
            $val = $QF_CharConv->UTFto_Conv($val);

        $cache[$from][$var_name] = $val;
    }
    else
        $val = $cache[$from][$var_name];

    switch ($type) {        case 'b': // boolean
            $val = ($val) ? true : false;
            $len = false;
            break;
        case 'i':
            $val = intval($val);
            $len = false;
            break;
        case 'f':
            $val = floatval($val);
            $len = false;
            break;
        case 'h': //heximal string;
            $val = preg_replace('#[^0-9a-fA-F]#', '', $val);
            break;
        case 's': //string;
            $val = trim(strval($val));
            break;
        case 'ht': //hypertext escaped string;
            $val = HTMLStrVal($val, ENT_NOQUOTES, QF_ENCODING);
            break;
        case 'v': //var string without spaces;
            $val = preg_replace('#[^0-9a-zA-Z_\-]#', '', $val);
            break;
    }

    if ($len>0)
        $val = substr($val, 0, intval($len));

    return $val;
}

function Get_Request_Multi($vals_list, $from=0, $type = '', $len = false){
    $needed=explode(' ',$vals_list);
    $out = Array();
    if (is_array($needed))
        foreach ($needed as $needval)
            $out[] = Get_Request($needval, $from, $type, $len);

    return $out;
}

function qf_str_is_url($string)
{
    static $MASK1, $MASK2;
    $MASK1 = '#^'.QF_FURL_MASK.'$#D';
    $MASK2 = '#^'.QF_RURL_MASK.'$#D';
    if (preg_match($MASK1, $string))
        return 1;
    elseif (preg_match($MASK2, $string))
        return 2;
    else
        return 0;
}

function qf_array_2dresort($array, $field, $rsort = false, $sort_flags = SORT_REGULAR)
{
    if (!is_array($array))
        return $array;
    $resorter = Array();
    foreach ($array as $key=>$val)
    {
        if (!is_array($val) || !isset($val[$field]))
            $skey = 0;
        else
            $skey = $val[$field];

        if (!isset($resorter[$skey]))
            $resorter[$skey] = Array();
        $resorter[$skey][$key] = $val;
    }
    if ($rsort)
        krsort($resorter, $sort_flags);
    else
        ksort($resorter, $sort_flags);
    $array = Array();
    foreach ($resorter as $valblock)
        $array+= $valblock;

    return $array;
}

//
// drops slashes inside array
//
function Array_Unslash($arr)
{    if (!is_array($arr)) {
        if (!is_numeric($arr))
            $arr = stripslashes($arr);
    }
    else {        foreach ($arr AS $key=>$val)
            $arr[$key] = Array_Unslash($val);
    }

    return $arr;
}

//
// Sets globals from the request
//
function Glob_Request($vals_list,$allreq=false,$overwrite=false){
    $needed=explode(' ',$vals_list);
    foreach ($needed as $needval) {
        global $$needval;
        if (!isset($$needval) || $overwrite) {
            $found=($allreq) ? $_REQUEST[$needval] : $_GET[$needval];
            if (isset($found))                $$needval=(get_magic_quotes_gpc()) ? stripslashes($found) : $found;
        }
    }
}

//
// Stripslashes for $_POST data
//
Function PostStrip()
{    Global $_POST, $HTTP_POST_VARS;
    if (get_magic_quotes_gpc())
        foreach ($_POST as $key=>$val)
        if (!is_array($val)) {            $val = stripslashes($val);
            $_POST[$key] = $val;
            $HTTP_POST_VARS[$key] = $val;
        }
}


// Closes all the OB buffers
function qf_ob_free()
{
    if (function_exists('ob_get_level')) // PHP 4.2.0
        while (ob_get_level())
            ob_end_clean();
    else
        while (ob_get_contents()!==false)
            ob_end_clean();
}

function qf_ob_flush()
{
    if (function_exists('ob_get_level')) // PHP 4.2.0
        while (ob_get_level())
            ob_end_flush();
    else
        while (ob_get_contents()!==false)
            ob_end_flush();
}

//
// Accurate Exit
//
Function QF_exit($mess = false)
{    global $QF_DBase;

    if ($mess === false)
        qf_ob_flush();
    else
    {
        qf_ob_free();
        print $mess;
    }
    exit();
}

function GetFullUrl($url, $no_amps = true)
{
    global $QF_Config;

    if ($url{0} == '#')
        return $url;

    $url_p = parse_url($url);

    if (preg_match('#mailto#i', $url_p['scheme']))
        return $url;

    $url = '';
    if (isset($url_p['scheme']))
        $url.= $url_p['scheme'].'://';
    else
        $url.= ($QF_Config['cookie_secure']) ? 'https://' : 'http://';

    if (isset($url_p['host']))
    {
        if (isset($url_p['username']))
        {
            $url.= $url_p['username'];
            if (isset($url_p['password']))
                $url.= $url_p['password'];
            $url.= '@';
        }
        $url.= $url_p['host'];
        if (isset($url_p['port']))
            $url.= ':'.$url_p['port'];

        if (isset($url_p['path']))
            $url.= preg_replace('#(\/|\\\)+#', '/', $url_p['path']);
    }
    else
    {
        $url.= trim($QF_Config['server_name']);
        if (isset($url_p['path']))
        {
            if ($url_p['path']{0} != '/')
                $url_p['path'] = '/'.$QF_Config['root'].'/'.$url_p['path'];
        }
        else
            $url_p['path'] = '/'.$QF_Config['root'].'/index.php';

        $url_p['path'] = preg_replace('#(\/|\\\)+#', '/', $url_p['path']);
        $url.= $url_p['path'];
    }

    if (isset($url_p['query']))
        $url.= '?'.$url_p['query'];

    if (isset($url_p['fragment']))
        $url.= '#'.$url_p['fragment'];

    $url = ($no_amps) ? str_replace('&amp;', '&', $url) : preg_replace('#\&(?![A-z]+;)#', '&amp;', $url);

    return $url;
}


//
// Redirecting function
//
function redirect($url)
{
    global $QF_DBase, $QF_Config;

    Ignore_User_Abort(True);

    if (strstr(urldecode($url), "\n") || strstr(urldecode($url), "\r"))
        trigger_error('Tried to redirect to potentially insecure url.', 256);

    $furl=GetFullUrl($url);

    // Redirect via an HTML form for PITA webservers
    if (@preg_match('/Microsoft|WebSTAR|Xitami/', getenv('SERVER_SOFTWARE')))
    {
                ob_clean();
                header('Refresh: 0; URL=' . $furl);
                echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"><html><head><meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"><meta http-equiv="refresh" content="0; url=' . $server_protocol . $server_name . $server_port . $script_name . $url . '"><title>Redirect</title></head><body><div align="center">If your browser does not support meta redirection please click <a href="' . $server_protocol . $server_name . $server_port . $script_name . $url . '">HERE</a> to be redirected</div></body></html>';
                QF_exit();
    }

    // Behave as per HTTP/1.1 spec for others
    header('Location: ' . $furl);
    QF_exit();
}

//
// Create date/time from format and timezone
//
function create_date($format, $timestamp, $tz='', $dontconv=false)
{
    global $QF_Config, $lang, $timer;

    static $translate, $now, $correct, $today, $yesterday, $last_tz = '-';

    if (!$now) {        if (is_object($timer))
            $now = $timer->time;
        else
            $now=time();
    }

    if (!$correct)
        $correct=intval($QF_Config['date_corr_mins']);

    if ( empty($translate) && $QF_Config['lang'] != 'en' )
        if (is_array($lang['datetime']))
            foreach ($lang['datetime'] as $match=>$replace)
                $translate[$match] = $replace;

    if (!strlen($tz))
        $tz=$QF_Config['tz'];
    else
        $tz=intval($tz);

    $tzc = (3600 * $tz + 60 * $correct); // correction of GMT

    if ($last_tz!==$tz) {
        $today = $now + $tzc;
        //if (qf_time_DST($now, $tz))
        //    $today+= 3600;
        $today = floor($today/86400)*86400;
        $yesterday = $today - 86400;
        $last_tz = $tz;
    }

    if (!$format)
        $format=$QF_Config['def_date_format'];

    $timetodraw = $timestamp + $tzc;
    //if (qf_time_DST($timestamp, $tz))
    //    $timetodraw+= 3600;


    if ($timestamp == $now || $dontconv)
        $out = @gmdate($format, $timetodraw);
    elseif ($timestamp > $now) {
        if ($timestamp<$now + 60)
            $out = sprintf($lang['time_future_secs'],($timestamp - $now));
        elseif ($timestamp<$now + 3600)
            $out = sprintf($lang['time_future_mins'],round(($timestamp - $now)/60));
        else
            $out = @gmdate($format, $timetodraw);
    }
    elseif ($timestamp>$now - 60)
        $out = sprintf($lang['time_seconds'],($now - $timestamp));
    elseif ($timestamp>$now - 3600)
        $out = sprintf($lang['time_minutes'],round(($now - $timestamp)/60));
    elseif ($timetodraw>$today)
        $out = sprintf($lang['time_today'],@gmdate($QF_Config['def_time_format'], $timetodraw));
    elseif ($timetodraw>$yesterday)
        $out = sprintf($lang['time_yesterday'],@gmdate($QF_Config['def_time_format'] ,$timetodraw));
    else
        $out = @gmdate($format, $timetodraw);

    return ( !empty($translate) ) ? strtr($out, $translate) : $out;

}

// checks if given timestamp is DST (QF2 kernel function)
function qf_time_DST($time, $tz = 0, $style = '')
{
    static $styles = Array(
        'eur' => Array('+m' => 3, '+d' => 25, '+wd' => 0, '+h' => 2, '-m' => 10, '-d' => 25, '-wd' => 0, '-h' => 2),
        'usa' => Array('+m' => 3, '+d' =>  8, '+wd' => 0, '+h' => 2, '-m' => 11, '-d' =>  1, '-wd' => 0, '-h' => 2),
        );
    static $defstyle;
    $defstyle = $styles['eur'];

    $style = strtolower($style);

    if (isset($styles[$style]))
        $DST = $styles[$style];
    else
        $DST = $defstyle;

    if (!isset($DST['gmt']))
        $time += (int) $tz*3600;

    if ($data = gmdate('n|j|w|G', $time))
    {
        $data = explode('|', $data);
        $cm = $data[0];
        if ($cm < $DST['+m'] || $cm > $DST['-m'])
            return false;
        elseif ($cm > $DST['+m'] && $cm < $DST['-m'])
            return true;
        else
        {
            if ($cm == $DST['+m'])
            {
                $dd = $DST['+d'];
                if (isset($DST['+wd']))
                    $dwd = $DST['+wd'];
                $dh = $DST['+h'];
                $bres = false;
            }
            else
            {
                $dd = $DST['-d'];
                if (isset($DST['-wd']))
                    $dwd = $DST['-wd'];
                $dh = $DST['-h'];
                $bres = true;
            }
            $cd = $data[1];


            if ($cd < $dd)
                return $bres;
            elseif (!isset($dwd))
            {
                if ($cd > $dd)
                    return !$bres;
                else
                    return ($data[3] >= $dh) ? !$bres : $bres;
            }
            else
            {
                $cvwd = $cd - $dd;
                if ($cvwd >= 7)
                    return !$bres;

                $cwd = $data[2];
                $dvwd = ($dwd - $cwd + $cvwd) % 7;
                if ($dvwd < 0)
                    $dvwd += 7;

                if ($cvwd < $dvwd)
                    return $bres;
                elseif ($cvwd > $dvwd)
                    return !$bres;
                else
                    return ($data[3] >= $dh) ? !$bres : $bres;
            }
        }
    }
    else
        return false;
}


//
// File Reading function with error protection
//
function File_Read($filename) {

    if (!@file_exists($filename))
        trigger_error('Filer: file is missing: '.$filename, 256);

    if (!($fd = @fopen($filename, 'r')))
        trigger_error('Filer: file is corrupted: '.$filename, 256);

    return $fd;
}


//
// Extended CSS parser
//
function Combine_ECSS($file) {
    Global $QF_Config;
    $CSSVars=Array();

    if ($sfile=@fopen($file,'r')) {
        $indata=fread($sfile,filesize($file));
        $vars_mask='#\{([\w\-_]+)\}\s*=\s*([^\s]+)#';
        $vars_block='#\{VARS\}(.*)\{/VARS\}#si';

        preg_match_all($vars_block,$indata,$blocks);
        $blocks=implode(' ',$blocks[0]);

        preg_match_all($vars_mask,$blocks,$sets);
        if (is_array($sets[1]))
            foreach ($sets[1] as $num => $name)
                $CSSVars[$name] = $sets[2][$num];

        $CSSVars['CSS_IMGS']=$QF_Config['CSS_imgs_dir'];

        $cssdata=preg_replace($vars_block,'',$indata);
        $cssdata=preg_replace('#\{(\w+)\}#e','$CSSVars["\1"]',$cssdata);

        fclose($sfile);
        return $cssdata;
    }
}

function Load_Language($part = '')
{
    Global $QF_Config, $lang;
    $part = ($part) ? $part : 'main';
    $lang_file = ($part) ? $part.'.php' : 'main.php';
    $lang_file = 'langs/'.$QF_Config['def_lang'].'/'.$part.'.php';
    $mlang_file = 'langs/'.$QF_Config['def_lang'].'/'.$part.'_mods.php';

    if (file_exists($lang_file))
    {
        include $lang_file;
        if (file_exists($mlang_file))
            include $mlang_file;
    }
    else
        trigger_error('Error loading language file for ['.$QF_Config['def_lang'].'] part "'.$part.'"', 256);
}

//
// Sets a script result data. Must be used to show the script out data.
//
function Set_Result($error, $result, $redirurl="") {
    global $QF_DBase;
    global $QF_User, $QF_Config, $timer, $QF_Session;

    $rsrc=($QF_User->uid) ? $QF_User->uname : 'QF_Guest';
    $rid=md5(uniqid($timer->time.$rsrc));

    $QF_DBase->sql_dodelete('{DBKEY}results', 'WHERE time < '.($timer->time - 600) );

    $QF_DBase->sql_doinsert('{DBKEY}results', Array('id' => $rid, 'time' => $timer->time, 'error' => strval($error), 'result' => strval($result), 'redirect' => strval($redirurl)), true );

    $err=$QF_DBase->sql_error();

    if ($err['message'])
        trigger_error('Result_management: Can\'t set result record '.$err['message'], 256);
    else
        redirect($QF_Session->AddSID('index.php?st=showresult&rid='.$rid));
}

//
// Sets a admin lock. Set 0 to Drop block.
//
function Set_Adm_Lock($period=600)
{    global $QF_DBase;
    global $QF_User, $QF_Config, $QF_Session, $timer;
    if ($QF_User->admin && $QF_Session->Get('is_admin')) {        $period = intval($period);
        if ($period>3600)
            $period=3600;
        elseif ($period<60)
            $period=0;

        if ($period>0)
        {
            $QF_DBase->sql_doinsert('{DBKEY}config', Array('parent' => '', 'name' => 'site_locked_for', 'value' => ($timer->time+$period)), true );
            $QF_DBase->sql_doinsert('{DBKEY}config', Array('parent' => '', 'name' => 'site_locker_sid', 'value' => $QF_Session->SID), true );
        }
        else
        {
            $query='DELETE FROM {DBKEY}config WHERE parent="" AND name IN ("site_locked_for", "site_locker_sid") ';
            $QF_DBase->sql_query($query);
        }
    }
}

// --------------------------------<GZIP Compressors>--------------------

//
// GZIP Compression initializer
Function StartGZIP() {
    Global $GZipped, $QF_Pagedata, $Vis, $QF_Client;

    If (extension_loaded('zlib')) {
        if (!$GZipped) {
            $phpver = phpversion();


            if ($phpver > '4.0') $compressor='qf_gzhandler';

            if ( strstr($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') ) $compress=true;
            elseif ( preg_match('#(mozilla/[4-9])|(opera/[7-9])#i',$QF_Client['uagent']) ) $compress=true;

            if ($compress && $compressor)
                $GZipped = ob_start($compressor);

            return $GZipped;
        }
    }

    return False;
}


//
// Gzip handler for old PHP
//
function qf_gzhandler($page) {

    $gzip_size = strlen($page);
    $gzip_crc = crc32($page);

    header('Content-Encoding: gzip');

    $page = gzcompress($page, 9);
    $page = substr($page, 0, strlen($page) - 4);

    $out = "\x1f\x8b\x08\x00\x00\x00\x00\x00";
    $out.=  $page;
    $out.=  pack('V', $gzip_crc);
    $out.=  pack('V', $gzip_size);

    header('Content-Length: '.strlen($out), true);

    return $out;
}

// --------------------------------<Common parsers>----------------------

function ArrayDefinition($data, $tabs=0)
{    $tab = '    ';
    $tabs = intval($tabs);
    $pref = str_repeat($tab, $tabs);

    if (!is_array($data))
        $def = $data;
    else {        $def = "Array (\n";
        $fields = Array();
        $maxlen = 0;
        foreach( $data as $key => $val ) {            if (is_numeric($key))
                $field=$pref.$tab.$key." => ";
            else
                $field=$pref.$tab."'".$key."' => ";

            if (is_numeric($val))
                $field.=$val;
            elseif (is_bool($val))
                $field.=(($val) ? 'true' : 'false');
            elseif (is_array($val))
                $field.=ArrayDefinition($val, $tabs+1);
            else
                $field.="'".addslashes($val)."'";

            $fields[]=$field;
        }
        $def.=implode(" ,\n", $fields)."\n$pref) ";
    }
    return $def;
}

//
// Correct mess string value
//
function HTMLStrVal($string, $mode = null)
{
    return htmlspecialchars(trim($string), $mode ? $mode : ENT_COMPAT, QF_ENCODING);
}

//
// Trims String with '...'
//
function STrim($inp,$len=15)
{    if (strlen($inp)>$len) {        $len=$len-2;
        $inp=substr($inp,0,$len+1);
        $pos=strrpos($inp,' ');
        if ($pos>0)
            $inp=substr($inp,0,min($pos,$len)).' ';
        else
            $inp=substr($inp,0,$len);
        return $inp.'…';
    }
    else
        return $inp;
}

//
// IP codec functiond
//
function encode_ip($dotquad_ip)
{
    $ip_sep = explode('.', $dotquad_ip);
    return sprintf('%02x%02x%02x%02x', $ip_sep[0], $ip_sep[1], $ip_sep[2], $ip_sep[3]);
}

function decode_ip($int_ip)
{
    $hexipbang = explode('.', chunk_split($int_ip, 2, '.'));
    return hexdec($hexipbang[0]). '.' . hexdec($hexipbang[1]) . '.' . hexdec($hexipbang[2]) . '.' . hexdec($hexipbang[3]);
}

//
// Transliterizes Cyrilic string
//
function Translit($string)
{
    static $Tran = array (
        "À" => "A",  "Á" => "B",  "Â" => "V",  "Ã" => "G",  "Ä" => "D",  "Å" => "E",  "¨" => "JO",  "Æ" => "ZH",  "Ç" => "Z",  "È" => "I",
        "É" => "JJ", "Ê" => "K",  "Ë" => "L",  "Ì" => "M",  "Í" => "N",  "Î" => "O",  "Ï" => "P",   "Ð" => "R",   "Ñ" => "S",  "Ò" => "T",
        "Ó" => "U",  "Ô" => "F",  "Õ" => "KH",  "Ö" => "C",  "×" => "CH", "Ø" => "SH", "Ù" => "SHH", "Ú" => "",   "Û" => "Y",  "Ü" => "",
        "Ý" => "EH", "Þ" => "JU", "ß" => "JA", "à" => "a",  "á" => "b",  "â" => "v",  "ã" => "g",   "ä" => "d",   "å" => "e",  "¸" => "jo",
        "æ" => "zh", "ç" => "z",  "è" => "i",  "é" => "jj", "ê" => "k",  "ë" => "l",  "ì" => "m",   "í" => "n",   "î" => "o",  "ï" => "p",
        "ð" => "r",  "ñ" => "s",  "ò" => "t",  "ó" => "u",  "ô" => "f",  "õ" => "kh",  "ö" => "c",   "÷" => "ch",  "ø" => "sh", "ù" => "shh",
        "ú" => "~",  "û" => "y",  "ü" => "'",  "ý" => "eh", "þ" => "ju", "ÿ" => "ja", " " => "_", "_" => "_",
        );

    $string = strtr($string, $Tran);
    return trim($string);
}

//
// Parses the formated message (qfcodes and smiles)
//
function ParseMSG($msgo)
{
    global $QF_Parser;
    return $QF_Parser->parse_mess($msgo);
}

//
// Smiles sorter function for correct sorting by text length
//
function smiles_sort($a, $b)
{
    if ( strlen($a['sm_text']) == strlen($b['sm_text']) )
        return 0;

    return ( strlen($a['sm_text']) > strlen($b['sm_text']) ) ? -1 : 1;
}

//
// Text templates parser
//
function ParseTempl($templ,$params)
{
    foreach($params as $pname => $ptext)
        $templ=str_replace('{'.$pname.'}',$ptext,$templ);

    $templ=preg_replace("#{(.*?)}#si", '', $templ);
    return $templ;
}

function smartAmpersands($string)
{
    return preg_replace('#\&(?!([A-z]+|\#\d{1,5}|\#x[0-9a-fA-F]{2,4});)#', '&amp;', $string);
}


function smartHTMLSchars($string, $no_quotes = false)
{
    static $SCHARS = null;
    static $NQSCHARS = null;

    if (is_null($SCHARS))
    {
        $SCHARS = get_html_translation_table(HTML_SPECIALCHARS);
        unset($SCHARS['&']);
        $NQSCHARS = $SCHARS;
        unset($NQSCHARS['"'], $NQSCHARS['\'']);
    }

    return strtr(smartAmpersands($string), $no_quotes ? $NQSCHARS : $SCHARS);
}


// returns true if given function link leads to existing func
// provides is_callable function on old PHP
function qf_func_exists($func_link)
{
    if (function_exists('is_callable'))
        return is_callable($func_link, true);

    if (is_string($func_link))
    {
        return function_exists($func_link);
    }
    elseif (is_array($func_link))
    {
        if (count($func_link)!=2)
            return false;

        $obj =& $func_link[0];
        $met =& $func_link[1];
        if (is_object($obj))
        {
            if (is_string($met))
            {
                return method_exists($obj, $met);
            }
            else
                return false;
        }
        else
            return false;
    }
    else
        return false;
}

// calls user function with checkup
// first three arguments may be parsed by link
function qf_func_call($func_link, $p1 = null, $p2 = null, $p3 = null)
{
    if (qf_func_exists($func_link))
    {
        $args = Array(&$p1, &$p2, &$p3);
        if (($nargs = func_num_args()) > 4)
            for ($i = 4; $i<$nargs; $i++)
                $args[] = func_get_arg($i);

        if (function_exists('call_user_func_array'))
            return call_user_func_array($func_link, $args);
        else
        {
            $eval = '$res = call_user_func($func_link';
            foreach (array_keys($args) as $id)
                $eval.= ', &$args['.$id.']';
            $eval.= ');';
            eval($eval);
            return $res;
        }
    }
    else
        return false;
}

?>
