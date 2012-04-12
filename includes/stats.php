<?php

// Simple InUSE Check
if ( !defined('QF_STARTED') )
        die('Hacking attempt');

if (!$QF_User->is_spider) {
    $SeenToday='';
	$SeenNow='';
	$SeenLast='';
	$SeenGuests='';
	$LastCount=0;
	$tzs=floatval($QF_Config['tz'])*3600 + intval($QF_Config['date_corr_mins'])*60;
	if (qf_time_DST($timer->time, $QF_Config['tz']))
	    $tzs+= 3600;
	$chtime=$timer->time - 86400*10;
	$STtime=floor(($timer->time + $tzs)/86400)*86400 - $tzs;
	$SNtime=$timer->time - 600;

	$ulist->load('WHERE approved = true AND lastseen>'.$chtime, true);
	$ulist->timesort();
	Foreach($ulist->users as $suser)
	{
    	$tmpl=Array(
	        'user'   => $suser['nick'],
    	    'time'   => create_date("d M Y",$suser['lastseen']),
    	    'id'     => $suser['id'],
    	    'avatar' => Vis_Gen_Avatar($suser));

	    if ($suser['lastseen']>$SNtime && $suser['sessid']!='') {
            $tmpl['separator']=(!empty($SeenNow)) ? ', ' : '';
            $SeenNow.=Visual('SEENNOW_ROW', $tmpl);
	        //$SeenNow.="<b><a href=index.php?st=info&amp;infouser=".$suser['id'].">".$suser['nick']."</a></b>";
	    }
	    elseif ($suser['lastseen']>$STtime) {
	        $tmpl['separator']=(!empty($SeenToday)) ? ', ' : '';
            $SeenToday.=Visual('SEENTODAY_ROW', $tmpl);
	    }
	    elseif ($suser['lastseen']>$chtime && $LastCount<5) {
	        $tmpl['separator']=(!empty($SeenLast)) ? ', ' : '';
	        $SeenLast.=Visual('SEENLAST_ROW', $tmpl);
	        $LastCount++;
        }
	}

	if ($QF_Config['enable_guests'] && $QF_Config['ustats']['show_guests']) {
	  $query='SELECT gid FROM {DBKEY}guests WHERE lastseen>'.$SNtime.' AND sessid!="" GROUP BY gcode';
	  $result = $QF_DBase->sql_query($query);
	  if ($result) $curcount = $QF_DBase->sql_numrows($result);
	  if ($curcount) $SeenGuests=sprintf($lang['USTATS_GUESTS'],$curcount);
	}

	if ($QF_Config['enable_spiders'] && $QF_Config['ustats']['show_spiders']) {
	  $query='SELECT s.name FROM {DBKEY}spiders_stats ss
	          JOIN {DBKEY}spiders s ON (s.id = ss.id)
	          WHERE ss.lastseen>'.$SNtime.' GROUP BY s.name';
	  $result = $QF_DBase->sql_query($query);
	  if ($result)
	      if ($QF_DBase->sql_numrows($result)>0) {
	          $SeenSpidersList=Array();
	          while ($spider=$QF_DBase->sql_fetchrow($result)) {
	              $SeenSpidersList[]=$spider['name'];
	          }
	          $SeenSpiders=sprintf($lang['USTATS_SPIDERS'],implode(', ',$SeenSpidersList));
	      }
	}

	$tmpl=Array(
    	'seen_now'    => $SeenNow,
	    'seen_today'  => $SeenToday,
    	'seen_last'   => $SeenLast,
	    'seen_guests' => $SeenGuests,
    	'seen_spiders'=> $SeenSpiders);

	print Vis_Draw_Panel(Visual('USERSTAT_TABLE', $tmpl),$lang['USTATS_CAPT']);
}
else {
    $tmpl=Array(
        'spider_name'   => $QF_User->spider['name'],
        'spider_visits' => $QF_User->spider['visits']);

    print Vis_Draw_Panel(Visual('USERSTAT_SPIDER_MESS', $tmpl),$lang['USTATS_CAPT']);
}

?>