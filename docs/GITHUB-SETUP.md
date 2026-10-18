# GitHub setup plan · not yet applied

Project lead: Andreas Holzer @WebCrew (GitHub).

Creation and publication remain conditional on Andreas's successful online installer/CMS test. No repository, branch rule, CI workflow, wiki or Discussion has been created by this document. Verify the account's available GitHub features and the final repository identity at setup time.

## Permanent product requirement

No mandatory paid API, subscription or proprietary provider account. Maintain documented free/open-source self-hosting paths and disclose hosting/traffic requirements. This upstream rule does not alter MIT license rights for independent forks.

## Repository and access

- Public source repository under the agreed owner, with an explicit development-release status and MIT license.
- Include reviewed source, docs, issue/PR templates, CODEOWNERS and editor settings. Exclude installation config, databases, runtime media, credentials and the proprietary reference archive.
- Accept third-party contributions via forks/PRs. Do not grant write access automatically. Keep administration and releases with the owner; appoint additional maintainers individually with minimal necessary access.
- Give the description and README clear Andreas Holzer / @WebCrew attribution and a restrained Guided-CMS reference. Add external business/project links only when their real addresses are available.

## Protect the default branch

Configure a supported ruleset/branch protection with:

1. Pull requests required; no routine direct pushes, force pushes or branch deletion.
2. At least one independent approving maintainer review and code-owner review.
3. Dismiss stale approvals after material changes; require approval of the latest reviewable push and resolution of review conversations.
4. Require the actual quality checks once their GitHub jobs have been created, run and verified. Do not invent check names or make unrun checks look green.
5. Require an up-to-date branch or a supported merge-queue process; choose one after validating CI behavior.
6. Squash merges for a clear main history; disable routine automatic merging.
7. No broad bypass grant. Document the owner's narrow security-emergency procedure from GOVERNANCE.md and review any actual bypass afterwards.

CODEOWNERS initially names only @WebCrew. Before requiring independent approval for the owner's own PRs, appoint another trusted maintainer/code owner. An owner cannot review-approve their own PR. Do not claim this is solved by the CODEOWNERS file alone.

## CI and automation

Start with read-only workflow permissions and no production deployment. Pin third-party actions to reviewed commit SHAs; keep dependencies explicit. Run untrusted fork code without repository secrets or write credentials. Do not execute fork-controlled code in a privileged pull_request_target job. Require approval for workflows from new external contributors where available.

Implement and verify PHP syntax, JavaScript syntax, isolated integration checks and a real MySQL-5.7 test job. Keep HTTP fixture tests isolated and label physical audio/device checks as manual acceptance. A green local helper is not a substitute for these jobs. No GitHub workflow is included yet because repository setup and real-MySQL acceptance are still pending.

Enable supported dependency/security alerts, secret scanning/push protection where available and private vulnerability reporting. Establish a verified private reporting/contact route before announcing community support. Do not add untrusted marketplace bots or automatic package publishing just to improve a community-health score.

## Community and wiki

- Enable Issues and the supplied bug/proposal templates; disable blank public issues through the supplied chooser configuration.
- Enable Discussions with Announcements (maintainer posts), Support/Q&A, Ideas and General. Explain that actionable bugs go to Issues and vulnerabilities to private reporting.
- Enable Wiki with collaborator-only editing. Publish an index for installation, hosting/HTTPS/audio, administration, extensions, architecture and contribution rules.
- Keep canonical policy and technical instructions in reviewed repository files. The wiki links to them; do not maintain conflicting copies or let unrestricted wiki edits redefine policy.
- Add the code of conduct, governance and contribution guide to the community entry points. Create only labels that will actually be used.

## Release handoff

Before publishing a stable release, record the real online installer result, supported environment, MySQL-5.7 results and applicable audio acceptance tests. Publish an explicitly labelled development snapshot while these are incomplete. Attach a clean source archive, changelog, checksums and upgrade limitations. Do not treat GitHub distribution as PHP hosting for the running CMS.

Official references: [Code owners](https://docs.github.com/en/repositories/managing-your-repositorys-settings-and-features/customizing-your-repository/about-code-owners), [Protected branches](https://docs.github.com/en/repositories/configuring-branches-and-merges-in-your-repository/managing-protected-branches/about-protected-branches), [Issue/PR templates](https://docs.github.com/en/communities/using-templates-to-encourage-useful-issues-and-pull-requests/about-issue-and-pull-request-templates).

Copyright © 2026 Andreas Holzer @WebCrew (GitHub).
