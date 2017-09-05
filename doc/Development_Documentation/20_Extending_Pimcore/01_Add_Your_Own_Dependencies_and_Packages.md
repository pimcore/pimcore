# Add your own Dependencies and Packages

Pimcore manages itself all dependencies using composer and therefore you can add your own dependencies by using 
 standard composer functionalities. 

## Basic Example
Use composer in your project root directory, eg. 
```bash
composer require mtdowling/cron-expression
```

## Third party bundles

You can install third party bundles via composer as shown above (as Pimcore is a standard Symfony application, you should
be able to use any third-party Symfony bundle).

To load a bundle with the application, it must first be registered on the kernel (see [bundles documentation](http://symfony.com/doc/current/bundles.html)).
By default there's a `registerBundles` method on the `AppKernel` which is expected to return a list of bundles to load. As
Pimcore defines a list of default bundles in its base kernel and priority can be important for config auto loading, the
Pimcore Kernel exposes a `registerBundlesToCollection`  method which allows to add bundles to a `BundleCollection` with
an optional priority (higher priority is loaded first) and a list of environments to handle (e.g. load only in `dev`
environment).

> Bundles without a priority are registered with a default priority of 0. You can set a negative value if you need to set
  a priority lower than default.

As an example, register a third party bundle on the collection:

```php
<?php

use Pimcore\HttpKernel\BundleCollection\BundleCollection;
use Pimcore\Kernel;

class AppKernel extends Kernel
{
    /**
     * Adds bundles to register to the bundle collection. The collection is able
     * to handle priorities and environment specific bundles.
     *
     * @param BundleCollection $collection
     */
    public function registerBundlesToCollection(BundleCollection $collection)
    {
        if (class_exists('\\AppBundle\\AppBundle')) {
            $collection->addBundle(new \AppBundle\AppBundle);
        }

        // add a custom third-party bundle here with a high priority and only for dev environment
        $collection->addBundle(new Third\Party\PartyBundle, 10, ['dev']);
    }
}
```

 Internally, the `BundleCollection` will be ordered by priority, filtered by environment and returned as plain array in
`registerBundles`. If you need full control over the registered bundles, you can override `registerBundles` and add your
customizations on the resulting array.

### Pimcore Bundles

For more information see [Pimcore Bundles](./13_Bundle_Developers_Guide/05_Pimcore_Bundles).

Pimcore bundles can be registered on the kernel by enabling them in the extension manager. The extension manager also allows
you to set a priority and environments to handle (as comma-separated string).

You can also enable pimcore bundles manually by adding them via code as shown above. Bundles which are manually enabled
can't be enabled or disabled through the extension manager. Instead, the extension manager will only expose functionality
to interact with the bundle installer (install/uninstall/update). 

## Version Checking
To avoid compatibility problems with plugins or custom components, that are compatible with a special Pimcore version only, Pimcore
has following requirement `pimcore/core-version` that defines its current version: 

```jsonmus
{
    ...
    "require": {
        ...
        "pimcore/core-version": "5.0.0",
        ...
    }
    ...
}
```

If your components have the same requirement to the versions they can work with, composer prevents you from installing your components
to an unsupported version of Pimcore due to version conflicts to the requirement `pimcore/core-version`. 
