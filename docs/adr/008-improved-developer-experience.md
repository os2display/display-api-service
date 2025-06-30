# ADR 008 - Improved developer experience

Date: 30-06-2025

## Status

Proposed

## Context

When a new feature is added to the system, it often depends on changes to multiple repositories, at the same time.
This split across the repositories complicates the development process.

Another part of this is the maintenance of the repositories. It is important that the code uses up-to-date
dependencies. At the moment, this is especially an issue in the admin, client and templates repositories that all
depend on React and other javascript libraries. By merging these repositories the process of updating the
dependencies will be handled once instead of 3 times.

## Decision

We gather the different repositories of the OS2Display project into one repository (the API repository) and
rename it from os2display/display-api-service to os2display/display.

Symfony will handle the routes for the admin and client.

## Consequences

All the code will be gathered into one repository. This will result in a single point of entry for development.

Maintaining the javascript parts of the code will be unified. This will make the upgrading tasks easier.

By unifying the code, features can be gathered into one feature branch.
This will make it easier to track the changes.

Releases will only involve one tag, instead of multiple tags across repositories.
