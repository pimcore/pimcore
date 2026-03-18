---
title: Bundle Collection
description: Register bundles with priorities, environments, and dependencies.
---

# Bundle Collection

The `BundleCollection` provides a unified API for registering bundles. Pimcore gathers
bundles from multiple sources (code in `App\Kernel` and `config/bundles.php`), so a
single collection manages registration, priorities, and environment restrictions.

Register your bundles in the `registerBundlesToCollection()` method on your Kernel class.

:::note
Bundles without a priority are registered with a default priority of 0.
Set a negative value if you need a priority lower than default.
:::

Usage examples:

```php
<?php

namespace App;

use Pimcore\HttpKernel\BundleCollection\BundleCollection;
use Pimcore\HttpKernel\BundleCollection\Item;
use Pimcore\HttpKernel\BundleCollection\LazyLoadedItem;
use Pimcore\Kernel as PimcoreKernel;

class Kernel extends PimcoreKernel
{
    public function registerBundlesToCollection(BundleCollection $collection): void
    {
        // add a bundle
        $collection->addBundle(new BundleA());

        // add a bundle, set a higher priority and restrict it to an environment
        $collection->addBundle(new BundleB(), 10, ['dev']);

        // add a bundle again - it will be ignored and still be loaded with prio 10
        $collection->addBundle(new BundleB());

        // add a bundle as string argument to load it lazily - the class instance will
        // only be built when really needed (when the environment matches), so this makes
        // sense for every item added with an environment restriction
        $collection->addBundle(BundleC::class, 10, ['dev']);

        // addBundle() is actually just a wrapper for add() which you can also directly use
        $collection->add(new Item(new BundleD(), 10, ['dev', 'prod']));

        // addBundle() is actually just a wrapper for add() which you can also directly use
        $collection->add(new LazyLoadedItem(BundleE::class, 10, ['dev']));

        // the collection expects an ItemInterface - if needed you can get fancy and implement
        // your own item type
        $collection->add(new FancyItem(/* whatever your item needs */));
    }
}
```

## Bundle Dependencies

When a bundle depends on other bundles, implement
[`DependentBundleInterface`](https://github.com/pimcore/pimcore/blob/2026.x/lib/HttpKernel/Bundle/DependentBundleInterface.php)
to automatically register dependencies instead of requiring users to add them manually:

```php
<?php

namespace CustomBundle;

use Pimcore\HttpKernel\Bundle\DependentBundleInterface;
use Pimcore\HttpKernel\BundleCollection\BundleCollection;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class CustomBundle extends Bundle implements DependentBundleInterface
{
    public static function registerDependentBundles(BundleCollection $collection): void
    {
        // register any bundles your bundle depends on here
        $collection->addBundle(new FooBundle);
    }
}
```

**Important:** `registerDependentBundles` is called as soon as your bundle is added
to the collection. Even if your bundle has environment restrictions, dependencies
registered from `registerDependentBundles` still load. Restrict dependency environments
with the `env` argument to `addBundle()`. For performance, add dependencies as lazy
(class name string or `LazyLoadedItem`) so the instance is only built when needed:

```php
<?php

// ...
use Pimcore\HttpKernel\BundleCollection\LazyLoadedItem;

class CustomBundle extends Bundle implements DependentBundleInterface
{
    public static function registerDependentBundles(BundleCollection $collection): void
    {
        // call addBundle with a class name as string and restrict it to the dev environment
        $collection->addBundle(FooBundle::class, 0, ['dev']);

        // directly add a LazyLoadedItem - this is what addBundle does internally when gets a string
        $collection->add(new LazyLoadedItem(FooBundle::class, 0, ['dev']));
    }
}
```

## Overriding Collection Items

Override a dependency's priority or environment restrictions by adding the item to the
collection **before** it is loaded as a dependency. The duplicate registration is ignored,
and your definition takes precedence. For example, if `CustomBundle` loads `FooBundle`
with priority 10, but you need priority 25:

```php
<?php

// ...

class CustomBundle extends Bundle implements DependentBundleInterface
{
    public static function registerDependentBundles(BundleCollection $collection): void
    {
        $collection->addBundle(FooBundle::class, 10);
    }
}
``` 

To override this, register `FooBundle` manually with your priority:


```php
<?php
namespace App;

use Pimcore\HttpKernel\BundleCollection\BundleCollection;
use Pimcore\Kernel as PimcoreKernel;

class Kernel extends PimcoreKernel
{
    public function registerBundlesToCollection(BundleCollection $collection): void
    {
        // register FooBundle manually
        $collection->addBundle(FooBundle::class, 25);
        
        // FooBundle won't be registered again here as it is already registered
        $collection->addBundle(new \CustomBundle\CustomBundle);
    }
}
```
