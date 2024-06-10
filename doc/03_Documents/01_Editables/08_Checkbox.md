# Checkbox Editable

## Configuration

| Name     | Type    | Description                                                                        |
|----------|---------|------------------------------------------------------------------------------------|
| `reload` | boolean | Set to true to reload the page in editmode after changing the state.               |
| `label`  | string  | a `<label>` which is added in the editmode                                         |
| `class`  | string  | A CSS class that is added to the surrounding container of this element in editmode |

## Methods

| Name          | Return    | Description                                                            |
|---------------|-----------|------------------------------------------------------------------------|
| `isChecked()` | boolean   | Get status of the checkbox.                                            |
| `isEmpty()`   | boolean   | Whether the editable is empty or not (alias of `isChecked()`)          |

## Simple Example

```twig
{{ pimcore_checkbox("myCheckbox") }}
```


## Advanced Example

```twig
Setting XYZ: {{ pimcore_checkbox("myCheckbox") }}

{% if pimcore_checkbox("myCheckbox").isChecked() %}
    <div>
        {{ dump("do something") }}
    </div>
{% endif %}
```
