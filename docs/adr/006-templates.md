# ADR 006 - Templates

Date: 27-11-2024

## Status

Pending

Written years after the decision was made.

## Context

The display client should run in a browser. Slide templates should therefore be written in javascript/React.

We would like to open up the options for extending the system with custom templates.

We can use [remote-component](https://github.com/Paciolan/remote-component) to dynamically load react components into
a running React application.

## Decision

We will use `remote-component` to load the templates when rendering slides.

## Consequences

Templates should be built for use with `remote-component`.

We introduce a dependency on the `remote-component` library to the project.

Custom templates can be loaded into the system without being in the core code.
