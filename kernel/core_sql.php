<?php
// Simple InUSE Check
if ( !defined('QF_STARTED') )
        die('Hacking attempt');

if ( defined('CORE_SQL_LOADED') )
        die('Scripting error');

define('CORE_SQL_LOADED', True);

class mysql_dumper
{
    var $struct = Array();
    var $fstream, $filename, $fgzip;
    var $repldbkey = false;
    var $tblslist = Array();

    function mysql_dumper($repldbkey=false)
    {
        global $QF_DBase;

        $this->repldbkey = ($repldbkey) ? true : false;

        $query='SHOW TABLES';
        $result=$QF_DBase->sql_query($query);
        $this->tblslist = Array();
        if ($result) while($tbl=$QF_DBase->sql_fetchrow($result, false)) {
            list($tblname)=$tbl;
            $this->tblslist[]=$tblname;
        }

    }

    function file_init($file, $try_gzip=false )
    {
        if ($try_gzip && extension_loaded('zlib')) {
            $file.='.gz';
            $this->fgzip = true;
            $this->stream = gzopen($file, 'wb9');
        }
        else {
            $this->fgzip = true;
            $this->stream = fopen($file, 'wb');
        }
        if ($this->stream)
            $this->filename = $file;

        return $this->stream;
    }

    function file_write($string)
    {
        if (!$this->stream)
            return false;
        if ($this->fgzip)
            return gzwrite($this->stream, $string);
        else
            return fwrite($this->stream, $string);
    }

    function file_close()
    {
        if (!$this->stream)
            return false;
        if ($this->fgzip)
            $res=gzclose($this->stream);
        else
            $res=fclose($this->stream);
        if ($res)
            $this->stream = false;
        return $res;
    }

    function get_table_structure($table)
    {
        global $QF_DBase;
        $dbkey = $QF_DBase->tbl_prefix;

        if (!in_array($table, $this->tblslist)) return false;

        $field_query = 'SHOW FIELDS FROM '.$table;
        $key_query = 'SHOW KEYS FROM '.$table;

        $tblstruct = Array();

        $result = $QF_DBase->sql_query($field_query);
        if(!$result)
                trigger_error("Failed in get_table_def (show fields) ".$field_query,256);

        $fields = Array();

        while ($row = $QF_DBase->sql_fetchrow($result))
        {
            $fname = $row['Field'];

            $fstruct = Array(
                'type'  => $row['Type'],
                'null'  => ($row['Null'] == "YES") ? true : false,
                'def'   => $row['Default'],
                'extra' => $row['Extra'] );

            $fields[$fname] = $fstruct;
        }


        $result = $QF_DBase->sql_query($key_query);
        if(!$result)
                trigger_error("FAILED IN get_table_def (show keys) ".$key_query,256);

        $keys = Array();

        while($row = $QF_DBase->sql_fetchrow($result))
        {
            $kname = $row['Key_name'];

            if(!is_array($keys[$kname]))
            {
                $ktype = $row['Index_type'];
                if ($ktype=='BTREE') {
                    if ($kname == 'PRIMARY')
                        $ktype = 'PRIMARY';
                    elseif ($row['Non_unique'] == 0)
                        $ktype = 'UNIQUE';
                    else
                        $ktype = 'INDEX';
                }

                $keys[$kname] = Array(
                    'type' => $ktype,
                    'cols' => Array() );

            }
            $keys[$kname]['cols'][] = $row['Column_name'];
        }

        $tblstruct = Array(
            'fields' => $fields,
            'keys'   => $keys );

        if (strpos($table,$dbkey)==0 && $this->repldbkey)
            $tblname = preg_replace('#^'.$dbkey.'#', '{DBKEY}', $table);
        else
            $tblname = $table;

        $tblstruct['name'] = $tblname;

        $this->struct[$tblname] = $tblstruct;

        return $tblstruct;
    }

    function combine_create_table($tblstruct, $dropfirst=false)
    {
        if (!is_array($tblstruct)) return false;

        $tblname = $tblstruct['name'];

        $tblquery = "# Table definition for $tblname \n";
        if ($dropfirst)
            $tblquery.= "DROP TABLE IF EXISTS `$tblname` ;\n";
        $tblquery.= "CREATE TABLE `$tblname` ( \n";

        $fields = $tblstruct['fields'];
        $flist = Array();
        if (is_array($fields))
            foreach ($fields as $name => $fdata)
                if (is_array($fdata)) {
                    $field='`'.$name.'` '.$fdata['type'];
                    if (!$fdata['null']) $field.=' NOT NULL';
                    if (strlen($fdata['def'])>0) $field.=' default \''.addslashes($fdata['def']).'\'';
                    elseif ($fdata['null']) $field.=' default NULL';
                    if (strlen($fdata['extra'])>0) $field.=' '.$fdata['extra'];
                    $flist[]=$field;
                }

        $flist=implode(", \n    ",$flist);

        $keys = $tblstruct['keys'];
        $klist = Array();
        if (is_array($keys))
            foreach ($keys as $name => $data)
                if (is_array($data)) {
                    foreach ($data['cols'] as $num => $col)
                        $data['cols'][$num] = '`'.$col.'`';
                    if ($data['type']=='PRIMARY')
                        $klist[]='PRIMARY KEY ('.implode(', ', $data['cols']).') ';
                    else
                        $klist[]=$data['type'].' `'.$name.'` ('.implode(', ', $data['cols']).') ';
                }

        $klist=implode(", \n    ",$klist);

        $tblquery.='    '.$flist;
        if (!empty($klist))
            $tblquery.=", \n    ".$klist;
        $tblquery.=" \n); \n\n";

        if(get_magic_quotes_runtime())
        {
                return(stripslashes($tblquery));
        }
        else
        {
                return($tblquery);
        }


    }

    function get_create_table($table, $dropfirst=false)
    {
        global $QF_DBase;
        $dbkey = $QF_DBase->tbl_prefix;

        if (strpos($table,$dbkey)==0 && $this->repldbkey)
            $tblname = preg_replace('#^'.$dbkey.'#', '{DBKEY}', $table);
        else
            $tblname = $table;

        if(!is_array($this->struct[$tblname]))
            $tblstruct = $this->get_table_structure($table);

        return $this->combine_create_table($tblstruct, $dropfirst);
    }

    function dump_content($table, $do_replace=false, $addSQL = '')
    {
        global $QF_DBase;
        $dbkey = $QF_DBase->tbl_prefix;

        $comm = ($do_replace) ? 'REPLACE INTO ' : 'INSERT INTO ';

        if (!in_array($table, $this->tblslist)) return false;

        if (strpos($table,$dbkey)==0 && $this->repldbkey)
            $tblname = preg_replace('#^'.$dbkey.'#', '{DBKEY}', $table);
        else
            $tblname = $table;

        if (!($result = $QF_DBase->sql_query("SELECT * FROM $table $addSQL")))
                trigger_error("Failed in dump_content (select *) SELECT * FROM $table",256);

        if ($row = $QF_DBase->sql_fetchrow($result))
        {
            $this->file_write("# Data content for $tblname \n");
            $field_names = array();

            // Grab the list of field names.
            $num_fields = $QF_DBase->sql_numfields($result);
            for ($j = 0; $j < $num_fields; $j++)
                $field_names[$j] = $QF_DBase->sql_fieldname($j, $result);

            $table_list = '(`'.implode('`, `', $field_names).'`)';

            do
            {
                        // Start building the SQL statement.
                $dump = $comm." $tblname $table_list \n    VALUES(";

                for ($j = 0; $j < $num_fields; $j++)
                {
                    $dump.= ($j > 0) ? ', ' : '';

                    if (!isset($row[$field_names[$j]]))
                        $dump.= 'NULL';

                    elseif ($row[$field_names[$j]] != '')
                        $dump.= '\'' . addslashes($row[$field_names[$j]]) . '\'';

                    else
                        $dump.= '\'\'';
                }

                $dump.= ");\n";

                if(get_magic_quotes_runtime())
                    $this->file_write(stripslashes($dump));
                else
                    $this->file_write($dump);

            }
            while ($row = $QF_DBase->sql_fetchrow($result));

        }

        $this->file_write("\n\n");

    }

    function dump_tables($file, $try_gzip=false, $sets=Array())
    {
        global $QF_DBase;
        $all_tables = false;
        $nostruct = false;
        $nocontent = false;
        $dropfirst= true;
        $dbkey = $QF_DBase->tbl_prefix;

        if (is_array($sets))
            foreach($sets as $set=>$val)
                $$set = $val;

        $this->file_init($file, $try_gzip);
        $this->file_write("#\n# QuickFox mysql database dump file \n#\n");

        $query='SHOW TABLES';
        $result=$QF_DBase->sql_query($query);
        if ($result) while($tbl=$QF_DBase->sql_fetchrow($result, false)) {
            list($tblname)=$tbl;
            if ((strpos($tblname,$dbkey)==0 && substr_count($tblname,$dbkey)) || $all_tables) {
                if (!$nostruct)
                    $this->file_write($this->get_create_table($tblname,$dropfirst));
                if (!$nocontent)
                    $this->dump_content($tblname);
            }
        }

        $this->file_close();

    }

}

class mysql_importer
{
    var $struct = Array();
    var $fstream, $filename, $fgzip;
    var $buffer = '';
    var $EOF = false;
    var $errlog = Array();

    function mysql_importer($repldbkey=false)
    {
        global $QF_DBase;

        $query='SHOW TABLES';
        $result=$QF_DBase->sql_query($query);
        if ($result) while($tbl=$QF_DBase->sql_fetchrow($result, false)) {
            list($tblname)=$tbl;
            $this->tblslist[]=$tblname;
        }

    }

    function file_init($file, $force_gzip=false )
    {
        $finfo=pathinfo($file);
        if (preg_match('#^gz$#i', $finfo['extension']) || $force_gzip) {
            if (extension_loaded('zlib')) {
                $this->fgzip = true;
                $this->stream = gzopen($file, 'rb');
            }
            else
                return false;
        }
        else {
            $this->fgzip = true;
            $this->stream = fopen($file, 'rb');
        }
        if ($this->stream)
            $this->filename = $file;

        $this->EOF = false;
        return $this->stream;
    }

    function file_read($length = 10240)
    {
        if (!$this->stream)
            return false;
        if ($this->fgzip) {
            $this->buffer = gzread($this->stream, $length);
            $this->EOF = gzeof($this->stream);
        }
        else {
            $this->buffer = fread($this->stream, $length);
            $this->EOF = feof($this->stream);
        }

        if ($this->EOF)
            $this->file_close();

        return true;
    }

    function file_close()
    {
        if (!$this->stream)
            return false;
        if ($this->fgzip)
            $res=gzclose($this->stream);
        else
            $res=fclose($this->stream);
        //if ($res)
            $this->stream = null;
        return $res;
    }

    function set_data($string)
    {
        $this->buffer = $string;
        $this->file_close();
    }

    function parse_sql($sel_db='')
    {
        global $QF_DBase;

        $sql_pos = 0;
        $exit = false;

        $sql = '';

        $char = '';
        $dchar = '';

        $in_str = '';
        $in_comm = 0;

        $time0 = time();

        do {
            $char = $this->buffer[$sql_pos] ;
            $sql_pos++;
            $dchar = substr($dchar.$char, -2);

            if ($sql_pos>=strlen($this->buffer)) {
                if ($this->stream) {
                    $this->file_read();
                    $sql_pos = 0;
                }
                else {
                    $exit = true;
                    $in_comm = 0;
                    if ($in_str)
                        $sql.= $in_str;
                    $in_str = '';
                }
            }

            if ($in_comm) {
                switch ($in_comm) {                    case 2:
                        if ($dchar == '*/')
                            $in_comm = 0;
                        break;
                    default:
                        if ($char == "\n")
                            $in_comm = 0;
                }
            }

            elseif ($in_str) {
                $sql.= $char;
                if ($char == $in_str) {

                    if ($in_str == '`') {
                        $in_str = '';
                    }
                    else {
                        // ... first checks for escaped backslashes
                        $j = strlen($sql)-2;
                        $escaped = false;
                        while ($j > 0 && $sql[$j] == '\\') {
                            $escaped = !$escaped;
                            $j--;
                        }
                        // ... if escaped backslashes: it's really the end of the
                        // string -> exit the loop
                        if (!$escaped) {
                            $in_str  = '';
                        }
                    }
                }
            }

            elseif (($char == '"') || ($char == '\'') || ($char == '`')) {
                $sql.= $char;
                $in_str = $char;
            }

            elseif ($char == '#') {
                $in_comm = 1;
            }
            elseif (($dchar == '/*') || ($dchar == '--')) {
                $in_comm = ($dchar == '/*') ? 2 : 1;
                $sql = substr($sql, 0, strlen($sql)-1);
            }

            elseif ($char == ';' || $exit) {
                $sql = trim($sql);
                if (!empty($sql)) {
                    if ($sel_db)
                        $QF_DBase->sql_dbquery($sel_db, $sql);
                    else
                        $QF_DBase->sql_query($sql);

                    if (count($QF_DBase->history)>10000)
                        $QF_DBase->history = Array();
                }

                $sql = '';
            }

            else {
                $sql.= $char;
            }

            $time1     = time();
            if ($time1 >= $time0 + 30) {
                $time0 = $time1;
                header('X-pmaPing: Pong');
            } // end if

        } while (!$exit);
        return true;
    }

    function apply_table_structure($tblstruct, $drop_extra_fields = false)
    {
        global $QF_DBase;
        $dbkey = $QF_DBase->tbl_prefix;

        if (!is_array($tblstruct)) return false;
        if (!$tblstruct['name']) return false;
        $tblstruct['name'] = str_replace('{DBKEY}', $dbkey, $tblstruct['name']);
        $table = $tblstruct['name'];

        $dumper = new mysql_dumper();
        $oldstruct = $dumper->get_table_structure($table);

        if (!is_array($oldstruct)) { // we must create table
            $query = $dumper->combine_create_table($tblstruct);
            $QF_DBase->sql_query($query);
            $err=$QF_DBase->sql_error();
            if ($err['code'])
                $this->errlog[]=$err;
            return true;
        }

        if ($oldstruct['name']!=$table) return false;

        $fields = $tblstruct['fields'];
        $old_fields = $oldstruct['fields'];
        $keys = $tblstruct['keys'];
        $old_keys = $oldstruct['keys'];


        if (!is_array($keys)) $keys=Array();
        if (!is_array($old_keys)) $old_fields=Array();
        if (!is_array($fields)) return false;
        if (!is_array($old_fields)) $old_fields=Array();

        $keys_add=Array();
        $keys_drop=Array();
        $commands=Array();

        foreach ($keys as $kname => $kdata) {
            if (!is_array($old_keys[$kname])) {
                $keys_add[]=$kname;
            }
            else {
                $do_correct = false;
                if ($old_keys[$kname]['type']!=$kdata['type'])
                    $do_correct = true;
                if (sizeof($kdata['cols'])!=sizeof($old_keys[$kname]['cols']))
                    $do_correct = true;
                foreach ($kdata['cols'] as $col)
                    if (!in_array($col, $old_keys[$kname]['cols']))
                        $do_correct = true;
                if ($do_correct) {
                    $keys_drop[]=$kname;
                    $keys_add[]=$kname;
                }
            }
        }

        foreach ($old_keys as $kname => $kdata) {
            if (!is_array($keys[$kname])) {
                $keys_drop[]=$kname;
            }
        }

        // Droping keys first
        foreach ($keys_drop as $kname) {
            if ($kname=='PRIMARY')
                $commands[] = 'DROP PRIMARY KEY';
            else
                $commands[] = 'DROP INDEX `'.$kname.'`';
        }

        // Comparing fields

        $prev_field = ''; // previous field - for inserting operations

        foreach ($fields as $fname => $fdata) {
            $do_correct = false;
            $do_add = false;
            if (!is_array($old_fields[$fname])) {
                $do_correct = true;
                $do_add = true;
            }
            else {
                foreach ($fdata as $param => $val)                    if ($old_fields[$fname][$param]!=$val)
                        $do_correct = true;
            }

            if (!empty($do_correct)) {
                $extra = '';
                $field = '`'.$fname.'` '.$fdata['type'];
                if (!$fdata['null']) $field.=' NOT NULL';
                if (strlen($fdata['def'])>0) $field.=' default \''.addslashes($fdata['def']).'\'';
                elseif ($fdata['null']) $field.=' default NULL';
                if (strlen($fdata['extra'])>0) {
                    $field.=' '.$fdata['extra'];
                    if (preg_match('#^auto_(.*?)$#i', $fdata['extra']) && !in_array('PRIMARY', $keys_add)) {
                        $field.=' PRIMARY KEY';
                        if (is_array($old_keys['PRIMARY']) && !in_array('PRIMARY', $keys_drop))
                            $commands[]='DROP PRIMARY KEY';
                    }
                }

                if ($do_add) {
                    $query = ' ADD '.$field;
                    $query.= (empty($prev_field)) ? ' FIRST' : ' AFTER `'.$prev_field.'`';
                }
                else
                    $query = ' CHANGE `'.$fname.'` '.$field;

                $commands[]=$query;
            }

            $prev_field = $fname;
        }

        // Droping extra fields
        if ($drop_extra_fields) {
            foreach ($old_fields as $fname => $fdata)
                if (!is_array($fields[$fname]))
                    $commands[]='DROP `'.$fname.'`';
        }

        // Adding keys
        foreach ($keys_add as $kname) {
            $kdata = $keys[$kname];

            foreach ($kdata['cols'] as $num => $col)
                $kdata['cols'][$num] = '`'.$col.'`';
            if ($kdata['type']=='PRIMARY')
                $key = 'PRIMARY KEY ('.implode(', ', $kdata['cols']).') ';
            else
                $key = $kdata['type'].' `'.$kname.'` ('.implode(', ', $kdata['cols']).') ';

            $commands[] = 'ADD '.$key;
        }

        if (sizeof($commands)>0) {
            $query = 'ALTER TABLE `'.$table.'` '.implode(', ', $commands);

            $QF_DBase->sql_query($query);
            $err=$QF_DBase->sql_error();
            if ($err['code'])
                $this->errlog[]=$err;
            //print $query;
        }
    }
}

function PMA_splitSqlFile(&$ret, $sql)  //From php my admin
{
    // do not trim, see bug #1030644
    //$sql          = trim($sql);
    $sql          = rtrim($sql, "\n\r");
    $sql_len      = strlen($sql);
    $char         = '';
    $string_start = '';
    $in_string    = FALSE;
    $nothing      = TRUE;
    $time0        = time();

    for ($i = 0; $i < $sql_len; ++$i) {
        $char = $sql[$i];

        // We are in a string, check for not escaped end of strings except for
        // backquotes that can't be escaped
        if ($in_string) {
            for (;;) {
                $i         = strpos($sql, $string_start, $i);
                // No end of string found -> add the current substring to the
                // returned array
                if (!$i) {
                    $ret[] = array('query' => $sql, 'empty' => $nothing);
                    return TRUE;
                }
                // Backquotes or no backslashes before quotes: it's indeed the
                // end of the string -> exit the loop
                else if ($string_start == '`' || $sql[$i-1] != '\\') {
                    $string_start      = '';
                    $in_string         = FALSE;
                    break;
                }
                // one or more Backslashes before the presumed end of string...
                else {
                    // ... first checks for escaped backslashes
                    $j                     = 2;
                    $escaped_backslash     = FALSE;
                    while ($i-$j > 0 && $sql[$i-$j] == '\\') {
                        $escaped_backslash = !$escaped_backslash;
                        $j++;
                    }
                    // ... if escaped backslashes: it's really the end of the
                    // string -> exit the loop
                    if ($escaped_backslash) {
                        $string_start  = '';
                        $in_string     = FALSE;
                        break;
                    }
                    // ... else loop
                    else {
                        $i++;
                    }
                } // end if...elseif...else
            } // end for
        } // end if (in string)

        // lets skip comments (/*, -- and #)
        else if (($char == '-' && $sql_len > $i + 2 && $sql[$i + 1] == '-' && $sql[$i + 2] <= ' ') || $char == '#' || ($char == '/' && $sql_len > $i + 1 && $sql[$i + 1] == '*')) {
            $i = strpos($sql, $char == '/' ? '*/' : "\n", $i);
            // didn't we hit end of string?
            if ($i === FALSE) {
                break;
            }
            if ($char == '/') $i++;
        }

        // We are not in a string, first check for delimiter...
        else if ($char == ';') {
            // if delimiter found, add the parsed part to the returned array
            $ret[]      = array('query' => substr($sql, 0, $i), 'empty' => $nothing);
            $nothing    = TRUE;
            $sql        = ltrim(substr($sql, min($i + 1, $sql_len)));
            $sql_len    = strlen($sql);
            if ($sql_len) {
                $i      = -1;
            } else {
                // The submited statement(s) end(s) here
                return TRUE;
            }
        } // end else if (is delimiter)

        // ... then check for start of a string,...
        else if (($char == '"') || ($char == '\'') || ($char == '`')) {
            $in_string    = TRUE;
            $nothing      = FALSE;
            $string_start = $char;
        } // end else if (is start of string)

        elseif ($nothing) {
            $nothing = FALSE;
        }

        // loic1: send a fake header each 30 sec. to bypass browser timeout
        $time1     = time();
        if ($time1 >= $time0 + 30) {
            $time0 = $time1;
            header('X-pmaPing: Pong');
        } // end if
    } // end for

    // add any rest to the returned array
    if (!empty($sql) && preg_match('@[^[:space:]]+@', $sql)) {
        $ret[] = array('query' => $sql, 'empty' => $nothing);
    }

    return TRUE;
} // end of the 'PMA_splitSqlFile()' function


?>
