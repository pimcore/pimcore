# Checkbox Editable

## Configuration

| Name     | Type    | Description                                                                                                    |
|----------|---------|----------------------------------------------------------------------------------------------------------------|
| `reload` | boolean | Set to true to reload the page in editmode after changing the state.                                           |
| `label`  | string  | a `<label>` which is added in the editmode                                                                     |
| `class`  | string  | A CSS class that is added to the surrounding container of this element in editmode                             |
| `tag`    | string  | A tag name that is used instead of the default `div` for the surrounding container of this element in editmode |

## Methods

| Name          | Return    | Description                                                            |
|---------------|-----------|------------------------------------------------------------------------|
| `isChecked()` | boolean   | Get status of the checkbox.                                            |
| `isEmpty()`   | boolean   | Whether the editable is empty or not (alias of `isChecked()`)          |

## Simple Example

```php
<?= $this->checkbox("myCheckbox"); ?>
```

## Advanced Example

```php
Setting XYZ: <?= $this->checkbox("myCheckbox"); ?>

<?php if($this->checkbox("myCheckbox")->isChecked()): ?>
    <div>
        <?php //do something... ?>
    </div>
<? endif; ?>
```