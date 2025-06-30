# ADR 009 - Remove remote components

Date: 30-06-2025

## Status

Proposed

## Context

The library for loading remote components we use for importing the templates is abandoned.

This makes it harder to keep the React applications up-to-date. Furthermore, remote-components have added
an unfortunate layer that hides javascript errors from the templates. This makes it harder to debug template issues.

## Decision

We remove the option of loading external templates into the system (remote components) and
replacing it with templates that are a part of the code.

## Consequences

Removing remote components will remove the option of importing templates from other locations. Therefore, the templates
need to be a part of the repository code.
