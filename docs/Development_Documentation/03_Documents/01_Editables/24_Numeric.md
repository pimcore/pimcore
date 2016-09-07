# Numeric

## General
The numeric editable is like a normal textfield but with special configurations for numbers.

## Configuration

| Name     | Type    | Description                 |
|----------|---------|-----------------------------|
| maxValue | float   | Define a maximum value      |
| minValue | float   | Define a minimum value      |
| width    | integer | Width of the field in pixel |

## Accessible properties

| Name       | Type      | Description                                                                  |
|------------|-----------|------------------------------------------------------------------------------|
| number     | float     | Value of the numeric field, this is useful to get the value even in editmode |

## Examples

### Basic usage

```php
<?php echo $this->numeric("myNumber"); ?>
```

<div class="inline-imgs">

Now you can see the **numeric** input in the Editmode view 
![Numeric input - editmode](../../img/editables_numeric_simple_editmode.png)

</div>

### Advanced usage

You can also, specify the values range and the decimal precision:

```php
<?php echo $this->numeric("myNumber", [
    "width" => 300,
    "minValue" => 0,
    "maxValue" => 100,
    "decimalPrecision" => 0
]); ?>
```

To show the number, just use the number property:

```php
<p>
    <?php echo $this->numeric("myNumber")->number; ?>
</p>
```