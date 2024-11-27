# ADR 007 - Configurability

Date: 27-11-2024

## Status

Accepted

Written years after the decision was made.

## Context

We know the system will be used in different contexts, from a simple setup with showing content on a couple of screens
to large setups with hundreds of screens connected.

Therefore, we want implementers to be able to configure the project to turn off features that will not be used.

Environment variables can be used to configure a Symfony application.

In the browser configuration can come from a file that is available to the client.

## Decision

We will make features configurable through environment and config files.

## Consequences

Features that are not "core" will have to be implemented as configurable wherever possible.
This will introduce extra work when implementing features.
