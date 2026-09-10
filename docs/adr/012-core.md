# ADR 012 - Core governance boundary and contribution requirements

Date: 10-09-2026

## Status

Proposal

## Context

Historically, all code in `os2display-api-service` has been treated as core and
therefore subject to long-term shared maintenance by the OS2Display community.
In practice, this means every integrated feature is expected to remain secure,
stable, and generally usable across member municipalities.

That default has become difficult to sustain from a governance perspective.
Different parts of the codebase have different ownership and lifecycle needs,
but the project has lacked a formal boundary for where core obligations apply.

To address this, the architecture introduces explicit governance zones:

- A **Core Domain** with strict shared quality and maintenance obligations.
- **Plugin-like zones** (defined separately) with different lifecycle and
	support expectations.

This ADR defines the Core Domain boundary and its contribution requirements. It
must be read together with ADR 013, which defines the plugin-like zones.

## Decision

Core is defined as all code in `os2display-api-service`, except for the paths
explicitly designated as plugin-like zones in ADR 013.

Core scope also includes the hosting and deployment baseline maintained in
`os2display-docker-server`:

- https://github.com/os2display/os2display-docker-server

Any contribution to core must satisfy the following acceptance criteria:

- **Workflow:** Follow the workflow outlined in CONTRIBUTING.md.
- **Security:** Changes must follow current security requirements and avoid
	introducing vulnerabilities.
- **Code standards:** Project coding standards and quality gates must pass.
- **Code review:** Changes must be reviewed before merge.
- **Reusability:** Features must be designed for broad use across the
	OS2Display community and not optimized solely for one local case.
- **Third-party integrations by exception:** New third-party integrations are
	not core by default and require explicit justification and governance
	approval.

## Consequences

- Governance obligations become explicit: core changes carry shared,
	long-term maintenance responsibility.
- New functionality can be placed in plugin-like zones when it does not meet
	core-wide reuse/support expectations.
- Reviewers can evaluate scope early: whether a change belongs in core or in a
	plugin-like zone.






