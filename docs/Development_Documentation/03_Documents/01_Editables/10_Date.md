# Date Editable

### Configuration

| Name     | Type   | Description                                                                        |
|----------|--------|------------------------------------------------------------------------------------|
| `format` | string | A string which describes how to output the date. (see below)                       |
| `class`  | string | A CSS class that is added to the surrounding container of this element in editmode |

## Methods

| Name          | Return    | Description                                                            |
|---------------|-----------|------------------------------------------------------------------------|
| `isEmpty()`   | boolean   | Whether the editable is empty or not                                   |

## List of Supported Formats

The list of all currently supported formats ou can find in [the PHP manual](http://php.net/manual/en/function.date.php#function.date).

[comment]: #TODOtableOfDates


## Simple Example

The following code will create a simple date widget in editmode. 
In frontend it will format the date as defined in `format`.

Localization (output-format, ...) is automatically used from the globally registered locale.
Please read the topic [Localization](../../06_Multi_Language_i18n/README.md).

```php
<?= $this->date("myDate", [
     "format" => "%d.%m.%Y"
]); ?>
```

