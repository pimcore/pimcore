# Data Object Placeholder
The Data Object Placeholder is used to replace values that are stored in a Data Object.

Valid config parameter:

| Parameter | Description |
| --------- | ----------- |
| callMethod | The method of the data object which should be called (the call is localized) |
| locale | The Locale to use (optional) |
| nl2br | Convert content to HTML (optional) |

### Example Usage
```php
public function objectPlaceholderAction()
{
    $this->disableViewAutoRender();
 
    $text = 'Thank you for the order of "%DataObject(object_id,{"method" : "getName"});"';
    $placeholder = new \Pimcore\Placeholder();
 
    $replaced = $placeholder->replacePlaceholders($text, ['object_id' => 73613, 'locale' => 'de_DE']);
    echo $replaced;
}
```