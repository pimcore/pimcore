# Area Editable

## General
The area editable is similar to the [areablock](./02_Areablock/README.md) editable, the only difference is that the area bricks are not wrapped 
into a block element, and the editor cannot choose which area is used, this has to be done in the editable configuration in the template.

## Configuration

| Name     | Type    | Description                                                                                   |
|----------|---------|-----------------------------------------------------------------------------------------------|
| `type`   | string  | ID of the brick which should be used in this area                                             |
| `params` | array   | Optional Parameter see [areablock](./02_Areablock/README.md) for details                      |
| `class`  | string  | A CSS class that is added to the surrounding container of this element in editmode            |

## Methods

| Name                | Return             | Description                                                 |
|---------------------|--------------------|-------------------------------------------------------------|
| `getElement($name)` | Document\Editable  | Retrieves an editable from within the actual area           |

## Example

```twig
<div>
{{ pimcore_area("myArea", {"type": "gallery-single-images"}) }}
</div>
```

## Example with Parameters

```twig
<div>
    {{ pimcore_area("myArea", {
        type: "gallery-single-images",
        params: {
            "gallery-single-images": {
                "param1": 123,
            }
        }
    }) }}
</div>
```

Get the params in your brick:

```twig
<div>
    {{ param1 }}
</div>
```

### Accessing Data within an Area Element

Assuming your area uses a brick `gallery-single-images` which contains a `gallery` block (see CMS demo):

```php
<?php
// load document
$document = \Pimcore\Model\Document\Page::getByPath('/en/basic-examples/galleries');

/** @var \Pimcore\Model\Document\Editable\Area $area */
$area = $document->getEditable('myArea');

/** @var \Pimcore\Model\Document\Editable\Block $block */
$block = $area->getElement('gallery');
?>
```

See [Block](./06_Block.md) for an example how to get elements from a block editable.
