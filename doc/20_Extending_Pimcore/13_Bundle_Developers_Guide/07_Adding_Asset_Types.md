# Adding Asset Types

This feature allows users to add their own custom asset types.
To register new custom asset types, you must follow these steps:


## 1) Create the PHP asset class

The asset **must** extend `Pimcore\Model\Asset`. Let´s create a class for InDesign (the namespace does not matter)
but it's best practice to put your assets into a `Model\Asset` sub-namespace):

For examples have a look at the Pimcore core asset types at
[github](https://github.com/pimcore/pimcore/tree/11.x/models/Asset).

```php
<?php
// src/Model/Asset/InDesign.php

namespace App\Model\Asset;

class InDesign extends \Pimcore\Model\Asset
{
    protected string $type = 'indesign';
}
```

## 2) Create JavaScript class for the asset view editor:

It needs to extend `pimcore.asset.asset`, be located in the namespace `pimcore.asset` and named after the
`$type` property of the corresponding PHP class.

For examples have a look at the Pimcore core asset types at
[github](https://github.com/pimcore/admin-ui-classic-bundle/tree/1.x/public/js/pimcore/asset)

## 3) Register the asset on the asset type map

Next we need to update the `pimcore.assets.type_definitions.map` configuration to include our asset. This can be done in any config
file which is loaded (e.g. `/config/config.yaml`). The matching has to be an array of regular expressions of your data type.

```yaml
# /config/config.yaml

pimcore:
    assets:
        type_definitions:
            map:
                indesign:
                    class: \App\Model\Asset\InDesign
                    matching: ["/\\.indd/"]
```
