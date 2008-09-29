<?php

// Simple InUSE Check
if ( !defined('QF_STARTED') )
        die('Hacking attempt');

if ( defined('CORE_SESSION_LOADED') )
        die('Scripting error');

define('CORE_SESSION_LOADED', True);

// ----------------------------------------------------------- \\
//            Quick Fox config control interface  LION 2007    \\
// ----------------------------------------------------------- \\


class qf_config_cl
{    var $values = Array();        //Main Config values
    var $groups = Array();        //Config vals for groups of configs
    var $val_upd = Array();       //Updates list for saving
    var $grp_upd = Array();       //Updates list for saving
    var $need_save = false;

    function qf_config_cl()
    {
    }

    function Load()
    {        global $QF_DBase;
        if ( $result=$QF_DBase->sql_doselect('{DBKEY}config') )
        {
            while ( $setting = $QF_DBase->sql_fetchrow($result))
                if ( !empty($setting['name']) )
                {
                    if (!empty($setting['parent']))
                        $this->groups[$setting['parent']][$setting['name']] = $setting['value'];
                    else
                        $this->values[$setting['name']] = $setting['value'];
                }

            $QF_DBase->sql_freeresult($result);
        };
    }

    function Get($name, $group=false)
    {        if ($group) {
            if (is_array($this->groups[$group]))
                return $this->groups[$group][$name];
            else
                return null;
        }
        else
            return $this->values[$name];
    }

    function Set($name, $value, $group=false, $global=false)
    {        if ($group)
            $this->groups[$group][$name] = $value;
        else
            $this->values[$name] = $value;

        if ($global) {            if ($group)
                $this->grp_upd[$group][$name] = true;
            else
                $this->val_upd[$name] = true;
            if (!$this->need_save) {                $this->need_save = true;
                register_shutdown_function('qf_config_shutdown');
            }
        }
    }
}

function qf_config_shutdown();
{    Global $QF_Config;
    Global $QF_DBase;

    if (!is_a($QF_Config, 'qf_config_cl'))
        return true;

    if (!$QF_Config->need_save)
        return true;

    if (

    return true;
}
?>