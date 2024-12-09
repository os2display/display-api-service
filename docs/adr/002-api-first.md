# ADR 002 - API first

Date: 26-11-2024

## Status

Accepted

Written years after the decision was made.

## Context

The "API first" approach is to enforce that all interactions with the system must go through the API.
See more about the "API first" approach [here](https://swagger.io/resources/articles/adopting-an-api-first-approach/).

The previous version of OS2Display was used without the admin module in some contexts.
We want to support other uses than the standard OS2Display setup.

By adopting the API first approach it will be possible to replace or write other clients without rewriting the entire
application. E.g. an external system could create content through calls to the API.
This will make the system more future-proof.

[OpenAPI](https://www.openapis.org/) is a standard for describing an API interface.

## Decision

We will use an API first approach where the only way to get and manage content is through calls to the API.
The API specification will be included [with the project](../../public/api-spec-v2.json) and kept up to date.
We will to develop the clients (admin and screen) separately from the API project to enforce the "API first" approach.

## Consequences

The main consequence is that all interactions with data in the system should be implemented in the API.
This can in some cases be more work, but will give the benefit that the interaction can be used in new contexts later
on.

By supplying an OpenAPI specification clients will be able to auto-generate code for interacting with the API.
This will make it easier to write clients for the system.

By adopting the "API first" approach the API specification will be the contract between the API and clients.
This will limit the extensibility of the project, since the client and API need to be aligned on the interface
between them (the API specification).
