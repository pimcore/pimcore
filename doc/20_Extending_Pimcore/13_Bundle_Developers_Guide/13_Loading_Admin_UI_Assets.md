# Loading Assets in the Admin UI

If you need to load assets (JS, CSS) in the Admin or Editmode UI, you have 2 options, depending on if you do that from a
[Pimcore Bundle](./05_Pimcore_Bundles/README.md) or from somewhere else.

## Pimcore Bundles

Just add the [`PimcoreBundleAdminClassicInterface`](https://github.com/pimcore/pimcore/blob/11.x/lib/Extension/Bundle/PimcoreBundleAdminClassicInterface.php) to your bundle class.
The interface prescribes the following methods: 
- `getJsPaths`
- `getCssPaths`
- `getEditmodeJsPaths`
- `getEditmodeCssPaths`


In order to implement all four methods prescribed by the interface you can use the [`BundleAdminClassicTrait`](https://github.com/pimcore/pimcore/blob/11.x/lib/Extension/Bundle/Traits/BundleAdminClassicTrait.php).

### Encore

As Pimcore uses [Encore](https://symfony.com/doc/current/frontend/encore/simple-example.html) to build its assets, it also provides an [`EncoreHelper`](https://github.com/pimcore/pimcore/blob/131b0e917f9e7b929cb189e74f9404b73551938c/lib/Helper/EncoreHelper.php)-Class to include built files in your bundle. 

You can use `EncoreHelper::getBuildPathsFromEntryPoints` to get all paths from the assets and load them with the methods mentioned above. 
> This command accepts the path to `entrypoints.json` file ending as string and returns an array with paths to the built files.

The following example illustrates this for loading the built webencore-files:
```php
use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Pimcore\Extension\Bundle\PimcoreBundleAdminClassicInterface;
use Pimcore\Extension\Bundle\Traits\BundleAdminClassicTrait;
use Pimcore\Extension\Bundle\Traits\PackageVersionTrait;
use Pimcore\Helper\EncoreHelper;

class EncoreBundle extends AbstractPimcoreBundle implements PimcoreBundleAdminClassicInterface
{
    use BundleAdminClassicTrait;
    use PackageVersionTrait;

    public function getCssPaths(): array
    {
        return EncoreHelper::getBuildPathsFromEntrypoints($this->getPath() . '/public/build/encorebundle/entrypoints.json', 'css');
    }

    public function getJsPaths(): array
    {
        return EncoreHelper::getBuildPathsFromEntrypoints($this->getPath() . '/public/build/encorebundle/entrypoints.json');
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    // ...
}
```

## Event Based

You can add additional paths to load by handling the events defined on [`BundleManagerEvents`](https://github.com/pimcore/pimcore/blob/11.x/lib/Event/BundleManagerEvents.php).
For example, to load the JS file when loading the admin UI, implement an event listener like the following (please see
[Events](../../20_Extending_Pimcore/11_Event_API_and_Event_Manager.md) for details on how to implement and register event
listeners): 

```php
<?php

namespace App\EventListener;

use Pimcore\Event\BundleManager\PathsEvent;
use Pimcore\Event\BundleManagerEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AdminAssetsListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            BundleManagerEvents::JS_PATHS => 'onJsPaths'
        ];
    }

    public function onJsPaths(PathsEvent $event): void
    {
        $event->addPaths([
            '/bundles/app/js/admin.js'
        ]);
    }
}
```
