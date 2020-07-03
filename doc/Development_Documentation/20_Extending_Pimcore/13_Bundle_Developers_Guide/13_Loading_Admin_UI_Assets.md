# Loading Assets in the Admin UI

If you need to load assets (JS, CSS) in the Admin or Editmode UI, you have 2 options, depending on if you do that from a
[Pimcore Bundle](./05_Pimcore_Bundles) or from somewhere else.

## Pimcore Bundles

Just implement the methods defined in the [`PimcoreBundleInterface`](https://github.com/pimcore/pimcore/blob/master/lib/Extension/Bundle/PimcoreBundleInterface.php),
e.g. `getJsPaths()` or `getEditmodeCssPaths()`.

## Event Based

You can add additional paths to load by handling the events defined on [`BundleManagerEvents`](https://github.com/pimcore/pimcore/blob/master/lib/Event/BundleManagerEvents.php).
For example, to load the JS file when loading the admin UI, implement an event listener like the following (please see
[Events](../../20_Extending_Pimcore/11_Event_API_and_Event_Manager.md) for details on how to implement and register event
listeners): 

```php
<?php

namespace AppBundle\EventListener;

use Pimcore\Event\BundleManager\PathsEvent;
use Pimcore\Event\BundleManagerEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AdminAssetsListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            BundleManagerEvents::JS_PATHS => 'onJsPaths'
        ];
    }

    public function onJsPaths(PathsEvent $event)
    {
        $event->setPaths(array_merge($event->getPaths(), [
            '/bundles/app/js/admin.js'
        ]));
    }
}
```

 
