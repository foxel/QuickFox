<?php

// Simple InUSE Check
if ( !defined('QF_STARTED') )
        die('Hacking attempt');

Glob_Request('file download');
$Page_SubTitle = $lang['GETFILE_CAPT'];

$QF_DBase->sql_dodelete('{DBKEY}dloads', 'WHERE time<'.($timer->time - 3600*3));

$result=$QF_DBase->sql_doselect('{DBKEY}files', '*', Array( 'id' => $file ) );
if ($result) $gfile=$QF_DBase->sql_fetchrow($result);
if ($gfile) {
if (file_exists('files/'.$gfile['file'])) {
if ($QF_User->level>=$gfile['rights']) {

  $info=pathinfo($gfile['filename']);

  $result=$QF_DBase->sql_doselect('{DBKEY}dloads', '*', Array( 'fileid' => $file, 'session' => $QF_Session->SID) );
  if ($result) $dload=$QF_DBase->sql_fetchrow($result);

  $result=$QF_DBase->sql_doselect('{DBKEY}mime', '*', Array( 'ext' => strtolower($info['extension']) ) );
  if ($result) $mime=$QF_DBase->sql_fetchrow($result);

  if (!is_array($dload)) {
    $fcode=md5(uniqid('dfile'));
    $ins_data = Array(
        'fileid'   => $file,
        'filecode' => $fcode,
        'user'     => $QF_User->uname,
        'session'  => $QF_Session->SID,
        'ip'       => $QF_Client['ipaddr'],
        'time'     => $timer->time,
        );
    $QF_DBase->sql_doinsert ('{DBKEY}dloads', $ins_data, true);
  }
  else {
    $fcode=$dload['filecode'];
    $QF_DBase->sql_doupdate('{DBKEY}dloads', Array( 'time' => $timer->time), Array( 'filecode' => $fcode, 'session' => $QF_Session->SID) );
  }
  $ulist->load(' WHERE u.id='.intval($gfile['user_id']));
  $puser=$ulist->get($gfile['user_id']);

  $tmpl=Array(
      'filecaption' => $gfile['caption'],
      'filedescr'   => $gfile['descr'],
      'filename'    => $gfile['filename'],
      'filelevel'   => Vis_Gen_Rights($gfile['rights']),
      'filesize'    => round(filesize('files/'.$gfile['file'])/1024,2),
      'fileid'      => $gfile['id'],
      'fcode'       => $fcode,
      'downcount'   => intval($gfile['dloads']),
      'time'        => create_date("", $gfile['time']) );

  if ($mime['preview']) $tmpl['thumb']='index.php?sr=thumb&amp;fid='.$gfile['id'];
  elseif ($mime['icon']) $tmpl['thumb']='imgs/mime/'.$mime['icon'];

  $tmpl['user']=($puser) ? '<a href="index.php?st=info&amp;infouser='.$puser['id'].'">'.$puser['nick'].'</a>' :
  str_replace('[|]','',$gfile['user']);

  $content=Visual('GETFILE_TABLE', $tmpl);

  $filetable= Vis_Draw_Table($lang['GETFILE_CAPT'],$content).'<br />';

}
else $filetable= Visual('ERR_STRING', Array('message'=>$lang['GETFILE_LOWLEVEL']));
}
else $filetable= Visual('ERR_STRING', Array('message'=>$lang['GETFILE_NOT_FOUND']));
}
else $filetable= Visual('ERR_STRING', Array('message'=>$lang['GETFILE_NO_INFO']));

if ($QF_Config['files']['no_attc'] && $download)
    redirect('index.php?sr=download&file='.$gfile['id'].'&fcode='.$fcode);
else
    print Visual('GETFILE_PAGE', Array('filetable' => $filetable));

?>
