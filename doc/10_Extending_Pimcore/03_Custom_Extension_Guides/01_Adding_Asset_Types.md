# Adding Asset Types

This feature allows users to add their own custom asset types.
To register a new custom asset type, follow these three steps:

## 1) Create the PHP Asset Class

The asset class must extend an existing `Pimcore\Model\Asset` subclass. Choose the base class
that best matches your type's storage behavior — for example, extend `Asset\Document` for
office-like files, or `Asset\Image` for image variants.

Place the class in a `Model\Asset` sub-namespace:

```php
<?php
// src/Model/Asset/InDesign.php

namespace App\Model\Asset;

class InDesign extends \Pimcore\Model\Asset
{
    protected string $type = 'indesign';
}
```

For reference, see the built-in asset types in [pimcore/pimcore on GitHub](https://github.com/pimcore/pimcore/tree/2026.x/models/Asset).

## 2) Register the Asset in Configuration

Add your type to the `pimcore.assets.type_definitions.map` configuration. The `matching`
array contains regular expressions — when a file is uploaded, its filename is tested against
these patterns to determine the asset type automatically.

```yaml
# config/config.yaml

pimcore:
    assets:
        type_definitions:
            map:
                indesign:
                    class: \App\Model\Asset\InDesign
                    matching: ["/\\.indd/"]
```

No database migration is needed — the `assets.type` column is `varchar(20)`.

## 3) Add a Studio UI Frontend Plugin

To give your asset type a proper editor in Pimcore Studio, create a frontend plugin that:

1. **Creates a `TabManager`** subclass with your type name
2. **Registers the type** in the `Asset/Editor/TypeRegistry`
3. **Registers editor tabs** (Custom Metadata, Properties, Versions, etc.)

The complete working example is in the [Studio Example Bundle](https://github.com/pimcore/studio-example-bundle/tree/main/assets/js/src/examples/custom-asset-type):

- [Plugin entry (index.ts)](https://github.com/pimcore/studio-example-bundle/blob/main/assets/js/src/examples/custom-asset-type/index.ts) — binds TabManager and registers the module
- [Module (indesign-asset-module.tsx)](https://github.com/pimcore/studio-example-bundle/blob/main/assets/js/src/examples/custom-asset-type/modules/indesign-asset-module.tsx) — registers type, tabs, and context menu
- [TabManager (indesign-tab-manager.ts)](https://github.com/pimcore/studio-example-bundle/blob/main/assets/js/src/examples/custom-asset-type/asset/editor/types/indesign/tab-manager/indesign-tab-manager.ts) — extends `TabManager` with `type = 'indesign'`

See also the [Plugin Development Examples](https://pimcore.com/docs/platform/Studio_UI/Extending/Plugin_Development_Examples/Custom_Asset_Type) documentation.
