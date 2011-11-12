<?php

// Simple InUSE Check
if ( !defined('QF_STARTED') )
        die('Hacking attempt');

$Vis=Array();
$VParts = Array();

function Vis_Gen_Rights($r, $ifzero='')
{
    global $Vis, $lang;
    if ($ifzero=='')
        $ifzero=$lang['FOR_ALL'];

    return ($r>0) ? str_repeat($Vis['LEVEL_POINT'], $r) : $ifzero;
}

Function Vis_Err_String($message)
{    global $Vis;
    return preg_replace('#\{C_message\}#i', $message, $Vis['ERR_STRING']);
}

Function Vis_Print_Email($mail) {    global $QF_User, $QF_Config, $lang;

    if (!empty($mail)) {
        if ( ($QF_User->uid || $QF_Config['force_show_email']) && !$QF_User->is_spider)
            return '<a href="mailto:'.$mail.'">'.$mail.'</a>';
        else
            return '<b>'.$lang['EMAIL_HIDDEN'].'</b>';
    }
    else return 'n/a';
}

Function Vis_Gen_Avatar($user, $nolink=false)
{    Global $Vis;

    if ($user['avatar']) {
        $av_data = '<img border="0" src="'.$user['avatar'].'" alt="avatar">';
        if (!$nolink)
            $av_data = '<a href="index.php?st=info&amp;infouser='.$user['id'].'">'.$av_data.'</a>';
    }
    else
        $av_data = $Vis['NO_AVATAR'];

    return $av_data;}


// Draws a simple bordered message with caption
function Vis_Draw_Table($title, $content, $err=false, $width=500) {
    if (is_numeric($width))
        $width.='px';
    return '
    <div style="width: '.$width.'; margin: auto">
    <table class="border '.(($err) ? 'error' : '').' fullwidth" >
    <tr><td>
    '.Visual('TABLE_CAPT', Array('title'=>$title)).'
    </td></tr>
    <tr><td>
    '.$content.'
    </td></tr>
    </table>
    </div>';
}

// Draws a simple bordered message
function Vis_Draw_Border($content, $width=500) {    if (is_numeric($width))
        $width.='px';
    return '
    <div style="width: '.$width.'; margin: auto">
    <table class="border fullwidth"  >
    <tr><td>
    '.$content.'
    </td></tr>
    </table>
    </div>';
}

// Draws a ShowHide Block
function Vis_Draw_Fliper($content, $caption, $width='', $hidden=False) {
    if (!$width) $width='99%';
    if (is_numeric($width))
        $width.='px';
    $tmpl=Array(
        'title'   => $caption,
        'width'   => $width,
        'content' => $content,
        'hidden'  => $hidden );

    return Visual('FLIPER_BODY', $tmpl);
}

// Draws a ShowHide Block
function Vis_Draw_Panel($content, $caption, $width='', $hidden=False, $addborder=False) {
    if (!$width) $width='100%';
    if (is_numeric($width))
        $width.='px';
    $tmpl=Array(
        'title'   => $caption,
        'width'   => $width,
        'content' => $content,
        'hidden'  => $hidden );

    return Visual('PANEL_BODY', $tmpl);
}

// Draws a Hint PopUp Block
function Vis_Draw_Hint($content, $object='') {
    global $Vis;
    if (!$object)
        $object=$Vis['IMG_HINT'];

    return Visual('HINT', Array('object' => $object, 'text'=>$content));
}


//
// JS includer
//
function Connect_JS($name) {
    Global $QF_Pagedata, $QF_Config, $lang;

    $file = 'jscripts/'.$name.'.js';
    if ($QF_Config['force_css_separate'])
        $QF_Pagedata['JAVA'].='<script language="JavaScript" type="text/javascript" src="index.php?sr=JS&amp;scripts='.$name.'"></script>';
    elseif ($sfile =@ fopen($file, 'r')) {
        $indata=fread($sfile,filesize($file));
        $indata=preg_replace('#\{L_(\w+)\}#e', '\$lang["\\1"]', $indata);
        $QF_Pagedata['JAVA'].='<script language="JavaScript" type="text/javascript">
        <!---
        '.$indata.'
        //--->
        </script>
        ';
        fclose($sfile);
    }
}


//
// Styles includer
//
function LoadStyle($part='')
{
    Global $QF_Pagedata, $lang, $imgs_dir, $QF_Config;

    $CSSFile = ($part) ? $part.'.ecss' : 'main.ecss';

    if (!file_exists('styles/'.$QF_Config['CSS'].'/'.$CSSFile))
        $QF_Config['CSS']='qf_def';

    $CSSFile = 'styles/'.$QF_Config['CSS'].'/'.$CSSFile;
    $QF_Config['CSS_imgs_dir']='styles/'.$QF_Config['CSS'].'/imgs';

    if ($QF_Config['force_css_separate'])
        $QF_Pagedata['META'].='<link rel="stylesheet" type="text/css" href="index.php?sr=CSS&amp;style='.$QF_Config['CSS'].(($part) ? '&amp;part='.$part : '').'">';
    else
        $QF_Pagedata['CSS'].=Combine_ECSS($CSSFile);
}

//
// Visuals includer
//
function LoadVisuals($part='') {
    Global $lang, $QF_Config, $QF_Session;
    Global $Vis, $VIF, $VParts;
    Static $c_loaded = false;
    if (!$c_loaded) {//        $cache = $QF_Session->Cache_Get('visuals');
//        if (is_array($cache))
//            list ($Vis, $VIF, $VParts) = $cache;
        $c_loaded = true;
    }

    if (!$part)
        $part = 'main';

    if (!in_array($part, $VParts)) {
        $file = $part.'.vis';
        $vdir = 'visuals/'.$QF_Config['visual'];
        $ddir = 'visuals/qf_def';
        $odir = (file_exists($vdir.'/'.$file)) ? $vdir : $ddir;

        if ($sfile=@fopen($odir.'/'.$file,'r')) {

            $indata=fread($sfile,filesize($odir.'/'.$file));

            $indata=str_replace('{VIS_IMGS}',$odir.'/imgs',$indata);
            $indata=str_replace('{CSS_IMGS}',$QF_Config['CSS_imgs_dir'],$indata);

            $indata=preg_replace('#\{L_(\w+)\}#e','\$lang["$1"]',$indata);

            preg_match_all("#<<\+ '(\w+)'>>(.*?)<<- '\\1'>>#s", $indata, $blocks);
            if (is_array($blocks[1]))
                foreach ($blocks[1] as $num => $name)
                    AddVisual($name, $blocks[2][$num]);

            fclose($sfile);
        }
        $VParts[] = $part;
        //$QF_Session->Cache_Add('visuals', Array($Vis, $VIF, $VParts));
    }
}

function AddVisual($name, $templ) {
    Global $Vis, $VIF, $VParts;

    $templ=trim($templ);
    if (substr_count($templ, '{V_')>0)
        $VIF[$name]['VIS'] = 1;

    if (substr_count($templ, '{ELSE}')>0)
        $VIF[$name]['IF'] = 2;
    elseif (substr_count($templ, '{IF_'))
        $VIF[$name]['IF'] = 1;
    else
        $VIF[$name]['IF'] = 0;
    $Vis[$name]=$templ;

    return true;
}
//
// Use Visual
//
function Visual($name, $content=Array()) {
    Global $Vis, $VIF, $QF_Session;
    Static $counter=1;

    $templ=$Vis[$name];
    $UseVIS=$VIF[$name]['VIS'];
    $UseIF=$VIF[$name]['IF'];

    if ($UseVIS)
        $templ=preg_replace('#\{V_(\w+)\}#se', 'Visual(\'$1\', \$content)', $templ);

    if ($UseIF == 2)
    	$templ=preg_replace('#\{IF_([\w_]+)\}(.*?)\{ENDIF\}#se', 'VisIfExp("$2",\$content["$1"])', $templ);
    elseif ($UseIF == 1) {
    	foreach($content as $pname => $ptext)
       	    if (strlen($ptext)>0) {
               	$templ=preg_replace('#\{IF_'.$pname.'\}(.*?)\{ENDIF\}#si', '$1', $templ);
            }
    	$templ=preg_replace('#\{IF_([\w_]+)\}(.*?)\{ENDIF\}#s', '', $templ);
    }

    foreach($content as $pname => $ptext)
       if (strlen($ptext)>0)
       {
    	    $templ=preg_replace('#\{C_'.$pname.'\}#si', str_replace('$', '\$', $ptext), $templ);
    	    $templ=preg_replace('#\{\!C_'.$pname.'\}#si', str_replace('$', '\$', smartHTMLSchars($ptext)), $templ);
       }

    $templ=preg_replace('#\{\!?C_([\w_]+)\}#s', '', $templ);
    $templ=str_replace('{COUNTER}', $counter++, $templ);
    $templ=str_replace('{SCRIPT_TOKEN}', is_object($QF_Session) ? $QF_Session->Get('script_token') : '', $templ);
    return $templ;
}

function VisIfExp($exp, $param) {
	$exp=str_replace("\'","'",$exp);
	$pos=strpos($exp,'{ELSE}');

	if (strlen($param)>0) {
		if ($pos === false)
			$pos = strlen($exp);
		return substr($exp,0,$pos);
	}
	elseif ($pos !== false)
		return substr($exp,$pos+6);
	else
		return '';
}

//
// Adds a meta tag
//
function Add_META($tag){
    Global $QF_Pagedata;
    if ($tag) $QF_Pagedata['META'].='<META '.$tag.'>';
}

function Vis_Draw_Form($caption, $frmname, $script, $text, $fields, $width='', $tblclass='', $frmadds=''){
    global $lang, $QF_Session;

    if (!$width) $width='500';
    if (is_numeric($width))
        $width.='px';

    $frmfields='';
    $hid_fields='';

    foreach ($fields as $name=>$field){
        $field['name']=$name;
        switch(strtolower($field['type'])){
        case '':
        break;
        case 'hidden':
            $hid_fields.='<input type="hidden" name="'.$name.'" value="'.$field['value'].'">'.PHP_EOL;
            if ($name == 'script' && is_object($QF_Session)) {
                $hid_fields.='<input type="hidden" name="script_token" value="'.$QF_Session->Get('script_token').'" />'.PHP_EOL;
            }
        break;
        case 'textarea':
            $frmfields.=Visual('UNIFORM_TEXTAREA', $field);
        break;
        case 'select':
            $selfileds='';
            foreach ($field['subs'] as $subn=>$subf){
                $selfileds.='<option value="'.$subf['value'].'" '.(($subf['selected'])?'SELECTED':'').' '.$subf['params'].'>'.$subf['name'].'</option>';
            }
            $field['subs']=$selfileds;
            $frmfields.=Visual('UNIFORM_SELECT', $field);
        break;
        case 'multiselect':
            $selfileds='';
            foreach ($field['subs'] as $subn=>$subf){
                $sfdata = Array(
                    'name'    => $name.'_'.$subf['value'],
                    'value'   => 1,
                    'checked' => ($subf['selected']) ? 'CHECKED' : '',
                    'capt'    => $subf['name'],
                    'poarams' => $subf['params'] );
                $selfileds.=Visual('UNIFORM_MULTISELECT_ROW', $sfdata);
            }
            $field['subs']=$selfileds;
            $frmfields.=Visual('UNIFORM_MULTISELECT', $field);
        break;
        case 'checkbox':
            $field['checked']=($field['checked'])?'CHECKED':'';
            $frmfields.=Visual('UNIFORM_CHECKBOX', $field);
        break;
        case 'doubledpass':
            $frmfields.=Visual('UNIFORM_DOUBLEDPASS', $field);
        break;
        case 'separator':
            $frmfields.=Visual('UNIFORM_SEPARATOR', $field);
        break;
        case 'separator1':
            $frmfields.=Visual('UNIFORM_SEPARATOR1', $field);
        break;
        case 'submit':
            $frmfields.=Visual('UNIFORM_SUBMIT', $field);
        break;
        default:
            $frmfields.=Visual('UNIFORM_DEFAULT', $field);
        }
    }

    $tmpl=Array(
        'width'      => $width,
        'caption'    => $caption,
        'action'     => $script,
        'name'       => $frmname,
        'tblclass'   => $tblclass,
        'additional' => $frmadds,
        'text'       => $text,
        'fields'     => $frmfields,
        'hid_fields' => $hid_fields );

    return Visual('UNIFORM_TABLE', $tmpl);
}

$Vis['COPYRIGHT']=
'Powered by<br />
QuickFox<br />
&copy; Foxel aka LION<br />
2006 - 2011';
?>
