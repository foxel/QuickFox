<?php

if (defined('SQL_DRIVER'))
    die('Scripting error');


define('SQL_DRIVER','mysql4');

if (!function_exists('mysql_real_escape_string')) {
    function mysql_real_escape_string($string)
    {
        /** @noinspection PhpDeprecationInspection */
        return mysql_escape_string($string);
    }
}

class qf_sql_base
{
    /** @var resource */
    var $db_connect_id = null;

    var $server        = '';
    var $database      = '';
    var $codepage      = 'cp1251';

    var $tbl_prefix    = 'qf_';
    var $auto_prefix   = false;

    var $query_result  = null;
    var $do_warnings   = false;

    var $row       = array();
    var $rowset    = array();

    var $num_queries   = 0;
    var $queries_time  = 0;
    var $history       = array();

    // Constructor
    function qf_sql_base($conn_config, $persistency = true, $auto_prf = true)
    {
        $this->persistency = $persistency;

        $this->server      = $conn_config['location'];
        $this->database    = $conn_config['database'];
        $password          = $conn_config['password'];
        $username          = $conn_config['username'];

        $this->auto_prefix = ( $auto_prf ) ? true : false;

        $this->codepage    = ( $conn_config['codepage'] ) ? $conn_config['codepage'] : 'cp1251';
        $this->tbl_prefix  = ( $conn_config['prefix'] ) ? $conn_config['prefix'] : 'qf_';


        $this->db_connect_id = ($this->persistency) ? @mysql_pconnect($this->server, $username, $password) : @mysql_connect($this->server, $username, $password);

        if( $this->db_connect_id )
        {
            if( $this->database != '' )
            {
                $dbselect = mysql_select_db($this->database);

                if( !$dbselect )
                {
                    mysql_close($this->db_connect_id);
                    $this->db_connect_id = null;
                }
            }

            mysql_query('SET NAMES '.$this->codepage, $this->db_connect_id);

            return $this->db_connect_id;
        } else {
            $this->db_connect_id = null;
            return false;
        }
    }

    // Other base methods
    function sql_selectdb($database)
    {
        if( $this->db_connect_id )
        {
            if( $database != "" )
            {
                $this->database = $database;
                $dbselect = mysql_select_db($this->database);

                if( !$dbselect )
                {
                    mysql_close($this->db_connect_id);
                    $this->db_connect_id = null;
                }
            }

            return $this->db_connect_id;
        }

    }

    function sql_close()
    {
        $connect = $this->db_connect_id;
        $this->db_connect_id = null;
        if ( $connect )
            return mysql_close($connect);
        else
            return false;
    }

    // High level query constructors

    // simple one table select constructor
    function sql_doselect ($table, $fields = Array(), $where = '', $other = '', $dontescape = false)
    {
        $where = $this->sql_whereparse($where, $dontescape);

        if ($this->auto_prefix)
            $table = preg_replace('#^\{DBKEY\}#', $this->tbl_prefix, $table, 1);

        $query = 'SELECT ';

        if (is_array($fields)) {
            if (count($fields))
                $fields = implode(', ', $fields).' ';
            else
                $fields = '*';
        }

        if (empty($fields))
            $fields = '*';

        $query.=$fields.' ';

        $query.='FROM `'.$table.'` '.$where.' '.strval($other);
        return $this->sql_query($query, true);
    }

    // insert function - please use this one instead of direct query? 'cause this will correctly escape the strings
    function sql_doinsert ($table, $data = Array(), $replace = false, $dontescape = false)
    {
        $query = ($replace) ? 'REPLACE INTO ' : 'INSERT INTO ';

        if ($this->auto_prefix)
            $table = preg_replace('#^\{DBKEY\}#', $this->tbl_prefix, $table, 1);

        $query.= '`'.$table.'` ';

        if (is_array($data) && count($data)) {
            $names = $vals = Array();
            foreach ($data AS $field=>$val)
                if (!is_null($val)) {
                    $names[] = '`'.$field.'`';

                    if (!is_numeric($val)) {
                        if (!$dontescape)
                            $val = mysql_real_escape_string($val, $this->db_connect_id);
                        $val = '"'.$val.'"';
                    }
                    elseif (is_string($val))
                        $val = '"'.$val.'"';

                    $vals[] = $val;
                }
            $query.='('.implode(', ', $names).') VALUES ('.implode(', ', $vals).')';

            return $this->sql_query($query, true);
        }
        else
            return false;
    }

    // update function - please use this one instead of direct query? 'cause this will correctly escape the strings
    function sql_doupdate ($table, $data = Array(), $where = '', $dontescape = false)
    {
        $where = $this->sql_whereparse($where, $dontescape);

        if ($this->auto_prefix)
            $table = preg_replace('#^\{DBKEY\}#', $this->tbl_prefix, $table, 1);

        $query = 'UPDATE `'.$table.'` SET ';

        if (is_array($data) && count($data)) {
            $names = $vals = Array();
            foreach ($data AS $field=>$val)
                if (!is_null($val)) {
                    if (!is_numeric($val)) {
                        if (!$dontescape)
                            $val = mysql_real_escape_string($val, $this->db_connect_id);
                        $val = '"'.$val.'"';
                    }
                    elseif (is_string($val))
                        $val = '"'.$val.'"';

                    $fields[] = '`'.$field.'` = '.$val;
                }
            $query.= implode(', ', $fields);
            $query.= ' '.$where;
            return $this->sql_query($query, true);
        }
        else
            return false;
    }

    // update function - please use this one instead of direct query? 'cause this will correctly escape the strings
    function sql_dodelete ($table, $where = '', $dontescape = false)
    {
        $where = $this->sql_whereparse($where, $dontescape);

        if ($this->auto_prefix)
            $table = preg_replace('#^\{DBKEY\}#', $this->tbl_prefix, $table, 1);

        $query = 'DELETE FROM `'.$table.'` '.$where;

        return $this->sql_query($query, true);
    }

    // constructs simple WHERE with AND construction
    function sql_whereparse ($where, $dontescape = false)
    {
        if (empty($where))
            return '';

        if (is_array($where)) {
            $parts = Array();
            foreach ($where AS $field=>$val) {
                if (!is_numeric($val)) {
                    if (!$dontescape)
                        $val = mysql_real_escape_string($val, $this->db_connect_id);
                    $val = '"'.$val.'"';
                }
                $parts[] = '`'.$field.'` = '.$val;
            }
            return 'WHERE '.implode(' AND ', $parts);
        }
        else {
            $where = trim(strval($where));
            if (!preg_match('#^WHERE\s#i', $where))
                $where = 'WHERE '.$where;
            return $where;
        }
    }

    // Base direct query method
    function sql_query($query = '', $noprefix = false)
    {
        if (!$this->db_connect_id)
            return false;

        $stime = explode(' ',microtime());
        $start_time=$stime[1]+$stime[0];

        unset($this->query_result);

        if( $query != '' )
        {
            if ($this->auto_prefix && !$noprefix)
                $query = preg_replace('#(\s|\`)\{DBKEY\}(\w+)(\\1|$|\n|\r)#s', '${1}'.$this->tbl_prefix.'${2}${1}', $query);

            $this->history[]=$query;
            if (count($this->history)>10000)
                $this->history = Array();
            $this->num_queries++;

            $this->query_result = mysql_query($query, $this->db_connect_id);
        }

        if( $this->query_result )
        {
            unset($this->row[$this->query_result]);
            unset($this->rowset[$this->query_result]);
        }
        else
        {
            $this->query_result = false;
            if ($error['code'] = mysql_errno($this->db_connect_id))
            {
                $error['message'] = mysql_error($this->db_connect_id);
                $this->Fast_DbCheck('', true);
                trigger_error('MYSQL error '.$error['code'].': '.$error['message'].' in '.$query, ($this->do_warnings) ? E_USER_WARNING : E_USER_ERROR);
            }
        }

        $stime = explode(' ',microtime());
        $stop_time=$stime[1]+$stime[0];
        $this->queries_time += $stop_time - $start_time;
        return $this->query_result;
    }

    function sql_dbquery($database, $query, $noprefix = false )
    {
        if (mysql_select_db($database) )
            $result = $this->sql_query($query, $noprefix );

        mysql_select_db($this->database);

        return $result;
    }

    // Other query methods
    function sql_numrows($query_id = 0)
    {
        if( !$query_id )
            $query_id = $this->query_result;

        return ( $query_id ) ? mysql_num_rows($query_id) : false;
    }

    function sql_affectedrows()
    {
        return ( $this->db_connect_id ) ? mysql_affected_rows($this->db_connect_id) : false;
    }

    function sql_numfields($query_id = 0)
    {
        if( !$query_id )
            $query_id = $this->query_result;

        return ( $query_id ) ? mysql_num_fields($query_id) : false;
    }

    function sql_fieldname($offset, $query_id = 0)
    {
        if( !$query_id )
            $query_id = $this->query_result;

        return ( $query_id ) ? mysql_field_name($query_id, $offset) : false;
    }

    function sql_fieldtype($offset, $query_id = 0)
    {
        if( !$query_id )
            $query_id = $this->query_result;

        return ( $query_id ) ? mysql_field_type($query_id, $offset) : false;
    }

    function sql_fetchrow($query_id = 0, $assoc=true)
    {
        $style=($assoc) ? MYSQL_ASSOC : MYSQL_BOTH ;

        if( !$query_id )
            $query_id = $this->query_result;

        if( $query_id )
        {
            $this->row[$query_id] = mysql_fetch_array($query_id, $style);
            return $this->row[$query_id];
        }
        else
            return false;
    }

    function sql_fetchrowset($query_id = 0, $field_name = '')
    {
        if( !$query_id )
            $query_id = $this->query_result;

        if( $query_id )
        {
            unset($this->rowset[$query_id]);
            unset($this->row[$query_id]);

            while($this->rowset[$query_id] = mysql_fetch_array($query_id, MYSQL_ASSOC))
            {
                if ($field_name) $result[$this->rowset[$query_id][$field_name]] = $this->rowset[$query_id];
                else $result[] = $this->rowset[$query_id];
            }

            return $result;
        }
        else
            return false;
    }

    function sql_fetchfield($field, $rownum = -1, $query_id = 0)
    {
        if( !$query_id )
            $query_id = $this->query_result;

        if( $query_id )
        {
            if( $rownum > -1 )
            {
                $result = mysql_result($query_id, $rownum, $field);
            }
            else
            {
                if( empty($this->row[$query_id]) && empty($this->rowset[$query_id]) )
                {
                    if( $this->sql_fetchrow() )
                    {
                        $result = $this->row[$query_id][$field];
                    }
                }
                else
                {
                    if( $this->rowset[$query_id] )
                    {
                        $result = $this->rowset[$query_id][0][$field];
                    }
                    else if( $this->row[$query_id] )
                    {
                        $result = $this->row[$query_id][$field];
                    }
                }
            }

            return $result;
        }
        else
            return false;
    }

    function sql_rowseek($rownum, $query_id = 0)
    {
        if( !$query_id )
            $query_id = $this->query_result;

        return ( $query_id ) ? mysql_data_seek($query_id, $rownum) : false;
    }

    function sql_nextid()
    {
        return ( $this->db_connect_id ) ? mysql_insert_id($this->db_connect_id) : false;
    }

    function sql_freeresult($query_id = 0)
    {
        if( !$query_id )
            $query_id = $this->query_result;

        if ( $query_id )
        {
            unset($this->row[$query_id]);
            unset($this->rowset[$query_id]);

            mysql_free_result($query_id);

            return true;
        }
        else
            return false;
    }

    function sql_info()
    {
        return ($this->db_connect_id) ? mysql_info($this->db_connect_id) : false;
    }

    function sql_error()
    {
        if( !$this->db_connect_id )
            return false;

        $result['message'] = mysql_error($this->db_connect_id);
        $result['code'] = mysql_errno($this->db_connect_id);

        return $result;
    }

    function sql_quote($string)
    {
        return mysql_real_escape_string($string, $this->db_connect_id);
    }

    function srv_info()
    {
        return ($this->db_connect_id) ? 'MySQL. Version '.mysql_get_server_info($this->db_connect_id) : 'Unconnected';
    }

    // Maintenance functions
    function Fast_DbCheck($dbase = '', $no_quick = false)
    {
        static $Got_Checked = Array();

        if (!$dbase)
            $dbase = $this->database;

        if (isset($Got_Checked[$dbase]))
            return $Got_Checked[$dbase];

        if (!($result = mysql_query('SHOW TABLES FROM '.$dbase, $this->db_connect_id)))
            return ($Got_Checked[$dbase] = false);

        $tbls = Array();
        while (list($tbl) = mysql_fetch_array($result, MYSQL_NUM))
            $tbls[] = '`'.$tbl.'`';
        if (count($tbls) == 0)
            return ($Got_Checked[$dbase] = true);

        $query = 'CHECK TABLE '.implode(', ', $tbls).(($no_quick) ? '' : ' QUICK');
        if (!($result = mysql_query($query, $this->db_connect_id)))
            {
                trigger_error('MYSQL FastCheck: Query error while checking: '.mysql_error($this->db_connect_id), E_USER_ERROR);
                return ($Got_Checked[$dbase] = false);
            }

        $tbls = Array();
        while ($tbl = mysql_fetch_array($result, MYSQL_ASSOC))
            if ($tbl['Msg_type'] == 'error')
            {
                trigger_error('MYSQL FastCheck: Table "'.$tbl['Table'].'" is corrupted: '.$tbl['Msg_text'], E_USER_WARNING);
                $tbls[] = str_replace($dbase.'.', $dbase.'.`', $tbl['Table']).'`';
            }
        if (count($tbls) == 0)
            return ($Got_Checked[$dbase] = true);

        $query = 'REPAIR TABLE '.implode(', ', array_unique($tbls)).' EXTENDED';
        if (!($result = mysql_query($query, $this->db_connect_id)))
            {
                trigger_error('MYSQL FastCheck: Query error while repeiring: '.mysql_error($this->db_connect_id), E_USER_ERROR);
                return ($Got_Checked[$dbase] = false);
            }

        while ($tbl = mysql_fetch_array($result, MYSQL_ASSOC))
            if ($tbl['Msg_type'] == 'error')
            {
                trigger_error('MYSQL FastCheck: Table "'.$tbl['Table'].'" is corrupted and was not fixed automatically: '.$tbl['Msg_text'], E_USER_ERROR);
                return ($Got_Checked[$dbase] = false);
            }

        return ($Got_Checked[$dbase] = true);
    }

} // class sql_db

?>
