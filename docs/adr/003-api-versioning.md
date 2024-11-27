# ADR 003 - API versioning

Date: 26-11-2024

## Status

Accepted

Written years after the decision was made.

## Context

By versioning the API we can communicate changes in the API. If an endpoint changes in a way that breaks backwards
compatibility we will change the route version. An example of a backwards compatibility break is changing field names.

E.g. changing the field "name" to "title" will be breaking backwards compatibility.
On the other hand, adding the "title" field without removing the "name" field and updating "name" when "title" is
changed will not be a breaking change.

## Decision

We will version our API routes when we break backwards compatibility.
We will aim at avoiding breaking backwards compatibility as much as possible.

## Consequences

By versioning the API we will communicate changes in the API in a clear way.
This will make it easier to maintain clients communicating with the API.
