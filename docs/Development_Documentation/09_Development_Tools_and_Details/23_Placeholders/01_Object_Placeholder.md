# Object Placeholder
The Object Placeholder is used to replace values that are stored in a Object.

Valid config parameter:

| Parameter | Description |
| --------- | ----------- |
| callMethod | The method of the object which sould be called (the call is localized) |
| locale | The Locale to use (optional) |

### Example Usage
```php
public function objectPlaceholderAction()
{
    $this->disableViewAutoRender();
 
    $text = 'Thank you for the order of "%Object(object_id,{"method" : "getName"});"';
    $placeholder = new \Pimcore\Placeholder();
 
    $replaced = $placeholder->replacePlaceholders($text, ['object_id' => 73613, 'locale' => 'de_DE']);
    echo $replaced;
}
```