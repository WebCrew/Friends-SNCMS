# Security

Friends-SNCMS is an open-source project by Andreas Holzer @WebCrew (GitHub).

Please do not disclose credentials, private databases or exploitable vulnerabilities in public issues. Use [GitHub private vulnerability reporting](https://github.com/WebCrew/Friends-SNCMS/security/advisories/new). Do not use public Discussions or Issues for exploitable vulnerabilities.

In the default root-upload layout, the bundled server allowlist exposes only index.php, install.php and public assets. Internal directories and runtime files must be inaccessible through HTTP; the root entry checks the server protection marker before loading configuration. Never commit installation configurations or runtime data. Review plugin source before enabling it: plugins are trusted server-side code.

Version 1.0.0 has no independent security certification. Outstanding review items and compatibility tests are recorded in `docs/TODO.md` and `docs/VALIDATION.md`.

## Implemented protection

Prepared SQL, escaped output, POST/CSRF mutation checks, strict HttpOnly/SameSite sessions, session ID rotation, password hashes and authentication version revocation. Idle timeout: 30 minutes; absolute lifetime: 12 hours. Core login/write limits remain enabled even when the optional spam layer is disabled. Administrator access and post/chat/room/media ownership are checked server-side. Responses restrict framing, scripts, resource origins, microphone origin and camera access; HTTPS responses include HSTS.

The default spam layer adds honeypot/timed registration tickets, one-use tickets, 90-second duplicate suppression, link limits and stricter first-day account posting limits. Friendship invitations, audio uploads and voice-room actions have independent limits. Audio files use MIME/signature validation, private storage, opaque names and quotas. Room tokens are hashed, signal recipients must still be joined and capacity/expiry are enforced. Sessions are bound to the installation as well as the user's authentication version. These controls reduce common automated abuse; they are not an external CAPTCHA, antivirus scanner or full media transcoder.

Operators should protect hosting access, configure HTTPS and database backups, monitor storage/logs, use scoped TURN credentials and avoid trusting client-supplied proxy headers as identity. This project does not supply DDoS filtering, automatic content moderation or a finished data-retention policy.

Copyright © 2026 Andreas Holzer @WebCrew (GitHub).

## Version 0.3 additions

Feed/profile files stay in protected private storage, with opaque names and authenticated audience checks. Images are decoded and re-encoded through GD; PDF/TXT/ZIP documents are download-only, not a virus-scanned document sandbox. Per-file, daily and storage limits apply; per-account filesystem locks serialize quota checks through commit. Post failures clean up filesystem writes, and post deletion removes related files.

Link previews reject private/reserved IPs, pin vetted public IPv4 DNS results, enforce standard HTTP(S) ports and TLS verification, disable proxies/redirects and cap fetched HTML. No remote preview images or scripts are loaded. Non-fetchable pages get domain-only cards.

Static pages escape HTML. Public contact uses CSRF, one-use timed tickets and limits. Newsletter consent is explicit, is rechecked before delivery and can be withdrawn without sign-in through a token plus POST. SMTP uses TLS with verified certificates; credentials stay in a protected private file. Delivery errors are not automatically retried.

Extension ZIP checks reject traversal, symlinks, duplicates and oversized/compression-abusive entries. Installation is admin-only with password/trust confirmation, disabled by default and cannot overwrite existing packages. This does not sandbox PHP plugins or guarantee a package is benign. Uploaded CSS themes cannot contain PHP or external resource loading. Read [package trust rules](docs/PACKAGES.md).

Back up private storage and database before schema updates; MySQL DDL cannot be atomically rolled back. No external penetration test or MySQL-5.7 acceptance is claimed.

## Root hosting 0.3.1

Root index.php and install.php share the existing installer and installation lock. .htaccess restricts URL paths; unknown/internal files are denied rather than routed to PHP. A server rewrite marker (including CGI redirect prefixes) is checked before bootstrap. Client-supplied HTTP headers cannot substitute for it. /public/index.php also refuses to bypass root protection when public is not the actual document root. Local development uses a matching allowlist router; production requires real web-server rules.

