# Textarea Editable

## General

The textarea editable is very similar to the [Input](./16_Input.md) editable, the only difference is multi-line support.  

## Configuration

| Name               | Type    | Description                                                                           |
|--------------------|---------|---------------------------------------------------------------------------------------|
| `height`           | integer | Height of the textarea in pixel                                                       |
| `width`            | integer | Width of the textarea in pixel                                                        |
| `htmlspecialchars` | boolean | Set to false to get the raw value without HTML special chars like & (default: `true`) |
| `nl2br`            | boolean | Set to true to get also breaks in frontend                                            |
| `placeholder`      | string  | A placeholder that is displayed when the field is empty                               |
| `class`            | string  | A CSS class that is added to the surrounding container of this element in editmode    |
| `required`         | boolean | set to true to make field value required for publish                                  |
| `defaultValue`     | string  | A default value for the available options.                                            |

## Methods

| Name        | Return | Description                           |
|-------------|--------|---------------------------------------|
| `getData()` | array  | Get the value of the textarea         |
| `isEmpty()` | bool   | Whether the editable is empty or not. |

## Example

```twig
<p class="product-description">
    {{ pimcore_textarea("product_description", {
        "nl2br": true,
        "height": 300,
        "placeholder": "Product Description"
    }) }}
</p>
```

In the editmode, you can see the textarea and the predefined `placeholder`.
 
![Product description textarea - editmode](../../img/editable_textarea_editmode_preview.png)
