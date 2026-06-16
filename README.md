<p align="center">
  <a href="https://pimcore.com/"><img src="doc/img/logo-readme.svg" alt="Pimcore" width="350"></a>
</p>

<p align="center">
  <strong>Open Core Platform for Product Experience Management (PXM)</strong><br>
  One platform. Any data. Any channel. Any process.
</p>

<p align="center">
  <a href="https://packagist.org/packages/pimcore/pimcore"><img src="https://img.shields.io/packagist/v/pimcore/pimcore.svg" alt="Packagist Version"></a>
  <a href="https://github.com/pimcore/pimcore/blob/2026.x/LICENSE.md"><img src="https://img.shields.io/badge/license-POCL-brightgreen.svg" alt="License: POCL"></a>
  <a href="https://packagist.org/packages/pimcore/pimcore"><img src="https://img.shields.io/packagist/php-v/pimcore/pimcore.svg" alt="PHP Version"></a>
</p>

<p align="center">
  <a href="https://pimcore.com/">Website</a> ·
  <a href="https://docs.pimcore.com/platform/">Documentation</a> ·
  <a href="https://demo.pimcore.com/">Live Demo</a> ·
  <a href="https://www.youtube.com/watch?v=JC8q_6Mu7g0&list=PLrlLr70ddFwLjIkVO8k4vk2rHZ82LwhvF">Pimcore Inside</a> ·
  <a href="https://github.com/orgs/pimcore/discussions">Discussions</a> ·
  <a href="https://github.com/pimcore/platform-version/issues">Report an issue</a>
</p>

---

## What is Pimcore

Pimcore is an open core platform for Product Experience Management (PXM). It provides a solid, API-driven foundation for
managing digital data and customer experience, combining a Core Framework with a modular set of Core Extensions covering
PIM, MDM, DAM, CDP, DXP/CMS, and Digital Commerce — all licensed under the Pimcore Open Core License (POCL). Data is
stored independently of the channel and delivered to any output: websites, commerce systems, mobile apps, print,
digital signage, or headless consumers via REST and GraphQL. [Pimcore Studio](#pimcore-studio) provides the unified
administration interface for the entire platform.

![pimcore-general.png](doc/img/pimcore-general.png)

Pimcore ships with rich out-of-the-box functionality and is designed to be fully customizable and extensible. You define
your own data models, build your own templates or consume the APIs, integrate with any IT infrastructure, and tailor
the platform to exact project requirements — from a single standalone implementation to a complex multi-system
architecture.

## Features

All data in Pimcore is organized into three core element types that can be linked and related to each other:

- **Data Objects** — manage any structured data based on a class-editor-defined model, either manually or via API.
  Covers products (PIM/MDM), categories, customers (CDP), orders (Digital Commerce), and articles (DXP/CMS).
  Delivers consistent data to multiple output channels from a single source.
  ![pimcore-objects.png](doc/img/pimcore-objects.png)

- **Assets (DAM)** — store and manage any file type. Preview 200+ formats directly in Pimcore, auto-generate
  channel-specific output formats, and enrich files with metadata and versioning.
  ![pimcore-assets.png](doc/img/pimcore-assets.png)

- **Documents (DXP/CMS)** — build pages with Twig templates and inline editables, with full multilingual and multi-site 
  support, plus emails, newsletters, and web-to-print. 
  ![pimcore-documents.png](doc/img/pimcore-documents.png)

### The Pimcore Platform

Pimcore is modular. Its modules ship as separate Composer packages (Core Modules & Extensions), all licensed under POCL:

- **[Data Onboarding & Distribution](https://docs.pimcore.com/platform/Datahub/)** — Datahub (GraphQL/REST), 
  Data Importer, File Export, Simple REST, Webhooks
- **[Productivity](https://docs.pimcore.com/platform/Backend_Power_Tools/)** — Backend Power Tools, Direct Edit, 
  Workflow Designer
- **[Automation](https://docs.pimcore.com/platform/Copilot/)** — Copilot, Copilot Showcases, Workflow Automation
- **[Portals & Dashboards](https://docs.pimcore.com/platform/Studio_Dashboards/)** — Studio Dashboards, Portal Engine, 
  Statistics Explorer
- **[Advanced Data Management](https://docs.pimcore.com/platform/Headless_Documents/)** — Headless Documents,
  Asset Metadata Class Definitions, Data Quality Management, Web-to-Print, Customer Management Framework
- **[Marketing & Personalization](https://docs.pimcore.com/platform/Targeting/)** — Personalization
- **[Integrations](https://docs.pimcore.com/platform/OpenID_Connect/)** — OpenID Connect, Translation Provider
  Interfaces, Datahub Productsup
- **[E-Commerce Framework](https://docs.pimcore.com/platform/Ecommerce_Framework/)** — catalog, pricing, cart,
  checkout, and order management

See [Pimcore Modules](https://docs.pimcore.com/platform/Pimcore_Platform/Pimcore_Modules/) for the full list and 
[Pimcore Editions](https://docs.pimcore.com/platform/Pimcore_Platform/Pimcore_Editions/) for module availability per 
edition (Community, Professional, Enterprise, PaaS).

## Architecture

Pimcore follows a layered architecture where multiple interfaces — the Studio administration UI, 
server-rendered websites, and headless API consumers — all operate on the same core data layer.

**Technology stack:**

- **Backend** — PHP 8.5+, Symfony (MVC, DI, Messenger, Routing, Security)
- **Studio UI** — React, TypeScript, Ant Design, Redux, RTK Query, Mercure, Rsbuild
- **Persistence** — MySQL/MariaDB via Doctrine DBAL for structured data; Flysystem for file storage
  (local, S3, and other adapters)
- **Search & indexing** — OpenSearch or Elasticsearch via the Generic Data Index
- **Cache** — Redis or Symfony Cache
- **Background processing** — Symfony Messenger with a configurable message queue backend
- **Real-time** — Mercure for server-sent events

**Two delivery patterns:**

- **Server-rendered** — Symfony controllers with Twig templates and Pimcore editables for inline content editing; pages 
  are built and rendered by the Pimcore application itself.
- **Headless** — REST and GraphQL APIs via Datahub; Pimcore acts as a pure data and content backend for any frontend or
  external system.

Both patterns can be used alongside each other in the same project.

See the [Architecture documentation](https://docs.pimcore.com/platform/Pimcore_Platform/Pimcore_Architecture/) for full 
details including application layers and the data flow between them.

### Platform Versions

Each module has its own repository and is released independently. The [pimcore/platform-version](https://github.com/pimcore/platform-version) 
package bundles a set of specific module versions that are tested and verified to work together, released as a single 
version such as `2026.1`. Major Platform Versions ship once per year; the documentation and demos are based on Platform 
Versions. Starting with 2026.1, every module carries the same version number as the platform. New projects depend 
on `pimcore/platform-version` by default.

## Quick start

Pimcore 2026.x uses Docker for local development. No local PHP or Composer installation is required.

**Prerequisites:** Docker and Docker Compose installed, and your user must be allowed to run Docker commands and change
file permissions.

See the full [Installation guide](https://docs.pimcore.com/platform/Getting_Started/Installation/) for necessary 
installation steps.

### Try the live demo

A hosted Enterprise Edition demo is available without any local setup:

- URL: https://demo.pimcore.com/
- Username: `superuser`
- Password: `enterprisedemo`

## Contributing

Code lives in this and the other Pimcore repositories, and pull requests work as they always have.

- **Bug fixes** — open a pull request including step-by-step instructions to reproduce the problem.
- **New features** — open a discussion with the core team before you start developing.
- **Security vulnerabilities** — see our [security policy](https://github.com/pimcore/pimcore/security/policy).

Read the [contributing guide](https://github.com/pimcore/pimcore/blob/2026.x/CONTRIBUTING.md) before submitting a pull
request. Contributions require accepting the [CLA](https://github.com/pimcore/pimcore/blob/2026.x/CLA.md).

### Reporting issues

Issue reporting for all Pimcore repositories is centralized.

- **Public issues** — open them in [pimcore/platform-version/issues](https://github.com/pimcore/platform-version/issues),
  not in individual repositories. This gives the community and maintainers one place to track, prioritize, and resolve
  them.
- **Private / customer-specific issues** — Pimcore partners and customers can use the
  [Enterprise Portal](https://get.support.pimcore.com) for non-public information.

Both paths are handled with the same priority; only the visibility differs.

## Community & support

- [GitHub Discussions](https://github.com/orgs/pimcore/discussions) — questions, ideas, and announcements
- [Documentation](https://docs.pimcore.com/platform/) — guides and API reference
- [Pimcore Academy](https://pimcore.com/en/resources/learning-hub) — tutorials and certification
- [Pimcore Inside](https://www.youtube.com/watch?v=JC8q_6Mu7g0&list=PLrlLr70ddFwLjIkVO8k4vk2rHZ82LwhvF) — weekly 5-9 min
  episodes covering new features and product insights straight from the team

## License

Pimcore is licensed under the [Pimcore Open Core License (POCL)](https://github.com/pimcore/pimcore/blob/2026.x/LICENSE.md).

Copyright Pimcore GmbH.
