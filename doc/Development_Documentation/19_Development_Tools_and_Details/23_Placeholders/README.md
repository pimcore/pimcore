# Placeholders

Pimcore Placeholders are useful when you have a text (e.g. a wysiwyg editor) and you want to embed 
dynamic values within this text.

Most of the time you will use Placeholders in combination with Email Documents and the `Pimcore\Mail`
Class.

The Syntax of a Placeholder is `%PLACEHOLDERNAME(PARAMETERKEY,JSON-CONFIG);`

* `PLACEHOLDERNAME`: This is the last Part of the Placeholder class name 
E.g.: To use the Placeholder `Pimcore\Placeholder\DataObject` you would type `%DataObject(key,params);`
* `PARAMETERKEY`: This is the key of the parameter-array that you pass as second parameter to the 
"replacePlaceholders" method.
* `JSON-CONFIG`: You can pass a Json-Config to the Placeholder. (It depends on the Placeholder if the 
config is used)


### Example Usage
Lets assume you have a text `"Thank you for the order of "PRODUCTNAME""` and you want replace 
`"PRODUCTNAME"` with the real Product name.

The following code-snippet replaces the Placeholder `%DataObject(...);` with the Product name.

```php
$text = 'Thank you for the order of "%DataObject(object_id,{"method" : "getName"});"';
$placeholder = new \Pimcore\Placeholder();
 
echo $replaced = $placeholder->replacePlaceholders($text, ['object_id' => 73613, 'locale' => 'de_DE']);
```

### More Detailed:
The `replacePlaceholders()` method accepts 3 parameter:
* First one: The text that contains the placeholders which should be replaced or a document 
(the document is rendered to HTML)
* Second: The dynamic parameter for replacement
* Third: A document. This parameter is useful when you pass a plain text and you need values from a 
document in the placeholder object. In the placeholder object you can access the document with 
`$this->getDocument()`

When you call the `replacePlaceholders()` method, it determines the placeholders and creates the 
corresponding placeholder object.


### Implemented Placeholders
Pimcore ships with two placeholder implementations. 
* [Object Placeholder](./01_Object_Placeholder.md)
* [Text Placeholder](./02_Text_Placeholder.md)


### Create your own Placeholders

You can also create your own placeholder. By default it is assumed that your individual placeholders 
are located in `/AppBundle/Placeholder/YourPlaceholder.php`.

If you want to change the default placeholder location you can call 
`Pimcore\Placeholder::addPlaceholderClassPrefix(...)` to change the location (make sure you set them 
before any individual placeholder is called).
E.g. for `\Pimcore\Placeholder::addPlaceholderClassPrefix('MyBundle\Tool\');` -> the placeholder location 
would be `/MyBundle/Tool/YourPlaceholder.php`

Now we assume that we want to create a simple Placeholder that replaces the data of a person.

Therefore we create a new class that extends `Pimcore\Placeholder\AbstractPlaceholder`. Our class has 
to implement at least 2 methods `getTestValue()` and `getReplacement()`.

* The `getReplacement()` method has to return the value that should be used for the replacement of 
the placeholder.
* The `getTestValue()` method should return a value for testing purpose. 
Note: The `getTestValue()` is currently not in use but in upcoming releases it will be used for test 
replacements.

```php
<?php
  
namespace AppBundle\Placeholder;
use Pimcore\Placeholder;
class Person extends Placeholder\AbstractPlaceholder
{
 
    public function getTestValue()
    {
        $value = '';
        switch ($this->getPlaceholderKey()) {
            case 'firstName' :
                $value = 'Homer';
                break;
            case 'lastName' :
                $value = 'Simpson';
                break;
            //...
        }
        return '<span class="testValue">' . $value . ' </span>';
    }
 
    public function getReplacement()
    {
        $value = $this->getValue();
 
        //$document would contain the document that we passed as third parameter to the replacePlaceholders() method
        $document = $this->getDocument();
 
        switch ($this->getPlaceholderKey()) {
            case 'firstName' :
                return ucfirst($value);
                break;
            case 'lastName'  :
                return ucfirst($value);
                break;
            case 'salutation' :
                if ($value == 'mr') {
                    return 'Dear mr.';
                } elseif ($value == 'mrs') {
                    return 'Dear mrs.';
                }
                break;
        }
    }
}
```

##### Example Usage

```php
public function ownPlaceholderAction(){
    $text = '%Person(salutation); %Person(firstName); %Person(lastName); thank you for your order.';
    $placeholder = new \Pimcore\Placeholder();
 
    $params = ['firstName' =>'Pim', 'lastName' => 'Core', 'salutation' => 'mr'];
 
    $replaced = $placeholder->replacePlaceholders($text,$params,Document::getById(1));
    echo $replaced;
}
```

#### Available Getters
As all Placeholders must extend the class `Pimcore\Placeholder\AbstractPlaceholder` there are some getters you can use in 
your Placeholders.

| Method | Description |
| ------ | ----------- |
| `getEmptyValue()` | If the `getReplacement()` method returns an empty value the `getEmptyValue()` is called and the value which is returned by the `getEmptyValue()` method is used for the replacement. |
| `getLocale()` / `getLanguage()` | Returns the current locale / language |
| `getParam($key)` | Returns a specific parameter form the array that you passed to the `replacePlaceholders()` as second parameter. |
| `getParams()` | Returns the array that you passed to the `replacePlaceholders()` as second parameter. |
| `getPlaceholderConfig()` | Returns the JSON-Config of the placeholder (if it was set), otherwise returns an empty JSON-Config object |
| `getPlaceholderKey()` | Returns the key from the dynamic parameters. E.g. If you take a look at the "Create your own Placeholder" example. When the placeholder `"%Person(salutation);"` is processed the key would be "salutation" When the placeholder `"%Person(firstName);"` is processed the key would be "firstName" ... |
| `getPlaceholderString()` | Returns the placeholder string (raw text) |
| `getValue()` | Returns the value from the dynamic parameter. |
