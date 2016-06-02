<?
// Simple InUSE Check
if ( !defined('QF_STARTED') )
        die('Hacking attempt');

include 'kernel/core_files.php';
include 'kernel/core_images.php';

$QF_Session->Open_Session(true);
$QF_User->login();

Glob_Request('fid');

  $err_logo = '';

  $result=$QF_DBase->sql_doselect('{DBKEY}files', '*', Array( 'id' => $fid ) );
  if ($result) $gfile=$QF_DBase->sql_fetchrow($result);

  if (!$fid) $err_logo='imgs/att_nofile.gif';
  elseif (!$gfile) $err_logo='imgs/att_nofile.gif';
  elseif (!file_exists('files/'.$gfile['file'])) $err_logo='imgs/att_ferr.gif';
  elseif ($QF_User->level<$gfile['rights']) $err_logo='imgs/att_low.gif';

  if (empty($err_logo)) {

     $info=pathinfo($gfile['filename']);
     $mime=$mimes[strtolower($info['extension'])];
     if ($mime['preview']) {
         $gotfile = 'cache/thumbs/'.$gfile['file'].'.chc';
         if (!is_dir('cache/thumbs/')) {
             mkdir('cache/thumbs/', 0700, true);
         }
         if (!file_exists($gotfile)) {
             $gotfile = img_resize_to('files/'.$gfile['file'], $gotfile, $QF_Config['thumb_width'], $QF_Config['thumb_height']);
         }

         if ($gotfile) {
             $img_info = getimagesize($gotfile);

             $dfile = new DownLoadFile($gotfile, 'thumb-'.$gfile['filename'], $img_info['mime']);
             $dfile->out();
         } else {
             $err_logo = 'imgs/att_ferr.gif';
             copy($err_logo, 'cache/thumbs/'.$gfile['file'].'.chc');
         }
     } else {
         $err_logo = ($mime['icon']) ? 'imgs/mime/'.$mime['icon'] : 'imgs/mime/file.gif';
     }
  }

  if ($err_logo)
  {
      $dfile = new DownLoadFile($err_logo);
      $dfile->out();
  }
