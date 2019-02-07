# Bundle Collection

The `BundleCollection` is a container which is used to register every used bundle. As Pimcore gathers bundles from multiple 
sources - registered via code in `AppKernel` and registered through the extension manager config, it makes sense to have 
a unified API how bundles can be registered. 

While Symfony's standard edition uses a `registerBundles` method building an array of bundles to load, Pimcore expects you
to register your bundles in the `registerBundlesToCollection()` method and to use the bundle collection to add bundles.

> Bundles without a priority are registered with a default priority of 0. You can set a negative value if you need to set
  a priority lower than default.

Below are a couple of examples how the bundle collection can be used:

```php
<?php

use Pimcore\HttpKernel\BundleCollection\BundleCollection;
use Pimcore\HttpKernel\BundleCollection\Item;
use Pimcore\HttpKernel\BundleCollection\LazyLoadedItem;
use Pimcore\Kernel;

class AppKernel extends Kernel
{
    public function registerBundlesToCollection(BundleCollection $collection)
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

        // the collection expectes an ItemInterface - if needed you can get fancy and implement
        // your own item type
        $collection->add(new FancyItem(/* whatever your item needs */));
    }
}
```

## Bundle Dependencies

If a bundle depends on other bundles, e.g. because it uses features provided by a third-party bundle you need to
make sure that third-party bundle is loaded together with your bundle. You can either instruct your users to manually
load the bundles your bundle depends on in their `AppKernel` or you can implement the [`DependentBundleInterface`](https://github.com/pimcore/pimcore/blob/master/lib/HttpKernel/Bundle/DependentBundleInterface.php)
and define a list of bundles which should be loaded together with your bundle:

```php
<?php

namespace CustomBundle;

use Pimcore\HttpKernel\Bundle\DependentBundleInterface;
use Pimcore\HttpKernel\BundleCollection\BundleCollection;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class CustomBundle extends Bundle implements DependentBundleInterface
{
    public static function registerDependentBundles(BundleCollection $collection)
    {
        // register any bundles your bundle depends on here
        $collection->addBundle(new FooBundle);
    }
}
```

**Important:** the `registerDependentBundles` method will be called as soon as your bundle is added to the collection. Even
if your bundle has environment restrictions, the bundles added to the collection from `registerDependentBundles` will still
be loaded. If you need to restrict the environments where the dependencies should be loaded, restrict them with the `env`
argument to `addBundle()`. For performance reasons, you should add the bundle as lazy by adding a class name as string or
by using `addItem` directly and passing a `LazyLoadedItem` instance. This ensures that the bundle instance is only built
when it is really needed. Example:  

As example:

```php
<?php

// ...
use Pimcore\HttpKernel\BundleCollection\LazyLoadedItem;

class CustomBundle extends Bundle implements DependentBundleInterface
{
    public static function registerDependentBundles(BundleCollection $collection)
    {
        // call addBundle with a class name as string and restrict it to the dev environment
        $collection->addBundle(FooBundle::class, 0, ['dev']);

        // directly add a LazyLoadedItem - this is was addBundle does internally when gets a string
        $collection->add(new LazyLoadedItem(FooBundle::class, 0, ['dev']));
    }
}
```

## Overriding collection items

In case bundle defines a dependency which has the wrong priority or environment restrictions for your project you can
override the dependency definition by adding the item to the collection **before** it is loaded as dependency. The dependency
is simply ignored and your item will be used. Let's assume `CustomBundle` defines `FooBundle` as dependency and loads it 
with a priority of 10, but we need to set the priority to 25:

```php
<?php

// ...

class CustomBundle extends Bundle implements DependentBundleInterface
{
    public static function registerDependentBundles(BundleCollection $collection)
    {
        $collection->addBundle(FooBundle::class, 10);
    }
}
``` 

To override this, register `FooBundle` manually with your priority:


```php
<?php

use Pimcore\HttpKernel\BundleCollection\BundleCollection;
use Pimcore\Kernel;

class AppKernel extends Kernel
{
    public function registerBundlesToCollection(BundleCollection $collection)
    {
        // register FooBundle manually
        $collection->addBundle(FooBundle::class, 25);
        
        // FooBundle won't be registered again here as it is already registered
        $collection->addBundle(new \CustomBundle\CustomBundle);
    }
}
```
