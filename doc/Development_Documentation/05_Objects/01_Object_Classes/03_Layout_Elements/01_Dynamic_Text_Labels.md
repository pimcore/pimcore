# Dynamic Text Labels

Similar to the [CalculatedValue](../../../05_Objects/01_Object_Classes/01_Data_Types/33_Calculated_Value_Type.md) data type,
it is possible to generate the Layout Text dynamically based on the current object and the label's context.
This is an alternative to the static text defined in the class definition.

Let's consider the following example. It states that we want to use a custom renderer class called
*\Website\CustomRenderer* and pass some additional data (*some additional data - 12345679* in this example) to the rendering method.

![Class Definition](../../../img/dynamic_textlabel_1.png)

Here is an example for a rendering class.

```php
<?php

namespace Website;

use Pimcore\Model\DataObject\Concrete;

class CustomRenderer
{
    /**
     * @param $data string as provided in the class definition
     * @param $object Concrete
     * @param $params mixed
     * @return string
     */
    public static function renderLayoutText($data, $object, $params) {
        return "<h1 style='color: #F00;'>Last reload: " . date('c') . '</h1>'
            . '<h2>Additional Data: ' . $data . "</h2>"
            . '<h3>BTW, my fullpath is: ' . $object->getFullPath() . ' and my ID is ' . $object->getId() . '</h3>'
            . var_export($params, true);
    }
}
```

*$data* will contain the additional data from the class definition. In *$params* you will find additional information about the current context.
For example: If the text label lives inside a field collection, *$params* will contain the name of the field collection (and of course the name of the label itself).

The result will be as follows:

![Editmode](../../../img/dynamic_textlabel_2.png)
   
   