# Date

## Basic usage

The following code will create a simple date widget in editmode. 
In frontend it will output the date as defined in **output**.

Localization (output-format, ...) is automatically used from the global locale, which is either defined by the 
underlying document or in your code ( `$this->setLocale("de_AT")` in your action ), for more information, 
please read the topic [Pimcore localization](../../06_Multi_Language_i18n/README.md).

## Simple example
```php
<?php echo $this->date("myDate", [
     "format" => "d.m.Y"
]); ?>
```

### Configuration

| Name   | Type   | Description                                                  |
|--------|--------|--------------------------------------------------------------|
| format | string | A string which describes how to output the date. (see below) |

## List of supported formats

The list of all currently supported formats ou can find in [the PHP manual](http://php.net/manual/en/function.date.php#function.date).

[comment]: #TODOtableOfDates

## Accesible properties

| Name         | Type   | Description                                                                             |
|--------------|--------|-----------------------------------------------------------------------------------------|
| data         | mixed  | Value of the data field, this is useful to get the value even in editmode.              |
| dataEditmode | string | Value of the data field as timestamp, this is useful to get the value even in editmode. |