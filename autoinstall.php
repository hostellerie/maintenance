<?php

// +---------------------------------------------------------------------------+
// | Maintenance Plugin 1.1.0                                                  |
// +---------------------------------------------------------------------------+

require_once dirname(__FILE__) . '/functions.inc';

function plugin_autoinstall_maintenance($pi_name)
{
    $piName = 'maintenance';
    $displayName = 'Maintenance Mode';
    $adminGroup = $displayName . ' Admin';

    return array(
        'info' => array(
            'pi_name'         => $piName,
            'pi_display_name' => $displayName,
            'pi_version'      => '1.1.0',
            'pi_gl_version'   => '2.1.1',
            'pi_homepage'     => 'https://geeklog.net'
        ),
        'groups' => array(
            $adminGroup => 'Users in this group can administer the ' . $displayName . ' plugin'
        ),
        'features' => array(
            $piName . '.admin' => 'Full access to ' . $displayName . ' plugin',
            'config.' . $piName . '.tab_main' => 'Access to Maintenance configuration'
        ),
        'mappings' => array(
            $piName . '.admin' => array($adminGroup),
            'config.' . $piName . '.tab_main' => array($adminGroup)
        ),
        'tables' => array()
    );
}

function plugin_load_configuration_maintenance($pi_name)
{
    global $_CONF;

    $defaults = $_CONF['path'] . 'plugins/' . $pi_name . '/install_defaults.php';
    if (!file_exists($defaults)) {
        return false;
    }

    require_once $defaults;

    return function_exists('plugin_initconfig_' . $pi_name)
        && call_user_func('plugin_initconfig_' . $pi_name);
}

/**
 * Create or repair the optional administrator warning block.
 *
 * Schema detection preserves compatibility with installations whose blocks
 * table still has the legacy tid column.
 *
 * @return bool
 */
function maintenance_install_block()
{
    global $_TABLES;

    $blockResult = DB_query("SELECT bid FROM {$_TABLES['blocks']} "
        . "WHERE name = 'maintenance_check' OR "
        . "(type = 'phpblock' AND phpblockfn = 'phpblock_maintenance_check') "
        . 'ORDER BY bid LIMIT 1', 1);
    if (DB_error()) {
        COM_errorLog('Maintenance Plugin: could not look up the warning block.');
        return false;
    }

    if (DB_numRows($blockResult) > 0) {
        $block = DB_fetchArray($blockResult);
        $blockId = (int) $block['bid'];
    } else {
        $rootGroupId = (int) DB_getItem($_TABLES['groups'], 'grp_id', "grp_name = 'Root'");
        $rootUserId = (int) DB_getItem($_TABLES['users'], 'uid', "username = 'Admin'");
        if ($rootUserId < 1) {
            $rootUserId = (int) DB_getItem($_TABLES['users'], 'MIN(uid)', 'uid > 1');
        }
        if ($rootGroupId < 1 || $rootUserId < 1) {
            COM_errorLog('Maintenance Plugin: could not resolve a block owner or the Root group.');
            return false;
        }

        $columnResult = DB_query("SHOW COLUMNS FROM {$_TABLES['blocks']} LIKE 'tid'", 1);
        if (DB_error()) {
            COM_errorLog('Maintenance Plugin: could not inspect the blocks schema.');
            return false;
        }
        $hasTid = DB_numRows($columnResult) > 0;
        $columns = 'is_enabled,name,type,title,blockorder,onleft,phpblockfn,'
            . 'group_id,owner_id,perm_owner,perm_group,perm_members,perm_anon';
        $values = "1,'maintenance_check','phpblock','',0,1,"
            . "'phpblock_maintenance_check',$rootGroupId,$rootUserId,3,3,3,3";
        if ($hasTid) {
            $columns = 'is_enabled,name,type,title,tid,blockorder,onleft,phpblockfn,'
                . 'group_id,owner_id,perm_owner,perm_group,perm_members,perm_anon';
            $values = "1,'maintenance_check','phpblock','','all',0,1,"
                . "'phpblock_maintenance_check',$rootGroupId,$rootUserId,3,3,3,3";
        }

        DB_query("INSERT INTO {$_TABLES['blocks']} ($columns) VALUES ($values)", 1);
        if (DB_error()) {
            COM_errorLog('Maintenance Plugin: failed to create the warning block.');
            return false;
        }
        $blockId = (int) DB_insertId();
    }

    $assignment = DB_query("SELECT id FROM {$_TABLES['topic_assignments']} "
        . "WHERE tid = 'all' AND type = 'block' AND id = $blockId", 1);
    if (DB_error()) {
        COM_errorLog('Maintenance Plugin: could not inspect the block topic assignment.');
        return false;
    }
    if (DB_numRows($assignment) === 0) {
        DB_query("INSERT INTO {$_TABLES['topic_assignments']} "
            . "(tid,type,id,inherit,tdefault) VALUES ('all','block',$blockId,1,0)", 1);
        if (DB_error()) {
            COM_errorLog('Maintenance Plugin: failed to assign the warning block to all topics.');
            return false;
        }
    }

    return true;
}

/**
 * Ensure an upgraded installation grants configuration access to its group.
 *
 * @return bool
 */
function maintenance_install_config_permission()
{
    global $_TABLES;

    $featureName = 'config.maintenance.tab_main';
    $featureId = (int) DB_getItem($_TABLES['features'], 'ft_id',
        "ft_name = '$featureName'");
    if ($featureId < 1) {
        DB_save($_TABLES['features'], 'ft_name,ft_descr',
            "'$featureName','Access to Maintenance configuration'");
        $featureId = (int) DB_getItem($_TABLES['features'], 'ft_id',
            "ft_name = '$featureName'");
    }

    $groupId = (int) DB_getItem($_TABLES['groups'], 'grp_id',
        "grp_name = 'Maintenance Mode Admin'");
    if ($featureId < 1 || $groupId < 1) {
        COM_errorLog('Maintenance Plugin: could not resolve its configuration permission or administration group.');
        return false;
    }

    if (DB_count($_TABLES['access'], array('acc_ft_id', 'acc_grp_id'),
        array($featureId, $groupId)) == 0) {
        DB_save($_TABLES['access'], 'acc_ft_id,acc_grp_id',
            "$featureId,$groupId");
    }

    return true;
}

function plugin_postinstall_maintenance($pi_name)
{
    return maintenance_install_config_permission() && maintenance_install_block();
}
