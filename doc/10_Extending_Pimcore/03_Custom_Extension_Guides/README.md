---
title: Custom Extension Guides
description: Guides for adding custom asset types, data types, permissions, and more.
---

# Custom Extension Guides

Step-by-step guides for extending Pimcore with custom implementations.
These guides focus on the core framework layer. Where a customization spans multiple layers
(e.g. adding a custom asset type requires both PHP configuration and a Studio UI plugin),
the guide covers the full workflow and links to the relevant Studio Backend or Studio UI documentation.

## Element Types

| Guide | Description |
|-------|-------------|
| [Adding Asset Types](./01_Adding_Asset_Types.md) | Define custom asset types beyond the built-in set |
| [Adding Document Types](./02_Adding_Document_Types.md) | Map custom document types to class names via config |
| [Adding Document Editables](./03_Adding_Document_Editables.md) | Create custom editables for document templates |
| [Adding Object Datatypes](./04_Adding_Object_Datatypes.md) | Add custom data types to Pimcore data objects |
| [Adding Object Layout Types](./05_Adding_Object_Layout_Types.md) | Add custom layout types for data object editing |

## Permissions and Security

| Guide | Description |
|-------|-------------|
| [Add Your Own Permissions](./06_Add_Your_Own_Permissions.md) | Register and manage custom permission keys |
| [Modifying Permissions on Object Data](./12_Modifying_Permissions_on_Object_Data.md) | Dynamically adjust field-level permissions based on object data |

## Models and Data

| Guide | Description |
|-------|-------------|
| [Overriding Models](./08_Overriding_Models.md) | Replace default Pimcore model implementations |
| [Custom Persistent Models](./09_Custom_Persistent_Models.md) | Store additional data with custom database models |

## System and Maintenance

| Guide | Description |
|-------|-------------|
| [Maintenance Tasks](./07_Maintenance_Tasks.md) | Register scheduled tasks for periodic operations |

## UI Customization

| Guide | Description |
|-------|-------------|
| [Custom Icons and Tooltips](./10_Custom_Icons_and_Tooltips.md) | Define dynamic icons and tooltips in the element tree |
| [Adding Button to Object Editor](./11_Adding_Button_to_Object_Editor.md) | Add custom buttons to the object editor interface |
| [Custom Layouts Based on Object Data](./13_Custom_Layouts_Based_on_Object_Data.md) | Show different layouts depending on the object's data |
