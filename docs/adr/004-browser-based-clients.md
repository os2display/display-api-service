# ADR 004 - Browser based clients

Date: 26-11-2024

## Status

Accepted

## Context

Creating a system for managing and displaying content on different machines requires some consideration about how the
content will be displayed on different machines. Creating an application directed at a specific operating system will
limit the application options.

An alternative is to implement the system with a common technology. Web pages can be displayed in multiple contexts.
By creating browser based clients we can make the system easier to adopt.

## Decision

We will write clients as web pages.

## Consequences

By being contained in a browser of the client machine we are limited in what we can do and know in the client machine.
The browser sets sandboxing constraints on the application when running in a browser.
