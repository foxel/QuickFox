<?php

// Simple InUSE Check
if ( !defined('QF_STARTED') )
        die('Hacking attempt');

if ( defined('CORE_CHARSET_LOADED') )
        die('Scripting error');

define('CORE_CHARSET_LOADED', True);

// ----------------------------------------------------------- \\
//            QuickFox charsets converter     (c) LION 2007    \\
// ----------------------------------------------------------- \\

class qf_charconv
{	var $tables = Array();

	function GetChartable($name)
	{	    static $loads = Array();
        $name = strtolower($name);
	    if (is_array($loads[$name]))
	        return $loads[$name];
	    else {
    	    $file = 'kernel/CharTables/'.$name.'.chr';
	        $table = Array();
    	    if (file_exists($file))
	            if ($stream = fopen($file, 'rb')) {
                    while(!feof($stream)) {
                        if ($line = trim(fgets($stream))) {                            $rec = preg_split ("/[\s,]+/", $line, 3);
                            if (substr($rec[1], 0, 1) != "#") {                                $Key = $this->HexToUtf(str_replace('0x', '', strtolower($rec[1])));
                                $Value = pack('C', hexdec(str_replace('0x', '', strtolower($rec[0]))));
								$table[$Key] = $Value;
                            }
                        }
	                }
    	        }
    	    $loads[$name] = $table;
    	    return $table;
	    }
	}

	function HexToUtf ($UtfCharInHex)
	{
		$OutputChar = "";
		$UtfCharInDec = hexdec($UtfCharInHex);
		if($UtfCharInDec<128)
		    $OutputChar .= chr($UtfCharInDec);
        else if($UtfCharInDec<2048)
            $OutputChar .= chr(($UtfCharInDec>>6)+192).chr(($UtfCharInDec&63)+128);
        else if($UtfCharInDec<65536)
            $OutputChar .= chr(($UtfCharInDec>>12)+224).chr((($UtfCharInDec>>6)&63)+128).chr(($UtfCharInDec&63)+128);
        else if($UtfCharInDec<2097152)
            $OutputChar .= chr($UtfCharInDec>>18+240).chr((($UtfCharInDec>>12)&63)+128).chr(($UtfCharInDec>>6)&63+128). chr($UtfCharInDec&63+128);
	    return $OutputChar;
	}

	function UTFto_Conv($string, $charset = 'cp1251')
	{	    $table = $this->GetChartable($charset);
        foreach ($table as $key=>$val)
            $string = str_replace($key, $val, $string);
        return $string;
	}
}
?>