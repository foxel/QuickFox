<?php
// Simple InUSE Check
if ( !defined('QF_STARTED') )
        die('Hacking attempt');

if ( defined('CORE_PARSER_LOADED') )
        die('Scripting error');

define('CORE_PARSER_LOADED', True);

// defining parse mode constants
define('QF_BBPARSE_CHECK', 0);     // only checks and reconstructs bb-code structure
define('QF_BBPARSE_ALL', 1);       // parses all tags
define('QF_BBPARSE_PREP', 2);      // parses only static tags (preaparation to cache)
define('QF_BBPARSE_POSTPREP', 3);  // parses only tags (preaparation to cache)

// defining tag mode constants
define('QF_BBTAG_NOSUB',  1);      // inside that tag bbtags can not be opened
define('QF_BBTAG_SUBDUP', 2);      // can have itself as a subtag (e.g. quote inside quote)
define('QF_BBTAG_BLLEV',  4);      // close all tags opening this
define('QF_BBTAG_USEBRK', 8);      // this tag uses bekers within it's contents
define('QF_BBTAG_FHTML', 16);      // formatted html string as a replace (not a html tag name)
define('QF_BBTAG_NOCH',  32);      // tag data in not cachable (must have function to parse)

// seme usefull defines
define('QF_URL_MASK', '[0-9A-z]+://[\w\#$%&~/.\-;:=,?@+\(\)\[\]]+');

class qf_parser
{    var $mode = 0;
    var $tags = Array();
    var $pregs = Array();
    var $noparse_tag = 'no_bb';    // contetns of this tag will not be parsed (lower case)
    var $tagbreaker   = '*';
    var $tag_stack = Array();

    var $last_time = 0;
    var $cur_mode = 0;

    var $base_loaded=False;
    var $ext_loaded=False;

    // constructor
    Function qf_parser($nodefaults = False)
    {        if ($nodefaults) $this->loaded=True;
    }

    // load base tags that are cacheble
    function load_basic()
    {        if ($this->base_loaded)
            return true;

        // Standart tags
        $this->Add_Tag('b', 'b');
        $this->Add_Tag('i', 'i');
        $this->Add_Tag('u', 'u');
        $this->Add_Tag('s', 'strike');
        $this->Add_Tag('sub', 'sub');
        $this->Add_Tag('sup', 'sup');
        $this->Add_Tag('color', '<span style="color: {param};">{data}</span>', QF_BBTAG_FHTML, Array('param_mask' => '\#[0-9a-f]{6}|[a-z\-]+') );
        $this->Add_Tag('background', '<span style="background-color: {param};">{data}</span>', QF_BBTAG_FHTML, Array('param_mask' => '\#[0-9a-f]{6}|[a-z\-]+') );
        $this->Add_Tag('font', '<span style="font-family: {param};">{data}</span>', QF_BBTAG_FHTML, Array('param_mask' => '[a-zA-Z\x20]+') );
        $this->Add_Tag('size', '<span style="font-size: {param}px;">{data}</span>', QF_BBTAG_FHTML, Array('param_mask' => '[1-2]?[0-9]') );
        $this->Add_Tag('img', '', QF_BBTAG_NOSUB, Array('func' => Array( &$this, 'BBCode_Std_UrlImg') ) );
        $this->Add_Tag('url', '', false, Array('func' => Array( &$this, 'BBCode_Std_UrlImg') ) );
        $this->Add_Tag('table', '', QF_BBTAG_BLLEV | QF_BBTAG_USEBRK | QF_BBTAG_SUBDUP, Array('func' => Array( &$this, 'BBCode_Std_Table') ) );

        // Replacers
        $this->Add_Preg(QF_URL_MASK, '[url]{data}[/url]');
        $this->Add_Preg('\([cñ]\)','&copy;');
        $this->Add_Preg('-','—');

        // QuickFox unique base tags
        $this->Add_Tag('file', '<br /><a href="?st=getfile&amp;file={param}" title="{data}"><img src="index.php?sr=thumb&amp;fid={param}" alt="{data}" /></a> <b>{data}</b>', QF_BBTAG_FHTML, Array('param_mask' => '[0-9a-fA-F]+') );
        $this->Add_Tag('section', '<a href="?st=section&amp;section={param}" title="{data}">{data}</a>', QF_BBTAG_FHTML, Array('param_mask' => '[0-9]+') );
        $this->Add_Tag('branch', '<a href="?st=branch&amp;branch={param}" title="{data}">{data}</a>', QF_BBTAG_FHTML, Array('param_mask' => '[0-9]+') );
        $this->Add_Tag('post', '<a href="?st=branch&amp;postfind={param}#{param}" title="{data}">{data}</a>', QF_BBTAG_FHTML, Array('param_mask' => '[0-9]+') );

        // Smile replaces are cacheble too
        $this->load_smiles();

        $this->base_loaded = true;
    }

    // Automatic inilial loading
    Function load_extends()
    {
        global $QF_User, $lang;

        if ($this->ext_loaded)
            return true;

        // Quote/Code tags
        $this->Add_Tag('quote', Visual('QUOTE_TABLE', Array( 'caption'=> '{param}', 'content' => '{data}')), QF_BBTAG_BLLEV | QF_BBTAG_FHTML | QF_BBTAG_SUBDUP | QF_BBTAG_NOCH );
        $this->Add_Tag('code', Visual('CODE_TABLE', Array( 'caption'=> '{param}', 'content' => '{data}')), QF_BBTAG_BLLEV | QF_BBTAG_FHTML | QF_BBTAG_NOCH );

        // QuickFox user related tags
        $this->Add_Tag('hide', (($QF_User->uid) ? '\\1' : ''), QF_BBTAG_FHTML | QF_BBTAG_NOCH );

        // Set inited
        $this->ext_loaded = True;
    }

    // Smiles
    function load_smiles()
    {        global $QF_DBase;
        $result = $QF_DBase->sql_doselect('{DBKEY}smiles', '*', '', 'ORDER BY LENGTH(sm_text) DESC');
        if ($result) {            while ( $smile = $QF_DBase->sql_fetchrow($result))
                $this->smiles[$smile['id']] = $smile;

            $QF_DBase->sql_freeresult($result);
        };

        // Smiles
        if (count($this->smiles)) {
            usort($this->smiles, 'parser_smiles_sort');

            foreach ($this->smiles as $item)
                $this->Add_Preg(preg_quote($item['sm_text'], '#'), '<img src="imgs/smiles/'.$item['sm_icon'].'" alt="'.$item['sm_text'].'" title="'.$item['sm_capt'].'" border="0" />');
        }
    }

    // Parses message for rendering in Browser
    // $mode sets parsing mode^
    //      0 - full parsing
    //      1 - precache base parsing
    //      2 - postcache quick parsing
    Function parse_mess($text, $mode=0)
    {        switch (intval($mode)) {            case 2:
                $this->load_extends();
                return $this->Parse($text, QF_BBPARSE_POSTPREP);
                break;
            case 1:
                $this->load_basic();
                return $this->Parse($text, QF_BBPARSE_PREP);
                break;
            default:
                $this->load_basic();
                $this->load_extends();
                return $this->Parse($text, QF_BBPARSE_ALL);
        }
    }

    // Message preparation and validation
    Function prep_mess($text)
    {        $this->load_basic();
        $this->load_extends();
        return $this->BB_Parse($text, QF_BBPARSE_CHECK);
    }


    function Add_Tag($bbtag, $html, $tag_mode=0, $extra = null)
    {
        static $extras = Array( 'param', 'param_mask', 'func', 'data_mask' );

        $bbtag = strtolower($bbtag);
        if (!$bbtag)
            return false;

        $newtag = Array(
            'html'       => strtolower($html),
            'mode'       => (int) $tag_mode,
            );

        if (is_array($extra))
        {
            foreach ($extras as $exname)
                if (isset($extra[$exname]))
                    $newtag[$exname] = $extra[$exname];
                else
                    $newtag[$exname] = '';
        }
        else
            foreach ($extras as $exname)
                $newtag[$exname] = '';

        $this->tags[$bbtag] = $newtag;

        return true;
    }

    function Add_Preg($mask, $data, $func = null)
    {
        $id = count($this->pregs);
        $mask = '#(?<=\s|^)'.$mask.'(?=\s|\r|$)#m';
        $data = str_replace(Array('\\', '$'), Array('\\\\', '\\$'), $data);
        $data = str_replace('{data}', '${0}', $data);
        $new_preg = Array(
            'mask' => $mask,
            'data' => $data,
            );
        if ($func && qf_func_exists($func)) // some tric with functioned replaces
        {
            $gen_tag = 'preg_trigger_'.$id;
            $this->Add_Tag($gen_tag, '', QF_BBTAG_NOSUB, Array('func' => $func ) );
            $new_preg['data'] = '['.$gen_tag.']${0}[/'.$gen_tag.']';
        }
        $this->pregs[$id] = $new_preg;

        return true;
    }

    function Parse($input, $mode = 0)
    {
        if ($mode == QF_BBPARSE_ALL || $mode == QF_BBPARSE_PREP) // doing replaces and html strips
        {
            //$input = htmlspecialchars($input, ENT_NOQUOTES);
            $input = $this->Pregs_Parse($input);
            $input = nl2br($input);
        }
        elseif ($mode == QF_BBPARSE_POSTPREP) // in postprep mode tagparcer works with all the tags
            $mode = QF_BBPARSE_ALL;

        $input = $this->BB_Parse($input, $mode);

        return $input;
    }

    function Pregs_Parse($input)
    {
        if (!is_array($this->pregs))
            return $input;
        foreach ($this->pregs as $preg)
        {
            $input = preg_replace($preg['mask'], $preg['data'], $input);
        }

        return $input;
    }

    function BB_Parse($input, $mode = 0)
    {
        $stime = explode(' ',microtime());
        $start_time=$stime[1]+$stime[0];

        if (!count($this->tags))
            return $input;       // there is no loaded tags data

        $this->cur_mode = (int) $mode;

        $state_nobb  = false;
        $state_strip = false;
        $state_breakers = 0;
        $used_tags   = Array();
        $cur_tag     = null;
        $buffer      = '';
        $struct      = Array();

        preg_match_all('#(\[((?>[\w]+)|'.preg_quote($this->tagbreaker).')(\s*=\s*(\"([^\"\[\]]*)\"|[^\s<>\[\]]*)\s*)?\])|(\[\/((?>\w+))\])|([^\[]+)|(\[)#', $input, $struct, PREG_SET_ORDER);

        $this->TStack_Clear();

        foreach ($struct as $part)
        {

            if ($tagname = strtolower($part[2]))      // open tag
            {
                if ($tagname == $this->noparse_tag)
                {
                    if ($this->cur_mode == QF_BBPARSE_CHECK || $this->cur_mode == QF_BBPARSE_PREP || $state_nobb)
                    {
                        $tdata = '['.$this->noparse_tag.']';
                        if (!$this->TStack_Write($tdata))
                            $buffer.= $tdata;
                    }
                    $state_nobb = true;
                }
                elseif ($tagname == $this->tagbreaker && !$state_nobb)
                {
                    if ($state_breakers)
                    {
                        while ($subtname = $this->TStack_Last())
                        {
                            $subtmode = $this->tags[$subtname]['mode'];
                            if ($subtmode & QF_BBTAG_USEBRK)
                                break;
                            else
                            {
                                $tdata = $this->TStack_Get();
                                if (isset($used_tags[$subtname]))
                                    $used_tags[$subtname]--;

                                $tdata = $this->Parse_Tag($tdata['name'], $tdata['param'], $tdata['buffer']);
                                if (!$this->TStack_Write($tdata))
                                    $buffer.= $tdata;

                            }
                        }
                    }

                    $tdata = '['.$this->tagbreaker.']';
                    if (!$this->TStack_Write($tdata))
                        $buffer.= $tdata;
                }
                elseif (isset($this->tags[$tagname]) && !$state_nobb)
                {
                    $tag = $this->tags[$tagname];
                    $tmode = $tag['mode'];

                    if ($state_strip)
                    {
                        // do nothing - strippeng tags
                    }
                    else
                    {
                        if ($tmode & QF_BBTAG_BLLEV)
                            while ($subtname = $this->TStack_Last())
                            {
                                $subtmode = $this->tags[$subtname]['mode'];
                                if ($subtmode & QF_BBTAG_BLLEV)
                                    break;
                                $tdata = $this->TStack_Get();
                                $subtname = $tdata['name'];
                                if (isset($used_tags[$subtname]))
                                    $used_tags[$subtname]--;

                                if ($subtmode & QF_BBTAG_USEBRK && $state_breakers)
                                    $state_breakers--;

                                $tdata = $this->Parse_Tag($tdata['name'], $tdata['param'], $tdata['buffer']);
                                if (!$this->TStack_Write($tdata))
                                    $buffer.= $tdata;
                            }

                        if ($tmode & QF_BBTAG_USEBRK)
                            $state_breakers++;

                        $tused = (isset($used_tags[$tagname])) ? $used_tags[$tagname] : 0;

                        if (!$tused || ($tmode & QF_BBTAG_SUBDUP))
                        {
                            $tparam = ($part[4]) ? (($part[5]) ? $part[5] : $part[4]) : '';
                            $this->TStack_Add($tagname, $tparam);

                            if ($tmode & QF_BBTAG_NOSUB)
                                $state_strip = true;

                            $tused++;

                            $used_tags[$tagname] = $tused;
                        }
                    }

                }
                else
                {
                    if (!$this->TStack_Write($part[0]))
                        $buffer.= $part[0];
                }
            }
            elseif ($tagname = strtolower($part[7]))  // close tag
            {
                if ($tagname == $this->noparse_tag)
                {
                    if ($state_nobb && ($this->cur_mode == QF_BBPARSE_CHECK || $this->cur_mode == QF_BBPARSE_PREP))
                    {
                        $tdata = '[/'.$this->noparse_tag.']';
                        if (!$this->TStack_Write($tdata))
                            $buffer.= $tdata;
                    }
                    $state_nobb = false;
                }

                elseif (isset($this->tags[$tagname]) && !$state_nobb)
                {
                    $tag = $this->tags[$tagname];
                    $tmode = $tag['mode'];

                    if ($state_strip)
                    {
                        if ($tagname == $this->TStack_Last())
                            $state_strip = false;
                    }

                    if (!$state_strip)
                    {
                        $tused = (isset($used_tags[$tagname])) ? $used_tags[$tagname] : 0;

                        if ($tused)
                            while ($tdata = $this->TStack_Get())
                            {
                                $subtname = $tdata['name'];
                                $subtmode = $this->tags[$subtname]['mode'];
                                if (isset($used_tags[$subtname]))
                                    $used_tags[$subtname]--;

                                if ($subtmode & QF_BBTAG_USEBRK && $state_breakers)
                                    $state_breakers--;

                                $tdata = $this->Parse_Tag($tdata['name'], $tdata['param'], $tdata['buffer']);
                                if (!$this->TStack_Write($tdata))
                                    $buffer.= $tdata;

                                if ($subtname == $tagname)
                                    break;
                            }
                    }

                }
                else
                {
                    if (!$this->TStack_Write($part[0]))
                        $buffer.= $part[0];
                }

            }
            else              // string data
            {
                if (!$this->TStack_Write($part[0]))
                    $buffer.= $part[0];
            }

        }

        if ($state_nobb && ($this->cur_mode == QF_BBPARSE_CHECK || $this->cur_mode == QF_BBPARSE_PREP))
        {
            $tdata = '[/'.$this->noparse_tag.']';
            if (!$this->TStack_Write($tdata))
                $buffer.= $tdata;
            $state_nobb = false;
        }

        while ($tdata = $this->TStack_Get())
        {
            $subtname = $tdata['name'];
            if (isset($used_tags[$subtname]))
                $used_tags[$subtname]--;

            $tdata = $this->Parse_Tag($tdata['name'], $tdata['param'], $tdata['buffer']);
            if (!$this->TStack_Write($tdata))
                $buffer.= $tdata;
        }

        $stime = explode(' ',microtime());
        $stop_time = $stime[1]+$stime[0];
        $this->last_time = $stop_time - $start_time;

        return $buffer;
    }

    function Parse_Tag($name, $param, $buffer='')
    {
        if (!$buffer)
            return '';

        $param = preg_replace('#\[(\/?\w+)#', '[ $1', $param);
        if ($this->cur_mode == QF_BBPARSE_CHECK)
            return ('['.$name.($param ? '="'.$param.'"' : '').']'.$buffer.'[/'.$name.']');

        elseif ($tag = $this->tags[$name])
        {
            $tmode = $tag['mode'];

            if ($tag['func'])
            {
                if (($tmode & QF_BBTAG_NOCH) && $this->cur_mode == QF_BBPARSE_PREP)
                    return ('['.$name.($param ? '="'.$param.'"' : '').']'.$buffer.'[/'.$name.']');
                else
                    return qf_func_call($tag['func'], $name, $buffer, $param);
            }

            if ($p_mask = $tag['param_mask'])
            {
                if (preg_match('#('.$p_mask.')#', $param, $parr))
                    $param = $parr[0];
                else
                    return $buffer;
            }
            if ($d_mask = $tag['data_mask'])
            {
                if (preg_match('#('.$d_mask.')#', $buffer, $darr))
                    $buffer = $darr[0];
                else
                    return $buffer;
            }

            if (($tmode & QF_BBTAG_NOCH) && $this->cur_mode == QF_BBPARSE_PREP)
                return ('['.$name.($param ? '="'.$param.'"' : '').']'.$buffer.'[/'.$name.']');
            elseif ($tmode & QF_BBTAG_FHTML)
            {
                $out = str_replace(Array('{param}', '{data}'), Array($param, $buffer), $tag['html']);
                return $out;
            }
            else
            {
                $out = '<'.$tag['html'].(($param && $tag['param']) ? ' '.$tag['param'].'="'.$param.'"' : '').'>'.$buffer.'</'.$tag['html'].'>';
                return $out;
            }
        }
    }

    function TStack_Clear()
    {
        $this->tag_stack = Array();
    }

    function TStack_Add($name, $param='')
    {
        $pos = count($this->tag_stack);
        $new = Array('name' => $name, 'param' => $param, 'buffer' => '');
        $this->tag_stack[$pos] =& $new;
    }

    function TStack_Write($text)
    {
        $pos = count($this->tag_stack)-1;
        if ($pos>=0)
        {
            $this->tag_stack[$pos]['buffer'].= $text;
            return true;
        }
        else
            return false;
    }

    function TStack_Get()
    {
        $pos=count($this->tag_stack)-1;
        if ($pos>=0) {
            $out=$this->tag_stack[$pos];
            Unset($this->tag_stack[$pos]);
            return $out;
        }
        else
            return false;
    }

    function TStack_Last()
    {
        $pos=count($this->tag_stack)-1;
        if ($pos>=0) {
            $out = $this->tag_stack[$pos]['name'];
            return $out;
        }
        else
            return false;
    }

    function BBCode_Std_UrlImg($name, $buffer, $param = false)
    {
        if ($name == 'url')
            $html = '<a href="{url}" title="{url}" >{capt}</a>';
        elseif ($name == 'img')
            $html = '<img src="{url}" alt="{capt}" />';
        else
                return $buffer;

        if ($param)
        {
            $url = $param;
            $capt = $buffer;
        }
        else
        {
            $url = $capt = $buffer;
        }

        if (preg_match('#^'.QF_URL_MASK.'$#D', $url, $uarr))
            $url = $uarr[0];
        else
            return $buffer;

        if ($name == 'img')
            if (!preg_match('#\.(jpg|jpeg|png|gif|swf|bmp|tif|tiff)$#i', $url))
                $html = '<a href="{url}" title="{url}" >{capt} [Image blocked]</a>';

        return str_replace(Array('{url}', '{capt}'), Array($url, $capt), $html);
    }

    function BBCode_Std_Table($name, $buffer, $param = false)
    {
        $useborder = false;
        $parr = explode('|', $param);
        if (count($parr)>1)
        {
            $param = $parr[0];
            $useborder = (bool) $parr[1];
        }
        $param = (int) $param;
        if ($param <= 0)
            $param = 1;

        $table = explode('['.$this->tagbreaker.']', $buffer);
        $buffer = ($useborder)
            ? '<table style="border: solid 1px;"><tr>'
            : '<table><tr>';
        $i = 0;
        foreach ($table as $part)
        {
            if ($i>0 && ($i%$param == 0))
                $buffer.= '</tr><tr>';

            if ($part==='')
                $part = '&nbsp;';

            $buffer.= '<td>'.$part.'</td>';
            $i++;
        }
        while ($i%$param != 0)
        {
            $buffer.= '<td>&nbsp;</td>';
            $i++;
        }
        $buffer.= '</table>';

        return $buffer;
    }

}

//
// Smiles sorter function for correct sorting by text length
//
  function parser_smiles_sort($a, $b)
  {
        if ( strlen($a['sm_text']) == strlen($b['sm_text']) )
        {
                return 0;
        }

        return ( strlen($a['sm_text']) > strlen($b['sm_text']) ) ? -1 : 1;
  }

?>