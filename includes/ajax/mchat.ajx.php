<?php

// Simple InUSE Check
if ( !defined('QF_STARTED') )
        die('Hacking attempt');

$_JS_REPLACE = array(
       '\\' => '\\\\', '/'  => '\\/', "\r" => '\\r', "\n" => '\\n',
       "\t" => '\\t',  "\b" => '\\b', "\f" => '\\f', '"'  => '\\"',
       );

if (($mess = Get_Request('newmess', 2, 's')) && $QF_User->uid && $QF_User->wlevel)
{
    if ($mess{0} == '!' && $QF_User->cuser['admin'])
    {
        list($command, $mess) = explode(' ', $mess, 2);
        if ($command == '!clear')
            $QF_DBase->sql_query('truncate table {DBKEY}minichats');
    }

    $new_mess = Array(
        'author' => $QF_User->uname,
        'author_id' => $QF_User->uid,
        'text'   => nl2br(htmlspecialchars(substr($mess, 0, 2048))),
        'time' => time() );
    if (($msglvl = Get_Request('messlevel', 2, 'i')) && $msglvl > 0 && $msglvl <= $QF_User->level )
        $new_mess['acc_lv'] = $msglvl;
    if ($mess)
        $QF_DBase->sql_doinsert('{DBKEY}minichats', $new_mess);
}

$mesages = Array();
$query = 'SELECT * FROM {DBKEY}minichats WHERE acc_lv <= '.$QF_User->level.' ORDER BY time DESC LIMIT 0, 25';
if ( $result = $QF_DBase->sql_query($query) ) {
    while ( $mess = $QF_DBase->sql_fetchrow($result))
        $mesages[] = $mess;

    $QF_DBase->sql_freeresult($result);
}

$script = '';
$datas = '';
$threshold = time() - 20*3600;
//$datas = '<table class="fullwidth" style="max-width: 200px;">';
if ($QF_User->uid)
foreach ($mesages as $mess)
{
    $q_author = strtr($mess['author'], $_JS_REPLACE);
    $datas.= '<div style="border: '.((stristr($mess['text'], $QF_User->uname)) ? '1px solid #7F0000' : 'none').'; margin: 2px;">['.create_date($mess['time'] < $threshold ? "d M H:i" : "H:i", $mess['time'], '', true).']&nbsp;<a href="#" onClick="javascript: mchat_quote(\''.$q_author.'\'); return false;">'.$mess['author'].'</a>'.(($mess['acc_lv'])?'('.$mess['acc_lv'].')':'').': '.$mess['text'].'</div>'."\n";
//    $datas.= '<tr><td style="border: '.((stristr($mess['text'], $QF_User->uname)) ? '1px solid #7F0000' : 'none').'; padding: 2px;">['.create_date("H:i", $mess['time'], '', true).']&nbsp;<a href="#" onClick="javascript: mchat_quote(\''.$q_author.'\'); return false;">'.$mess['author'].'</a>'.(($mess['acc_lv'])?'('.$mess['acc_lv'].')':'').': '.$mess['text'].'</td></tr>'."\n";
}
//$datas.= '</table>';

print $datas;
?>
