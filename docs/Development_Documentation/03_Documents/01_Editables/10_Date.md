# Date Editable

## Basic Usage

The following code will create a simple date widget in editmode. 
In frontend it will format the date as defined in `format`.

Localization (output-format, ...) is automatically used from the globally registered locale.
Please read the topic [Localization](../../06_Multi_Language_i18n/README.md).

## Simple Example
```php
<?= $this->date("myDate", [
     "format" => "d.m.Y"
]); ?>
```

### Configuration

| Name     | Type   | Description                                                  |
|----------|--------|--------------------------------------------------------------|
| `format` | string | A string which describes how to output the date. (see below) |

## Methods

| Name          | Return    | Description                                                            |
|---------------|-----------|------------------------------------------------------------------------|
| `isEmpty()`   | boolean   | Whether the editable is empty or not                                   |

## List of supported formats

The list of all currently supported formats ou can find in [the PHP manual](http://php.net/manual/en/function.date.php#function.date).

[comment]: #TODOtableOfDates

## Accesible properties

| Name         | Type   | Description                                                                             |
|--------------|--------|-----------------------------------------------------------------------------------------|
| data         | mixed  | Value of the data field, this is useful to get the value even in editmode.              |
| dataEditmode | string | Value of the data field as timestamp, this is useful to get the value even in editmode. |