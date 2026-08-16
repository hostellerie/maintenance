<?php

// +---------------------------------------------------------------------------+
// | Maintenance Plugin 1.1.1 - authorized login                              |
// +---------------------------------------------------------------------------+

require_once dirname(__FILE__) . '/../../../lib-common.php';

if (!maintenance_is_enabled() || maintenance_user_can_bypass()) {
    header('Location: ' . $_CONF['site_admin_url'] . '/index.php');
    exit;
}

if (!headers_sent()) {
    header('X-Robots-Tag: noindex, nofollow, noarchive');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
}

$useNativeTemplate = function_exists('COM_newTemplate')
    && function_exists('CTL_core_templatePath')
    && defined('VERSION')
    && version_compare(VERSION, '2.2.0', '>=');

if ($useNativeTemplate) {
    $template = COM_newTemplate(CTL_core_templatePath($_CONF['path_layout'] . 'users'));
    $template->set_file(array('authenticationrequired' => 'authenticationrequired.thtml'));
    $template->set_var('site_admin_url', $_CONF['site_admin_url']);
    $template->set_var('lang_nonstandardlogin', '');
    $template->set_var('lang_username', $LANG20[4]);
    $template->set_var('lang_password', $LANG20[5]);
    $template->set_var('lang_warning', $LANG20[6]);
    $template->set_var('lang_login', $LANG20[8]);
    $template->set_var('value_login', $LANG20[7]);
    $template->set_var('xhtml', defined('XHTML') ? XHTML : '');

    if (empty($_CONF['user_login_method']['standard'])) {
        $template->set_var('lang_nonstandardlogin', $LANG_LOGIN[2]);
    } else {
        PLG_templateSetVars('loginform', $template);
    }

    $content = $template->finish($template->parse('output', 'authenticationrequired'));
    $display = COM_createHTMLDocument($content, array(
        'pagetitle' => $LANG_MAINTENANCE['login_summary']
    ));
    COM_output($display);
    exit;
}

require_once $_CONF['path_system'] . 'lib-template.php';
$template = new Template($_CONF['path'] . 'plugins/maintenance/templates/');
$template->set_file('page', 'login.thtml');
$template->set_var('language', maintenance_escape(maintenance_get_html_language()));
$template->set_var('login_action', maintenance_escape(rtrim($_CONF['site_admin_url'], '/') . '/index.php'));
$template->set_var('login_summary', maintenance_escape($LANG_MAINTENANCE['login_summary']));
$template->set_var('login_name', maintenance_escape($LANG_MAINTENANCE['login_name']));
$template->set_var('login_password', maintenance_escape($LANG_MAINTENANCE['login_password']));
$template->set_var('login_submit', maintenance_escape($LANG_MAINTENANCE['login_submit']));
$template->set_var('login_unavailable', '');
$template->set_var('captcha', '');
$template->set_var('invisible_recaptcha', '');
$template->set_var('recaptcha_v3', '');

if (empty($_CONF['user_login_method']['standard'])) {
    $template->set_var('login_unavailable', maintenance_escape($LANG_LOGIN[2]));
} else {
    PLG_templateSetVars('loginform', $template);
}

$template->parse('output', 'page');
echo $template->finish($template->get_var('output'));
