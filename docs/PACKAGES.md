# Extension packages · API 1

Andreas Holzer · @WebCrew (GitHub) · Copyright © 2026 Andreas Holzer.

## ZIP layout

An administrator uploads a ZIP of at most 5 MB with `manifest.json` at its root, **without an enclosing package directory**. At most 100 entries and 5 MB expanded content; at most 1 MB per file. Paths use lowercase letters, digits, hyphens and underscores; allowed file extensions: `json`, `php`, `css`, `md`, `txt`. No hidden paths, absolute paths, traversal, symlinks, encrypted entries, duplicate names or extreme compression ratios. File/directory entries are checked before writing. Existing package identifiers cannot be overwritten.

Plugin manifest:

```json
{
  "id": "my-plugin",
  "type": "plugins",
  "name": "My plugin",
  "version": "1.0.0",
  "api": 1,
  "author": "Your name",
  "description": {"en": "Description", "de": "Beschreibung"}
}
```

The root also contains `plugin.php`:

```php
<?php
return static function (Friends\Extensions $extensions, Friends\App $app): void {
    $extensions->on('feed.before', static function () use ($app): void {
        echo '<p>' . Friends\e($app->t('welcome')) . '</p>';
    });
};
```

Available events: `feed.before`, `user.created` (user ID), `post.created` (post ID). No automatic package install scripts, SQL migrations, downloads or dependency installation are run. Plugins must validate permissions and escape output; the existing author/copyright/license remains attached to contributed code.

Theme manifest uses `"type": "themes"` and its own `id`, with root `style.css` instead of `plugin.php`. Themes share the core templates; this version supports CSS styling, not arbitrary PHP template replacement. PHP files, `url(...)`, `@import`, `@font-face`, executable markup and external resource requests are rejected in uploaded themes.

```css
:root { --accent: #a74617; --soft: #fcf0e7; }
:root[data-mode="dark"] { --accent: #f2ac77; --soft: #453025; }
```

Keep contrast, responsive layout, focus states and both appearance modes working.

## Trust and lifecycle

Only administrators may upload. Installation requires the current administrator password and an explicit trust acknowledgement. Every new package starts **disabled**. Enabling an uploaded PHP plugin again requires password and trust confirmation. Theme enablement and active-theme selection are separate; disabling the active theme restores the bundled default. The bundled default cannot be disabled.

ZIP validation is **not** source review, a malware scanner or a PHP sandbox. Enabled plugins execute with full CMS privileges and can read private configuration and database data. Review their entire source and provenance first. Disable a broken plugin through administration; if it prevents boot, restore the backed-up code or set its `plugin.ID` database setting to `0` through trusted server administration.

`plugins/` and `themes/` must be writable by the PHP user for browser installation and share a filesystem with private staging storage for atomic rename. They are blocked from direct HTTP by the root server rules (or outside DocumentRoot in public-only deployments). The web installer does not change global filesystem permissions. Grant only the intended PHP account write access, never world-writable permissions. Deployments that keep code read-only may install reviewed packages through deployment access instead.

Automatic overwrite, upgrade, uninstallation and plugin-specific database migrations are intentionally absent. Keep the previous package and data in backups before a maintainer-reviewed manual update. Do not upload full CMS archives or original third-party commercial packages: only original API-1 packages matching this format.

Deutsch: ZIP an der Wurzel mit Manifest und Einstiegspunkt; Plugins sind vertrauenswürdiger PHP-Code, Themes reine CSS-Pakete. Installation immer deaktiviert, Aktivierung separat. Keine automatische Ausführung von Installationsskripten und kein automatisches Überschreiben bestehender Pakete.
