# ADR 013 - Plugin-like zone governance and boundaries

Date: 10-09-2026

## Status

Proposal

## Context

ADR 012 defines core as the default governance scope in this repository.
To make that boundary operational, this ADR names the paths that are treated as
plugin-like zones.

This boundary is also intended to support flexibility and faster delivery for
community members with special adaptation needs.

## Decision

The following paths are designated as plugin-like zones:

- `src/Feed/`
- `assets/shared/templates/`
- `assets/admin/components/feed-sources/templates/`

Scope note:

- Files that only register or reference plugin-like functionality from generic
 core UI or infrastructure are not automatically plugin-like zones.

Because plugin-like zones are an administrative governance construct,
exceptions to the path definitions may be approved by the OS2Display
coordination group, subject to confirmation by the OS2Display steering group.

Lists of templates and feeds that are considered part of core is maintained in the
OS2display governace repository. 

- [core-templates]: https://github.com/os2display/os2display-produktforvaltning/blob/main/dokumenter/core-templates.md
- [core-feeds]: https://github.com/os2display/os2display-produktforvaltning/blob/main/dokumenter/core-feeds.md

The lists are subject to periodic reevaluation.

Only templates that are considered part of core are enabled by default when
installing via the community-maintained hosting and deployment package.

Any contribution to the plugin-like zones must satisfy the following acceptance criteria:

- **Workflow:** Follow the workflow outlined in CONTRIBUTING.md.
- **Security:** Security requirements remain mandatory, as for core.
- **Code standards:** Project coding standards and quality gates must pass.
- **Code review:** Changes must be reviewed before merge.
- **Third-party integrations:** Third-party integrations are allowed and
 encouraged.
- **Reusability:** Reusability is encouraged but may be local to one or a few
 municipalities.

To avoid breaking plugin-like feeds and templates, we commit to not change
their interfaces except in major versions and with a well-defined upgrade path.

- Feed contract:
	https://github.com/os2display/display-api-service/blob/develop/src/Feed/FeedTypeInterface.php
- Template contract:
	https://github.com/os2display/display-api-service/blob/release/3.0.0/assets/shared/slide-utils/templates.js#L28
  
Plugin-like functionality must be controllable through deployment
configuration, including the ability to disable the feature completely.

If plugin-like code is left unmaintained, it can be removed by the community to reduce burden on the shared codebase.

## Consequences

- The governance boundary from ADR 012 becomes operational through explicit,
 path-based scope.
- Contributors and reviewers can evaluate early whether a change belongs in
 core or in plugin-like zones, reducing governance ambiguity.
- Plugin-like features may evolve faster, including municipality-specific and
 third-party integrations, while still meeting shared security and quality
 requirements.
