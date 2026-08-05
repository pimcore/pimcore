---
title: Core Framework
description: Documentation for the Pimcore Core Framework - data modeling, content management, workflows, and extensibility.
---

# Pimcore Core Framework

The Core Framework (`pimcore/pimcore`) provides the foundation for managing structured and
unstructured data in Pimcore. It handles data modeling, content management, workflows, and
the extension infrastructure that the rest of the platform builds on.

This documentation covers the core framework specifically. For the Studio Backend API and
Studio UI frontend, see the sidebar sections
[Studio Backend](https://github.com/pimcore/studio-backend-bundle/blob/1.x/doc/README.md)
and
[Studio UI](https://github.com/pimcore/studio-ui-bundle/blob/1.x/doc/04_Extending/README.md).

## Element Types

Pimcore manages three element types. Each element is stored once with a unique ID that serves
as the reference wherever it is reused (single-source publishing).

- [Documents](./01_Documents/README.md) - web pages, email templates, print pages, and other
  structured content with editable regions and dynamic rendering
- [Assets](./02_Assets/README.md) - media library and digital asset management with
  automatic thumbnail generation, metadata handling, and format conversion
- [Objects](./03_Objects/README.md) - custom data models with class definitions, field types,
  data inheritance, localization, and classification stores (PIM / MDM)

## Content and Data Management

- [Multilanguage and Localization](./04_Multi_Language_i18n/README.md) - shared translations,
  localized fields, language fallbacks, and locale-aware content delivery
- [Content Management Features](./05_Content_Management_Features/README.md) - properties,
  tags, notes, glossary, redirects, GDPR data extraction, and custom views
- [Reporting](./06_Reporting/README.md) - custom reports based on SQL queries or data adapters
- [Workflow Management](./07_Workflow_Management/README.md) - state machines for editorial
  and approval workflows with configurable actions, notifications, and permissions

## Development

- [Development Details](./08_Development_Details/README.md) - configuration, cache, session,
  database, authentication, and other framework internals
- [Development Tools](./09_Development_Tools/README.md) - console commands, profiler,
  generic execution engine, and debugging utilities
- [Extending Pimcore](./10_Extending_Pimcore/README.md) - events, bundles, custom data types,
  permissions, and a cross-layer index of all extension guides across core, Studio Backend,
  and Studio UI
- [Deployment Recommendations](./11_Deployment_Recommendations/README.md) - environments,
  configuration management, and deployment tools

## Reference

- [Implementation Inspirations](./12_Implementation_Inspirations/README.md) - patterns and
  examples for common implementation scenarios
- [Upgrade Notes](./13_Upgrade_Notes/README.md) - version-specific migration guides and
  breaking changes
