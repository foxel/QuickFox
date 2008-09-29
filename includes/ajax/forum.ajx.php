<?php

// Simple InUSE Check
if ( !defined('QF_STARTED') )
        die('Hacking attempt');

$AJAX_Job = Get_Request('job', 2, 's');
LoadVisuals();
if ($AJAX_Job == 'preview')
{    $message = Get_Request('message', 2, 'ht');
    if ($message)
        print $QF_Parser->parse_mess($message);
    else
        print $lang['ERR_NO_MESS'];
}

?>