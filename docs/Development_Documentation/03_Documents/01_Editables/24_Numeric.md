# Numeric Editable

## General
The numeric editable is like a normal textfield but with special configurations for numbers.

## Configuration

| Name       | Type    | Description                 |
|------------|---------|-----------------------------|
| `maxValue` | float   | Define a maximum value      |
| `minValue` | float   | Define a minimum value      |
| `width`    | integer | Width of the field in pixel |

## Methods

| Name        | Return      | Description                                                                  |
|-------------|-------------|------------------------------------------------------------------------------|
| `getData()` | int|float   | Value of the numeric field, this is useful to get the value even in editmode |
| `isEmpty()` | boolean  | Whether the editable is empty or not                                            |

## Examples

### Basic usage

```php
<?= $this->numeric("myNumber"); ?>
```


Now you can see the **numeric** input in the Editmode view 
![Numeric input - editmode](../../img/editables_numeric_simple_editmode.png)

### Advanced usage

You can also, specify the values range and the decimal precision:

```php
<?= $this->numeric("myNumber", [
    "width" => 300,
    "minValue" => 0,
    "maxValue" => 100,
    "decimalPrecision" => 0
]); ?>
```

To show the number, just use the number property:

```php
<p>
    <?= $this->numeric("myNumber")->number; ?>
</p>
```