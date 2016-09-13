# Checkbox

## Configuration

| Name   | Type    | Description                                                          |
|--------|---------|----------------------------------------------------------------------|
| reload | boolean | Set to true to reload the page in editmode after changing the state. |
| label  | string  | a `<label>` which is added in the editmode                           |

## Accessible Methods & Types

| Name        | Type      | Description                                                            |
|-------------|-----------|------------------------------------------------------------------------|
| value       | boolean   | Status of the checkbox.                                                |
| isChecked() | boolean   | Get status of the checkbox.                                            |

## Simple example

```php
<?php echo $this->checkbox("myCheckbox"); ?>
```

## Advanced example

```php
<?php if($this->checkbox("myCheckbox")->isChecked()): ?>

<div>
    <?php //do something... ?>
</div>

<? endif; ?>
```