# Architecture / Architektur

Andreas Holzer @WebCrew (GitHub) · Copyright © 2026 Andreas Holzer.

The default request enters root index.php (or install.php), checks active server protection, sets the asset prefix and then enters public/index.php. `app/bootstrap.php` configures sessions, response headers and autoloading. `App` selects the route, loads language/settings/current user, validates POST requests and renders a view. `Db` wraps native PDO prepared statements. `Installer` owns first-time MySQL setup. There is no build step or runtime package dependency.

## Directory map

| Directory | Purpose |
|---|---|
| root index.php / install.php / .htaccess | Classical upload entry, automatic installer and HTTP access allowlist |
| `public/` | Shared internal entry and public/assets; root-upload requests expose only those assets |
| `app/` | Request handling, database, installer, extension registry |
| `resources/views/` | PHP templates; all user text is escaped with `Friends\e()` |
| `resources/lang/` | Matching English/German dictionaries |
| `database/` | Base schema, additive feature schema and repeatable schema-2-to-3 upgrade |
| `plugins/` | Trusted, locally installed PHP plugins and API manifests |
| `themes/` | Bundled palettes and uploaded CSS theme packages; shared core templates |
| `storage/` | Private generated configuration and installation locks; not committed |
| `tests/` | Isolated permission, persistence and render tests |
| `bin/` | Local server and optional preview fixture creation |

## Extension API 1

Create `plugins/my-plugin/manifest.json` with `name`, `version`, `api: 1`, `author` and optionally translated `description`. A local `plugin.php` returns a function accepting `Friends\Extensions` and `Friends\App`. The administration discovers valid API-1 manifests, and an enabled plugin is booted on the next request.

Events currently available: `feed.before` (render slot), `user.created` (new user ID), `post.created` (new post ID). `plugins/welcome/` is the executable example. Plugins run trusted PHP with application privileges. Administrator ZIP upload installs disabled after password/trust confirmation and archive validation; enabling uploaded PHP requires renewed trust. There is no code sandbox. Plugin output must escape its own dynamic text. See [package format](PACKAGES.md).

Bundled palettes live in `public/assets/tokens.css`. Uploaded CSS themes are served through the active-theme route and share the core view layer; executable templates and external resource loading are not accepted.

## Security and data rules

- POST-only mutations with CSRF tokens; sign-in rotates the session ID.
- PDO prepared statements, server-side ownership/admin checks, escaped HTML output.
- A private post is visible to its author only through normal feed/interaction routes. Administrators can view reported content through moderation; there is no end-to-end encryption.
- Groups and pages are member-visible, not private. Pages allow owner publishing only; groups require membership to publish.
- Messages require an accepted friendship. The authenticated server can read stored messages.
- The live community voice room is separate from private messaging: up to six active members explicitly opt in. Room-session tokens are hashed, signaling is limited to current joined sessions, media is peer-to-peer/relay WebRTC and not stored by PHP. See `CHAT.md`.
- Suspension, installation binding and a server-side authentication version are checked on every request. Each configuration path has a separate session cookie name. Password changes revoke other sessions; suspension invalidates prior session versions. Idle timeout: 30 minutes; absolute session lifetime: 12 hours. Chat polling counts as activity.
- Shared database rate limits on login/registration and writes; HMAC buckets avoid raw IP/email in the throttle table.
- InnoDB foreign keys cascade post/user relations. Destructive post deletion is permanent in this version.
- No third-party CDN, telemetry, tracking or license activation. Live audio contacts only configured ICE services and joined room peers. Explicit link previews additionally fetch vetted public pages server-side; mail uses the administrator-configured host.

## Deliberate limits

This is a compact initial application, not a micro-framework to maintain forever. Split `App` into domain controllers when complexity warrants it. Current member/community lists and message windows are bounded, not completely paginated. Comments are loaded in a batch but still need individual paging for large threads. Add indexes and query benchmarks from observed load. The versioned schema-2-to-3 core update is additive and repeatable; plugin-specific migrations and automatic package replacement remain unsupported.

Deutsch: Die Anwendung ist absichtlich klein und direkt nachvollziehbar. Die Trennung von öffentlichem Webverzeichnis und privaten Dateien ist Teil des Sicherheitsmodells. Erweiterungen sind vertrauenswürdiger PHP-Code und keine isolierten Mini-Apps. Neue Funktionen müssen dieselben serverseitigen Rechteprüfungen nutzen.

## Version 0.3 modules

`Features` handles profiles, attachments, previews, static pages and administration mutations; `Admin` supplies section-specific view data. `Media` owns private uploads/authorized reads, image re-encoding and per-account quota locks. `Links` validates URLs, pins vetted public IPv4 addresses and extracts textual metadata without following redirects. `Mailer` owns private SMTP settings, verified TLS delivery and consent-aware queues. `Packages` validates archives and stages disabled extensions in HTTP-protected private paths. `Upgrade` applies repeatable additive DDL.
