# Numeric Editable

## General
The numeric editable is very similar to the [input editable](./16_Input.md), but with special configurations for the use with numbers.

## Configuration

| Name           | Type    | Description                                                                        |
|----------------|---------|------------------------------------------------------------------------------------|
| `maxValue`     | float   | Define a maximum value                                                             |
| `minValue`     | float   | Define a minimum value                                                             |
| `width`        | integer | Width of the field in pixel                                                        |
| `class`        | string  | A CSS class that is added to the surrounding container of this element in editmode |
| `defaultValue` | string  | A default value for the available options **as string**                            |
| `required`     | boolean | set to true to make field value required for publish                               |

## Methods

| Name        | Return      | Description                                                                  |
|-------------|-------------|------------------------------------------------------------------------------|
| `getData()` | int,float   | Value of the numeric field, this is useful to get the value even in editmode |
| `isEmpty()` | boolean     | Whether the editable is empty or not                                         |

## Examples

### Basic Usage

```twig
{{ pimcore_numeric("myNumber") }}
```

Now you can see the **numeric** value in the editmode view 
![Numeric input - editmode](../../img/editables_numeric_simple_editmode.png)

### Advanced Usage

In the following example we're going to use a minimal and maximum value as well as a decimal precision. 

```twig
{{ pimcore_numeric("myNumber", {
		"width": 300,
		"minValue": 0,
		"maxValue": 100,
		"decimalPrecision": 0
	}) 
}}
```

To display the number also in editmode, you can use the method `getData()`

```twig
<p>
    {{ pimcore_numeric("myNumber").getData() }}
</p>
```
