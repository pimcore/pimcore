# Text Placeholder

The Text Placeholder is used to replace the placeholder with the passed value.

There is no config available for text placeholders. 

### Example Usage
```php
public function textPlaceholderAction(){
     $this->disableViewAutoRender();
 
     $text = 'Hello %Text(firstName); %Text(lastName);!';
     $placeholder = new \Pimcore\Placeholder();
 
     $params = ['firstName' => 'Bart', 'lastName' => 'Simpson'];
 
     $replaced = $placeholder->replacePlaceholders($text, $params);
     echo $replaced; //Will be: Hello Bart Simpson!
}
```
