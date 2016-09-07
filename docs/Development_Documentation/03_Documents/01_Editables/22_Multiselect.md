# Multiselect

## General

The Multiselect implemention in documents.
The Multiselect editable generates **multiselect** box component in Editmode, 
next you can use those values in the application.

## Configuration

| Name   | Type    | Description                                |
|--------|---------|--------------------------------------------|
| store  | array   | Key/Value pairs for the available options. |
| width  | integer | Width of a generated block in editmode     |
| height | integer | Height of a generated block in editmode    |

## Available methods

| Name      | Type  | Description                                     |
|-----------|-------|-------------------------------------------------|
| getData() | array | Returns array of values chosen in the editmode. |

## Example

The code below renders multiselectbox in the backend. Also, shows the list of choosen elements on the frontend. 

```php
<?php if($this->editmode): ?>

<?php echo $this->multiselect("categories", [
    "width" => 200,
    "height" => 100,
    "store" => [
        ["cars", "Cars"], //the first array element is a key, the second is a label rendered in editmode
        ["motorcycles", "Motorcycles"],
        ["accessories", "Accessories"] 
    ]
]) ?>

<?php else: ?>
<p><?php echo $this->translate("This page is linked to"); ?>:
    <?php foreach($this->multiselect("categories")->getData() as $categoryKey): ?>

        <span>
            <?php echo $this->translate($categoryKey); ?>
        </span>

    <?php endforeach; ?>
    categories
</p>
<?php endif; ?>
```

The editmode preview:

![Multiselect editable - editmode](../../img/editables_multiselect_editmode.png)

In the frontend you can find simple text with categories which you chose.

![Multiselect editable - frontend](../../img/editables_multiselect_frontend.png)
