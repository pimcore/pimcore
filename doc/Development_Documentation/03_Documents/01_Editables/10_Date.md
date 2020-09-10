# Date Editable

### Configuration

| Name     | Type   | Description                                                                        |
|----------|--------|------------------------------------------------------------------------------------|
| `format` | string | A string which describes how to format the date in editmode                       |
| `outputFormat` | string | A string which describes how to format the date in frontend, [see possible formats](https://carbon.nesbot.com/docs/)   (new in v5.6.4)                 |
| `class`  | string | A CSS class that is added to the surrounding container of this element in editmode |

## Methods

| Name          | Return    | Description                                                            |
|---------------|-----------|------------------------------------------------------------------------|
| `isEmpty()`   | boolean   | Whether the editable is empty or not                                   |


## Simple Example

The following code will create a simple date widget in editmode. 
In frontend it will format the date as defined in `format`.

Localization (output-format, ...) is automatically used from the globally registered locale.
Please read the topic [Localization](../../06_Multi_Language_i18n/README.md).

<div class="code-section">

```php
<?= $this->date('date', [
    'format' => 'd m Y',
    'outputFormat' => '%d.%m.%Y'
]); ?>
```

```twig
{{ pimcore_date('myDate', {
    'format': 'd.m.Y',
    'outputFormat': '%d.%m.%Y'
    })
}}
```
</div>
