# 1.0.0 — First Final

- First public release, authorized by the owner after online testing.
- Based on 0.3.7; schema 3 unchanged.
- Contribution rules, issue categories, extension directory and voluntary Ko-fi support.
- CI with PHP 8.4 and MySQL 5.7 smoke checks; see actual results and validation limits.

## 0.3.7

- Admin-defined positive file size and attachment count per post; PHP hosting limits displayed.
- Existing defaults remain 5 MB and four attachments; daily/storage quotas remain in effect.

# Changelog

Andreas Holzer · @WebCrew (GitHub) · Copyright © 2026 Andreas Holzer.

## 0.3.6 · Automatic link cards · 18 October 2026

- Detect the first valid HTTP(S) URL in post text and show a preview automatically; remove the separate Add Link control.
- Extract and persist the card on the server independently of JavaScript or preview timing; reuse short-lived server metadata and fall back to a domain card if lookup is unavailable or limited.
- Debounce requests, cancel stale browser results and clear the card when the URL is removed. Keep admin controls, SSRF protections and post privacy.
- Clarify the existing friend-request button in DE/EN.
- Shared browser/server extraction fixtures and publication/privacy HTTP regressions.

## 0.3.5 · Community heading spacing · 18 October 2026

- Keep page/group type labels and names below the cover image with 16px spacing, while the logo keeps its overlapping position. Applies at mobile and desktop widths.

## 0.3.4 · Emoji popover correction · 18 October 2026

- Reset inherited bottom positioning on the feed emoji grid so its background encloses both rows and the menu stays below its toolbar trigger.
- Version the refinement stylesheet URL so existing browsers load the corrected style after deployment.

## 0.3.3 · Interface refinements · 18 October 2026

- Dark-mode native audience selector and option colors; compact composer toolbar with emoji, upload and link controls.
- Desktop avatar rail, floating friend list and compact chat with minimize/restore/close, search, per-page drafts, session peer persistence, text, emojis and voice messages; existing full messenger retained.
- Separate page/group logos and covers with owner/admin permissions, protected delivery, rollback of incomplete replacements and cleanup of removed images.
- Guided voice-room setup: direct, optional public STUN, own TURN or validated advanced ICE list. DE/EN explanations, free coturn/eturnal installation links, costs and hosting requirements.
- No schema migration. Community image references use namespaced settings and the existing protected media store.

## 0.3.2 · Account email · 17 October 2026

- Password recovery by email: signed, single-use 30-minute links; successful reset revokes existing sessions.
- Mandatory email verification for new registrations: pending status, 24-hour links and resend option. Existing users remain active.
- Canonical configured HTTPS links, request limits, generic recovery/resend responses and scanner-safe confirmation forms; DE/EN interfaces and email bodies.
- No database migration. Configure email transport, sender and public HTTPS URL before allowing new registrations.
- Isolated account-token tests and full registration/verification/reset HTTP flow with local TLS SMTP capture.

## 0.3.1 · Classical webspace installation

Installer hotfix: distinguish MySQL authentication, permissions, unknown database, socket/network, DNS and TLS errors without exposing credentials. A hosted installation and first administrator login were completed successfully; details in docs/LIVE-INSTALLATION.md.

- One folder to extract and upload as a whole. Opening its URL starts the existing guided installer automatically; install.php is an additional direct entry.
- Bundled Apache-compatible rules restrict access to entry points and public assets. Root installation refuses to start without active server protection.
- Assets and form redirects work at the domain root and inside a subdirectory. No database schema change.
- Real Apache/CGI HTTP checks exercise installer entry, private-path denial and missing-protection refusal.

## 0.3.0-dev · 17 October 2026

- Feed emoji picker, image/document attachments, protected delivery, re-encoded images and safe server-side link cards.
- Profile About links, avatar/header upload and removal; avatars in feed, directory and navigation.
- Dedicated admin section navigation, member search/editing and protected role changes.
- Bilingual static/legal pages with drafts and a public footer.
- Host mail or verified TLS/STARTTLS SMTP, public contact form and explicit opt-in newsletter with manual delivery batches and unsubscribe.
- Admin ZIP plugin/CSS-theme installation, disabled by default; archive checks, password/trust confirmation and no package overwrites.
- New GD/cURL/ZIP/OpenSSL/DOM/Iconv requirements, upload controls/quotas and per-account serialized writes.
- Additive, repeatable schema-2-to-3 update after administrator backup confirmation. No direct 0.1 upgrade.
- Independent feature, HTTP upload/package and local TLS mail tests.

Development snapshot: real MySQL 5.7 acceptance, physical audio and independent security review remain open.

## 0.2.0-dev

Shared six-person voice room replaced mutual audio calls. Private recorded voice messages remained. Contributor governance, GitHub setup plan and installation-bound sessions added.

## 0.1.0-dev

Original PHP/PDO community foundation, DE/EN, installer, profiles/feed, friendships, groups/pages, chat, moderation, bundled extensions and Blue/Ember Orange day/night palettes.

