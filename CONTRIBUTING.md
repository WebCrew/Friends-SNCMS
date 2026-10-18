# Contributing to Friends-SNCMS

Project lead: **Andreas Holzer · [@WebCrew](https://github.com/WebCrew)**.
Copyright © 2026 Andreas Holzer. Contributions are distributed under the existing MIT license.

[Deutsche Fassung](docs/CONTRIBUTING.de.md) · [Architecture](docs/ARCHITECTURE.md) · [Governance](GOVERNANCE.md) · [Security](SECURITY.md)

These are the project's acceptance rules, not a promise that every proposal will be accepted. They apply to humans and AI-assisted contributions. The project is an early development release; existing limitations are documented in `docs/TODO.md` and `docs/VALIDATION.md`.

## 1. Agree the purpose before implementation

Report reproducible bugs through the bug template. Propose new features, dependencies, database changes, public extension APIs and architectural changes in an issue first; wait for explicit maintainer agreement before implementing them. A discussion, reaction or open issue is not approval. Small corrections to documentation and translations may go directly to a PR.

Keep the core focused: a small self-hosted community CMS with text, emoji and audio communication. Large optional features should normally be isolated extensions. The project lead may decline features that increase maintenance without serving the agreed scope.

## 2. One purpose per pull request

Work in a fork and a topic branch based on the current default branch. Submit one independently reviewable change. Do not mix bug fixes with unrelated formatting, renaming, dependency upgrades or speculative refactors. Prefer small diffs; split large work into an agreed sequence. Open a draft early for complex work. Keep the description current when scope changes.

Explain the problem, resulting behavior, linked issue, validation and limitations. Include before/after screenshots for visible changes. Screenshots and logs must contain fictional data and no credentials or private user content.

## 3. Preserve the technical contract

- Target PHP 8.4 and MySQL 5.7. Do not introduce PHP-8.5-only or MySQL-8-only constructs. Keep InnoDB/utf8mb4 and referential integrity.
- Keep the runtime dependency-free unless a maintainer explicitly approves an exception with its purpose, alternatives, license, security/update implications and version constraints.
- No required paid API, subscription or proprietary provider account. Every core feature must have a documented free/open-source self-hosting path. Hosting/bandwidth may cost money; disclose infrastructure requirements honestly. Optional commercial services must not become compulsory through a later change.
- Use plain PHP/PDO, local CSS and small JavaScript modules. No framework rewrite, build toolchain, CDN, telemetry, license server or mandatory hosted service by stealth.
- In the default root-upload layout, only the two entry points and public assets are HTTP-accessible; preserve the server allowlist and fail-closed entry guard. Keep configuration, uploads and runtime data private. Preserve the extension API unless a reviewed compatibility/migration plan says otherwise.
- Read `docs/ARCHITECTURE.md` before changing routing, authentication, storage, themes or plugins. Prefer extensions to core hooks added for a single unapproved feature.

## 4. Readable code is mandatory

Use strict types in new PHP files and explicit parameter/return types where practical. New PHP uses four-space indentation; JavaScript/CSS/JSON uses two. Use UTF-8, LF and the supplied `.editorconfig`. Choose concrete names and small focused functions. Explain non-obvious constraints, not every statement.

Avoid minification, clever one-liners, duplicated business rules, dead code, debug dumps and unrelated cleanup. A lower line count is not a quality metric. New query/permission logic belongs in the application layer, not templates. Existing compact legacy sections in this new baseline are not an excuse to make touched logic harder to read; reformat only the relevant section. Automated formatting enforcement has not yet been configured.

## 5. Security invariants cannot be relaxed

- Validate types, lengths and allowed values on the server. Parameterize SQL values; whitelist any dynamic identifiers. Escape dynamic output for its actual context.
- Check authentication, role, ownership, visibility and private-chat eligibility and voice-room session ownership on every relevant server endpoint. Hiding controls is not authorization.
- Mutations require the approved request method and CSRF protection. Preserve secure session handling, password hashing, session revocation and rate limits.
- Preserve upload size/quota/type checks and private media authorization, including range requests. Never trust an extension or client MIME alone.
- Audio capture requires a deliberate user action and browser consent. Release microphone tracks on stop, cancellation, errors and navigation. Never record the live room implicitly.
- Do not add arbitrary PHP/HTML execution, unsafe plugin upload, secrets in client code/logs, public runtime files or unauthorized external network calls.

Report exploitable vulnerabilities privately according to `SECURITY.md`, not as public reproduction instructions. A security-sensitive PR needs explicit maintainer security review, including negative permission tests.

## 6. Protect user data and existing installations

Never commit `storage/` contents, installation configuration, databases, credentials or real user media. Use synthetic fixtures. Do not run destructive tests against an existing installation.

Schema changes need both a clean-install test and an upgrade/recovery plan with backup and rollback limitations. MySQL DDL is not transactionally rolled back as a whole. The project does not yet have a general upgrade runner: its design must be agreed before shipping changes that require one. Never silently delete data, reset configuration or make the installer overwrite non-empty databases.

## 7. Maintain both languages and accessibility

Add or change English and German UI keys together. No new hard-coded user-facing control/error text; brand names and technical identifiers are exceptions. Never translate user content automatically without an approved feature.

Keep keyboard navigation, visible focus, labels, contrast, responsive behavior and both color themes/day-night modes working. Respect reduced-motion preferences. Do not rely on color alone to communicate state.

## 8. Supply evidence, not just a green screenshot

Run `bin/check.ps1` for the local baseline. Add meaningful regression tests for behavioral fixes and new features; do not merely assert the implementation's current output. Check success and refusal/error paths. Record the actual environment and commands/results, including tests you could not run.

SQL changes require real MySQL 5.7 validation; SQLite alone is not evidence of MySQL compatibility. Installer/storage changes require clean-install and failure-path checks. Audio changes require device/browser tests appropriate to the change; synthetic uploads and signaling checks do not prove a real cross-network call. UI changes require responsive/day-night/DE-EN checks. Documentation-only changes normally need link/content checks, not invented tests.

The local helper does not run the destructive HTTP fixture suite, install dependencies, configure GitHub CI or certify production readiness. See `docs/VALIDATION.md` for current test coverage.

## 9. Respect provenance and contributor credit

Contribute only material you have the right to distribute under the project's license. No proprietary reference code/assets or unexplained pasted libraries. Preserve existing copyright/license notices. Credit new contributors accurately; do not rewrite another author's work as exclusively the project lead's. New dependencies need their notices and approved provenance.

For substantial AI-assisted changes, disclose the assistance in the PR, read every changed line and take responsibility for behavior, provenance and tests. Private prompts or account details are not required. Generated code does not bypass review.

## 10. Review and merge

Address review comments with focused commits, resolve the actual issue and explain disagreement calmly. Do not mark a review resolved merely to clear a blocker. New substantive changes require renewed review. Only authorized maintainers merge; authors do not approve their own PRs. Code-owner approval and required checks will be enforced through repository settings once the GitHub repository exists.

For security, schema/migrations, authentication, extension APIs and dependency changes, the project lead's explicit approval is required. If independent review is unavailable for the lead's own change, it waits; do not describe self-review as independent approval. Emergency handling is defined in `GOVERNANCE.md`.

Follow the [Code of Conduct](CODE_OF_CONDUCT.md). Maintainers may reject or close contributions that violate these rules, lack evidence or fall outside scope, with a brief reason. No response-time guarantee is offered.


## Voluntary support / Freiwillige Unterstützung

Business operators and commercial theme/plugin publishers are especially encouraged to [support the project voluntarily](https://ko-fi.com/A0A66XI3A). No payment is required to use the MIT-licensed CMS, contribute, submit extensions or report issues. Donations never buy review or merge priority.

