<?php

// This file includes default QF config values
// Simple InUSE Check
if ( !defined('QF_STARTED') )
{
        die('Hacking attempt');
}

$def_config = Array (

    'CSS'             => 'qf_def',
    'visual'          => 'qf_def',
    'def_lang'        => 'ru',

    'site_name'       => 'QuickFox Site',
    'site_mail'       => 'admin@site.com',

    'style'           => '',

    'GZIP'             => False,
    'enable_guests'    => True,
    'enable_spiders'   => True,
    'restrict_spiders' => False,
    'use_spcode'       => True,

    'def_tz'           => 3,
    'date_corr_mins'   => 0,
    'def_date_format'  => 'd M Y H:i',
    'def_time_format'  => 'H:i',

    'post_files_rights' => 1,
    'post_file_size'    => 1048576,
    'thumb_width'       => 120,
    'thumb_height'      => 100,

    'def_greet_mess'    => 'Приветствую тебя,',

    'ustats' => Array(
        'show_guests'  => True,
        'show_spiders' => True ),

    'forum'  => Array(
        'posts_per_page' => 10,
        'mess_lock_time' => 3, // minutes
        'guest_book'     => 0, // id of the GB theme
        'post_upl_files' => 3 ),

);

?>