---
title: Events and Event Listeners
description: Hook into Pimcore behavior using Symfony EventDispatcher.
---

# Events and Event Listeners

Pimcore fires events during core operations such as saving, updating, and deleting
elements. Use these events to hook into Pimcore's behavior without modifying core code.

Pimcore uses the standard Symfony EventDispatcher. All Symfony core events and events
from third-party Symfony bundles are available alongside Pimcore-specific events.
For background on the event system, see the Symfony
[Events and Event Listeners](https://symfony.com/doc/current/event_dispatcher.html)
documentation.

## Registering Listeners

Register event listeners as services in `config/services.yaml` using the
`kernel.event_listener` tag, or implement `EventSubscriberInterface` for
automatic registration (no YAML needed when autoconfiguration is enabled):

```yaml
services:
    App\EventListener\MyListener:
        tags:
            - { name: kernel.event_listener, event: pimcore.asset.preUpdate, method: onPreUpdate }
```

Or implement `EventSubscriberInterface` for self-registering subscribers:

```php
<?php

namespace App\EventSubscriber;

use Pimcore\Event\Model\AssetEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MySubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'pimcore.asset.preUpdate' => 'onPreUpdate',
        ];
    }

    public function onPreUpdate(AssetEvent $event): void
    {
        // handle the event
    }
}
```

## Event Categories

**[Core Framework Events](./01_Core_Framework_Events.md)**
Events dispatched by `pimcore/pimcore` for elements (assets, documents, data objects),
cache, mail, workflows, versions, and other system components.

**[Studio Backend Events](./02_Studio_Backend_Events.md)**
Events for customizing Studio Backend API responses using the PreResponse pattern.

**[Other Bundle Events](./03_Other_Bundle_Events.md)**
Individual Pimcore bundles (Data Hub, Ecommerce Framework, Personalization, and others)
dispatch their own events. Each bundle documents its events separately.
