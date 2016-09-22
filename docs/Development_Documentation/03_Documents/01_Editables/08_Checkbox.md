# Checkbox Editable

## Configuration

| Name     | Type    | Description                                                          |
|----------|---------|----------------------------------------------------------------------|
| `reload` | boolean | Set to true to reload the page in editmode after changing the state. |
| `label`  | string  | a `<label>` which is added in the editmode                           |

## Methods

| Name          | Type      | Description                                                            |
|---------------|-----------|------------------------------------------------------------------------|
| `isChecked()` | boolean   | Get status of the checkbox.                                            |

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