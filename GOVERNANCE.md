# Project governance

Friends-SNCMS is led by **Andreas Holzer · @WebCrew**. The project lead decides scope, core architecture, releases, branding and maintainer appointments. MIT licensing remains as stated in `LICENSE`; project governance does not restrict the license rights of independent forks.

The upstream project's core must remain usable without mandatory subscriptions, paid APIs or proprietary provider accounts. Preserve documented free/open-source self-hosting paths. Hosting and bandwidth requirements must be transparent. Optional commercial integrations must never silently become compulsory.

## Roles and access

- Visitors may read, discuss and report issues according to the contribution rules.
- Contributors submit changes through forks and pull requests. A contribution, sponsorship or high activity level does not confer write access.
- Reviewers may assess changes within their expertise. Only explicitly appointed maintainers can merge or manage releases.
- Repository administration, permissions and release publication remain with the project lead unless specifically delegated.

Grant the lowest necessary GitHub role. Do not share personal accounts, tokens or hosting credentials. Remove access when responsibility ends. Changes to ownership, permissions, security policy, license or automation credentials require the project lead's express agreement.

## Decisions and reviews

Keep technical decisions in linked issues or small architecture-decision records, including alternatives and consequences. Discussions are exploratory; an approved issue records the accepted scope. Avoid deciding critical policy only in chat or an editable wiki.

Normal merges require an approved PR, passing relevant checks, resolved review conversations and an independent review by an authorized maintainer. New material changes invalidate earlier approval. An author cannot approve their own PR. Sensitive work additionally requires the project lead's explicit agreement as described in `CONTRIBUTING.md`.

For the lead's own work, seek an independent reviewer and never represent self-review as independent approval. The project lead may explicitly authorize an owner-reviewed release while there is no second maintainer; document the exception and actual validation. This does not grant contributors bypass rights. The inaugural 1.0.0 is such an owner-authorized release.

## Narrow emergency exception

For a confirmed exploitable vulnerability or broken release, the project lead may authorize the smallest urgent fix without the normal review sequence if waiting creates a greater concrete risk. Record the reason, affected behavior, validation and any GitHub rule bypass privately while disclosure is sensitive; publish an appropriate summary afterwards. Obtain independent follow-up review as soon as a qualified reviewer is available. This is not a standing bypass for features, convenience or deadlines. Never falsify approvals or test results.

## Releases

Development snapshots must be labelled as such. Releases document PHP-8.4/MySQL-5.7, installer, security and real-device audio evidence, remaining validation gaps, a changelog and upgrade limitations. The project lead decides release readiness; the label never substitutes for tests. Only designated maintainers publish releases. Never include private configuration, runtime data or credentials.

## Wiki and community

Canonical policies, installation instructions and architecture live in reviewed repository files. Wiki pages link to those sources; substantial wiki documentation changes follow PR review before maintainers publish them. Restrict wiki editing to collaborators. Use Discussions for support, proposals and Q&A, Issues for actionable bugs/agreed work, and private reporting for vulnerabilities. Do not publish another person's private details or logs.

## Enforcement

Maintainers may request changes, close out-of-scope submissions, remove abusive content or revoke project access. Explain the applicable rule briefly when safe. Concerns about a decision may be raised once with the project lead, without harassment or repeated reopening. No contributor is entitled to a merge or appointment.

Deutsch: Andreas führt das Projekt. Beiträge sind willkommen; Schreibrechte gibt es gezielt. Normale Änderungen brauchen unabhängiges Review. Ein eng begrenzter Sicherheitsnotfall darf dokumentiert vorgezogen werden. Wiki und Community ersetzen keine geprüften Projektregeln.

Copyright © 2026 Andreas Holzer @WebCrew (GitHub).

