---
title: Add Your Own Dependencies
description: Install external packages and register third-party bundles.
---

# Add Your Own Dependencies and Packages

Pimcore uses Composer for dependency management.
Add external packages with standard Composer commands.

## Basic Example

Run Composer in your project root directory:

```bash
composer require dragonmantank/cron-expression
```

## Third-Party Bundles

Install third-party bundles via Composer. Pimcore is a standard Symfony
application, so any Symfony bundle should work.

To load a bundle with the application, enable it in `config/bundles.php`
(see the Symfony [bundles documentation](https://symfony.com/doc/current/bundles.html)).

The Pimcore Kernel's base class defines a `registerBundles` method that returns default
bundles. Because bundle load order can matter for config auto-loading, the Kernel exposes
a `registerBundlesToCollection` method. This method accepts a `BundleCollection` with
optional priority (higher loads first) and environment restrictions (e.g. load only in `dev`).

:::note
Bundles without a priority are registered with a default priority of 0.
Set a negative value if you need a priority lower than default.
:::

Register a third-party bundle on the collection:

```php
<?php

namespace App;

use Pimcore\HttpKernel\BundleCollection\BundleCollection;
use Pimcore\Kernel as PimcoreKernel;

class Kernel extends PimcoreKernel
{
    /**
     * Adds bundles to register to the bundle collection. The collection is able
     * to handle priorities and environment specific bundles.
     */
    public function registerBundlesToCollection(BundleCollection $collection): void
    {
        if (class_exists('\\XYZBundle\\XYZBundle')) {
            $collection->addBundle(new \XYZBundle\XYZBundle);
        }

        // add a custom third-party bundle here with a high priority and only for dev environment
        $collection->addBundle(new Third\Party\PartyBundle, 10, ['dev']);
    }
}
```

For more details on priorities, environments, and dependency handling, see
[Bundle Collection](./04_Pimcore_Bundle_Developers_Guide/02_Bundle_Collection.md).

### Pimcore Bundles

Pimcore bundles extend the standard bundle concept with installation support and
Pimcore Studio integration. Register them in `config/bundles.php` or manually via the
`BundleCollection` as shown above. Use `pimcore:bundle:*` commands to list bundles
or interact with the bundle installer (install/uninstall).

For more information, see [Pimcore Bundles](./04_Pimcore_Bundle_Developers_Guide/01_Pimcore_Bundles/README.md).
