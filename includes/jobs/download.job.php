<?
// Simple InUSE Check
if ( !defined('QF_STARTED') )
        die('Hacking attempt');

include 'kernel/core_files.php';

Glob_Request('file fcode');

  $error = '';

  $QF_DBase->sql_dodelete('{DBKEY}dloads', 'WHERE time<'.($timer->time - 3600*3));

  $result=$QF_DBase->sql_doselect('{DBKEY}files', '*', Array( 'id' => $file ) );
  if ($result) $gfile=$QF_DBase->sql_fetchrow($result);

  $QSID=Get_Request('QF_SID', 3, 'h', 32);
  $result=$QF_DBase->sql_doselect('{DBKEY}dloads', '*', Array ( 'fileid' => $file, 'filecode' => $fcode ) );
  if ($result) $dload=$QF_DBase->sql_fetchrow($result);

  if ((!$fcode) || (!$file)) $error.='<LI>'.$lang['ERR_NO_PARAMS']."\n";

  if (!$gfile) $error.='<LI>'.$lang['GETFILE_NO_INFO']."\n";

  if (!file_exists('files/'.$gfile['file'])) $error.="<LI>".$lang['GETFILE_NOT_FOUND']."\n";

  if (!$dload) $error.='<LI>'.$lang['GETFILE_NO_ACCESS']."\n";

  if (($dload['ip']!=$QF_Client['ipaddr']) && ($dload['ip']!=$_SERVER["HTTP_X_FORWARDED_FOR"]) ) $error.='<LI>'.$lang['GETFILE_WRONG_IP']."\n";

  if(empty($error)) {

     $QF_DBase->sql_doupdate('{DBKEY}dloads', Array( 'used' => 1 ), Array ( 'fileid' => $file, 'filecode' => $fcode ) );

     if (!$dload['used']) {
          $QF_DBase->sql_doupdate('{DBKEY}files', Array( 'dloads' => ($gfile['dloads']+1) ), Array( 'id' => $file ) );
     }

     $dfile = new DownLoadFile('files/'.$gfile['file'], $gfile['filename']);
     $dfile -> out();
     exit;

  }
  else {
   $redir_url='index.php?st=getfile&amp;file='.$file;
   Set_Result($error, '', $redir_url);
  }


?>