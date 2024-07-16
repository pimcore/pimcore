# Custom Icons & Tooltips for Documents/Assets & Data Objects

Pimcore allows to dynamically define custom element icons & tooltips in the element tree. In addition, the icon of the editor tab can
be changed.

#### Properties that can be changed
* Element CSS class
* Element icon
* Element icon class
* Tree node tooltip
* Element Text

## How to override the default style
 
The basic idea is to provide one's own implementation of `Pimcore\Model\Element\AdminStyle`.
 
This can be achieved by attaching a listener to the [`AdminEvents::RESOLVE_ELEMENT_ADMIN_STYLE`](https://github.com/pimcore/admin-ui-classic-bundle/blob/1.x/src/Event/AdminEvents.php#L428-L439) event. 

Example:

In `config/services.yaml` add

```yaml
  App\EventListener\AdminStyleListener:
    tags:
      - { name: kernel.event_listener, event: pimcore.admin.resolve.elementAdminStyle, method: onResolveElementAdminStyle }
```

Create AdminStyleListener in EventListeners

```php
<?php

namespace App\EventListener;

class AdminStyleListener
{
    public function onResolveElementAdminStyle(\Pimcore\Bundle\AdminBundle\Event\ElementAdminStyleEvent $event): void
    {
        $element = $event->getElement();
        // decide which default styles you want to override
        if ($element instanceof \App\Model\Product\Car) {
            $event->setAdminStyle(new \App\Model\Product\AdminStyle\Car($element));
        }
    }
}

```

 
### Example: Custom Icon for the Car DataObject

This will change the `Car` icon depending on the car type:

```php
namespace App\Model\Product\AdminStyle;

use Pimcore\Model\DataObject;
use Pimcore\Model\Element\AdminStyle;

class Car extends AdminStyle
{
    protected ElementInterface $element;

    public function __construct(ElementInterface $element)
    {
        parent::__construct($element);

        $this->element = $element;

        if ($element instanceof \App\Model\Product\Car) {
            DataObject\Service::useInheritedValues(true, function () use ($element) {
                if ($element->getObjectType() == 'actual-car') {
                    // setting this to false is necessary for the elementIcon to actually be used
                    $this->elementIconClass = false;
                    $this->elementIcon = '/bundles/pimcoreadmin/img/twemoji/1f697.svg';
                }
            });
        }
    }
}
```

Result:

![Class Icons](../img/classes-icons2.png)


## Example: Custom Tooltips

It is possible to define custom tooltips which are shown while hovering over the element tree.

The example outlines how to provide a custom tooltip for `Car` objects.

```php
    public function getElementQtipConfig(): ?array
    {
        if ($this->element instanceof \App\Model\Product\Car) {
            $element = $this->element;

            return DataObject\Service::useInheritedValues(true, function () use ($element) {
                $text = '<h1>' . $element->getName() . '</h1>';

                $mainImage = $element->getMainImage();
                if ($mainImage) {
                    $thumbnail = $mainImage->getThumbnail("content");
                    $text .= '<p><img src="' . $thumbnail . '" width="150" height="150"/></p>';
                }

                $text .= wordwrap($this->element->getDescription(), 50, "<br>");

                return [
                    "title" => "ID: " . $element->getId() . " - Year: " . $element->getProductionYear(),
                    "text" => $text,
                ];
            });
        }

        return parent::getElementQtipConfig();
    }
```

Result:

![Class Icons](../img/classes-icons3.png)

#### Example: Custom Style for Assets

This will display the modification date and image size as additional information. Besides that, it shows
a different icon for all assets starting with a capital 'C' in their key. 

```php
namespace App\Model\Product\AdminStyle;

use Pimcore\Model\Asset;
use Pimcore\Model\Element\AdminStyle;

class AssetEventStyle extends AdminStyle
{
    public function __construct(ElementInterface $element)
    {
        parent::__construct($element);

        if ($element instanceof Asset\Image) {
            if (strpos($element->getKey(), 'C') === 0) {
                $this->elementIconClass = null;
                $this->elementIcon = '/bundles/pimcoreadmin/img/twemoji/1f61c.svg';
            }

            $this->elementQtipConfig = [
                'title' => 'ID: ' . $element->getId(),
                'text' => 'Path: ' . $element->getFullPath()
                        . '<br>Modified: ' . date('c', $element->getModificationDate())
                        . '<br>Size:  '. $element->getWidth() . 'x' . $element->getHeight() . " px"
            ];
        }
    }
}
```

![Class Icons](../img/asset-tree-custom-icon.png)
