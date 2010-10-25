<?php

// Simple InUSE Check
if ( !defined('QF_STARTED') )
        die('Hacking attempt');

// additional forum functions are rpresented in separated file
include 'includes/forum_core.php';
$QF_Forum = new qf_forum();


$show_section = Get_Request('section', 1, 'i');
$show_topic = Get_Request('branch', 1, 'i');
if ($show_topic>0) {    $sh_topic_option = Get_Request_Multi('page shownew postshow postfind postdel subscribe edittheme moderate editpost history', 1, 'i');

}
print $QF_Forum->Draw_Section($show_section);

?>