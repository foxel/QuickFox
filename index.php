<?
// Sequrity system - deleting all user posted data from globals
if (ini_get('register_globals'))
    foreach ($_REQUEST as $rvar=>$rval)
        unset ($$rvar);

define ('QF_STARTED',True);

// ------------------------------------------------------------
require 'QF_config.php';
require 'kernel/init.php';

if($QF_Job!='')
    $job_file='includes/jobs/'.$QF_Job.'.job.php';
    if (file_exists($job_file)) {
        include($job_file);
        QF_exit();
        }

  // If there is no job do
  require ('kernel/start.php');

  // starting Working buffer
  ob_start();

      include('includes/stats.php');
  $QF_Pagedata['stats']=ob_get_contents();
  ob_clean();

  $timer->Time_Log('Stats Taken');

  if (!$QF_Inc)
      $QF_Inc='main';

  $include_file='includes/pages/'.$QF_Inc.'.inc.php';

  If(file_exists($include_file))
      include($include_file);
  else
      include('includes/pages/main.inc.php');

  $QF_Pagedata['included']=ob_get_contents();

  ob_clean();

  $timer->Time_Log('Main Page Stored');

      include('includes/menu.php');
  $QF_Pagedata['menu']=ob_get_contents();
  ob_clean();

  $timer->Time_Log('Menu Stored');

      include('includes/enter.php');
  $QF_Pagedata['enter']=ob_get_contents();
  ob_clean();

  //
  // We'll not render all the data to search spiders -
  // they don't need it and wee too
  // This will increase system speed
  if (!$QF_User->is_spider)
  {
      include('includes/panels/fstat.pan.php');
	  $QF_Pagedata['stats'].=ob_get_contents();
	  ob_clean();

	  if ($QF_User->uid)
	  {
	      include('includes/panels/mchat.pan.php');
    	  $QF_Pagedata['stats'].=ob_get_contents();
	      ob_clean();
	  }

	  include('includes/panels/quicksets.pan.php');
	  $QF_Pagedata['stats'].=ob_get_contents();
	  ob_clean();

  }

  //Close working buffer
  ob_end_clean();

  $QF_Pagedata['adv']=$QF_Config['adv_data'];
  $QF_Pagedata['bottom_adv']=$QF_Config['bottom_adv_data'];
  $QF_Pagedata['site_logo']=$QF_Config['site_logo'];

  if (!empty($Page_SubTitle))
      $Page_Title.=': '.$Page_SubTitle;

  $QF_Pagedata['page_title']=$Page_Title;

  $QF_Pagedata['footstat'].=($GZipped) ? $Vis['GZIP_FLAG'] : '';

  header('Content-Type: text/html; charset=windows-1251', true);
  header('Cache-Control: no-cache');
  print Visual('GLOBAL_HTMLPAGE', $QF_Pagedata);
  QF_exit();
?>