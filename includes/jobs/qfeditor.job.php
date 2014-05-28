<?

// Simple InUSE Check
if ( !defined('QF_STARTED') )
        die('Hacking attempt');

include 'kernel/start.php';

Connect_JS('qfeditor');

// We'll not get smiles that have a same icon
$editorsmiles=Array();
$query='SELECT * FROM {DBKEY}smiles GROUP BY sm_icon ORDER BY id';
$result = $QF_DBase->sql_query($query);
if ($result) {
    while ( $smile = $QF_DBase->sql_fetchrow($result))
        $editorsmiles[$smile['sm_icon']] = $smile;

    $QF_DBase->sql_freeresult($result);
};

$smdata="";

if (count($editorsmiles)) {
    $i=0;
    $smdata.='<table width="100%"><tr>';
    Foreach($editorsmiles as $smile) {
        $i++;
        $smdata.=Visual('QFEDITOR_SMILE', $smile);
        if ($i % 3 == 0) $smdata.="</tr><tr>";
    }
    $smdata.="</tr></table>";
}
$QF_Pagedata['smiles']=$smdata;

$QF_Pagedata['senderform']=($_GET['senderform']) ? HTMLStrVal(Get_Request('senderform', 1)) : 'newmess';
print Visual('QFEDITOR', $QF_Pagedata);
