# ADR 008 - Mono repository

Date: 30-06-2025

## Status

Proposed

## Context

With the current multi-repository setup, when a new feature is added to the system, it often depends on changes to multiple repositories, at the same time.
This split across the repositories complicates the development process.

Another part of this is dependency management and maintenance across multiple repositories. It is important that the code uses up-to-date
dependencies. At the moment, this is especially an issue in the admin, client and templates repositories that all
depend on React and other javascript libraries. By merging these repositories the process of updating the
dependencies will be handled once instead of 3 times.

## Decision

We gather the code in `os2display/display-api-service` repository. The `os2display/display-client`,
`os2display/display-admin-client` and `os2display/display-templates` repositories will be merged into
`os2display/display-api-service`.

The `os2display/display-api-service` repository will be renamed to `os2display/display`.

The `os2display/display-docs` repository will be kept separate to avoid coupling usage updates to releases.

Symfony routes and controllers will be added for `/client` and `/admin` to handle serving and configuring the screen and
admin clients. This will enable us to move configuration for the two clients to .env and expose directly in html through
twig templates.

The static build javascript files will be served directly by nginx from `/public`.

## Consequences

All the code will be gathered into one repository. This will result in a single point of entry for development.

Maintaining the javascript parts of the code will be unified. This will make the upgrading tasks easier.

By unifying the code, features can be gathered into one feature branch.
This will make it easier to track the changes.

Releases will only involve one tag, instead of multiple tags across repositories.
