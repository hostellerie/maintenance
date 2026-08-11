<?php
/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Maintenance Plugin 1.1.0                                                  |
// +---------------------------------------------------------------------------+
// | install_defaults.php                                                      |
// +---------------------------------------------------------------------------+

// Prevent this file from being accessed directly
if (strpos(strtolower($_SERVER['PHP_SELF']), 'install_defaults.php') !== false) {
    die('This file cannot be used on its own!');
}

/*
 * Maintenance default settings
 *
 * Initial Installation Defaults used when loading the online configuration
 * records. These settings are only used during the initial installation
 * and are not referenced anymore once the plugin is installed.
 */

// Initialize default values
global $_MAINTENANCE_DEFAULT;
$_MAINTENANCE_DEFAULT = array();
$_MAINTENANCE_DEFAULT['enabled'] = 0;  // Maintenance mode off by default
$_MAINTENANCE_DEFAULT['message'] = 'The website is currently under maintenance. Please come back later.';

/**
 * Initialize Maintenance plugin configuration
 * This function checks if the configuration group exists, and if not,
 * it creates the necessary configuration group and fields for the plugin.
 *
 * @return bool True if the configuration is initialized, false otherwise.
 */
function plugin_initconfig_maintenance()
{
    global $_MAINTENANCE_DEFAULT, $_TABLES;

    $c = config::get_instance();

    // Check if the 'maintenance' group exists
    if (!$c->group_exists('maintenance')) {

        // Create the main subgroup #0
        $c->add('sg_0', NULL, 'subgroup', 0, 0, NULL, 0, true, 'maintenance');

        // A named tab gives non-Root administrators a configurable permission.
        $c->add('tab_main', NULL, 'tab', 0, 0, NULL, 0, true, 'maintenance', 0);

        // Create the fieldset #1 within subgroup #0
        $c->add('fs_01', NULL, 'fieldset', 0, 0, NULL, 0, true, 'maintenance', 0);

        // Add configuration fields
        $c->add('enabled', $_MAINTENANCE_DEFAULT['enabled'], 'select', 0, 0, 0, 10, true, 'maintenance', 0);
        $c->add('message', $_MAINTENANCE_DEFAULT['message'], 'text', 0, 0, 0, 20, true, 'maintenance', 0);
    } elseif (DB_count($_TABLES['conf_values'], array('name', 'group_name'),
        array('tab_main', 'maintenance')) == 0) {
        // Upgrade from 1.0.0: existing settings already use tab id 0.
        $c->add('tab_main', NULL, 'tab', 0, 0, NULL, 0, true, 'maintenance', 0);
    }

    return true;
}
