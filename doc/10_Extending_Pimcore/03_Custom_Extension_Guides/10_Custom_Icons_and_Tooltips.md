# Custom Icons & Tooltips in the Element Tree and Editor Tabs

Pimcore Studio lets you customize how elements appear in the **tree** and **editor tabs** by overriding their **icon** and **tooltip** via backend event subscribers. The Studio UI automatically renders these customizations — no frontend plugin code is needed for basic cases.

## Custom Attributes

The `CustomAttributes` schema on every tree response supports these fields:

| Field | Type | Rendered in tree? |
|-------|------|:-----------------:|
| `icon` | `ElementIcon` (type + value) | Yes |
| `tooltip` | `string` (HTML) | Yes (on hover) |
| `additionalIcons` | `string[]` | Not yet |
| `key` | `string` | Not yet |
| `additionalCssClasses` | `string[]` | Not yet |

The `icon` field accepts an `ElementIcon` with two properties:
- **`type`**: Either `'name'` (a named icon from the Studio icon set) or `'path'` (URL path to a custom SVG/image).
- **`value`**: The icon name or path.

## How to Override — Backend Event Subscriber

Subscribe to `pre_response.*` events to modify the `CustomAttributes` before the response is sent to the frontend. Each element type has its own event:

| Element type | Context | Event name | Event class |
|-------------|---------|------------|-------------|
| Data Object | Tree | `pre_response.data_object` | `DataObjectEvent` |
| Data Object | Editor tab | `pre_response.data_object_detail` | `DataObjectDetailEvent` |
| Asset | Tree | `pre_response.asset` | `AssetEvent` |
| Document | Tree | `pre_response.document` | `DocumentEvent` |

> **Important:** To customize the icon everywhere (tree **and** editor tabs), subscribe to **both** the tree event and the detail event. The tree endpoint and the detail/editor endpoint fire separate events.

### Example: Custom Icon and Tooltip for Data Objects (Tree and Editor Tabs)

This subscriber sets a flag icon and an HTML tooltip for all objects of class "Demo" — in both the tree and the editor tab:

```php
<?php
declare(strict_types=1);

namespace App\EventSubscriber;

use Pimcore\Bundle\StudioBackendBundle\DataObject\Event\PreResponse\DataObjectDetailEvent;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Event\PreResponse\DataObjectEvent;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\CustomAttributes;
use Pimcore\Bundle\StudioBackendBundle\Response\ElementIcon;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementIconTypes;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CustomTreeStyleSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            DataObjectEvent::EVENT_NAME => 'handleDataObject',
            DataObjectDetailEvent::EVENT_NAME => 'handleDataObjectDetail',
        ];
    }

    public function handleDataObject(DataObjectEvent $event): void
    {
        $dataObject = $event->getDataObject();

        if ($dataObject->getClassName() !== 'Demo') {
            return;
        }

        $this->applyDemoStyle($event->getCustomAttributes(), $dataObject->getId());
        $event->setCustomAttributes($event->getCustomAttributes());
    }

    public function handleDataObjectDetail(DataObjectDetailEvent $event): void
    {
        $dataObject = $event->getDataObject();

        if ($dataObject->getClassName() !== 'Demo') {
            return;
        }

        $this->applyDemoStyle($event->getCustomAttributes(), $dataObject->getId());
        $event->setCustomAttributes($event->getCustomAttributes());
    }

    private function applyDemoStyle(CustomAttributes $customAttributes, int $id): void
    {
        // Use a named icon from the Studio icon set
        $customAttributes->setIcon(
            new ElementIcon(
                ElementIconTypes::NAME->value,
                'flag'
            )
        );

        // HTML tooltip shown on hover in the tree
        $customAttributes->setTooltip(
            '<b>Demo Object</b><br>ID: ' . $id
        );
    }
}
```

With `autoconfigure: true` in your `services.yaml`, the subscriber is automatically registered — no manual tag needed.

### Example: Custom Icon for Assets

This subscriber sets a star icon for all assets whose filename starts with `important_`:

```php
<?php
declare(strict_types=1);

namespace App\EventSubscriber;

use Pimcore\Bundle\StudioBackendBundle\Asset\Event\PreResponse\AssetEvent;
use Pimcore\Bundle\StudioBackendBundle\Response\ElementIcon;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementIconTypes;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CustomAssetStyleSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            AssetEvent::EVENT_NAME => 'handleAsset',
        ];
    }

    public function handleAsset(AssetEvent $event): void
    {
        $asset = $event->getAsset();

        if (!str_starts_with($asset->getFilename(), 'important_')) {
            return;
        }

        $customAttributes = $event->getCustomAttributes();

        $customAttributes->setIcon(
            new ElementIcon(
                ElementIconTypes::NAME->value,
                'star'
            )
        );

        $customAttributes->setTooltip(
            '<b>' . htmlspecialchars($asset->getFilename()) . '</b>'
            . '<br>Type: ' . $asset->getType()
        );

        $event->setCustomAttributes($customAttributes);
    }
}
```

For a full list of available `pre_response.*` events, see [Additional and Custom Attributes](https://github.com/pimcore/studio-backend-bundle/blob/1.x/doc/03_Extending/01_Additional_and_Custom_Attributes.md).
