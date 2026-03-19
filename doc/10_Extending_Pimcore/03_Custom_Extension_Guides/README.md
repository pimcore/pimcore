---
title: Custom Extension Guides
description: Complete index of extension guides across core, backend API, and frontend layers.
---

# Custom Extension Guides

This page indexes all extension guides across Pimcore's three layers. Each guide indicates
which layers it touches so you know what to expect before diving in.

**Layer legend:**
- **Core** - PHP/Symfony: models, config, events, DI (`pimcore/pimcore`)
- **Backend** - Studio Backend API: endpoints, adapters, response customization (`studio-backend-bundle`)
- **UI** - Pimcore Studio frontend: plugins, tabs, widgets, navigation (`studio-ui-bundle`)

## Assets

| Guide | Description | Layers |
|-------|-------------|--------|
| [Adding Asset Types](./01_Adding_Asset_Types.md) | Define custom asset types beyond the built-in set | Core, UI |
| [Asset Metadata Adapters](https://github.com/pimcore/studio-backend-bundle/blob/1.x/doc/03_Extending/03_Assets/01_Extending_Metadata_Adapters.md) | Customize how asset metadata is read and written | Backend |
| [Editor Toolbar Button](https://github.com/pimcore/studio-ui-bundle/blob/1.x/doc/04_Extending/02_Plugin_Development_Examples/03_Add_Additional_Asset_Editor_Toolbar_Button.md) | Add custom buttons to editor toolbars (example uses asset editor; the same ComponentRegistry slot pattern works for data object and document editors) | UI |

## Documents

| Guide | Description | Layers |
|-------|-------------|--------|
| [Adding Document Types](./02_Adding_Document_Types.md) | Map custom document types to class names via config | Core, Backend, UI |
| [Adding Document Editables](./03_Adding_Document_Editables.md) | Create custom editables for document templates | Core, UI |
| [Custom Document Type Adapters](https://github.com/pimcore/studio-backend-bundle/blob/1.x/doc/03_Extending/07_Documents/01_Custom_Document_Types.md) | Register custom document type adapters for the API | Backend |

## Data Objects

| Guide | Description | Layers |
|-------|-------------|--------|
| [Adding Object Datatypes](./04_Adding_Object_Datatypes.md) | Add custom data types to Pimcore data objects | Core |
| [Adding Object Layout Types](./05_Adding_Object_Layout_Types.md) | Add custom layout types for data object editing | Core, UI |
| [Field Definition Adapters](https://github.com/pimcore/studio-backend-bundle/blob/1.x/doc/03_Extending/05_Data_Objects/01_Field_Definition_Adapters.md) | Customize how data object field definitions are processed | Backend |
| [Custom Layouts Based on Object Data](./13_Custom_Layouts_Based_on_Object_Data.md) | Dynamically select custom layouts based on object data by decorating the Studio Backend LayoutService | Backend |

## API and Endpoints

| Guide | Description | Layers |
|-------|-------------|--------|
| [Extending Endpoints](https://github.com/pimcore/studio-backend-bundle/blob/1.x/doc/03_Extending/04_Extending_Endpoints.md) | Add custom API endpoints to the Studio Backend | Backend |
| [Extending OpenAPI](https://github.com/pimcore/studio-backend-bundle/blob/1.x/doc/03_Extending/06_Extending_OpenApi.md) | Extend the OpenAPI specification with custom schemas | Backend |
| [Additional and Custom Attributes](https://github.com/pimcore/studio-backend-bundle/blob/1.x/doc/03_Extending/01_Additional_and_Custom_Attributes.md) | Enrich API responses with custom data via PreResponse events | Backend |
| [Extending Updater and Patcher](https://github.com/pimcore/studio-backend-bundle/blob/1.x/doc/03_Extending/10_Extending_Updater_and_Patcher.md) | Extend the element update and patch pipeline | Backend |
| [API Data in Plugins](https://github.com/pimcore/studio-ui-bundle/blob/1.x/doc/04_Extending/02_Plugin_Development_Examples/08_Use_API_Data.md) | Fetch and use Studio Backend API data in plugins | UI |

## Grid, Filters, and Listings

| Guide | Description | Layers |
|-------|-------------|--------|
| [Extending Grid with Custom Columns](https://github.com/pimcore/studio-backend-bundle/blob/1.x/doc/03_Extending/02_Extending_Grid_with_Custom_Columns.md) | Add custom columns to the element grid | Backend |
| [Extending Filters](https://github.com/pimcore/studio-backend-bundle/blob/1.x/doc/03_Extending/08_Extending_Filters/README.md) | Add custom search index and listing filters | Backend |
| [Custom Listing](https://github.com/pimcore/studio-ui-bundle/blob/1.x/doc/04_Extending/02_Plugin_Development_Examples/10_Custom_Listing.md) | Build custom listing views in the frontend | UI |

## UI: Navigation, Tabs, and Widgets

| Guide | Description | Layers |
|-------|-------------|--------|
| [Getting Started with Your First Plugin](https://github.com/pimcore/studio-ui-bundle/blob/1.x/doc/04_Extending/01_Getting_Started_with_Your_First_Plugin.md) | Set up a Studio UI plugin project from scratch | UI |
| [Main Navigation Entry](https://github.com/pimcore/studio-ui-bundle/blob/1.x/doc/04_Extending/02_Plugin_Development_Examples/01_Add_a_Main_Navigation_Entry.md) | Add entries to the Pimcore Studio main navigation | UI |
| [Left Sidebar Entry](https://github.com/pimcore/studio-ui-bundle/blob/1.x/doc/04_Extending/02_Plugin_Development_Examples/02_Add_an_Entry_to_the_Left_Sidebar.md) | Add custom entries to the left sidebar | UI |
| [Tab Manager](https://github.com/pimcore/studio-ui-bundle/blob/1.x/doc/04_Extending/02_Plugin_Development_Examples/04_Use_the_Tab_Manager.md) | Register custom tabs in element editors | UI |
| [Widget Manager](https://github.com/pimcore/studio-ui-bundle/blob/1.x/doc/04_Extending/02_Plugin_Development_Examples/05_Use_the_Widget_Manager.md) | Register custom dashboard widgets | UI |
| [Context Menus](https://github.com/pimcore/studio-ui-bundle/blob/1.x/doc/04_Extending/02_Plugin_Development_Examples/09_Customize_Context_Menus.md) | Add or modify context menu entries | UI |
| [Dynamic Types](https://github.com/pimcore/studio-ui-bundle/blob/1.x/doc/04_Extending/02_Plugin_Development_Examples/07_Use_Dynamic_Types.md) | Use the dynamic type system for extensible rendering | UI |
| [Perspectives and Widgets](https://github.com/pimcore/studio-backend-bundle/blob/1.x/doc/03_Extending/09_Perspectives/README.md) | Register custom perspective widgets (backend config) | Backend |

## Permissions and Security

| Guide | Description | Layers |
|-------|-------------|--------|
| [Add Your Own Permissions](./06_Add_Your_Own_Permissions.md) | Register custom permission keys and check them in Studio Backend (`#[IsGranted]`) and Studio UI (`isAllowed()`) | Core, Backend, UI |
| [Modifying Permissions on Object Data](./12_Modifying_Permissions_on_Object_Data.md) | Modify element permissions based on object data using the GenericDataIndex PermissionEvent | Core, Backend |

## Icons and Appearance

| Guide | Description | Layers |
|-------|-------------|--------|
| [Custom Icons and Tooltips](./10_Custom_Icons_and_Tooltips.md) | Define dynamic icons and tooltips in the element tree | Core |
| [Custom Icons (Studio)](https://github.com/pimcore/studio-ui-bundle/blob/1.x/doc/04_Extending/02_Plugin_Development_Examples/06_Adding_Custom_Icons.md) | Add custom icon sets to Pimcore Studio | UI |

## GDPR and Compliance

| Guide | Description | Layers |
|-------|-------------|--------|
| [GDPR Data Extractor](../../05_Content_Management_Features/06_GDPR_Data_Extractor.md) | Configure and extend the GDPR Data Extractor with custom data sources | Core, Backend, UI |

## Models and Data

| Guide | Description | Layers |
|-------|-------------|--------|
| [Overriding Models](./08_Overriding_Models.md) | Replace default Pimcore model implementations | Core |
| [Custom Persistent Models](./09_Custom_Persistent_Models.md) | Store additional data with custom database models | Core |

## System and Maintenance

| Guide | Description | Layers |
|-------|-------------|--------|
| [Maintenance Tasks](./07_Maintenance_Tasks.md) | Register scheduled tasks for periodic operations | Core |
