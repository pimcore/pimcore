# Area editable

## General
The area editable is similar to the [areablock](./02_Areablock/README.md) editable, the only difference is that the area bricks are not wrapped 
into a block element, and the editor cannot choose which area is used, this has to be done at the editable configuration in the template.

## Configuration

| Name   | Type   | Description                                                    |
|--------|--------|----------------------------------------------------------------|
| type   | string | ID of the brick which should be used in this area              |
| params | array  | Optional Parameter see [areablock](./02_Areablock/README.md) for details |

## Example

```php
<div style="margin: 20px;">
<?php echo $this->area("myArea", ["type" => "nameofbrick"]); ?>
</div>
```

## Example with params

```php
<div style="margin: 20px;">
    <?php echo $this->area("myArea", [
        "type" => "nameofbrick",
        "params" => [
            "nameofbrick" => [
                "param1" => 123
            ]
        ]
    ]); ?>
</div>
```

Get the params in your brick:

```php
<div style="margin: 20px;">
    <?php echo $this->param1; ?>
</div>
```