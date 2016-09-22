# Table Editable

## General

The Table editable provides ability to define a table structure.
With that, you can build tables for a frontend application in graphical way. 
Also, the Table editable allows you to add some predefined values.

## Configuration

| Name       | Type    | Description                                                             |
|------------|---------|-------------------------------------------------------------------------|
| `defaults` | array   | Array can have the following properties: rows, cols, data (see example) |
| `width`    | integer | Width of the field in pixel                                             |

## Methods

| Name        | Type  | Description                           |
|-------------|-------|---------------------------------------|
| `getData()` | array | Get the data of the table as array    |
| `isEmpty()` | bool  | Whether the editable is empty or not. |

# Examples

### Basic usage

I added the table editable responsible for additional product attributes in the `website/views/scripts/content/default.php` template file. 
The `defaults` row specify the predefined data and number of columns and rows in an initial stage. 

```php
<h4><?= $this->translate("Product attributes"); ?></h4>
<?= $this->table("productProperties", [
    "width" => 700,
    "height" => 400,
    "defaults" => [
        "cols" => 2,
        "rows" => 3,
        "data" => [
            ["Attribute name", "Value"], // headers line
            ["Color", "Black"],
            ["Size", "Large"],
            ["Availability", "Out of stock"]
        ]
    ]
]); ?>
```

You're able to change columns and predefined data in the edit mode.
Find the effect from the backend, below:

![Table editable rendered in the editmode](../../img/editables_table_editmode.png)

### Processing the data

Sometimes you would need use only the data from a filled table. 
You would just use the `getData` method instead of rendering the whole table html.

```php
<?php if($this->editmode):
echo $this->table("productProperties", [
    "width" => 700,
    "height" => 400,
    "defaults" => [
        "cols" => 2,
        "rows" => 3,
        "data" => [
            ["Attribute name", "Value"], // headers line
            ["Color", "Black"],
            ["Size", "Large"],
            ["Availability", "Out of stock"]
        ]
    ]
]);
else:
    $data = $this->table("productProperties")->getData();
    //do something
endif;
?>
```


The output from `getData`:

```
array(4) {
  [0] => array(3) {
    [0] => string(14) "Attribute name"
    [1] => string(5) "Value"
    [2] => string(18) " Additional column"
  }
  [1] => array(3) {
    [0] => string(5) "Color"
    [1] => string(5) "Black"
    [2] => string(1) " "
  }
  [2] => array(3) {
    [0] => string(4) "Size"
    [1] => string(5) "Large"
    [2] => string(1) " "
  }
  [3] => array(3) {
    [0] => string(12) "Availability"
    [1] => string(12) "Out of stock"
    [2] => string(1) " "
  }
}
```