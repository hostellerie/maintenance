<?php

// +---------------------------------------------------------------------------+
// | Maintenance Plugin 1.1.1 - administration                                |
// +---------------------------------------------------------------------------+

require_once dirname(__FILE__) . '/../../../lib-common.php';
require_once dirname(__FILE__) . '/../../auth.inc.php';

if (!SEC_hasRights('maintenance.admin')) {
    COM_accessLog('User tried to access Maintenance administration without permission.');
    $content = COM_showMessageText($MESSAGE[29], $MESSAGE[30]);
    $display = COM_createHTMLDocument($content, array('pagetitle' => $MESSAGE[30]));
    COM_output($display);
    exit;
}

$configUrl = $_CONF['site_admin_url'] . '/configuration.php';
$loginUrl = $_CONF['site_admin_url'] . '/plugins/maintenance/login.php';
$mailMessage = '';

if (isset($_POST['send_login_url'])) {
    if (!SEC_checkToken()) {
        $mailMessage = COM_showMessageText(
            $LANG_MAINTENANCE['email_token_error'],
            $LANG_MAINTENANCE['admin_title']
        );
    } elseif (empty($_POST['email_login_url'])) {
        $mailMessage = COM_showMessageText(
            $LANG_MAINTENANCE['email_checkbox_required'],
            $LANG_MAINTENANCE['admin_title']
        );
    } elseif (empty($_USER['email'])) {
        $mailMessage = COM_showMessageText(
            $LANG_MAINTENANCE['email_missing'],
            $LANG_MAINTENANCE['admin_title']
        );
    } else {
        $mailSubject = sprintf(
            $LANG_MAINTENANCE['email_subject'],
            $_CONF['site_name']
        );
        $mailBody = sprintf(
            $LANG_MAINTENANCE['email_body'],
            $_USER['username'],
            $_CONF['site_name'],
            $loginUrl
        );
        $mailSent = COM_mail(
            $_USER['email'],
            $mailSubject,
            $mailBody,
            $_CONF['site_mail']
        );
        $mailMessage = COM_showMessageText(
            $mailSent
                ? $LANG_MAINTENANCE['email_sent']
                : $LANG_MAINTENANCE['email_failed'],
            $LANG_MAINTENANCE['admin_title']
        );
    }
}

$mailToken = SEC_createToken();
$statusClass = maintenance_is_enabled() ? 'maintenance-status-enabled' : 'maintenance-status-disabled';
$statusText = maintenance_is_enabled()
    ? $LANG_MAINTENANCE['status_enabled']
    : $LANG_MAINTENANCE['status_disabled'];

$content = COM_startBlock(
    $LANG_MAINTENANCE['admin_title'],
    '',
    COM_getBlockTemplate('_admin_block', 'header')
);
$content .= '<style>'
    . '.maintenance-admin{max-width:760px}'
    . '.maintenance-admin h2{margin-top:1.5em}'
    . '.maintenance-status-enabled{background:#fee;border-left:5px solid #c00;padding:12px}'
    . '.maintenance-status-disabled{background:#eef8ee;border-left:5px solid #287d28;padding:12px}'
    . '.maintenance-actions{margin-top:18px}'
    . '.maintenance-config-button{cursor:pointer;padding:8px 14px}'
    . '.maintenance-login-url{background:#f5f5f5;border:1px solid #ddd;display:block;overflow-wrap:anywhere;padding:10px}'
    . '.maintenance-bookmark{display:block;margin-top:12px}'
    . '</style>';
$content .= '<section class="maintenance-admin">';
$content .= $mailMessage;
$content .= '<p>' . htmlspecialchars($LANG_MAINTENANCE['admin_intro'], ENT_QUOTES, 'UTF-8') . '</p>';
$content .= '<p class="' . $statusClass . '">'
    . htmlspecialchars($statusText, ENT_QUOTES, 'UTF-8') . '</p>';
$content .= '<div class="maintenance-actions"><form class="maintenance-config-form" method="post" action="'
    . htmlspecialchars($configUrl, ENT_QUOTES, 'UTF-8') . '">'
    . '<input type="hidden" name="conf_group" value="maintenance">'
    . '<button type="submit" class="maintenance-config-button">'
    . htmlspecialchars($LANG_MAINTENANCE['open_configuration'], ENT_QUOTES, 'UTF-8')
    . '</button></form></div>';
$content .= '<h2>' . htmlspecialchars($LANG_MAINTENANCE['documentation'], ENT_QUOTES, 'UTF-8') . '</h2>';
$content .= '<p>' . htmlspecialchars($LANG_MAINTENANCE['documentation_text'], ENT_QUOTES, 'UTF-8') . '</p>';
$content .= '<h2>' . htmlspecialchars($LANG_MAINTENANCE['login_url_title'], ENT_QUOTES, 'UTF-8') . '</h2>';
$content .= '<p>' . htmlspecialchars($LANG_MAINTENANCE['login_url_help'], ENT_QUOTES, 'UTF-8') . '</p>';
$content .= '<a class="maintenance-login-url" href="'
    . htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8') . '">'
    . htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8') . '</a>';
$content .= '<form class="maintenance-bookmark" method="post" action="">'
    . '<label><input type="checkbox" name="email_login_url" value="1"> '
    . htmlspecialchars($LANG_MAINTENANCE['email_login_url'], ENT_QUOTES, 'UTF-8') . '</label>'
    . '<input type="hidden" name="' . CSRF_TOKEN . '" value="'
    . htmlspecialchars($mailToken, ENT_QUOTES, 'UTF-8') . '">'
    . '<div><button type="submit" name="send_login_url" value="1" class="maintenance-config-button">'
    . htmlspecialchars($LANG_MAINTENANCE['email_submit'], ENT_QUOTES, 'UTF-8')
    . '</button></div></form>';
$content .= '</section>';
$content .= COM_endBlock(COM_getBlockTemplate('_admin_block', 'footer'));

$display = COM_createHTMLDocument($content, array(
    'pagetitle' => $LANG_MAINTENANCE['admin_title']
));
COM_output($display);
