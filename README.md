# Geeklog Maintenance Plugin

Maintenance 1.1.0 provides a small, standalone maintenance mode for Geeklog.

## Requirements

- Designed for PHP 5.6 and later
- Geeklog 2.1.1 or later, reviewed through Geeklog 2.2.2

## How it works

Geeklog loads active plugin `functions.inc` files near the end of `lib-common.php`,
after session and permission initialization and before the requested script. The
plugin uses this common loading point to stop requests before page rendering.

When enabled, blocked requests receive `503 Service Unavailable` and
`Retry-After: 3600`. The public standalone template contains no login form or
login link. GET access to `users.php` and the normal admin login page remains
blocked. Authorized operators can use the separate, unadvertised URL
`admin/plugins/maintenance/login.php`; its standalone form posts to Geeklog's
configured administration entry point. Only this page and a login POST with the
expected fields are allowed. Users with `maintenance.admin` bypass the mode.

The configured message is plain text and is HTML-escaped before rendering.

## Installation and configuration

Install through Geeklog's Plugin Administration page. Installation creates the
permission, `Maintenance Mode Admin` group, Configuration API values, and an
optional warning block assigned to all topics. Configure it in Command & Control
> Configuration > Maintenance. The mode is disabled by default.

The plugin also adds a Maintenance entry to Command and Control. Its small
administration page shows the current state, documents the request policy and
contains a POST button opening the native Geeklog configuration screen. Deploy
`admin/index.php` and `admin/login.php` under
`public_html/admin/plugins/maintenance/`, following the standard Geeklog plugin
layout. The page displays the dedicated login URL and can send it to the email
address of the currently authenticated Geeklog account. This action is protected
by Geeklog's CSRF token. The URL should be communicated privately to authorized
operators.

Block installation is idempotent, resolves database IDs dynamically, and uses
schema detection for installations where `blocks.tid` exists.

## Upgrade from 1.0.0

Replace the files and use Geeklog's normal plugin update action. The upgrade
hook updates metadata and creates or repairs the block and topic assignment;
uninstall/reinstall is not required.

## Uninstall

Geeklog removes configuration, feature, group, and PHP block. The plugin first
removes the block's topic assignments because supported Geeklog versions do not
cascade them when deleting PHP blocks.

## Limitation

The check covers endpoints that load `lib-common.php`, including normal core,
plugin, static page, search and feed requests. A standalone endpoint that never
loads `lib-common.php` needs separate web-server or application protection.

License: GNU GPL version 2 or later. See `LICENSE`.
