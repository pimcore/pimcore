---
title: Routing and URLs
description: How Pimcore resolves incoming requests to controllers through documents, custom routes, redirects, and URL slugs.
keywords:
    - routing
    - URL
    - pretty URL
    - custom routes
    - redirects
---

# Routing and URLs

Routing defines which controller handles a request based on its URL.
Pimcore's routing builds on top of
[Symfony routing](https://symfony.com/doc/current/routing.html)
and adds document-based routing, custom routes, URL slugs, and redirects.

## Routing Priority

Pimcore processes routes in the following order:

#### 1. System / Symfony Routes
Routes defined by Pimcore core, Symfony, or custom bundles. These are standard Symfony routes
and have the highest priority.

Use the router debugger to list all configured Symfony routes:
`./bin/console debug:router`

#### 2. Redirects with Priority 99
Redirects configured with priority 99 override all other dynamic routes.
See [Redirects](./02_Redirects.md) for details.

#### 3. Pimcore Documents and Pretty URLs
The document tree path defines the public URL. Individual documents can also have Pretty URLs
configured as alternative paths.
See [Documents and Pretty URLs](./01_Documents_and_Pretty_URLs.md) for details.

#### 4. URL Slugs of Data Objects
The [URL Slug](../../03_Objects/01_Object_Classes/01_Data_Types/65_Others.md#url-slug) data type
allows defining URLs for data objects. These must be unique and are evaluated per site.

#### 5. Custom Routes
For pages without a corresponding document (product lists, checkout flows, etc.),
Custom Routes map URL patterns to specific controllers.
See [Custom Routes](../../backlog/02_MVC_Custom_Routes.md) for details.

#### 6. Redirects
All redirects with priority lower than 99 are processed last, ordered by their configured priority.
See [Redirects](./02_Redirects.md) for details.

#### Multi-domain Sites
The routing process supports multi-domain sites.
See [Working with Sites](../08_Working_with_Sites.md) for details.
