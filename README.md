# Geeklog Maintenance Plugin

Maintenance 1.1.1 provides a small, standalone maintenance mode for Geeklog.

## Requirements

- Designed for PHP 5.6 and later
- Geeklog 2.1.1 or later, reviewed through Geeklog 2.2.2

## How it works

Geeklog loads active plugin `functions.inc` files near the end of `lib-common.php`,
after session and permission initialization and before the requested script. The
plugin uses this common loading point to stop requests before page rendering.

When enabled, blocked requests receive `503 Service Unavailable`, anti-cache
headers and a configurable `Retry-After` value. The public standalone template
contains no login form or login link. GET access to `users.php` and the normal
admin login page remains blocked.

Authorized operators can use the separate URL
`admin/plugins/maintenance/login.php`. On Geeklog 2.2.x, this page reuses the
native Geeklog authentication template and login-form plugin hooks, including
Captcha/recaptcha integrations supplied through the standard `loginform` hooks.
On older supported Geeklog releases it falls back to the plugin's standalone
login template while still delegating credential verification to Geeklog.

Only this page and a login POST with the expected fields are allowed while the
site is blocked. Users with `maintenance.admin` bypass maintenance mode.

The login URL is intentionally absent from the public maintenance page, but it
must not be treated as a secret authentication factor. Security still depends on
Geeklog account credentials, login throttling and the `maintenance.admin`
permission.

The configured maintenance message is plain text and is HTML-escaped before
rendering.

## Installation and configuration

Install through Geeklog's Plugin Administration page. Installation creates the
permission, `Maintenance Mode Admin` group, Configuration API values, and an
optional warning block assigned to all topics. Configure it in Command & Control
> Configuration > Maintenance. The mode is disabled by default.

Configuration includes:

- maintenance mode on/off;
- the plain-text visitor message;
- the `Retry-After` delay returned with HTTP 503 responses.

The plugin also adds a Maintenance entry to Command and Control. Its small
administration page shows the current state, documents the request policy and
contains a POST button opening the native Geeklog configuration screen. Deploy
`admin/index.php` and `admin/login.php` under
`public_html/admin/plugins/maintenance/`, following the standard Geeklog plugin
layout. The page displays the dedicated login URL and can send it to the email
address of the currently authenticated Geeklog account. This action is protected
by Geeklog's CSRF token.

Block installation is idempotent, resolves database IDs dynamically, and uses
schema detection for installations where `blocks.tid` exists. During an upgrade,
existing Maintenance blocks have their executable name/type/function fields
repaired while their enabled state, placement and permissions are preserved.

## Upgrade from 1.0.0 or 1.1.0

Replace the files and use Geeklog's normal plugin update action. The upgrade hook
updates metadata, adds the `Retry-After` setting when missing, and creates or
repairs the block and topic assignment. Uninstall/reinstall is not required.

## Version 1.1.1 changes

- Uses Geeklog's native authentication template and `loginform` hooks on Geeklog
  2.2.x, improving compatibility with Captcha/recaptcha integrations.
- Adds configurable `Retry-After` values.
- Adds `Cache-Control`, `Pragma` and `X-Robots-Tag` headers to blocked responses.
- Repairs executable fields of an existing Maintenance warning block on upgrade.
- Emits valid HTML language codes instead of Geeklog language filenames.
- Hardens direct-access checks for contexts where `PHP_SELF` is unavailable.
- Corrects French administration wording.

## Uninstall

Geeklog removes configuration, feature, group, and PHP block. The plugin first
removes the block's topic assignments because supported Geeklog versions do not
cascade them when deleting PHP blocks.

## Limitation

The check covers endpoints that load `lib-common.php`, including normal core,
plugin, static page, search and feed requests. A standalone endpoint that never
loads `lib-common.php` needs separate web-server or application protection.

The dedicated Maintenance login endpoint supports Geeklog's standard username
and password authentication. Sites configured exclusively for external or remote
authentication should keep an authorized standard administrator account available
for maintenance operations or provide separate server-level access controls.

License: GNU GPL version 2 or later. See `LICENSE`.
