# Contribution Guidelines for OS2Display

If you want to contribute to OS2Display, please follow these guidelines.

## Requesting a change

Bugs and feature requests are accepted from everyone and are processed by Product
Management and the Coordination Group. The issue tracker is public, and
creating an issue only requires a free GitHub account. Before submitting,
please first check whether it already exists in the repository
issue list:

- Existing issues: https://github.com/os2display/display-api-service/issues
- Create a new issue: https://github.com/os2display/display-api-service/issues/new/choose

## Security Issues
Do not report security vulnerabilities in public issues.

- Follow the private reporting process in SECURITY.md.

## Before you start coding

1. Before you start coding, read `GOVERNANCE.md` to understand how decisions
	are made and which governance groups approve scope and priorities.
2. For early-stage ideas or larger changes, ALWAYS contact Product Management
	  at os2display@os2.eu, or create a GitHub issue to discuss your idea and
	  align direction before implementation. Advice and guidance from Product
	  Management are free and can save you time and rework. Contributions risk
	  rejection if they are not aligned with product policies.
3. For architecture-impacting or governance-relevant changes, document the
	decision in `docs/adr` and align the proposal early with the governance
	groups.

## Getting started with development

For local environment setup, use these sections in `README.md`:

- [Prerequisites](README.md#prerequisites)
- [Development setup](README.md#development-setup)
- [Reverse proxy & local HTTPS](README.md#reverse-proxy--local-https)
- [Frontend dev server](README.md#frontend-dev-server)
- [Database (MariaDB)](README.md#database-mariadb)
- [Taskfile](README.md#taskfile)


## Code Standards and Quality
To keep the codebase clean and maintainable:

- Follow coding standards and workflow expectations in `README.md` and project
	documentation.
- Run coding standards locally before opening a pull request.
- Ensure CI passes on your branch.

Run these commands before creating a pull request:

- `task coding-standards:check`
- `task code-analysis`
- `task test:api`
- `task test:unit`

If your changes affect frontend behavior, also run one of:

- `task test:frontend-local`
- `task test:frontend-built`

## Forking the Repository
If you do not have direct write access, fork the repository and clone your fork
locally before making changes.

## Making Changes
Create a branch with a descriptive name, for example:

- `feature/some-new-feature` for new features
- `issue/some-issue` for issue fixes

After implementing changes:

- Keep commit messages short and descriptive.
- If relevant, reference the issue in your commit and/or pull request text
	(for example `Fixes #123`).
- Add or update tests to document and verify behavior.

If your change modifies API resources, DTOs, operations, or response shapes,
regenerate API artifacts:

- `task generate:api-spec`
- `task generate:redux-toolkit-api`

If new endpoints are added, update
`assets/shared/redux/enhanced-api.ts` for cache invalidation and hooks.

## Making a Pull Request
When your changes are ready:

- Push your branch to your fork.
- Open a pull request against the repository's default branch.

Pull requests are reviewed before merge. You may receive feedback that must be
addressed before approval.

## Release and Governance Review
All pull requests are reviewed for product security implications,
functionality, quality, and alignment with project governance. For contributions to the plugin-like zone contributers pay for the review. Review, merge,
and release are coordinated with Product Management.



