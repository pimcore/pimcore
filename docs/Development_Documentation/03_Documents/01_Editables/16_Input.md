# Input Editable

## General

The input editable is a fixed place when you can put your text headlines, paragraphs and other values. 
An administration user is not able to change any style properties in the editmode. 

## Configuration

| Name             | Type    | Configuration                                                                         |
|------------------|---------|---------------------------------------------------------------------------------------|
| width            | integer | Width of the input in editmode (in pixels)                                            |
| htmlspecialchars | boolean | Set to false to get the raw value without HTML special chars like & (default to true) |
| nowrap           | boolean | set to false to disable the automatic line break                                      |
| class            | string  | a css class that is added to the element only in editmode                             |
| placeholder      | string  | a placeholder that is displayed when the field is empty                               |

## Accesible properties

| Name | Type   | Description                                                           |
|------|--------|-----------------------------------------------------------------------|
| text | string | Value of the input, this is useful to get the value even in editmode. |

## Example 

### Basic usage 

```php
<h2>
 <?php echo $this->input("myHeadline"); ?>
</h2>
```

The code generates an editable area which you can fill with the text, see the picture:

![Inpute preview in the backend](../../img/input_backend_preview.png)

### Advanced usage

You could also specify other parameters, like the size:

```php
<h2>
 <?php echo $this->input("myHeadline", ["width" => 540]); ?>
</h2>
```

## Validation

In the input editable a simple validation is implemented. 
To validate the input you have to add `validator` parameter to the configuration array. 

```php
<h2>
    <?php echo $this->input("myHeadline", [
        "validator" => new Zend_Json_Expr('
            function(value){
              return value.match(/\d.*/) !== null;
            }'
        )
    ]); ?>
</h2>
```

> At the moment, the validation has **only visual effect**, user can still save an incorrect value. 
