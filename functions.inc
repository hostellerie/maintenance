<?php

// +---------------------------------------------------------------------------+
// | Maintenance Plugin 1.1.1                                                  |
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

function maintenance_is_enabled()
{
    $config = maintenance_get_config();

    return isset($config['enabled']) && (int) $config['enabled'] === 1;
}

function maintenance_user_can_bypass()
{
    return function_exists('SEC_hasRights') && SEC_hasRights('maintenance.admin');
}

/**
 * Return a valid BCP 47 language code for standalone HTML documents.
 *
 * @return string
 */
function maintenance_get_html_language()
{
    global $_CONF;

    $language = isset($_CONF['language']) ? strtolower((string) $_CONF['language']) : 'english';
    $normalized = str_replace('_', '-', $language);

    if (preg_match('/^[a-z]{2}(?:-[a-z]{2})?$/', $normalized)) {
        return $normalized;
    }

    $baseName = $normalized;
    $separator = strpos($normalized, '-');
    if ($separator !== false) {
        $baseName = substr($normalized, 0, $separator);
    }

    $map = array(
        'english'    => 'en',
        'french'     => 'fr',
        'german'     => 'de',
        'spanish'    => 'es',
        'italian'    => 'it',
        'dutch'      => 'nl',
        'portuguese' => 'pt',
        'danish'     => 'da',
        'norwegian'  => 'no',
        'swedish'    => 'sv',
        'finnish'    => 'fi',
        'japanese'   => 'ja'
    );

    return isset($map[$baseName]) ? $map[$baseName] : 'en';
}

function maintenance_get_retry_after()
{
    $config = maintenance_get_config();
    $retryAfter = isset($config['retry_after']) ? (int) $config['retry_after'] : 3600;

    if ($retryAfter < 60 || $retryAfter > 86400) {
        $retryAfter = 3600;
    }

    return $retryAfter;
}

/**
 * Create the signature used by the standalone login anti-bot guard.
 *
 * This is deliberately stateless so it remains compatible with Geeklog's
 * session implementation across the supported versions. It is not an
 * authentication token; Geeklog still performs the actual authentication.
 *
 * @param int $started Unix timestamp when the form was rendered
 * @return string
 */
function maintenance_create_login_guard($started)
{
    global $_CONF;

    $secret = (isset($_CONF['path']) ? $_CONF['path'] : '')
        . '|' . (isset($_CONF['path_data']) ? $_CONF['path_data'] : '')
        . '|' . (isset($_CONF['site_admin_url']) ? $_CONF['site_admin_url'] : '')
        . '|maintenance-login-guard';

    return hash_hmac('sha256', (string) (int) $started, hash('sha256', $secret));
}

/**
 * Validate the silent anti-bot fields from the standalone login form.
 *
 * A valid request must contain an empty honeypot, an untampered timestamp,
 * and must be submitted between 1 second and 30 minutes after rendering.
 *
 * @return bool
 */
function maintenance_login_guard_is_valid()
{
    if (!isset($_POST['maintenance_contact']) ||
        trim((string) $_POST['maintenance_contact']) !== '' ||
        empty($_POST['maintenance_started']) ||
        empty($_POST['maintenance_guard'])) {
        return false;
    }

    $started = (string) $_POST['maintenance_started'];
    if (!ctype_digit($started)) {
        return false;
    }

    $started = (int) $started;
    $elapsed = time() - $started;
    if ($started < 1 || $elapsed < 1 || $elapsed > 1800) {
        return false;
    }

    $expected = maintenance_create_login_guard($started);
    $provided = (string) $_POST['maintenance_guard'];

    if (function_exists('hash_equals')) {
        return hash_equals($expected, $provided);
    }

    return $expected === $provided;
}

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
 * Allow only a genuine submission from the standalone Maintenance login form.
 *
 * @return bool
 */
function maintenance_is_admin_login_request()
{
    global $_CONF;

    if (!isset($_SERVER['REQUEST_METHOD']) ||
        strtoupper($_SERVER['REQUEST_METHOD']) !== 'POST' ||
        empty($_POST['loginname']) || empty($_POST['passwd']) ||
        !maintenance_login_guard_is_valid()) {
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

function maintenance_request_is_allowed()
{
    if (!maintenance_is_enabled() || maintenance_user_can_bypass()) {
        return true;
    }

    return maintenance_is_login_page_request() || maintenance_is_admin_login_request();
}

function maintenance_escape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

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
    $template->set_var('language', maintenance_escape(maintenance_get_html_language()));
    $template->set_var('maintenance_title', maintenance_escape($LANG_MAINTENANCE['page_title']));
    $template->set_var('maintenance_message', maintenance_escape($message));
    $template->set_var('current_year', date('Y'));
    $template->set_var('site_name', maintenance_escape(isset($_CONF['site_name']) ? $_CONF['site_name'] : ''));
    $template->set_var('site_slogan', maintenance_escape(isset($_CONF['site_slogan']) ? $_CONF['site_slogan'] : ''));
    $template->parse('output', 'page');

    return $template->finish($template->get_var('output'));
}

function maintenance_send_response()
{
    if (!headers_sent()) {
        $protocol = !empty($_SERVER['SERVER_PROTOCOL'])
            ? $_SERVER['SERVER_PROTOCOL']
            : 'HTTP/1.1';
        header($protocol . ' 503 Service Unavailable', true, 503);
        header('Retry-After: ' . maintenance_get_retry_after());
        header('Content-Type: text/html; charset=UTF-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('X-Robots-Tag: noindex, nofollow, noarchive');
    } else {
        COM_errorLog('Maintenance Plugin: unable to send HTTP 503 because headers were already sent.');
    }

    echo maintenance_render_page();
    exit;
}

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
 * Return the icon used by Geeklog's Command and Control screen.
 *
 * @return string
 */
function plugin_geticon_maintenance()
{
    global $_CONF;

    return $_CONF['site_admin_url'] . '/plugins/maintenance/images/maintenance.svg';
}

/**
 * Add Maintenance to Geeklog's Command and Control screen (/admin/index.php).
 *
 * @return array
 */
function plugin_cclabel_maintenance()
{
    global $_CONF, $LANG_MAINTENANCE;

    if (!maintenance_user_can_bypass()) {
        return array();
    }

    return array(
        $LANG_MAINTENANCE['plugin_name'],
        $_CONF['site_admin_url'] . '/plugins/maintenance/index.php',
        plugin_geticon_maintenance()
    );
}

/**
 * Add Maintenance to the plugin administration menu on admin pages.
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

function plugin_getversion_maintenance()
{
    return '1.1.1';
}

function plugin_chkVersion_maintenance()
{
    return '1.1.1';
}

function plugin_upgrade_maintenance()
{
    global $_CONF, $_TABLES;

    require_once $_CONF['path'] . 'plugins/maintenance/autoinstall.php';
    $installedVersion = DB_getItem($_TABLES['plugins'], 'pi_version',
        "pi_name = 'maintenance'");

    if (version_compare($installedVersion, '1.1.1', '<')) {
        require_once $_CONF['path'] . 'plugins/maintenance/install_defaults.php';
        if (!plugin_initconfig_maintenance() ||
            !maintenance_install_config_permission() ||
            !maintenance_install_block()) {
            return false;
        }
        DB_query("UPDATE {$_TABLES['plugins']} SET pi_version = '1.1.1', "
            . "pi_gl_version = '2.1.1' WHERE pi_name = 'maintenance'", 1);
        if (DB_error()) {
            COM_errorLog('Maintenance Plugin: failed to update plugin metadata.');
            return false;
        }
    }

    return true;
}

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

if (!defined('GL_INSTALL_ACTIVE') && !maintenance_request_is_allowed()) {
    maintenance_send_response();
}
