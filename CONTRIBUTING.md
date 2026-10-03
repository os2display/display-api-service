# Contribution Guidelines for OS2Display

If you want to contribute to OS2Display, please follow these guidelines.

## Requesting a change

Bugs and feature requests are accepted from everyone and are processed by Product
Management and the Coordination Group. The issue tracker is public, and
creating an issue only requires a free GitHub account. Before submitting,
please first check whether it already exists in the repository
issue list:

- Existing issues: <https://github.com/os2display/display-api-service/issues>
- Create a new issue: <https://github.com/os2display/display-api-service/issues/new/choose>

## Security Issues

Do not report security vulnerabilities in public issues.

- Follow the private reporting process in SECURITY.md.

## Before you start coding

1. Before you start coding, read `GOVERNANCE.md` to understand project scope and priorities and how decisions are made.
2. For early-stage ideas or larger changes, ALWAYS contact Product Management
   at <os2display@os2.eu>, or create a GitHub issue to discuss your idea and
   align direction before implementation. Advice and guidance from Product
   Management are free and can save you time and rework. **Contributions risk
   rejection if they are not aligned with product policies.**
3. All contributions are subject to code review. Unless agreed otherwise, the
   contributer (Development Provider) is responsible for the review costs, as
   described in `GOVERNANCE.md`.

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

Changes that add or modify behavior should include automated tests for the
expected behavior. Run the relevant test suite before opening a pull request,
and ensure all required CI checks pass. Automated tests complement, but do not
replace, functional testing on the `develop` branch.

## Forking the Repository

If you do not have direct write access, fork the repository and clone your fork
locally before making changes.

## Making Changes

Create a branch with a descriptive name, for example:

- `feature/issue-456-some-new-feature` for new features
- `fix/issue-123-minor-problem` for issue fixes

After implementing changes:

- Keep commit messages short and descriptive.
- If relevant, reference the issue in your commit and/or pull request text
 (for example `Fixes #123`).
- Add or update tests to document and verify behavior.

The repository's `README.md` and the ADRs in `docs/adr` describe the project's
architecture, technology stack, coding practices, and preferred implementation
patterns. Review the relevant documentation before making changes so your
contribution follows the established architecture.

For changes that affect architecture or governance, document the decision in an
ADR under `docs/adr` and discuss the proposal with the governance groups early.

## Making a Pull Request

Pull requests that are not linked to an issue are rejected.

The pull request title should include its type and the issue number, for example:

- `fix: A minor problem (#123)`

When your changes are ready:

- Push your branch to your fork.
- Open a pull request against the repository's default branch.

Pull requests are reviewed before merge. You may receive feedback that must be
addressed before approval.

## Code Review

All pull requests are reviewed for product security implications,
functionality, quality, and alignment with project governance. For contributions
to the plugin-like zone contributers pay for the review. 

## Functional Testing

After code review, changes are merged into the `develop` branch for functional
testing. The community does not maintain a shared test environment and relies
on providers to establish test environments for their customers. Contributors can
use the [functional test guide](docs/test-guide/test-guide.md) when carrying out
these tests. 

Before changes are merged into the `main` branch, the contributor is responsible
for ensuring they have been tested and have not caused unintended side effects
elsewhere in the system. Testing may be carried out by the contributor's
customers, or, for new features, by the community where possible.

## Release

The project aims to do monthly releases, where additions to main are tagged for 
a release and a new release image is built. The community may also arrange 
unscheduled releases to address security issues or major bug fixes. Any release 
requested outside the regular schedule must be funded by the requester.

## Documentation

Update the README when a change affects information that developers, operators,
or users rely on, such as setup and deployment, configuration, authentication,
API or content behavior, extension points, testing, or operational procedures.
Keep commands, examples, defaults, and limitations accurate.

Put detailed integration guidance in the relevant documentation under `docs/`,
upgrade instructions in `UPGRADE.md`, and architectural decisions in an ADR.
Update generated API documentation when the API contract changes, and link to
specialized documentation from the README where useful.

