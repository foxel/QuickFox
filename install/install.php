<?php

// Simple InUSE Check
if ( !defined('QF_STARTED') || !defined('QF_SETUP_STARTED'))
        die('Hacking attempt');

define('EMAIL_MASK', '^[0-9a-zA-Z_\-\.]+@[0-9a-zA-Z_^\.]+\.[a-zA-Z]{2,4}$');
define('UNAME_MASK', '^[0-9\w_\+\-=\(\)\[\] ]{3,16}$');

$QF_Pagedata=Array();

$lang=Array(
    'ENCODING' => 'windows-1251',
    'QF_SETUP_CAPTION' => 'QuickFox Installation' ,
    'BTN_GO' => 'GO!' ,
    'SETUP_STEP_DATA_ACC' => 'Setting up database access' ,
    'SETUP_STEP_DATA_ACC_REQ' => 'Please fill up this fields with data you have received from your hoster.
     And set up QuickFox tables prefix if you don\'t want to use default.' ,
    'SETUP_STEP_DATA_ACC_DBLOC' => 'Database location' ,
    'SETUP_STEP_DATA_ACC_DBNAME' => 'Database name' ,
    'SETUP_STEP_DATA_ACC_DBUSER' => 'Database user' ,
    'SETUP_STEP_DATA_ACC_DBPASS' => 'Database password' ,
    'SETUP_STEP_DATA_ACC_DBKEY' => 'QuickFox tables prefix' ,
    'SETUP_STEP_DATA_ACC_LOADED' => '"QF_config.php" found -> settings loaded.' ,
    'SETUP_STEP_DATA_ERR' => 'Error connecting database. Please reenter connection settings.' ,
    'SETUP_STEP_DATA_ERR_FILE' => 'Error creating config file.' ,
    'SETUP_STEP_DATA_OK' => 'Database connected. Now we must continue installation.' ,
    'SETUP_STEP_DATA_IMP_REQ' => 'Please choose what type of data import you want to use.',
    'SETUP_STEP_DATA_IMP_UPD' => 'Update database to fit version requirements',
    'SETUP_STEP_DATA_IMP_NEW' => 'Create new EMPTY database structure',
    'SETUP_STEP_DATA_UPD_OK' => 'Database structure has been updated. Press "GO" to finish installation.' ,
    'SETUP_STEP_DATA_NEW_OK' => 'New database structure has been created. No we must setup administrator profile.' ,
    'SETUP_STEP_ADMIN_SET_REQ' => 'Please enter administrator account data',
    'SETUP_STEP_ADMIN_NAME' => 'Nick (login)',
    'SETUP_STEP_ADMIN_PASS' => 'Password',
    'SETUP_STEP_ADMIN_PASSD' => 'Psssword (retype please)',
    'SETUP_STEP_ADMIN_EMAIL' => 'e-Mail',
    'SETUP_STEP_ADMIN_NAME_ERR' => 'This nick is not valid',
    'SETUP_STEP_ADMIN_PASS_ERR' => 'Password is too short',
    'SETUP_STEP_ADMIN_PASSD_ERR' => 'Psssword retyped with mistakes',
    'SETUP_STEP_ADMIN_EMAIL_ERR' => 'e-Mail is not valid',
    'SETUP_STEP_ADMIN_OK' => 'Admin profile has been successfully created. Press "GO" to finish installation.',
    );

$QF_Config['visual']='qf_def';
$QF_Config['CSS']='qf_def';

// Loading Initial Modules and Data
require 'kernel/core_functs.php';
// Setting an error parser
set_error_handler('err_parse');
// Visulizer
include 'kernel/visualizer.php';

LoadStyle();
LoadVisuals('setup');

$SET_error = '';

if ($SET_step=='finish_install')
{
    $QF_Root = preg_replace('#\/+|\\\+#', '/', $_SERVER['SCRIPT_NAME']);
    $QF_Root = preg_replace('#^\/*([^\'\"]*)\/+([^\/]+)$#', '\\1', $QF_Root);

    // Init Server settings
    $QF_Config['server_name']=$_SERVER['SERVER_NAME'];
    $QF_Config['server_port']=$_SERVER['SERVER_PORT'];
    $QF_Config['root']=$QF_Root;

    if (!file_exists('setup.lock'))
	unlink('setup.php');
    redirect('index.php');
}

if ($SET_step=='admin_setup')
{
    include 'QF_config.php';
    require 'db/mysql4.php';

    $QF_DBase = new qf_sql_base($QF_Dbase_Config, True, True);
    $dbkey = $QF_Dbase_Config['prefix'];

    if ($SET_act == 'GO') {

        $nuser = Get_Request('admin_nick', 2, 'ht', 16);
        $npasssrc1 = Get_Request('admin_pass', 2);
        $npass1 = md5($npasssrc1);
        $npasssrc2 = Get_Request('admin_passd', 2);
        $npass2 = md5($npasssrc2);
        $nemail = Get_Request('admin_email', 2, 'ht', 36);

        if (!preg_match('/'.UNAME_MASK.'/i', $nuser))
            $SET_error = $lang['SETUP_STEP_ADMIN_NAME_ERR'];
        elseif (!preg_match('/'.EMAIL_MASK.'/i', $nemail))
            $SET_error = $lang['SETUP_STEP_ADMIN_EMAIL_ERR'];
        elseif (strlen($npasssrc1)<5)
            $SET_error = $lang['SETUP_STEP_ADMIN_PASS_ERR'];
        elseif ( $npass1 != $npass2 )
            $SET_error = $lang['SETUP_STEP_ADMIN_PASSD_ERR'];

        if (!$SET_error)
        {
            $ins_data = Array(
                'id'       => 1,
                'nick'     => $nuser,
                'pass'     => $npass1,
                'email'    => $nemail,
                'regtime'  => $timer->time,
                'lastseen' => $timer->time,
                'rights'   => 7,
                'descr'    => 'Root Admin',
                );
            $QF_DBase->sql_doinsert('{DBKEY}users', $ins_data, true);
            $QF_DBase->sql_doinsert('{DBKEY}userstats', Array( 'user_id' => 1 ), true );

            $QF_DBase->sql_dodelete('{DBKEY}config', Array('parent' => '', 'name' => 'site_locked_for') );

            $QF_Pagedata['step'] = 'finish_install';
            $QF_Pagedata['form_cont'] = $lang['SETUP_STEP_ADMIN_OK'];
        }
        else
        {
            $QF_Pagedata['step'] = $SET_step;
            $QF_Pagedata['form_cont'] = $SET_error;
        }
    }
    else {
        $QF_Pagedata['step'] = $SET_step;
        $QF_Pagedata['action'] = 'GO';
        $QF_Pagedata['form_cont'] = Visual('SETUP_STEP_ADMIN_SET');

    }
}

if ($SET_step=='dbase_import')
{
    $upd_possible = false;

    $imp_mode=Get_Request('imp_mode', 2);

    include 'QF_config.php';
    require 'db/mysql4.php';

    $QF_DBase = new qf_sql_base($QF_Dbase_Config, True, True);
    $dbkey = $QF_Dbase_Config['prefix'];

    include 'kernel/core_sql.php';

    $query='SHOW TABLES';
    $result=$QF_DBase->sql_query($query);
    if ($result) while($tbl=$QF_DBase->sql_fetchrow($result, false)) {
        list($tblname)=$tbl;
        if ($tblname == $dbkey.'users') {
            $upd_possible = true;
            break;
        }
    }

    if (!$upd_possible) {        $SET_act = 'GO';
        $imp_mode = 'new';
    }

    if ($SET_act == 'GO') {

        $import = new mysql_importer();

        if ($imp_mode=='upd') {            $impfile = fopen('install/qf_dbase.str', 'rb');
            $data = fread($impfile, filesize('install/qf_dbase.str'));
            eval('$bases = '.$data.';');
            foreach ($bases as $name=>$struct)
            $import->apply_table_structure($struct, true);

            $impfile2 = fopen('install/content.sql', 'rb');
            $content = str_replace('{DBKEY}', $dbkey, fread($impfile2, filesize('install/content.sql')));
            PMA_splitSqlFile($queries, $content);
            foreach ( $queries as $query )
                $QF_DBase->sql_query($query['query']);

            $QF_Pagedata['step'] = 'finish_install';
            $QF_Pagedata['form_cont'] = $lang['SETUP_STEP_DATA_UPD_OK'];
        }
        else {
            $impfile1 = fopen('install/structure.sql', 'rb');
            $impfile2 = fopen('install/content.sql', 'rb');
            $strucr = str_replace('{DBKEY}', $dbkey, fread($impfile1, filesize('install/structure.sql')));
            $content = str_replace('{DBKEY}', $dbkey, fread($impfile2, filesize('install/content.sql')));

            PMA_splitSqlFile($queries, $strucr);
            foreach ( $queries as $query )
                $QF_DBase->sql_query($query['query']);

            PMA_splitSqlFile($queries, $content);
            foreach ( $queries as $query )
                $QF_DBase->sql_query($query['query']);

            if (file_exists('install/empty.sql') && ($impfile3 = fopen('install/empty.sql', 'rb')))
            {                $content = str_replace('{DBKEY}', $dbkey, fread($impfile3, filesize('install/empty.sql')));
                PMA_splitSqlFile($queries, $content);
                foreach ( $queries as $query )
                    $QF_DBase->sql_query($query['query']);
            }

            $QF_DBase->sql_doinsert('{DBKEY}config', Array('parent' => '', 'name' => 'site_locked_for', 'value' => (time()+3600)), true );

            $QF_Pagedata['step'] = 'admin_setup';
            $QF_Pagedata['form_cont'] = $lang['SETUP_STEP_DATA_NEW_OK'];
        }

    }
    else {
        $QF_Pagedata['step'] = $SET_step;
        $QF_Pagedata['action'] = 'GO';
        $QF_Pagedata['form_cont'] = Visual('SETUP_STEP_DATA_IMP');

    }
}
elseif ($SET_step=='data_acc')
{    $QF_Pagedata['step_info'] = $lang['SETUP_STEP_DATA_ACC'];

    if ($SET_act == 'GO') {        require 'db/mysql4.php';

        $QF_Dbase_Config = Array(
            'location' => Get_Request('dblocation', 2),
            'database' => Get_Request('dbname', 2),
            'username' => Get_Request('dbuser', 2),
            'password' => Get_Request('dbpasswd', 2),
            'codepage' => 'cp1251',
            'prefix'   => Get_Request('dbkey', 2),
            );

        $QF_DBase = new qf_sql_base($QF_Dbase_Config);

        if (!$QF_DBase->db_connect_id)
        {
            $SET_error=$lang['SETUP_STEP_DATA_ERR'];
        }
        else
        {
            if (empty($QF_Dbase_Config['prefix']))
                $QF_Dbase_Config['prefix'] = 'qf_';

            $conffile=fopen('QF_config.php', 'w');
            if (!$conffile)
                  $SET_error=$lang['SETUP_STEP_DATA_ERR_FILE'];
            else
            {
                fwrite($conffile,'<?php '."\n");
                fwrite($conffile,'$QF_Dbase_Config = '.ArrayDefinition($QF_Dbase_Config)."\n");
                fwrite($conffile,'?>');
                fclose($conffile);
            }


        }

        unset($dbpasswd);

        if (!$SET_error)
        {
            $QF_Pagedata['step'] = 'dbase_import';
            $QF_Pagedata['form_cont'] = $lang['SETUP_STEP_DATA_OK'];
        }
        else
        {            $QF_Pagedata['step'] = $SET_step;
            $QF_Pagedata['form_cont'] = $SET_error;
        }
    }
    else
    {
        $tmpl = Array(
            'db_loc'  => 'localhost',
            'db_key'  => 'qf_' );

        if (file_exists('QF_config.php')) {
            include 'QF_config.php';
            if (is_array($QF_Dbase_Config))
                $tmpl=Array(
                    'db_loc'  => $QF_Dbase_Config['location'],
                    'db_name' => $QF_Dbase_Config['database'],
                    'db_user' => $QF_Dbase_Config['username'],
                    'db_pass' => $QF_Dbase_Config['password'],
                    'db_key'  => $QF_Dbase_Config['prefix'],
                    'db_loaded' => 1 );
        }

        $QF_Pagedata['step'] = $SET_step;
        $QF_Pagedata['action'] = 'GO';
        $QF_Pagedata['form_cont'] = Visual('SETUP_STEP_DATA_ACC', $tmpl);
    }
}


print Visual('GLOBAL_SETUPPAGE', $QF_Pagedata);



?>