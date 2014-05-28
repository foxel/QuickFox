<?php

// Simple InUSE Check
if ( !defined('QF_STARTED') )
        die('Hacking attempt');

// GZIP Starting
$GZipped=False;
if ($QF_Config['GZIP'])
    StartGZIP();

// Visulizer
include 'kernel/visualizer.php';

header('Content-Type: text/xml; charset=windows-1251', true);

$feed_template =
'<?xml version="1.0" encoding="windows-1251"?>
<rss version="2.0">
 <channel>
  <title>{title}</title>
  <link>{link}</link>
  <description>{description}</description>
  <language>ru</language>
  <copyright>{copyright}</copyright>
  <lastBuildDate>{time}</lastBuildDate>
  <ttl>5</ttl>
  <generator>QuickFox RSS</generator>
{items}
 </channel>
</rss>
';

$item_template =
'  <item>
   <title>{title}</title>
   <link>{link}</link>
   <description>{description}</description>
   <pubDate>{pubDate}</pubDate>
   <guid isPermaLink="false">{guid}</guid>
  </item>
';

$time_filter = $timer->time - 30*24*3600;

$rss_data = Array(
   '{title}'       => '',
   '{link}'        => '',
   '{description}' => '',
   '{copyright}'   => '',
   '{time}'        => '',
   '{items}'       => '',
);
$items  = Array();

$show_more = Get_Request('more', 1, 'b');
$topic     = Get_Request('topic', 1, 'i');
$limit     = Get_Request('limit', 1, 'i');
$by_time   = Get_Request('time', 1, 'i');
$time_zone = Get_Request('tz', 1, 's');
$cur_tz ='';

if ($limit < 3 || $limit > 30)
    $limit = 15;

if ($by_time >= 10 && $by_time <= 24*60)
{
    $time_filter = $timer->time - $by_time*60;
    $limit = 50;
}

if (strlen($time_zone) && is_numeric($time_zone))
{
    $time_zone = (int) $time_zone;
    if ($time_zone >= -12 && $time_zone <= 12)
        $cur_tz = $time_zone;
}

if ($topic > 0)
{
    $query = 'SELECT t.id, t.MaxID, t.name, t.lasttime, t.minrights, t.posts FROM {DBKEY}topics t
          LEFT JOIN {DBKEY}sections s ON (s.id = t.parent)
          WHERE t.id = '.$topic.' AND t.minrights <= 2 AND t.deleted = 0 AND (s.acc_group = 0 || t.parent = 0)';
    if ($result = $QF_DBase->sql_query($query)) {
        $topic = $QF_DBase->sql_fetchrow($result);
        $QF_DBase->sql_freeresult($result);
    }
    else
        $topic = false;
}

if (is_array($topic)) // by Topic RSS
{
    $posts = Array();

    $rss_data = Array(
       '{title}'       => HTMLStrVal(sprintf($lang['RSS_TITLE_TOPIC_MSGS'], $QF_Config['site_name'], $topic['name'])),
       '{link}'        => GetFullUrl('index.php?st=branch&branch='.$topic['id'].'&shownew=1#unread', false),
       '{description}' => HTMLStrVal(sprintf($lang['RSS_TITLE_TOPIC_MSGS_MORE'], $QF_Config['site_name'], $topic['name'])),
       '{copyright}'   => $QF_Config['site_name'],
       '{time}'        => date('r', $topic['lasttime']),
       '{items}'       => '',
    );

    $query = 'SELECT p.id, p.author, p.time, p.text, ch.ch_text AS parsed_text FROM {DBKEY}posts p
          LEFT JOIN {DBKEY}posts_cache ch ON (ch.ch_id = p.id)
          WHERE p.theme = '.$topic['id'].' AND p.time > '.$time_filter.' ORDER BY p.time DESC LIMIT 0, '.$limit;
    if ( $result = $QF_DBase->sql_query($query) ) {
        while ( $post = $QF_DBase->sql_fetchrow($result))
            $posts[$post['id']] = $post;

        $QF_DBase->sql_freeresult($result);
    }

    $post_no = $topic['posts'];

    if ($posts)
    foreach ($posts as $post)
    {
        $id = $post['id'];
        $post_text = ' --- ';
        if (!$topic['minrights'])
            $post_text = $QF_Parser->parse_mess(STrim($post['text'], 256));

        $link = GetFullUrl('index.php?st=branch&branch='.$topic['id'].'&postshow='.$id.'#'.$id, false);
        $guid = md5('QuickFox-'.$QF_Config['server_name'].'-'.$post['id'].'-'.$post['time']);

        $items[$id] = Array(
            '{guid}'  => $guid,
            '{title}' => '[#'.$post_no.'] '.$post['author'].' ['.create_date('', $post['time'], $cur_tz, true).']',
            '{link}'  => $link,
            '{description}' => '<![CDATA[ '.$post_text.' ]]>',
            '{pubDate}'     => date('r', $post['time']),
            );
        $post_no --;
    }
}
else // Common RSS feed
{
    $upd_time = $timer->time - 30*24*3600;
    $topics = Array();
    $postsi = $posts = Array();

    $query = 'SELECT t.id, t.MaxID, t.name, t.lasttime, t.minrights FROM {DBKEY}topics t
              LEFT JOIN {DBKEY}sections s ON (s.id = t.parent)
              WHERE t.minrights <= '.(($show_more) ? '2' : '0').' AND t.deleted = 0 AND (s.acc_group = 0 || t.parent = 0)
              AND t.lasttime > '.$time_filter.'
              ORDER BY t.lasttime DESC LIMIT 0, '.$limit;
    if ( $result = $QF_DBase->sql_query($query) ) {
        while ( $topic = $QF_DBase->sql_fetchrow($result))
        {
            $topics[$topic['id']] = $topic;
            if (!$topic['minrights'])
                $postsi[] = $topic['MaxID'];
            $upd_time = max($upd_time, $topic['lasttime']);
        }
        $QF_DBase->sql_freeresult($result);

        if ($postsi)
        {
            $query = 'SELECT p.id, p.author, p.time, p.text, ch.ch_text AS parsed_text FROM {DBKEY}posts p
                  LEFT JOIN {DBKEY}posts_cache ch ON (ch.ch_id = p.id)
                  WHERE p.id in ('.implode(', ', $postsi).')';
            if ( $result = $QF_DBase->sql_query($query) ) {
                while ( $post = $QF_DBase->sql_fetchrow($result))
                    $posts[$post['id']] = $post;

                $QF_DBase->sql_freeresult($result);
            }
        }

    }

    if ($topics)
    foreach ($topics as $topic)
    {
        $id = $topic['id'];
        $post_text = ' --- ';

        $plink = $link = GetFullUrl('index.php?st=branch&amp;branch='.$id.'&shownew=1#unread', false);
        if (isset($posts[$topic['MaxID']]) && ($post = $posts[$topic['MaxID']]))
        {
            $post_text = $QF_Parser->parse_mess(STrim($post['text'], 256));
            $post_text = '<b>'.$post['author'].':</b> <br />'.$post_text;
            $plink = GetFullUrl('http://quickfox1.ru/index.php?st=branch&branch='.$id.'&postshow='.$post['id'].'#'.$post['id'], false);
        }

        $guid = md5('QuickFox-'.$QF_Config['server_name'].'-'.$topic['id'].'-'.$topic['lasttime']);

        $items[$id] = Array(
            '{guid}'  => $guid,
            '{title}' => $topic['name'].' ['.create_date('', $topic['lasttime'], $cur_tz, true).']',
            '{link}'  => $link,
            '{description}' => '<![CDATA[ '.$post_text.' ]]>',
            '{pubDate}'     => date('r', $topic['lasttime']),
            );
    }

    $rss_data = Array(
       '{title}'       => HTMLStrVal(sprintf($lang['RSS_TITLE_LAST_MSGS'], $QF_Config['site_name'])),
       '{link}'        => GetFullUrl('index.php?st=section', false),
       '{description}' => HTMLStrVal(sprintf($lang['RSS_TITLE_LAST_MSGS_MORE'], $QF_Config['site_name'])),
       '{copyright}'   => $QF_Config['site_name'],
       '{time}'        => date('r', $upd_time),
       '{items}'       => '',
    );
}

foreach ($items as $item)
{
    $rss_data['{items}'].= strtr($item_template, $item);
}

$output = strtr($feed_template, $rss_data);

if (Get_Request('compact', 1, 'b'))
{
    $output = str_replace("\r", '', $output);
    $output = preg_replace('#(\n\s*)+#', "\n", $output);
    $output = preg_replace('#\n(.{1,5})$#m', ' \\1', $output);
    $output = preg_replace('#\x20+#', ' ', $output);
    //$output = str_replace("\n", "\r\n", $output);
}

function _FullURLs_Parse_Callback($vars)
{
    Global $QF;
    if (!is_array($vars))
        return false;

    if (isset($vars[6]))
    {
        $url = $vars[6];
        $bounds = '\'';
    }
    elseif (isset($vars[5]))
    {
        $url = $vars[5];
        $bounds = '"';
    }
    else
    {
        $url = $vars[4];
        $bounds = '';
    }

    if (qf_str_is_url($url) == 2)
        $url = GetFullUrl($url, false);

    return $vars[1].$vars[3].'='.$bounds.$url.$bounds;

}

$output = preg_replace_callback('#(<(a|form|img|link)\s+[^>]*)(href|action|src)\s*=\s*(\"([^\"<>\(\)]*)\"|\'([^\'<>\(\)]*)\'|[^\s<>\(\)]+)#i', '_FullURLs_Parse_Callback', $output);


print $output;

?>
