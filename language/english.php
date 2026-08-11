<?php

$LANG_configsections['maintenance'] = array('label' => 'Maintenance', 'title' => 'Maintenance Plugin Configuration');
$LANG_confignames['maintenance'] = array(
    'enabled' => 'Enable maintenance mode',
    'message' => 'Plain-text maintenance message displayed to visitors'
);
$LANG_configsubgroups['maintenance'] = array('sg_0' => 'Main Settings');
$LANG_fs['maintenance'] = array('fs_01' => 'Maintenance Plugin Settings');
$LANG_tab['maintenance'] = array('tab_main' => 'Main Settings');
$LANG_configselects['maintenance'] = array(
    0 => array('True' => 1, 'False' => 0),
    1 => array('Enabled' => 1, 'Disabled' => 0)
);
$LANG_MAINTENANCE = array(
    'plugin_name'     => 'Maintenance',
    'page_title'      => 'Website Under Maintenance',
    'default_message' => 'The website is currently under maintenance. Please come back later.',
    'admin_notice'    => 'Maintenance mode is active. Authorized administrators can still use the site.',
    'login_summary'   => 'Authorized access',
    'login_name'      => 'Username',
    'login_password'  => 'Password',
    'login_submit'    => 'Sign in',
    'admin_title'     => 'Maintenance administration',
    'admin_intro'     => 'Maintenance mode blocks normal Geeklog requests before page rendering.',
    'status_enabled'  => 'Maintenance mode is currently enabled.',
    'status_disabled' => 'Maintenance mode is currently disabled.',
    'open_configuration' => 'Open configuration',
    'documentation'   => 'Operation',
    'documentation_text' => 'Authorized users bypass maintenance mode. Blocked requests receive HTTP 503 and Retry-After: 3600. The public maintenance page contains no login form. Operators use the separate Maintenance login URL, which delegates authentication to Geeklog.',
    'login_url_title' => 'Authorized login URL',
    'login_url_help' => 'Save this private operational URL before enabling maintenance mode. It is intentionally absent from the public maintenance page.',
    'login_url_bookmarked' => 'I have saved this URL as a bookmark.',
    'email_login_url' => 'Send this login URL to my account email address.',
    'email_submit' => 'Send the email',
    'email_subject' => '%s maintenance access URL',
    'email_body' => "Hello %s,\n\nHere is the authorized maintenance login URL for %s:\n%s\n\nKeep this URL private.",
    'email_sent' => 'The login URL was sent to your account email address.',
    'email_failed' => 'Geeklog could not send the email. Check the site mail configuration and logs.',
    'email_missing' => 'Your Geeklog account does not have an email address.',
    'email_checkbox_required' => 'Select the email option before submitting.',
    'email_token_error' => 'The security token has expired. Please try again.'
);
