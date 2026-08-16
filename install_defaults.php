<?php
/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Maintenance Plugin 1.1.1                                                  |
// +---------------------------------------------------------------------------+
// | install_defaults.php                                                      |
// +---------------------------------------------------------------------------+

// Prevent this file from being accessed directly
if (isset($_SERVER['PHP_SELF']) &&
    strpos(strtolower($_SERVER['PHP_SELF']), 'install_defaults.php') !== false) {
    die('This file cannot be used on its own!');
}

/*
 * Maintenance default settings
 *
 * Initial Installation Defaults used when loading the online configuration
 * records. These settings are only used during the initial installation
 * and are not referenced anymore once the plugin is installed.
 */

global $_MAINTENANCE_DEFAULT;
$_MAINTENANCE_DEFAULT = array();
$_MAINTENANCE_DEFAULT['enabled'] = 0;
$_MAINTENANCE_DEFAULT['message'] = 'The website is currently under maintenance. Please come back later.';
$_MAINTENANCE_DEFAULT['retry_after'] = 3600;

/**
 * Initialize Maintenance plugin configuration.
 *
 * @return bool True if the configuration is initialized, false otherwise.
 */
function plugin_initconfig_maintenance()
{
    global $_MAINTENANCE_DEFAULT, $_TABLES;

    $c = config::get_instance();

    if (!$c->group_exists('maintenance')) {
        $c->add('sg_0', NULL, 'subgroup', 0, 0, NULL, 0, true, 'maintenance');
        $c->add('tab_main', NULL, 'tab', 0, 0, NULL, 0, true, 'maintenance', 0);
        $c->add('fs_01', NULL, 'fieldset', 0, 0, NULL, 0, true, 'maintenance', 0);
        $c->add('enabled', $_MAINTENANCE_DEFAULT['enabled'], 'select', 0, 0, 0, 10, true, 'maintenance', 0);
        $c->add('message', $_MAINTENANCE_DEFAULT['message'], 'text', 0, 0, 0, 20, true, 'maintenance', 0);
        $c->add('retry_after', $_MAINTENANCE_DEFAULT['retry_after'], 'select', 0, 0, 2, 30, true, 'maintenance', 0);
    } else {
        if (DB_count($_TABLES['conf_values'], array('name', 'group_name'),
            array('tab_main', 'maintenance')) == 0) {
            $c->add('tab_main', NULL, 'tab', 0, 0, NULL, 0, true, 'maintenance', 0);
        }

        if (DB_count($_TABLES['conf_values'], array('name', 'group_name'),
            array('retry_after', 'maintenance')) == 0) {
            $c->add('retry_after', $_MAINTENANCE_DEFAULT['retry_after'], 'select', 0, 0, 2, 30, true, 'maintenance', 0);
        }
    }

    return true;
}
