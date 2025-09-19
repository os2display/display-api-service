# ADR 009 - Remove remote components

Date: 30-06-2025

## Status

Proposed

## Context

The [library](https://www.npmjs.com/package/@paciolan/remote-component) for loading remote components we use for
importing the templates is abandoned.

This makes it harder to keep the React applications up-to-date. Furthermore, remote-components have added
an unfortunate layer that hides javascript errors from the templates. This makes it harder to debug template issues.

## Decision

We remove the option of loading external templates into the system (remote components) and replace it with templates
that are a part of the code. The `os2display/display-templates` will be merged with the`os2display/display-api-service`
repository (See ADR-008).

## Consequences

Removing remote components will remove the option of importing templates from other locations. Therefore, the templates
need to be a part of the repository code.

To add your own templates you will have to fork the repository, add your templates and build your own clients.
