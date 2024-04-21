# Select Editable

## General

The select editable generates select-box component in Editmode.

## Configuration

| Name           | Type     | Description                                                                                                                               |
|----------------|----------|-------------------------------------------------------------------------------------------------------------------------------------------|
| `store`        | array    | Key/Value pairs for the available options.                                                                                                |
| `reload`       | bool     | Set true to reload the page in editmode after selecting an item                                                                           |
| `width`        | integer  | Width of the select box in pixel                                                                                                          |
| `class`        | string   | A CSS class that is added to the surrounding container of this element in editmode                                                        |
| `defaultValue` | string   | A default value for the available options. Note: This value needs to be saved before calling getData() or use setDataFromResource().      |
| `required`     | boolean  | (default: false) set to true to make field value required for publish                                                                     |
| `editable`     | boolean  | (default: false) set to true to allow custom option                                                                                       |

## Methods

| Name        | Return | Description                                                           |
|-------------|--------|-----------------------------------------------------------------------|
| `getData()` | string | Value of the select, this is useful to get the value even in editmode |
| `isEmpty()` | bool   | Whether the editable is empty or not.                                 |

## Examples

### Basic Usage

The code below shows a select box in editmode,
in the frontend preview you will see simply the value of the chosen option.

```twig
{% if editmode %}
    {{ pimcore_select("valid_for", {
            "store": [
                ["one-month", "One month"],
                ["three-months", "Three months"],
                ["unlimited", "Unlimited"]
            ],
            "defaultValue": "unlimited"
        }) }}
{% else %}
    <p>
        {{ "Something is valid for" | trans }}:{{ pimcore_select("valid_for").getData() | trans  }}
    </p>
{% endif %}
```

Editmode:
![Select editable in editmode](../../img/editables_select_editmode_preview.png)

Frontend:
![Select editable in frontend](../../img/editables_select_frontend_preview.png)

### Preselect the option
You can *_preselect_* an option in your select editable by using `setDataFromResource()`

```twig
{% if editmode %}
    {% if pimcore_select('valid_for').isEmpty() %}
        {% do pimcore_select('valid_for').setDataFromResource('unlimited') %}
    {% endif %}
    
    ...
    
{% endif %}
```
