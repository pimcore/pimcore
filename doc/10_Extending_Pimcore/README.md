---
title: Extending Pimcore
description: Extension points and mechanisms for customizing Pimcore.
---

# Extending Pimcore

Pimcore builds on Symfony's extension mechanisms and adds its own extension points.
Most projects start with data modeling, controllers, templates, and documents,
all achievable through configuration and standard Symfony patterns.
When you need to go further, Pimcore provides several dedicated extension paths.

## Extension Points

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

**[Extending Pimcore Studio](https://github.com/pimcore/studio-ui-bundle/blob/1.x/doc/04_Extending/README.md)**
Extend the Pimcore Studio interface with custom plugins using the Studio UI SDK.
The SDK provides a plugin/module architecture, component registry, dependency injection,
and dynamic type system for client-side customization.

## See Also

- [Configuration](../08_Development_Details/01_Configuration/README.md) -
  override Pimcore constants (asset directory, temp directory, etc.)
- [Parent Class for Objects](../03_Objects/01_Object_Classes/04_Additional_Class_Settings/04_Parent_Class.md) -
  inject additional functionality into object classes
- [Overriding Models](./03_Custom_Extension_Guides/08_Overriding_Models.md) -
  replace default Pimcore model implementations
