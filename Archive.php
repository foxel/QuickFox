<?php

define ('QF_STARTED',True);

require 'QF_config.php';
require 'kernel/core_functs.php';

$arch_date=Array(
    'year'  => 2009,
    'month' => 2,
    'day'   => 8);

if (!defined('E_DEPRECATED'))
    define('E_DEPRECATED', 8192);
if (!defined('E_USER_DEPRECATED'))
    define('E_USER_DEPRECATED', 16384);
Error_Reporting(E_ALL & ~(E_NOTICE | E_USER_NOTICE | E_STRICT | E_DEPRECATED) );
set_magic_quotes_runtime(0);
set_time_limit(0);

function arch_err_parse($errno, $errstr, $errfile, $errline, $context)
{
    print "\n\n $errstr";
    debug_print_backtrace();
    err_parse($errno, $errstr, $errfile, $errline, $context);
}
set_error_handler('arch_err_parse', E_ALL & ~(E_NOTICE | E_USER_NOTICE | E_STRICT | E_DEPRECATED) );

require 'db/mysql4.php';

$QF_DBase = new qf_sql_base($QF_Dbase_Config, True, True);
$dbkey = $QF_Dbase_Config['prefix'];

unset($dbpasswd);

if (!$QF_DBase->db_connect_id)
{
  echo( '<P>SQL Initialization Error.
         Please try later.</P>' );
  exit();
}

include 'kernel/core_TGZ.php';
include 'kernel/core_sql.php';

// Let's create setup sql data

$sql_need_content = Array();
$sql_need_content[$dbkey.'mime']='';
$sql_need_content[$dbkey.'smiles']='';
$sql_need_content[$dbkey.'spiders']='';
$sql_need_content[$dbkey.'styles']='WHERE (id REGEXP "^[0-9]+$") =1';

$dumper = new mysql_dumper(1);
$dumper->file_init('install/structure.sql', false);
$dumper->file_write("#\n# QuickFox Setup SQL template [structure section]. \n#\n");

$query='SHOW TABLES';
$result=$QF_DBase->sql_query($query);
if ($result) while($tbl=$QF_DBase->sql_fetchrow($result, false)) {
    list($tblname)=$tbl;
    if ((strpos($tblname,$dbkey)==0 && substr_count($tblname,$dbkey))) {
            $dumper->file_write($dumper->get_create_table($tblname, true));
    }
}

$dumper->file_close();

$dumper->file_init('install/content.sql', false);
$dumper->file_write("#\n# QuickFox Setup SQL template [content section]. \n#\n");
foreach ($sql_need_content as $tblname => $extSQL)
    $dumper->dump_content($tblname, true, $extSQL);

$dumper->file_close();


$srtc = fopen('install/qf_dbase.str', 'wb');
fwrite($srtc, ArrayDefinition($dumper->struct) );
fclose($srtc);

function Arch_Date_Mask($file)
{
    Global $arch_date;
    Static $mtime;
    if (!$mtime && is_array($arch_date))
        $mtime = mktime(0,0,0,$arch_date['month'], $arch_date['day'], $arch_date['year']);

    if(!file_exists($file))
        return false;

    $time=filemtime($file);

    return ($mtime<$time) ? true : false;
}

function Archive_QF()
{
    global $arch;

    $arch->AddFileData('index.php', '644');
    $arch->ArchiveDir('install', 'html sql str php', '711', '600');
    $arch->ArchiveDir('avatars', 'html', '755', '644');
    $arch->ArchiveDir('cache', 'html', '711', '600');
    $arch->ArchiveDir('cms_pgs', 'txt', '777', '644');
    $arch->ArchiveDir('db', 'html php', '711', '600');
    $arch->ArchiveDir('files', 'html', '711', '600');
    $arch->ArchiveDir('imgs', 'gif png jpg jpeg ico', '755', '644');
    $arch->ArchiveDir('includes', 'html php', '711', '600');
    $arch->ArchiveDir('jscripts', 'html js', '755', '644');
    $arch->ArchiveDir('kernel', 'html php dat', '711', '600');
    $arch->ArchiveDir('kernel/CharTables', 'html chr', '711', '600');
    $arch->ArchiveDir('langs', 'html php tpl', '711', '600');
    $arch->ArchiveDir('styles/graver', 'ecss css gif png jpg jpeg ico', '755', '644');
    $arch->ArchiveDir('styles/green', 'ecss css gif png jpg jpeg ico', '755', '644');
    $arch->ArchiveDir('styles/qf_def', 'ecss css gif png jpg jpeg ico', '755', '644');
    $arch->ArchiveDir('styles/violet', 'ecss css gif png jpg jpeg ico', '755', '644');
    $arch->ArchiveDir('visuals/qf_def', 'vis gif png jpg jpeg ico', '755', '644');
    //$arch->ArchiveDir('styles/che_skin', 'ecss css gif png jpg jpeg ico', '755', '644');
    //$arch->ArchiveDir('styles/furry_08', 'ecss css gif png jpg jpeg ico', '755', '644');
    //$arch->ArchiveDir('styles/furry_10', 'ecss css gif png jpg jpeg ico', '755', '644');
    //$arch->ArchiveDir('visuals/furry_08', 'vis gif png jpg jpeg ico', '755', '644');
    $arch->close();
}

// Full Archive
$arch=new TGZWrite('pack.tgz');
Archive_QF();

// Only By Date
// $arch=new TGZWrite('upd_pack.tgz','','Arch_Date_Mask');
// Archive_QF();

$dumper->mysql_dumper();
$dumper->dump_tables('QF_DBDump.sql', true, Array('all_tables' => true));

print 'DONE!';


