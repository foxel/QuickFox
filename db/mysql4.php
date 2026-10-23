<?php

if (defined('SQL_DRIVER'))
    die('Scripting error');


// ----------------------------------------------------------------------------- \\
//            QuickFox MySQL database driver via mysqli  (c) LION 2006, 2026\\
// ----------------------------------------------------------------------------- \\

define('SQL_DRIVER','mysqli');

class qf_sql_base
{
    /** @var mysqli */
    var $db_connect_id = null;

    var $server        = '';
    var $database      = '';
    var $codepage      = 'cp1251';

    var $tbl_prefix    = 'qf_';
    var $auto_prefix   = false;
    var $persistency   = true;

    var $query_result  = null;
    var $do_warnings   = false;

    var $row       = array();
    var $rowset    = array();

    var $num_queries   = 0;
    var $queries_time  = 0;
    var $history       = array();

    // Constructor
    function __construct($conn_config, $persistency = true, $auto_prf = true)
    {
        $this->persistency = $persistency;

        $this->server      = $conn_config['location'];
        $this->database    = $conn_config['database'];
        $password          = $conn_config['password'];
        $username          = $conn_config['username'];

        $this->auto_prefix = ( $auto_prf ) ? true : false;

        $this->codepage    = ( $conn_config['codepage'] ) ? $conn_config['codepage'] : 'cp1251';
        $this->tbl_prefix  = ( $conn_config['prefix'] ) ? $conn_config['prefix'] : 'qf_';


        $server = ($this->persistency) ? 'p:' . $this->server : $this->server;
        $this->db_connect_id = @mysqli_connect($server, $username, $password);

        if( $this->db_connect_id )
        {
            if( $this->database != '' )
            {
                $dbselect = mysqli_select_db($this->db_connect_id, $this->database);

                if( !$dbselect )
                {
                    mysqli_close($this->db_connect_id);
                    $this->db_connect_id = null;
                }
            }

            mysqli_query($this->db_connect_id, 'SET NAMES '.$this->codepage);

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
                $dbselect = mysqli_select_db($this->db_connect_id, $this->database);

                if( !$dbselect )
                {
                    mysqli_close($this->db_connect_id);
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
            return mysqli_close($connect);
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
                            $val = mysqli_real_escape_string($this->db_connect_id, $val);
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
                            $val = mysqli_real_escape_string($this->db_connect_id, $val);
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
                        $val = mysqli_real_escape_string($this->db_connect_id, $val);
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

    // mysqli_result objects cannot be used directly as array keys.
    // Use a stable string key derived from the object instead.
    function _res_key($query_id)
    {
        if (is_object($query_id))
            return spl_object_hash($query_id);
        return strval($query_id);
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
                $query = preg_replace('#(\s|\`)\{DBKEY\}(\w+)(\1|$|\n|\r)#s', '${1}'.$this->tbl_prefix.'${2}${1}', $query);

            $this->history[]=$query;
            if (count($this->history)>10000)
                $this->history = Array();
            $this->num_queries++;

            $this->query_result = mysqli_query($this->db_connect_id, $query);
        }

        if( $this->query_result )
        {
            $res_key = $this->_res_key($this->query_result);
            unset($this->row[$res_key]);
            unset($this->rowset[$res_key]);
        }
        else
        {
            $this->query_result = false;
            if ($error['code'] = mysqli_errno($this->db_connect_id))
            {
                $error['message'] = mysqli_error($this->db_connect_id);
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
        if (mysqli_select_db($this->db_connect_id, $database) )
            $result = $this->sql_query($query, $noprefix );

        mysqli_select_db($this->db_connect_id, $this->database);

        return $result;
    }

    // Other query methods
    function sql_numrows($query_id = 0)
    {
        if( !$query_id )
            $query_id = $this->query_result;

        return ( $query_id ) ? mysqli_num_rows($query_id) : false;
    }

    function sql_affectedrows()
    {
        return ( $this->db_connect_id ) ? mysqli_affected_rows($this->db_connect_id) : false;
    }

    function sql_numfields($query_id = 0)
    {
        if( !$query_id )
            $query_id = $this->query_result;

        return ( $query_id ) ? mysqli_num_fields($query_id) : false;
    }

    function sql_fieldname($offset, $query_id = 0)
    {
        if( !$query_id )
            $query_id = $this->query_result;

        if( !$query_id ) return false;
        $field = mysqli_fetch_field_direct($query_id, $offset);
        return ($field) ? $field->name : false;
    }

    function sql_fieldtype($offset, $query_id = 0)
    {
        if( !$query_id )
            $query_id = $this->query_result;

        if( !$query_id ) return false;
        $field = mysqli_fetch_field_direct($query_id, $offset);
        if (!$field) return false;

        switch ($field->type) {
            case MYSQLI_TYPE_TINY:
            case MYSQLI_TYPE_SHORT:
            case MYSQLI_TYPE_LONG:
            case MYSQLI_TYPE_LONGLONG:
            case MYSQLI_TYPE_INT24:
                return 'int';
            case MYSQLI_TYPE_FLOAT:
            case MYSQLI_TYPE_DOUBLE:
            case MYSQLI_TYPE_DECIMAL:
            case MYSQLI_TYPE_NEWDECIMAL:
                return 'real';
            case MYSQLI_TYPE_TIMESTAMP:
                return 'timestamp';
            case MYSQLI_TYPE_DATE:
            case MYSQLI_TYPE_NEWDATE:
                return 'date';
            case MYSQLI_TYPE_TIME:
                return 'time';
            case MYSQLI_TYPE_YEAR:
                return 'year';
            case MYSQLI_TYPE_DATETIME:
                return 'datetime';
            case MYSQLI_TYPE_ENUM:
                return 'enum';
            case MYSQLI_TYPE_SET:
                return 'set';
            case MYSQLI_TYPE_TINY_BLOB:
            case MYSQLI_TYPE_MEDIUM_BLOB:
            case MYSQLI_TYPE_LONG_BLOB:
            case MYSQLI_TYPE_BLOB:
                return 'blob';
            case MYSQLI_TYPE_NULL:
                return 'null';
            case MYSQLI_TYPE_VAR_STRING:
            case MYSQLI_TYPE_STRING:
            case MYSQLI_TYPE_GEOMETRY:
            default:
                return 'string';
        }
    }

    function sql_fetchrow($query_id = 0, $assoc=true)
    {
        $style=($assoc) ? MYSQLI_ASSOC : MYSQLI_BOTH ;

        if( !$query_id )
            $query_id = $this->query_result;

        if( $query_id )
        {
            $res_key = $this->_res_key($query_id);
            $this->row[$res_key] = mysqli_fetch_array($query_id, $style);
            return $this->row[$res_key];
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
            $res_key = $this->_res_key($query_id);
            unset($this->rowset[$res_key]);
            unset($this->row[$res_key]);

            while($this->rowset[$res_key] = mysqli_fetch_array($query_id, MYSQLI_ASSOC))
            {
                if ($field_name) $result[$this->rowset[$res_key][$field_name]] = $this->rowset[$res_key];
                else $result[] = $this->rowset[$res_key];
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
            $res_key = $this->_res_key($query_id);
            if( $rownum > -1 )
            {
                mysqli_data_seek($query_id, $rownum);
                $row = mysqli_fetch_array($query_id, MYSQLI_BOTH);
                $result = $row[$field];
            }
            else
            {
                if( empty($this->row[$res_key]) && empty($this->rowset[$res_key]) )
                {
                    if( $this->sql_fetchrow() )
                    {
                        $result = $this->row[$res_key][$field];
                    }
                }
                else
                {
                    if( $this->rowset[$res_key] )
                    {
                        $result = $this->rowset[$res_key][0][$field];
                    }
                    else if( $this->row[$res_key] )
                    {
                        $result = $this->row[$res_key][$field];
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

        return ( $query_id ) ? mysqli_data_seek($query_id, $rownum) : false;
    }

    function sql_nextid()
    {
        return ( $this->db_connect_id ) ? mysqli_insert_id($this->db_connect_id) : false;
    }

    function sql_freeresult($query_id = 0)
    {
        if( !$query_id )
            $query_id = $this->query_result;

        if ( $query_id )
        {
            $res_key = $this->_res_key($query_id);
            unset($this->row[$res_key]);
            unset($this->rowset[$res_key]);

            mysqli_free_result($query_id);

            return true;
        }
        else
            return false;
    }

    function sql_info()
    {
        return ($this->db_connect_id) ? mysqli_info($this->db_connect_id) : false;
    }

    function sql_error()
    {
        if( !$this->db_connect_id )
            return false;

        $result['message'] = mysqli_error($this->db_connect_id);
        $result['code'] = mysqli_errno($this->db_connect_id);

        return $result;
    }

    function sql_quote($string)
    {
        return mysqli_real_escape_string($this->db_connect_id, $string);
    }

    function srv_info()
    {
        return ($this->db_connect_id) ? 'MySQL. Version '.mysqli_get_server_info($this->db_connect_id) : 'Unconnected';
    }

    // Maintenance functions
    function Fast_DbCheck($dbase = '', $no_quick = false)
    {
        static $Got_Checked = Array();

        if (!$dbase)
            $dbase = $this->database;

        if (isset($Got_Checked[$dbase]))
            return $Got_Checked[$dbase];

        if (!($result = mysqli_query($this->db_connect_id, 'SHOW TABLES FROM '.$dbase)))
            return ($Got_Checked[$dbase] = false);

        $tbls = Array();
        while (list($tbl) = mysqli_fetch_array($result, MYSQLI_NUM))
            $tbls[] = '`'.$tbl.'`';
        if (count($tbls) == 0)
            return ($Got_Checked[$dbase] = true);

        $query = 'CHECK TABLE '.implode(', ', $tbls).(($no_quick) ? '' : ' QUICK');
        if (!($result = mysqli_query($this->db_connect_id, $query)))
            {
                trigger_error('MYSQL FastCheck: Query error while checking: '.mysqli_error($this->db_connect_id), E_USER_ERROR);
                return ($Got_Checked[$dbase] = false);
            }

        $tbls = Array();
        while ($tbl = mysqli_fetch_array($result, MYSQLI_ASSOC))
            if ($tbl['Msg_type'] == 'error')
            {
                trigger_error('MYSQL FastCheck: Table "'.$tbl['Table'].'" is corrupted: '.$tbl['Msg_text'], E_USER_WARNING);
                $tbls[] = str_replace($dbase.'.', $dbase.'.`', $tbl['Table']).'`';
            }
        if (count($tbls) == 0)
            return ($Got_Checked[$dbase] = true);

        $query = 'REPAIR TABLE '.implode(', ', array_unique($tbls)).' EXTENDED';
        if (!($result = mysqli_query($this->db_connect_id, $query)))
            {
                trigger_error('MYSQL FastCheck: Query error while repeiring: '.mysqli_error($this->db_connect_id), E_USER_ERROR);
                return ($Got_Checked[$dbase] = false);
            }

        while ($tbl = mysqli_fetch_array($result, MYSQLI_ASSOC))
            if ($tbl['Msg_type'] == 'error')
            {
                trigger_error('MYSQL FastCheck: Table "'.$tbl['Table'].'" is corrupted and was not fixed automatically: '.$tbl['Msg_text'], E_USER_ERROR);
                return ($Got_Checked[$dbase] = false);
            }

        return ($Got_Checked[$dbase] = true);
    }

} // class sql_db
