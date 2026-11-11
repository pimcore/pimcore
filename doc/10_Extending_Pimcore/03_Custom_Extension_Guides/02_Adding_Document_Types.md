---
title: Adding Document Types
description: Register custom document types with PHP model, config, database ENUM, and Studio UI plugin.
---

# Adding Document Types

Custom document types extend Pimcore's document system with new types that appear in the
document tree alongside pages, snippets, emails, etc. A custom document type requires both
a PHP backend (model + configuration) and a Pimcore Studio frontend (plugin with editor
registration).

A complete working example is available in the
[Studio Example Bundle](https://github.com/pimcore/studio-example-bundle/tree/main/assets/js/src/examples/custom-document-type).

## 1) Create the Document Model Class

The document class must extend an existing Pimcore document type. For page-like documents,
extend `Pimcore\Model\Document\Page`:

```php
// src/Model/Document/Book.php
namespace App\Model\Document;

use Pimcore\Model\Document\Page;

class Book extends Page
{
    protected string $type = 'book';
}
```

## 2) Register the Document Type

Add the type to `pimcore.documents.type_definitions.map`. In a bundle, place a
`config.yaml` file in `config/pimcore/`. Pimcore
[auto-loads](../04_Pimcore_Bundle_Developers_Guide/04_Auto_Loading_Config_and_Routing.md)
config files from this directory:

```yaml
# config/pimcore/config.yaml
pimcore:
    documents:
        type_definitions:
            map:
                book:
                    class: \App\Model\Document\Book
                    direct_route: true
                    predefined_document_types: true
```

In an application (without a bundle), use a project config file instead:

```yaml
# config/config.yaml
pimcore:
    documents:
        type_definitions:
            map:
                book:
                    class: \App\Model\Document\Book
                    direct_route: true
```

## 3) Add the Type to the Database ENUM

The `documents` table stores the type in an ENUM column. Custom types must be added to
this ENUM via a bundle installer or migration:

```php
$db->executeQuery(
    'ALTER TABLE documents MODIFY COLUMN `type` ENUM(:enums)',
    ['enums' => array_merge($currentEnumTypes, ['book'])],
    ['enums' => ArrayParameterType::STRING]
);
```

See the
[example bundle installer](https://github.com/pimcore/studio-example-bundle/blob/main/src/Installer.php)
for the full implementation.

### Storage: When Do You Need a Custom Table?

Each built-in document type stores its data in a dedicated table (e.g., `documents_page`,
`documents_email`). When your custom type extends an existing type like `Page`, it reuses
that type's table (`documents_page`) and no additional table is needed. This is sufficient
when your custom type adds no extra persistent fields.

If your document type requires **additional database columns**, you need to:

1. Create a dedicated table (e.g., `documents_book`) in your installer
2. Create a custom `Dao` class that reads/writes from that table

For a reference implementation, see the
[Web-to-Print bundle](https://github.com/pimcore/ee-web-to-print-bundle), which adds
a `documents_printpage` table with extra fields like `lastgenerated` and
`lastgeneratemessage`. The relevant files are:

- [PrintAbstract/Dao.php](https://github.com/pimcore/ee-web-to-print-bundle/blob/2026.x/src/Model/Document/PrintAbstract/Dao.php) — custom Dao using `documents_printpage`
- [Installer.php](https://github.com/pimcore/ee-web-to-print-bundle/blob/2026.x/src/Installer.php) — creates the table and modifies the ENUM

## 4) Register the Frontend Editor (Pimcore Studio)

The document needs a Pimcore Studio plugin that registers the editor tabs, sidebars, and
context menu entry. This involves:

1. **TabManager** — a class extending `TabManager` from `@pimcore/studio-ui-bundle/modules/element`
   that defines which tabs the editor shows (Edit, Preview, Properties, Versions, etc.)
2. **SidebarManager** — bound as `DocumentSidebarManager` from `@pimcore/studio-ui-bundle/modules/document`,
   using the service ID pattern `Document/Editor/Sidebar/${Type}SidebarManager`
3. **TypeRegistry** — registers the document type name and its tab manager service ID
4. **Context menu** — adds an "Add Book" entry to the document tree right-click menu using
   `useAddDocument` from `@pimcore/studio-ui-bundle/modules/document`

The sidebar manager service ID **must** follow the convention
`Document/Editor/Sidebar/${CapitalizedType}SidebarManager` (e.g., `Document/Editor/Sidebar/BookSidebarManager`),
as the Studio UI core resolves it by this pattern.

See the complete frontend example:

- [Plugin entry (index.ts)](https://github.com/pimcore/studio-example-bundle/blob/main/assets/js/src/examples/custom-document-type/index.ts) — binds TabManager and SidebarManager
- [Module (book-document-module.tsx)](https://github.com/pimcore/studio-example-bundle/blob/main/assets/js/src/examples/custom-document-type/modules/book-document-module.tsx) — registers type, tabs, sidebars, context menu
- [TabManager (book-tab-manager.ts)](https://github.com/pimcore/studio-example-bundle/blob/main/assets/js/src/examples/custom-document-type/document/editor/types/book/tab-manager/book-tab-manager.ts) — extends `TabManager` with `type = 'book'`

For the full plugin scaffold (build configuration, `WebpackEntryPointProvider`, etc.), see the
[Getting Started with Your First Plugin](https://github.com/pimcore/studio-ui-bundle/blob/1.x/doc/04_Extending/01_Getting_Started_with_Your_First_Plugin.md)
guide.

## 5) Backend API Adapter (Optional)

If your custom document type has custom properties or settings that need to be processed when saving or returned in the 
Studio API detail endpoint, you need to register a **document type adapter** in the Studio Backend Bundle.

Without an adapter, the document's standard editables (from `PageSnippet`) still work, but custom fields won't be 
processed through the save pipeline or normalized for the API response.

This is **not required** for simple page-like types that only use standard document editables 
(like the `book` example). It becomes necessary when your document type adds custom properties, 
custom settings, or needs to transform data during save/load.

For details on implementing a document type adapter, see the
[Custom Document Types](https://github.com/pimcore/studio-backend-bundle/blob/2025.x/doc/03_Extending/07_Documents/01_Custom_Document_Types.md)
documentation in the Studio Backend Bundle.

## Do not use to override a class

The `type_definitions` should only be used to add new documents.
If you want to override an existing class, use `pimcore:models:class_overrides` instead.
For a more detailed explanation see [Overriding Models](./08_Overriding_Models.md).
