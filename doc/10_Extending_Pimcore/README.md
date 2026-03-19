---
title: Extending Pimcore
description: Extension points and mechanisms for customizing Pimcore across core, backend API, and frontend layers.
---

# Extending Pimcore

Pimcore builds on Symfony's extension mechanisms and adds its own extension points.
Most projects start with data modeling, controllers, templates, and documents,
all achievable through configuration and standard Symfony patterns.
When you need to go further, Pimcore provides dedicated extension points across
three layers.

## Architecture: Where to Extend What

Pimcore's extension points span three layers, each documented in its own chapter:

| Layer | What it covers | When to use |
|-------|---------------|-------------|
| **Core Framework** (this chapter) | Events, models, bundles, data types, Symfony DI, Composer dependencies | PHP/Symfony-level customization, data model extensions, lifecycle hooks, distributable bundles |
| **[Studio Backend API](https://github.com/pimcore/studio-backend-bundle/blob/1.x/doc/03_Extending/README.md)** | API endpoints, response customization, grid columns, filters, metadata adapters | Customizing the REST API layer that powers Pimcore Studio |
| **[Studio UI](https://github.com/pimcore/studio-ui-bundle/blob/1.x/doc/04_Extending/README.md)** | Plugins, tabs, widgets, navigation, context menus, sidebar extensions | Extending the Pimcore Studio frontend with custom UI components |

**Cross-cutting extensions** (e.g. adding a custom asset type or document type) typically
require changes across multiple layers. The guides in this chapter cover the full workflow
and link to the Studio Backend and Studio UI documentation for layer-specific detail.

## Core Framework Extensions

**[Events and Event Listeners](./01_Events/README.md)**
Hook into Pimcore's lifecycle (element CRUD, cache, mail, workflows) using
the Symfony EventDispatcher without modifying core code.

**[Add Your Own Dependencies](./02_Add_Your_Own_Dependencies.md)**
Install external Composer packages and register third-party Symfony bundles.

**[Custom Extension Guides](./03_Custom_Extension_Guides/README.md)**
Step-by-step guides for specific customizations: custom asset types, document types,
data types, permissions, persistent models, and more.

**[Bundle Developer's Guide](./04_Pimcore_Bundle_Developers_Guide/README.md)**
Build reusable, distributable Pimcore bundles with installers, service definitions,
auto-loaded configuration, and migrations.

## Studio Backend Extensions

The Studio Backend Bundle exposes extension points for the API layer that powers
Pimcore Studio: custom endpoints, response enrichment via events, grid columns,
filters, metadata adapters, and OpenAPI integration.

See the full reference:
**[Extending Pimcore Studio Backend](https://github.com/pimcore/studio-backend-bundle/blob/1.x/doc/03_Extending/README.md)**

## Studio UI Extensions

The Studio UI Bundle provides a plugin/module architecture, component registry,
dependency injection, and dynamic type system for client-side customization of
Pimcore Studio.

See the full reference:
**[Extending Pimcore Studio](https://github.com/pimcore/studio-ui-bundle/blob/1.x/doc/04_Extending/README.md)**

## See Also

- [Configuration](../08_Development_Details/01_Configuration/README.md) -
  override Pimcore constants (asset directory, temp directory, etc.)
- [Parent Class for Objects](../03_Objects/01_Object_Classes/04_Additional_Class_Settings/04_Parent_Class.md) -
  inject additional functionality into object classes
- [Overriding Models](./03_Custom_Extension_Guides/08_Overriding_Models.md) -
  replace default Pimcore model implementations
