<?php

// Simple InUSE Check
if ( !defined('QF_STARTED') )
        die('Hacking attempt');

$_JS_REPLACE = array(
       '\\' => '\\\\', '/'  => '\\/', "\r" => '\\r', "\n" => '\\n',
       "\t" => '\\t',  "\b" => '\\b', "\f" => '\\f', '"'  => '\\"',
       );

$mesages = Array();
$query = 'DELETE FROM {DBKEY}minichats WHERE time < '.(time() - 86400);
$QF_DBase->sql_query($query);
$query = 'SELECT * FROM {DBKEY}minichats WHERE acc_lv <= '.$QF_User->level.' ORDER BY time DESC LIMIT 0, 50';
if ( $result = $QF_DBase->sql_query($query) ) {
    while ( $mess = $QF_DBase->sql_fetchrow($result))
        $mesages[] = $mess;

    $QF_DBase->sql_freeresult($result);
}

Connect_JS('jscripts/mchat.js');


$datas = '';
//$datas = '<table class="fullwidth" style="max-width: 200px;">';
foreach ($mesages as $mess)
{    $q_author = strtr($mess['author'], $_JS_REPLACE);
    $datas.= '<div style="border: '.((stristr($mess['text'], $QF_User->uname)) ? '1px solid #7F0000' : 'none').'; margin: 2px;">['.create_date("H:i", $mess['time'], '', true).']&nbsp;<a href="#" onClick="javascript: mchat_quote(\''.$q_author.'\'); return false;">'.$mess['author'].'</a>'.(($mess['acc_lv'])?'('.$mess['acc_lv'].')':'').': '.$mess['text'].'</div>'."\n";
//    $datas.= '<tr><td style="border: '.((stristr($mess['text'], $QF_User->uname)) ? '1px solid #7F0000' : 'none').'; padding: 2px;">['.create_date("H:i", $mess['time'], '', true).']&nbsp;<a href="#" onClick="javascript: mchat_quote(\''.$q_author.'\'); return false;">'.$mess['author'].'</a>'.(($mess['acc_lv'])?'('.$mess['acc_lv'].')':'').': '.$mess['text'].'</td></tr>'."\n";
}
//$datas.= '</table>';

$data = '<form action="index.php" onSubmit="javascript: mchat_send(); return false;" method="post">
<input type="hidden" name="script" value="mchat" />
<textarea id="mchat_msg" name="newmess" style="width: 95%; height: 35px;"></textarea>
<input type="submit" value="Send" />
<select id="mchat_lv" name="messlevel" >
<option value="0">0</option>';
for ($i = 1; $i <= $QF_User->level; $i++)
    $data.= '<option value="'.$i.'">'.$i.'</option>';
$data.= '</select>
</form>
<div id="minichater" class="minichater" style="overflow: auto; max-height: 350px; max-width: 195px; padding: 0; margin: 0;">'.$datas.'</div>';

print $script;
print Vis_Draw_panel($data, 'QF Mini Chat :)');
?>