<?php

$packs[''] = Array(
`   'caption'  => $lang['ADMCAB_ADMIN_CONFIG'],
    'descr'    => $lang['CONFIG_COMMON_SITENAME'],
    'siteinfo' => Array(
        'caption'   => $lang['CONFIG_COMMON_COMMCONF'],
        'site_name' => Array(
            'value_type' => 'text',
            'default'    => $def_config['site_name'],
            'caption'    => $lang['CONFIG_COMMON_SITENAME'],
            'descr'      => $lang['CONFIG_COMMON_SITENAME_MORE'],
            ),
        'site_mail' => Array(
            'value_type' => 'text',
            'default'    => $def_config['site_mail'],
            'caption'    => $lang['CONFIG_COMMON_SITEMAIL'],
            'descr'      => $lang['CONFIG_COMMON_SITEMAIL_MORE'],
            ),
        'site_mail' => Array(
            'value_type' => 'text',
            'default'    => $def_config['site_mail'],
            'caption'    => $lang['CONFIG_COMMON_SITEMAIL'],
            'descr'      => $lang['CONFIG_COMMON_SITEMAIL_MORE'],
            ),

    ),
);

?>