---
title: Studio Backend Events
description: Events for customizing Studio Backend API responses.
---

# Studio Backend Events

The Studio Backend Bundle dispatches events that allow customization of API responses
and element resolution behavior. These events use the Symfony EventDispatcher and
follow the `EventSubscriberInterface` pattern.

## PreResponse Events

The Studio Backend fires PreResponse events before returning API responses for elements.
Use these to add custom attributes, modify response data, or inject additional
information into the API output.

Key event classes include:

- `Pimcore\Bundle\StudioBackendBundle\Asset\Event\PreResponse\AssetEvent` -
  fired before asset API responses
- `Pimcore\Bundle\StudioBackendBundle\DataObject\Event\PreResponse\DataObjectEvent` -
  fired before data object API responses
- `Pimcore\Bundle\StudioBackendBundle\Document\Event\PreResponse\DocumentEvent` -
  fired before document API responses
- `Pimcore\Bundle\StudioBackendBundle\Element\Event\PreResolve\ElementResolveEvent` -
  fired during element resolution to customize how elements are looked up by ID or search term

## Example: Adding Custom Attributes to Asset Responses

```php
<?php

namespace App\EventSubscriber;

use Pimcore\Bundle\StudioBackendBundle\Asset\Event\PreResponse\AssetEvent;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Image;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\CustomAttributes;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AssetResponseSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            AssetEvent::EVENT_NAME => 'onAssetEvent',
        ];
    }

    public function onAssetEvent(AssetEvent $event): void
    {
        if ($event->getAsset() instanceof Image) {
            $event->addAdditionalAttribute('isImage', true);
        }

        $event->setCustomAttributes(
            new CustomAttributes(
                key: 'My Awesome Key',
                additionalCssClasses: ['my-awesome-css-class'],
            )
        );
    }
}
```

## Further Reading

For the full list of Studio Backend events and additional examples, see
[Extending via Events](https://github.com/pimcore/studio-backend-bundle/blob/1.x/doc/03_Extending/11_Extending_via_Events.md)
in the Studio Backend Bundle documentation.
