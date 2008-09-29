<?php

// Simple InUSE Check
if ( !defined('QF_STARTED') )
    die('Hacking attempt');

include 'kernel/core_sql.php';

Load_Language('utils');

LoadVisuals('utils');

$Page_SubTitle = 'QuickFox Direct SQL admin tool.';

if ($QF_User->uid!=1 || $QF_Session->Get('is_admin')!=1)    print Vis_Err_String('Access denied!');

else
{
    Set_Adm_Lock(180);

    $timer->Time_Point();           // We'll count time
    $OQ_time = $QF_DBase->queries_time; //time taken with queries

    $sel_db = Get_Request('sel_db', 2);
    $todo = Get_Request('do_query', 2);

    $show_fulltexts = Get_Request('show_full', 2, 'b');

    if(!$todo)
        $todo='SHOW TABLES';

    $pieces = array();

    PMA_splitSqlFile($pieces, $todo);

    $pieces_count = count($pieces);


    if (!$sel_db)
        $sel_db=$QF_Dbase_Config['database'];

    //$DSQ_dbase = new

    $ptmpl = Array(
        'SQL_version'   => $QF_DBase->srv_info() ,
        'base_selected' => $sel_db ,
        'queries_count' => $pieces_count ,
        'queries'       => ''
    );

    for ($i = 0; $i < $pieces_count; $i++) {
        $a_sql_query = $pieces[$i]['query'];

        $qtmpl = Array(
            'query_str' => $a_sql_query,
            'query_num' => $i+1,
            );

        $result=$QF_DBase->sql_dbquery($sel_db, $a_sql_query);
        if ($result)
        {
            $qtmpl['query_res'] = $QF_DBase->sql_info().' OK';

            if (strpos($result,'id'))
            {
                $qtmpl['query_rows'] = $QF_DBase->sql_numrows();

                $query_table = '<table class="qf_grid fullwidth noborder">';

                // Grab the list of field names.
                $num_fields = $QF_DBase->sql_numfields($result);
                $capts_row = '<tr class="tblline">';
                for ($j = 0; $j < $num_fields; $j++) {
                    $field_names[$j] = $QF_DBase->sql_fieldname($j, $result);
                    $capts_row.= '<td>'.$field_names[$j].'</td>';
                }
                $capts_row.= '</tr>';

                $row_no = 0;

                while ($row=$QF_DBase->sql_fetchrow($result)) {                    if ( ($row_no % 20)==0 )
                        $query_table.= $capts_row."\n";

                    $cur_row = '<tr class="hlight">';
                    for ($j = 0; $j < $num_fields; $j++)
                    {
                        if (!isset($row[$field_names[$j]]))
                            $value = 'NULL';
                        elseif ($row[$field_names[$j]] != '') {
                            if (!$show_fulltexts)
                                $value = STrim($row[$field_names[$j]], 64);
                            else
                                $value = $row[$field_names[$j]];

                            $value = nl2br(htmlspecialchars($value));
                        }
                        else
                            $value = '&nbsp;';


                        $cur_row.='<td>'.$value.'</td>';
                    }
                    $cur_row.= '</tr>';

                    $query_table.= $cur_row."\n";

                    $row_no++;
                }

                $query_table.= '</table>';
                $QF_DBase->sql_freeresult($result);
            }
            else
            {                $qtmpl['query_rows'] = 0;
                $query_table = '';
            }

            $qtmpl['data_tbl'] = $query_table;

        }
        else
            $qtmpl['query_res'] = 'ERROR';

        $ptmpl['queries'].= Visual('DIRECT_SQL_QUERY', $qtmpl)."\n";
    }

    $ftmpl = Array(
        'curquery'     => $todo,
        'dbs_options'  => '',
        'tbls_options' => '',
        'show_full'    => ($show_fulltexts) ? 'on' : '',
        );

    $dbs = $QF_DBase->sql_query('SHOW DATABASES');
    IF ($dbs) {
        while ($row=$QF_DBase->sql_fetchrow($dbs, false))
            $ftmpl['dbs_options'].='<option value="'.$row[0].'" '.(($row[0]==$sel_db) ? 'SELECTED' : '').'>'.$row[0].'</option>';
        $QF_DBase->sql_freeresult($dbs);
    }

    $tbls = $QF_DBase->sql_query('SHOW TABLES');
    IF ($tbls) {
        while ($row=$QF_DBase->sql_fetchrow($tbls, false))
            $ftmpl['tbls_options'].='<option value="'.$row[0].'" >'.$row[0].'</option>';
        $QF_DBase->sql_freeresult($tbls);
    }

    $ptmpl['do_form'] = Visual('DIRECT_SQL_QFORM', $ftmpl);
    $ptmpl['exec_time'] = round($timer->Time_Point(), 3);
    $ptmpl['quers_time'] = round(($QF_DBase->queries_time - $OQ_time), 3); //time taken with queries

    print Visual('DIRECT_SQL_MAINPAGE', $ptmpl);

}
?>