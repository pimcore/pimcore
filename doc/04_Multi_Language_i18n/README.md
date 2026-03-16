---
title: Multi-Language & Localization
description: Overview of Pimcore's multi-language content management, document localization, translation system, and formatting capabilities.
---

# Multi-Language & Localization

Pimcore supports multi-language content and a fully localized user interface out of the box. Content localization and
UI localization are configured independently, so the languages available for your content can differ from the language
used in Pimcore Studio. Pimcore follows Symfony patterns for locale handling and translations, making multi-language
setups straightforward for both editors and developers.

## Content Localization

You configure the available languages centrally in Pimcore Studio under **System > System Settings > 
Localization & Internationalization (l10n/i18n)**. The following settings are available:

- **Available languages** - the set of locales editors can use for content.
- **Default language** - the system-wide fallback locale.
- **Fallback language per locale** - if a value is empty in the primary language, Pimcore returns the fallback
  language's value instead.
- **Required languages** - mark specific languages as mandatory in localized fields of data objects
  (see [Localized Fields](../03_Objects/01_Object_Classes/01_Data_Types/50_Localized_Fields.md#definition-of-required-languages)).

The activated languages apply to [Localized Fields](../03_Objects/01_Object_Classes/01_Data_Types/50_Localized_Fields.md)
and [Classification Store](../03_Objects/01_Object_Classes/01_Data_Types/15_Classification_Store.md) fields in data
objects, as well as to document and translation management.

> **Note:** Removing a language from the i18n/l10n list does not delete the corresponding database tables.
> Use the console command `pimcore:locale:delete-unused-tables` for cleanup.

## Document Localization

Pimcore documents carry a language system property that controls locale-aware rendering and routing. For setup details,
language-specific document trees, and best practices, see [Document Localization](./01_Document_Localization.md).

## Translations

Pimcore uses the Symfony Translator component and organizes translations into multiple domains. You manage all
translation entries through **Translations > Translations** in Pimcore Studio, where you can filter by domain, import,
and export. For details on each domain, see:

- [Shared Translations](./02_Shared_Translations.md) - website and application strings
- [Pimcore Studio Translations](./03_Pimcore_Studio_Translations.md) - UI labels for Pimcore Studio
- [Custom Translation Domains](./04_Custom_Translation_Domains.md) - project-specific domains

## Formatting

Pimcore provides a formatting service for locale-aware number, date, and currency formatting.
See [Formatting Service](./05_Formatting_Service.md) for usage and configuration.
