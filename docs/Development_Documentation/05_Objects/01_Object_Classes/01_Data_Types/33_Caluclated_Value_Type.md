# Calculated Value Datatype (ExtJS6 only)


## General

The calculated-value datatype allows you to calculate attributes based on the value of various other attributes. 
The only data stored is the one in the object's query tables.

Let's have a closer look by walking through a simple example.

Let's suppose we have a very simple class with 2 number fields called xValue and yValue and a calculated-value field 
called ```sum``` placed inside a localizedfields container.

![Calculated Value Configuration](../../../img/classes-datatypes-calculated.png)


The first step is to provide a PHP calculator class implementing at least one static method which computes the result 
for the ```sum``` field. An example is shown below.

The arguments passed into this method is the Pimcore object and the contextual information telling you which 
calculated-value field is affected and where it is located it.

The extent of information differs on which datatype is the owner of the calculated-value field 
(localizedfield, object brick etc.). The details are documented below.

```php
namespace Website;
 
use Pimcore\Model\Object\Concrete;
 
class CalculatorDemo
{
    /**
     * @param $object Concrete
     * @param $context \Pimcore\Model\Object\Data\CalculatedValue
     * @return string
     */
    public static function compute($object, $context) {
        if ($context->getFieldname() == "sum") {
            $language = $context->getPosition();
            return $object->getXValue($language) +  $object->getYValue($language);
        } else {
            \Logger::error("unknown field");
        }
    }
}
```

As we see here the calculator class sums up the x and y values from the corresponding language tab.

It is also possible to provide a different representation for edit mode by providing a second (optional) implementation just for this purpose. An example would be:
```php
/**
 * @param $object
 * @param $context \Pimcore\Model\Object\Data\CalculatedValue
 * @return string
 */
public static function getCalculatedValueForEditMode($object, $context) {
    $language = $context->getPosition();
    $result = $object->getXValue($language) . " + " . $object->getYValue($language) . " = " . self::compute($object, $context);
    return $result;
}
```

The visual outcome would be as follows: 
![Calculated Value Field](../../../img/classes-datatypes-calculated2.png)


## Working with PHP api

Getter methods on the object class are generated as usual. The code to retrieve the values would then be: 
```php
$object = Object_Abstract::getByPath("/demoobject");
$valueDe =  $object->getSum("de");   // => 38
$valueEn =  $object->getSum("en");   // => 11
```

## Context Information for Cacluation Class
As said before, the richness of the context information depends on the location of the calculated-value field.

#### Object (top-level)

| ownerType | "object" |
| fieldName | the name of the calcuated-value field (e.g. ```sum```) |




