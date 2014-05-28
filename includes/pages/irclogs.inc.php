<?php

// Simple InUSE Check
if ( !defined('QF_STARTED') )
        die('Hacking attempt');

include 'kernel/core_charset.php';
$QF_CharConv = new qf_charconv();


$showlog='#'.Get_Request('log', 1, 's').'.log';
$months = Array(
    1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
    5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug',
    9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec',
    );
$file = 'irclogs/'.$showlog;

if (!$QF_User->uid)
{
    print Vis_Err_String('Access Denied!');
}
elseif (file_exists($file))
{
    $log = file($file);
    $log = implode('', $log);
    $log = $QF_CharConv->UTFto_Conv($log);
    $log = nl2br(HTMLStrVal($log));
    preg_match('#^(.*)-(\d{4})-(\d{2})-(\d{2})(.*)$#', $showlog, $info);
    printf('<h3>%1$s [%2$d %3$s %4$d]  [<a href="index.php?st=irclogs">^^</a>]</h3>', $info[1], $info[4], $months[intval($info[3])], $info[2]);
    print '<table class="post" style="margin: 0 10px; width: auto;"><tr><td>'.$log.'</td></tr></table>';
}
elseif ($dir = opendir('irclogs'))
{
    $logslist = Array();
    while ($fn = readdir($dir))
    {
        $file = 'irclogs/'.$fn;
        if (is_file($file) && preg_match('#^\#(.*)\.log$#', $fn, $logg))
            $logslist[] = $logg[1];
    }
    print '<h3>Logs list:</h3>';
    if (count($logslist))
    {
        rsort($logslist);
        print '<table class="post" style="margin: 0 auto; width: 600px;"><tr><td>';
        print '<table class="invisible fullwidth"><tr>';
        $i = 0;
        $blockcapt = '';
        foreach ($logslist as $log)
        {
            if (preg_match('#^(.*)-(\d{4})-(\d{2})-(\d{2})(.*)$#', $log, $info))
            {
                $newcapt = sprintf('#%1$s %2$s %3$d', $info[1], $months[intval($info[3])], $info[2]);
                if ($blockcapt != $newcapt)
                {
                    if ($i)
                        print "\r\n</tr><tr>";
                    $blockcapt = $newcapt;
                    printf('<td style="text-align: center;" colspan="3"><b>%1$s</b></td>'."\r\n</tr><tr>", $blockcapt);
                    $i=0;
                }

                printf ('<td><a href="index.php?st=irclogs&amp;log=%1$s">#%2$s [%3$d %4$s %5$d]</a></td>', $log, $info[1], $info[4], $months[intval($info[3])], $info[2]);
                $i++;
            }
            if ($i%3 == 0)
                print "\r\n</tr><tr>";
        }
        print '</tr></table>';
        print '</td></tr></table>';
    }
}

