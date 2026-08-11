<?php

// +---------------------------------------------------------------------------+
// | Maintenance Plugin 1.1.0                                                  |
// +---------------------------------------------------------------------------+

if (isset($_SERVER['PHP_SELF']) &&
    strpos(strtolower($_SERVER['PHP_SELF']), 'functions.inc') !== false) {
    die('This file cannot be used on its own.');
}

global $_CONF, $_MAINTENANCE_CONF;

$maintenancePluginPath = $_CONF['path'] . 'plugins/maintenance/';
$maintenanceLanguage = isset($_CONF['language']) ? $_CONF['language'] : 'english';
$maintenanceLanguageFile = $maintenancePluginPath . 'language/' . $maintenanceLanguage . '.php';

if (file_exists($maintenanceLanguageFile)) {
    require_once $maintenanceLanguageFile;
} else {
    require_once $maintenancePluginPath . 'language/english.php';
}

/**
 * Return the current plugin configuration.
 *
 * @param bool $refresh Reload the values from Geeklog's Configuration API
 * @return array
 */
function maintenance_get_config($refresh = false)
{
    global $_CONF, $_MAINTENANCE_CONF;

    if ($refresh || !isset($_MAINTENANCE_CONF) || !is_array($_MAINTENANCE_CONF)) {
        require_once $_CONF['path_system'] . 'classes/config.class.php';
        $config = config::get_instance();
        $_MAINTENANCE_CONF = $config->get_config('maintenance');
    }

    return is_array($_MAINTENANCE_CONF) ? $_MAINTENANCE_CONF : array();
}

/**
 * Return whether maintenance mode is enabled.
 *
 * @return bool
 */
function maintenance_is_enabled()
{
    $config = maintenance_get_config();

    return isset($config['enabled']) && (int) $config['enabled'] === 1;
}

/**
 * Authorized administrators bypass maintenance mode.
 *
 * @return bool
 */
function maintenance_user_can_bypass()
{
    return function_exists('SEC_hasRights') && SEC_hasRights('maintenance.admin');
}

/**
 * Return the normalized basename of the executing Geeklog script.
 *
 * @return string
 */
function maintenance_get_script_name()
{
    $script = '';

    if (!empty($_SERVER['SCRIPT_NAME'])) {
        $script = $_SERVER['SCRIPT_NAME'];
    } elseif (!empty($_SERVER['PHP_SELF'])) {
        $script = $_SERVER['PHP_SELF'];
    }

    return strtolower(basename(str_replace('\\', '/', $script)));
}

/**
 * Allow only a real login POST to Geeklog's configured admin entry point.
 *
 * @return bool
 */
function maintenance_is_admin_login_request()
{
    global $_CONF;

    if (!isset($_SERVER['REQUEST_METHOD']) ||
        strtoupper($_SERVER['REQUEST_METHOD']) !== 'POST' ||
        empty($_POST['loginname']) || empty($_POST['passwd'])) {
        return false;
    }

    $adminPath = parse_url($_CONF['site_admin_url'], PHP_URL_PATH);
    $scriptPath = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
    if ($adminPath === false || $adminPath === null || $scriptPath === '') {
        return false;
    }

    $expectedPath = rtrim(str_replace('\\', '/', $adminPath), '/') . '/index.php';
    $scriptPath = str_replace('\\', '/', $scriptPath);

    return $scriptPath === $expectedPath;
}

/**
 * Allow the standalone, unadvertised Maintenance login page.
 *
 * @return bool
 */
function maintenance_is_login_page_request()
{
    global $_CONF;

    $adminPath = parse_url($_CONF['site_admin_url'], PHP_URL_PATH);
    $scriptPath = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
    if ($adminPath === false || $adminPath === null || $scriptPath === '') {
        return false;
    }

    $expectedPath = rtrim(str_replace('\\', '/', $adminPath), '/')
        . '/plugins/maintenance/login.php';

    return str_replace('\\', '/', $scriptPath) === $expectedPath;
}

/**
 * Keep only Geeklog's authentication endpoint available to blocked visitors.
 *
 * @return bool
 */
function maintenance_request_is_allowed()
{
    if (!maintenance_is_enabled() || maintenance_user_can_bypass()) {
        return true;
    }

    return maintenance_is_login_page_request() || maintenance_is_admin_login_request();
}

/**
 * Escape configured values because the maintenance message is plain text.
 *
 * @param string $value
 * @return string
 */
function maintenance_escape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * Render the standalone maintenance page.
 *
 * @return string
 */
function maintenance_render_page()
{
    global $_CONF, $LANG_MAINTENANCE;

    $config = maintenance_get_config();
    $message = isset($config['message']) ? trim($config['message']) : '';
    if ($message === '') {
        $message = $LANG_MAINTENANCE['default_message'];
    }

    require_once $_CONF['path_system'] . 'lib-template.php';
    $template = new Template($_CONF['path'] . 'plugins/maintenance/templates/');
    $template->set_file('page', 'maintenance.thtml');
    $template->set_var('language', maintenance_escape(isset($_CONF['language']) ? $_CONF['language'] : 'en'));
    $template->set_var('maintenance_title', maintenance_escape($LANG_MAINTENANCE['page_title']));
    $template->set_var('maintenance_message', maintenance_escape($message));
    $template->set_var('current_year', date('Y'));
    $template->set_var('site_name', maintenance_escape(isset($_CONF['site_name']) ? $_CONF['site_name'] : ''));
    $template->set_var('site_slogan', maintenance_escape(isset($_CONF['site_slogan']) ? $_CONF['site_slogan'] : ''));
    $template->parse('output', 'page');

    return $template->finish($template->get_var('output'));
}

/**
 * Send the maintenance response and stop the request.
 *
 * @return void
 */
function maintenance_send_response()
{
    if (!headers_sent()) {
        $protocol = !empty($_SERVER['SERVER_PROTOCOL'])
            ? $_SERVER['SERVER_PROTOCOL']
            : 'HTTP/1.1';
        header($protocol . ' 503 Service Unavailable', true, 503);
        header('Retry-After: 3600');
        header('Content-Type: text/html; charset=UTF-8');
    } else {
        COM_errorLog('Maintenance Plugin: unable to send HTTP 503 because headers were already sent.');
    }

    echo maintenance_render_page();
    exit;
}

/**
 * The legacy PHP block is retained only as an administrator warning banner.
 * Access control runs when this functions.inc file is loaded by lib-common.php.
 *
 * @return string
 */
function phpblock_maintenance_check()
{
    global $LANG_MAINTENANCE;

    if (!maintenance_is_enabled() || !maintenance_user_can_bypass()) {
        return '';
    }

    $notice = isset($LANG_MAINTENANCE['admin_notice'])
        ? $LANG_MAINTENANCE['admin_notice']
        : 'Maintenance mode is active.';

    return '<div style="background-color:#c00;color:#fff;text-align:center;'
        . 'padding:10px;font-weight:bold;font-size:14px;">'
        . maintenance_escape($notice) . '</div>';
}

/**
 * Add Maintenance to Command and Control.
 *
 * @return array
 */
function plugin_getadminoption_maintenance()
{
    global $_CONF, $LANG_MAINTENANCE;

    if (!maintenance_user_can_bypass()) {
        return array();
    }

    return array(
        $LANG_MAINTENANCE['plugin_name'],
        $_CONF['site_admin_url'] . '/plugins/maintenance/index.php',
        ''
    );
}

/**
 * Check plugin compatibility with Geeklog 2.1.1 or later.
 *
 * @param string $pi_name
 * @return bool
 */
function plugin_compatible_with_this_version_maintenance($pi_name)
{
    if (!defined('VERSION')) {
        return false;
    }

    if (function_exists('COM_versionCompare')) {
        return COM_versionCompare(VERSION, '2.1.1', '>=');
    }

    return version_compare(VERSION, '2.1.1', '>=');
}

/**
 * Return the version supplied by the plugin files.
 *
 * @return string
 */
function plugin_getversion_maintenance()
{
    return '1.1.0';
}

/**
 * Geeklog's standard code-version hook.
 *
 * @return string
 */
function plugin_chkVersion_maintenance()
{
    return '1.1.0';
}

/**
 * Upgrade Maintenance to 1.1.0 without requiring reinstallation.
 *
 * @return bool
 */
function plugin_upgrade_maintenance()
{
    global $_CONF, $_TABLES;

    require_once $_CONF['path'] . 'plugins/maintenance/autoinstall.php';
    $installedVersion = DB_getItem($_TABLES['plugins'], 'pi_version',
        "pi_name = 'maintenance'");

    if (version_compare($installedVersion, '1.1.0', '<')) {
        require_once $_CONF['path'] . 'plugins/maintenance/install_defaults.php';
        if (!plugin_initconfig_maintenance() ||
            !maintenance_install_config_permission() ||
            !maintenance_install_block()) {
            return false;
        }
        DB_query("UPDATE {$_TABLES['plugins']} SET pi_version = '1.1.0', "
            . "pi_gl_version = '2.1.1' WHERE pi_name = 'maintenance'", 1);
        if (DB_error()) {
            COM_errorLog('Maintenance Plugin: failed to update plugin metadata.');
            return false;
        }
    }

    return true;
}

/**
 * Uninstall metadata plus explicit cleanup for the block topic assignment.
 *
 * @return array
 */
function plugin_autouninstall_maintenance()
{
    global $_TABLES;

    $result = DB_query("SELECT bid FROM {$_TABLES['blocks']} "
        . "WHERE type = 'phpblock' AND phpblockfn = 'phpblock_maintenance_check'", 1);
    if (!DB_error()) {
        while ($row = DB_fetchArray($result)) {
            DB_delete($_TABLES['topic_assignments'], array('type', 'id'),
                array('block', $row['bid']));
        }
    }

    return array(
        'tables'     => array(),
        'groups'     => array('Maintenance Mode Admin'),
        'features'   => array(
            'maintenance.admin',
            'config.maintenance.tab_main'
        ),
        'php_blocks' => array('phpblock_maintenance_check'),
        'vars'       => array()
    );
}

// Every normal Geeklog request loads active plugin functions here, before the
// requested script starts producing page output. Authentication remains open.
if (!defined('GL_INSTALL_ACTIVE') && !maintenance_request_is_allowed()) {
    maintenance_send_response();
}
