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

require_once $_CONF['path_system'] . 'lib-template.php';
$template = new Template($_CONF['path'] . 'plugins/maintenance/templates/');
$template->set_file('page', 'login.thtml');

$started = time();
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
$template->set_var('login_started', (string) $started);
$template->set_var('login_guard', maintenance_escape(maintenance_create_login_guard($started)));

if (empty($_CONF['user_login_method']['standard'])) {
    $template->set_var('login_unavailable', maintenance_escape($LANG_LOGIN[2]));
} else {
    PLG_templateSetVars('loginform', $template);
}

$template->parse('output', 'page');
echo $template->finish($template->get_var('output'));
