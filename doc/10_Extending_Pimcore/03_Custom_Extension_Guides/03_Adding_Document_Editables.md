---
title: Adding Document Editables
description: Create custom document editables with PHP class, config, and Studio UI plugin.
---

# Adding Document Editables

A complete working example is available in the
[Studio Example Bundle](https://github.com/pimcore/studio-example-bundle/tree/main/assets/js/src/examples/custom-document-editable).

Register a custom editable in three steps:

## 1) Create the Editable Class

The editable **must** extend `Pimcore\Model\Document\Editable`. It's best practice to put 
your editables into a `Model\Document\Editable` sub-namespace:

```php
<?php
// src/Model/Document/Editable/Markdown.php

namespace App\Model\Document\Editable;

class Markdown extends \Pimcore\Model\Document\Editable implements \Pimcore\Model\Document\Editable\EditmodeDataInterface
{
    // methods as required by Pimcore\Model\Document\Editable and Pimcore\Model\Document\Editable\EditmodeDataInterface
}
```

## 2) Register the Editable on the Editable Map

Next we need to update `pimcore.documents.editables.map` configuration to include our editable. This can be done in any config
file which is loaded (e.g. `/config/config.yaml`), but if you provide the editable with a bundle you should define it
in a configuration file which is [automatically loaded](../04_Pimcore_Bundle_Developers_Guide/04_Auto_Loading_Config_and_Routing.md). Example:

```yaml
# config/pimcore/config.yaml

pimcore:
    documents:
        editables:
            map:
                markdown: \App\Model\Document\Editable\Markdown
```

### Objects in the Stored Data

An editable whose `getDataForResource()` returns an array is stored as one serialized PHP value. Since Pimcore 2026.3.5
it is read back (`getUnserializedData()`) with an
[`allowed_classes`](https://www.php.net/manual/en/function.unserialize.php) list that only covers the classes the core
editables store; any other class is reduced to `__PHP_Incomplete_Class`, so a forged value can never instantiate
arbitrary objects.

Prefer storing plain arrays and scalars - for example the id of a referenced element instead of the element itself. If
your editable has to store objects of its own, register their classes, otherwise those values are lost on the next
load and save:

```yaml
# config/pimcore/config.yaml

pimcore:
    documents:
        editables:
            unserialize_allowed_classes:
                - App\Model\Document\Editable\MarkdownOptions
```

The classes listed here are merged with the built-in ones.

## 3) Create the Studio UI Plugin

In Pimcore Studio, custom editables are registered as dynamic types via a Studio plugin. You need to:

1. Create a class extending `DynamicTypeDocumentEditableAbstract` that returns a React component from `getEditableDataComponent()`
2. Register it with the `DynamicTypeDocumentEditableRegistry` via a module
3. Wire everything together in a plugin

**Important:** The framework injects `value` and `onChange` props onto the **root element** returned by 
`getEditableDataComponent()` via `React.cloneElement`. Your root component must accept and forward these props to 
the actual input element.

You can use the `InheritanceOverlay` component 
(exported from `@pimcore/studio-ui-bundle/modules/document`) to support document inheritance, 
and `createStyles` from `antd-style` for theme-aware styling.

### Register the Bundle for the Document Editor Iframe

Document editables render inside an iframe that has its own plugin loading mechanism. Your bundle's 
`WebpackEntryPointProvider` must be tagged with **both** the main and the document editor iframe 
entry point provider tags, otherwise your editable type will not be available in edit mode:

```yaml
# config/services.yaml

services:
    App\Webpack\WebpackEntryPointProvider:
        tags:
            - { name: pimcore_studio_ui.webpack_entry_point_provider }
            - { name: pimcore_studio_ui.webpack_entry_point_provider.document_editor_iframe }
```

### Example Files

The complete working example is in the [Studio Example Bundle](https://github.com/pimcore/studio-example-bundle/tree/main/assets/js/src/examples/custom-document-editable):

- [Plugin entry (index.ts)](https://github.com/pimcore/studio-example-bundle/blob/main/assets/js/src/examples/custom-document-editable/index.ts) — binds the dynamic type and registers the module
- [Module (markdown-editable-module.tsx)](https://github.com/pimcore/studio-example-bundle/blob/main/assets/js/src/examples/custom-document-editable/modules/markdown-editable-module.tsx) — registers the type in the `DynamicTypeDocumentEditableRegistry`
- [Dynamic type (dynamic-type-document-editable-markdown.tsx)](https://github.com/pimcore/studio-example-bundle/blob/main/assets/js/src/examples/custom-document-editable/dynamic-types/definitions/dynamic-type-document-editable-markdown.tsx) — extends `DynamicTypeDocumentEditableAbstract` with a `<textarea>` component
- [Styles (dynamic-type-document-editable-markdown.styles.ts)](https://github.com/pimcore/studio-example-bundle/blob/main/assets/js/src/examples/custom-document-editable/dynamic-types/definitions/dynamic-type-document-editable-markdown.styles.ts) — theme-aware styling via `createStyles`
- [PHP editable class (Markdown.php)](https://github.com/pimcore/studio-example-bundle/blob/main/src/Model/Document/Editable/Markdown.php) — backend editable model
- [Service config (services.yaml)](https://github.com/pimcore/studio-example-bundle/blob/main/config/services.yaml) — entry point provider with iframe tag

For more information about creating Studio plugins, see the [Getting Started with Your First Plugin](https://github.com/pimcore/studio-ui-bundle/blob/1.x/doc/04_Extending/01_Getting_Started_with_Your_First_Plugin.md).
