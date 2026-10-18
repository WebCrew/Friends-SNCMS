# Validation report · 17 October 2026

Andreas Holzer @WebCrew (GitHub) · Copyright © 2026 Andreas Holzer.

Version: **0.3.1-dev / schema 3**. Local environment: Windows, PHP **8.4.24**, Node **24.19.0**, Apache **2.4.69** with CGI for root-hosting acceptance. PDO SQLite/MySQL, Fileinfo, GD, cURL, ZIP and OpenSSL were enabled per process. No local MySQL server was available. A subsequent hosted installation, administrator login, feed/admin reads and access-protection checks succeeded; see [live installation](LIVE-INSTALLATION.md).

## Passed

- Nine database error classifications have matching DE/EN explanations. Private numeric diagnostics exclude connection/account/password values and are deleted after successful installation.

- **28 hosting policy checks**: entry/asset allowlist, refusal of internal/runtime paths, server-marker handling and rejection of client-header spoofing.
- **54 actual Apache/CGI hosting HTTP checks**: bare domain/subdirectory URLs automatically show the installer, install.php shares the flow, assets load, private directories are denied, server check leads to database setup, and disabled .htaccess protection causes a 503 refusal before installation. The 33 feature and 36 chat HTTP checks below were also rerun successfully through Apache with the new root entry.
- Local PHP development router: automatic installer and CSS succeed, direct schema request is denied. Browser: ordinary site URL opens the styled installer and Next leads to database setup.

- Syntax checks for all PHP sources and all application/chat/test JavaScript.
- **56 isolated core integration checks**: schema creation (21 tables), persistence, ownership/privacy denial, friendships/messages, notifications, groups/pages, moderation, plugins/themes, audit history, rendering and installation-bound sessions.
- **34 voice-room checks**: explicit join/leave, hashed session tokens, participant-only signaling, offer direction, ICE delivery and candidate field validation, six-person capacity, stale/rejoined sessions, suspension/authentication revocation, signal expiry and rejection of video/data offers. The old direct-call flow is removed.
- Spam: duplicate messages, link limits, account volume limits surviving IP changes, honeypot, timed/replayed tickets and admin toggle.
- Session revocation and idle expiry; matching DE/EN keys; ten authenticated views with author metadata and escaped content.
- **36 HTTP checks** on a separate disposable fixture: authentication/authorization, CSRF, cookie/security headers, room join/leave and signaling, credential isolation, removed direct-call endpoint rejection, emoji/text delivery, duplicate rejection, actual upload of generated Ogg/Opus silence, protected byte-range playback, denial to guests/outsiders, rejection of PHP disguised as audio, HTML escaping and actual password change with revocation of another session.
- **67 feature integration checks**: URL/private-IP validation, textual metadata parsing, profile links, static-page escaping/publication, admin permissions/editing, session revocation, explicit newsletter consent and withdrawal, UTF-8 MIME/folded subjects, private SMTP secret, image re-encoding, malicious ZIP traversal/symlink/CSS/PHP rejection, repeatable additive schema-2-to-3 update preserving accounts and all eleven admin sections.
- **33 feature HTTP checks** against a separate disposable project copy: actual image/file multipart upload, private audience/guest denial, forced downloads, complete post rollback including filesystem cleanup, profile avatar/header, feature toggles enforced server-side, preview fallback, public legal footer, contact form availability, newsletter preparation/withdrawal without sending, password/trust-protected plugin install/enable/disable, CSS theme install/activation/disable/fallback, overwrite denial and attachment revocation after post deletion.
- **Local SMTP transport test**: verified direct TLS and STARTTLS with authentication and MIME delivery, rejection of an untrusted certificate, exactly two synthetic deliveries to an isolated local server. No external email recipients or real mail service were used.
- Browser login/feed, actual emoji insertion, multiple file attachment/upload, HTTPS Example Domain title preview, avatar/header upload, About links, admin navigation/upload settings, DE/EN and day/night switching. Profile and admin at 390 px had no horizontal overflow. No browser console errors were captured. Screenshots accompany delivery.

## Outstanding acceptance

- Real MySQL **5.7** installation/runtime behavior and final installer failure/concurrency cases. InnoDB/utf8mb4/MySQL-5.7-compatible SQL is the design target, not a passed certification.
- Physical microphone recording and simultaneous multi-device voice-room audio across different networks with real STUN/TURN.
- Actual host/system-mail delivery, DNS/SPF/DKIM/DMARC setup and recipient mailbox deliverability. Local SMTP fixtures do not certify a particular mail provider.
- Full browser/device/accessibility coverage, load testing and independent penetration testing.
- Full original-feature parity is intentionally outside the final agreed scope; see `TODO.md`.

## Repeat

```sh
php -d extension=pdo_sqlite tests/integration.php
php -d extension=pdo_sqlite tests/voice-room.php
node --check public/assets/app.js
node --check public/assets/chat.js
node --check public/assets/voice-room.js
```

`tests/http.mjs` is restricted to localhost. Create a disposable fixture with `bin/seed-preview.php`, serve it with PDO SQLite/Fileinfo, set `FRIENDS_TEST_URL` (default `http://127.0.0.1:8090/`) and `FRIENDS_TEST_PASSWORD`, then run it. It creates sample text/audio, so never target a real community.

For feature checks, enable PDO SQLite, GD, Fileinfo and ZIP and run tests/features.php; bin/check.ps1 includes these checks on Windows. For HTTP uploads, generate a NEW directory with tests/upload-fixtures.php (GD/ZIP enabled), set FRIENDS_TEST_UPLOADS to that directory and optionally FRIENDS_TEST_STORAGE to the disposable private storage directory, then run tests/features-http.mjs against the localhost fixture. Do not run this suite twice against the same fixture: it intentionally leaves installed test package identifiers. Use a fresh project copy/fixture each time.

For local SMTP verification, run node tests/smtp-local.mjs with an available OpenSSL executable; optional FRIENDS_OPENSSL_EXE and FRIENDS_PHP_EXE select installed executables. It generates temporary test certificates, trusts them only for its own PHP subprocess, and removes its own temporary directory. It never disables certificate verification or changes system trust.

Development databases, credentials, certificates and uploaded test media are outside the delivered project and excluded from the distribution.

## 0.3.2 account mail

22 isolated account-token/state checks and 13 complete HTTP-flow checks passed, with two messages captured by a local TLS SMTP fixture. Registration remains pending until confirmation; password reset invalidates prior sessions. No external email recipient was contacted. Live anonymous checks: login, forgot, verify and reset returned HTTP 200 with expected controls/messages; AccountMail.php and private config returned HTTP 403. Live mail delivery remains dependent on the configured host transport and has not been tested by sending mail in this update.


## 0.3.3 interface refinements — 18 October 2026

52 PHP and 10 JavaScript files passed syntax checks. The baseline passed 240 PHP checks (56 core, 34 voice, 67 features, 28 hosting, 9 installer classifications, 22 account-email and 24 new refinement checks). 17 additional localhost HTTP checks verified protected group artwork, owner/admin editing, invalid upload rollback and guided voice configuration; all 36 existing chat/voice HTTP regressions passed. Reused disposable fixtures initially hit the normal login throttle; the regression was repeated after resetting throttles in that disposable SQLite fixture only. A test harness also needed complete response-body consumption with the local Node HTTP client.

Browser checks exercised dark/light native selector colors, the compact toolbar and emoji insertion, the contact list, text send, minimization and peer persistence after navigation, image upload through the actual group form, and voice help. At 390 CSS-pixel width, the page had no horizontal overflow and the compact window stayed inside the viewport. Screenshots use synthetic localhost data; no live messages, voice recordings, images or accounts were created for this update.

18 application files were staged and activated on the authorized test deployment, preserving configuration and content; the chat script received an additional recording-owner cleanup refinement. Live anonymous checks confirmed updated assets/login and private-path denial (403), and the installer remained locked (303). No schema change or external STUN/TURN activation was performed. Real device/cross-network microphone/relay acceptance remains a separate test.


After the user signed in to the reference site, its desktop contact-avatar rail and compact window were inspected through the browser. Friends now includes an original desktop avatar rail at widths of 1200px and above, a contact avatar in the window heading and the existing mobile friend-list fallback. Browser verification at 1280px confirmed direct contact opening, reserved page space and no horizontal overflow; at 390px the rail was hidden and the compact window stayed in the viewport. The final five changed files were also deployed to the authorized test site. The baseline was rerun successfully after these changes.


## 0.3.4 emoji popover fix

Browser verification used the actual open feed emoji menu: all 16 button rectangles were enclosed by the opaque panel at desktop width and at 390px; the page had no horizontal overflow. Dark and light mode backgrounds were checked with the menu open. Desktop uses eight columns; smaller widths adapt the number of columns. The inherited bottom anchor was reset and intrinsic grid overflow removed. Layout/admin PHP syntax passed; no PHP behavior or schema changed. Corrected stylesheet and versioned asset URL were deployed to the test site.


## 0.3.5 community heading spacing

Browser verification at desktop width measured 16px between cover edge and the group/page type label, while the logo retained its 35px overlap. At 390px, the label gap remained 16px with no horizontal overflow. Shared markup covers groups and pages. CSS-only behavior correction with a versioned stylesheet URL; no schema, permission, upload or voice settings changed.


## 0.3.6 automatic link cards

Baseline passed: 53 PHP files, 13 JavaScript files, 240 existing PHP checks, 21 automatic-link PHP checks and 12 JavaScript extraction cases. The isolated HTTP fixture passed 12 checks covering publishing, permissions, CSRF, changed URLs, disabled previews and private cards. In the browser, a URL typed into the post body fetched Net Twin metadata and publishing persisted the same card in the feed, with no separate Add Link control. No schema change. MySQL 5.7 compatibility was not independently exercised by this release's local SQLite suite.

Three fictional member accounts were separately created on the authorized live test installation at the user's request. All three logins were verified; pending friend requests were sent to the main admin account. Their credentials remain outside the source and release archive. The temporary maintenance entrypoint was restored byte-for-byte and the temporary script removed.

## 0.3.7 configurable upload limits

Full baseline passed (53 PHP / 13 JavaScript files). Additional checks verify 64 MB and 999 MB settings, a custom attachment count, rejection of zero limits and rejection of excess attachments before any post is created. Browser verification saved 64 MB and eight attachments in an isolated local fixture. Both values persisted after submission; PHP hosting limits were visible. These are example fixture settings, not changes to the live site's existing preferences. Runtime changes deployed to the authorized test installation; no schema change. Existing daily and 250 MB lifetime storage quotas remain. A real 64 MB HTTP transfer was not performed.

## 1.0.0 First Final — GitHub validation

The owner authorized the first Final publication after online installer/interface testing. The public GitHub Quality run at https://github.com/WebCrew/Friends-SNCMS/actions/runs/39083019745 passed both jobs: PHP 8.4 and JavaScript, and MySQL 5.7. The MySQL smoke job verified an actual 5.7 server, empty-database schema creation, active admin loading, 64 MB/eight-attachment settings, utf8mb4 emoji storage, private-post and member/admin access denial, accepted friendships, comment insertion, foreign-key cascades and atomic throttle upserts. This is a smoke suite, not complete coverage of every CMS action on MySQL.

Local final baseline passed: 54 PHP and 13 JavaScript files plus isolated integration checks. Source review found no copied live/test-account passwords or runtime configuration in the release tree. See REPOSITORY.md for configured community and protection policy. Physical microphone/cross-network and independent security-review gaps remain; Final does not imply certification.
