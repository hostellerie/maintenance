<?php

// Standalone authorized login page. This URL is intentionally not linked from
// the public maintenance response or from Command and Control.
require_once dirname(__FILE__) . '/../../../lib-common.php';

if (!maintenance_is_enabled() || maintenance_user_can_bypass()) {
    header('Location: ' . $_CONF['site_admin_url'] . '/index.php');
    exit;
}

require_once $_CONF['path_system'] . 'lib-template.php';
$template = new Template($_CONF['path'] . 'plugins/maintenance/templates/');
$template->set_file('page', 'login.thtml');
$template->set_var('language', maintenance_escape(isset($_CONF['language']) ? $_CONF['language'] : 'en'));
$template->set_var('login_action', maintenance_escape(rtrim($_CONF['site_admin_url'], '/') . '/index.php'));
$template->set_var('login_summary', maintenance_escape($LANG_MAINTENANCE['login_summary']));
$template->set_var('login_name', maintenance_escape($LANG_MAINTENANCE['login_name']));
$template->set_var('login_password', maintenance_escape($LANG_MAINTENANCE['login_password']));
$template->set_var('login_submit', maintenance_escape($LANG_MAINTENANCE['login_submit']));
$template->parse('output', 'page');

header('X-Robots-Tag: noindex, nofollow, noarchive');
header('Cache-Control: no-store, private');
echo $template->finish($template->get_var('output'));
