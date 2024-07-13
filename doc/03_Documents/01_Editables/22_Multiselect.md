# Multiselect Editable

## General

The Multiselect editable generates a **multiselect** box component in editmode.

## Configuration

| Name     | Type    | Description                                                                        |
|----------|---------|------------------------------------------------------------------------------------|
| `store`  | array   | Key/Value pairs for the available options.                                         |
| `width`  | integer | Width of a generated block in editmode                                             |
| `height` | integer | Height of a generated block in editmode                                            |
| `class`  | string  | A CSS class that is added to the surrounding container of this element in editmode |

## Methods

| Name        | Return  | Description                                     |
|-------------|---------|-------------------------------------------------|
| `getData()` | array   | Returns array of values chosen in the editmode. |
| `isEmpty()` | boolean | Whether the editable is empty or not            |

## Example

The code below renders a multiselectbox in the backend. 
Also, it shows the list of chosen elements in the frontend. 

```twig
{% if editmode %}
    {{ pimcore_multiselect("categories", {
        "width": 200,
        "height": 100,
        "store": [
            ["cars", "Cars"],
            ["motorcycles", "Motorcycles"],
            ["accessories", "Accessories"],
        ]
    }) }}
{% else %}
    <p>{{ "This page is linked to" | trans }}
        {% for categoryKey in pimcore_multiselect("categories").getData %}
            <span>
            {{ categoryKey | trans }}
        </span>
        {% endfor %}
        categories
    </p>
{% endif %}
```

The editmode preview:

![Multiselect editable - editmode](../../img/editables_multiselect_editmode.png)

In the frontend you can find the rendered text with the categories you have chosen in the editmode: 

![Multiselect editable - frontend](../../img/editables_multiselect_frontend.png)
