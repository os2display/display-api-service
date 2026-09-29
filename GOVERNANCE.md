# Governance

## Governance Roles

OS2Display follows the OS2 governance model with a focus on transparency,
shared ownership, quality, and reuse.

The product is governed by two working groups:

- Steering Group (Styregruppe)
- Coordination Group (Koordinationsgruppe)

## How We Work

Governance work is based on openness, documented decisions, and shared
responsibility in the community.

All contributions are made under the Mozilla Public License Version 2.0

### Steering Group (Styregruppe)

The Steering Group has the overall strategic and managerial responsibility.
It sets direction and priorities, approves major decisions, and helps ensure
the product has the required resources and budget.

Main responsibilities:

- Oversee and support strategic and economic planning
- Ensure common direction and alignment across participating organizations.
- Ensure compliance with OS2 governance requirements, including
 follow-or-explain when a requirement is not met.
- Ensure key stakeholders are involved and heard.
- Support long-term sustainability and connection to operations.

### Coordination Group (Koordinationsgruppe)

The Coordination Group are responsible for decisions regarding dayly operation and delivery management.
It translates needs into actionable priorities and works closely with
providers and contributors.

Main responsibilities:

- Oversee and support daily operation.
- Collect, coordinate, and prioritize development and maintenance requests.
- Ensure the product remains stable, secure, and continuously improved.
- Facilitate and support community collaboration.
- Defining requirements and acceptance criteria for feature requests and new functionality.

### Product Management

Responsibilities:

- Manage dayly operation and delivery pipeline
- Facilitate collaboration and communication in the community.
- Single point of contact for providers.
- Can be contacted at <os2display@os2.eu>.

## Provider Roles

OS2Display has multiple contributors and providers, and organizations can also
self-host the platform. To support these different models, we define three
provider roles, each with distinct rights, responsibilities, and funding
arrangements.

Three three provider er roles are:

**Operations Provider**: Hosts OS2Display installations for public-sector
organizations or is a public-sector self-hosting organization.
**Development Provider**: Contributes code to OS2Display. This may include both
features and bug fixes.
**Maintenance Provider**: Performs core maintenance of the product core source
code, the build code and technical documentation.

### Operations Provider

Hosts OS2Display installations for public-sector organizations or is a public-sector self-hosting organization
Solely funded by their customers.

Responsibilities:

- Keep up to date with new product versions and update customer environments.
- Configure environment settings that must be adapted to local conditions.
- Enable templates relevant to the customer.
- Configure customer sites in relation to SSO.
- Help with theme customization on templates.
- Help customers create issues in the issue tracker.
- Provide first-level support for use of the product.
- Optionally establish a test site for customers.
- Optionally provide courses/training for customers onboarding OS2Display.

### Development Provider

Contributes code to OS2Display. This may include both features and bug fixes.
This applies to both the OS2Display core and the plugin-like zones where templates and data sources reside.

Responsibilities:

- Carries out work as agreed with Product Management.
- Follow the workflow described in CONTRIBUTING.md.
- Comply with delivery quality requirements described in CONTRIBUTING.md.

Funding:

- Funding may come from the community, individual members, or a group of members
  through crowdfunding.
- Product Management can support project initiation and help coordinate
  crowdfunding among community members.
- When the community initiates development, the work is carried out on a
  time-and-materials basis, based on a requirements specification.

Note: All code contributions are subject to review. Unless otherwise agreed, the
Development Provider is responsible for the review costs. Funding arrangements
must be in place before development begins.

### Maintenance Provider

Maintains the product's core codebase, build system, and technical
documentation. The community funds this work through a long-term agreement.

Responsibilities funded by the community:

- Release management.
- Advisory support to Product Management.
- Monitor and report security risks in issues.
- Monitor and report license-related issues.
- Monitor and report required changes to dependencies.
- Perform corrective maintenance of core on request from Product Management.
- Maintain technical documentation related to the core product.
- Maintain repository automation and required CI checks, and merge approved
  contributions in accordance with the agreed workflow.

Responsibilities funded by development providers:

- Code review of code from development providers.
- Advisory support to development providers.

### Git Maintainer

The Git Maintainer oversees OS2Display's Git repositories, including contributor
access, repository settings, and the workflows for reviewing, merging, and
releasing changes.
Its responsibilities are shared between Product Management and the Maintenance
Provider as follows.

Product Management is responsible for:

- Coordinating repository access and branch-protection requirements.
- Keeping contribution guidance, templates, labels, and pull request workflows clear and up to date.
- Helping contributors navigate branching, reviews, merges, and release branches.
- Keeping the repository organized, including managing stale branches and clarifying issue and pull request ownership.
- Ensuring repository practices support traceability and comply with licensing and security requirements.

The Maintenance Provider is responsible for:

- Monitoring repository automation and maintaining required CI checks.
- Merging approved contributions and resolving related issues in line with the agreed workflow.
- Managing releases, including release tags and the repository changelog.

## Links to further reading

- Meeting minutes and decisions from the Coordination Group and Steering Group
 are publicly available at <https://github.com/os2display/os2display-produktforvaltning>.
- Documentation for product users is available at <https://os2display.os2.eu>.
- Documentation for developers and operations providers is available in
 CONTRIBUTING.md and README.md.
