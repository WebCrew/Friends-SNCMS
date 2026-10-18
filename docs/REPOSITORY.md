# Repository operations

Repository: https://github.com/WebCrew/Friends-SNCMS

Owner and initial code owner: Andreas Holzer, @WebCrew. Public source and MIT license; Issues, Discussions and collaborator-edited Wiki are enabled. The first owner-authorized publication is 1.0.0 First Final.

## Community entry points

- Issues have Bug, Change proposal and Documentation templates with matching labels; blank issues are disabled. All start with needs-triage.
- Support questions go to Q&A, early ideas to Ideas. Announcements are maintainer-only.
- Plugins and Themes have separate Discussion categories and structured publisher forms. One entry per original package. See [catalog rules](EXTENSIONS.md).
- Exploitable vulnerabilities go through private Security Advisories. Never request credentials in public.
- Donations appear in README, SUPPORT.md, contributor documentation, community introductions and the GitHub funding button. They are voluntary, including for commercial operators and extension sellers.

## Merge and automation policy

Main requires pull requests, one approving review, code-owner review, resolved conversations and passing, up-to-date PHP 8.4/JavaScript and MySQL 5.7 checks. Stale approvals are dismissed; force pushes and branch deletion are blocked. Administrators are included. Only squash merges are enabled; merged branches are deleted. No auto-merge or automatic deployment.

Because @WebCrew is initially the only code owner, the owner's own future PRs need an additional trusted reviewer/code owner to satisfy independent approval. The owner retains settings administration but there is no standing rule bypass. Do not grant write access just to work around review requirements. The initial publication precedes enforcement and is documented in GOVERNANCE.md.

Actions use read-only credentials, do not approve PRs, and run only the allowed GitHub-owned action plus the pinned setup-php action. External contributor runs require maintainer approval. Never use pull_request_target to execute fork-controlled code with secrets. Workflows perform tests only, with disposable local databases and no production credentials.

Secret scanning, push protection, dependency alerts and private vulnerability reporting are enabled. This does not replace review or guarantee that every type of secret/defect will be detected.

## Documentation and releases

Canonical technical instructions/policies live in Git-tracked files. Wiki pages link to those files. A catalog listing or Wiki edit cannot change core policy. Maintain Wiki editing for collaborators only.

Releases distribute a clean upload-ready ZIP and SHA-256 checksum. Preserve private storage/configuration when upgrading. Never build a public package from a configured installation or include the local test-account credential file. Local packaging excludes .git and runtime files.

English and German are supported for community reports. Main contributor rules: [CONTRIBUTING](../CONTRIBUTING.md), [Deutsch](CONTRIBUTING.de.md).
