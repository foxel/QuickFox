<?php

?>

<div style="width: 80%; margin: auto;">
<?php
    if (!($cm_tti=Get_Request('tti', 1, 'v')))
        if (!($cm_hti=Get_Request('hti', 1, 'v')))
            $cm_hti = 'index';

    $cmfile = ($cm_tti)
        ? 'cms_pgs/'.$cm_tti.'.tti'
        : 'cms_pgs/'.$cm_hti.'.hti';
    if (!file_exists($cmfile))
    {
        $cm_tti = null;
        $cmfile = 'cms_pgs/index.hti';
        $cm_hti = 'index';
    }

    $data = '';
    if (file_exists($cmfile) && filesize($cmfile))
        if ($incms=fopen($cmfile, 'rb'))
            $data = fread($incms, filesize($cmfile));
    if ($cm_tti)
    {
        $data = nl2br(preg_replace('#^([^\n\S]+)#me', 'str_repeat("&nbsp; ", strlen("\\1"));', htmlspecialchars($data)));
    }
    if (!$data)
        print Visual('CMS_NOINDEXPAGE');
    else
        print $data;
?>
</div>
