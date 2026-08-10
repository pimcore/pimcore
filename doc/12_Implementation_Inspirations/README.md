---
title: Implementation Inspirations
description: Standalone articles demonstrating common implementation patterns and approaches with Pimcore.
---

# Implementation Inspirations

:::info
These articles are implementation inspirations, not step-by-step tutorials.
Some code examples and UI references may be outdated, but the underlying ideas
and architectural concepts remain valid. Always check the current API documentation
for up-to-date method signatures and UI paths.
:::

Pimcore is a standard Symfony application, so
[Symfony best practices](https://symfony.com/doc/current/best_practices.html) apply.

The following articles cover practical implementation patterns across different
Pimcore use cases. Each stands on its own and can be read independently:

- [**Implementing PIM**](./01_Implementing_PIM.md) -
  Building a product data model with variants, bundles, object bricks, event listeners,
  and workflows.
- [**Role & Rights for Frontends**](./02_Build_Role_Rights_for_Frontends.md) -
  Combining Pimcore data objects with Symfony Security to build portal permission systems.
- [**Custom REST API Endpoint**](./03_Custom_REST_API_Endpoint.md) -
  Exposing Pimcore data through custom Symfony controller actions.
- [**Integrating Commerce Data**](./04_Integrating_Commerce_Data.md) -
  Using Renderlets to embed product teasers in Pimcore documents.
- [**Tags for Filtering**](./05_Using_Tags_for_Filtering.md) -
  Leveraging Pimcore tags to build frontend filtering functionality.
