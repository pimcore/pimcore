# Calculate with Prices

As floating point numbers (`float`, `double`) are not able to represent numbers exactly (see [here](http://floating-point-gui.de/)
if you want to know details), and exact numbers are a strict demand to e-commerce applications the E-Commerce Framrwork
uses [`Decimal`](https://github.com/pimcore/pimcore/blob/master/pimcore/lib/Pimcore/Bundle/EcommerceFrameworkBundle/Type/Decimal.php)
value objects instead of floats to represent prices. These value objects internally store the represented value as integer
by defining a fixed amount of supported digits (so-called `scale`) after the comma and by multiplying the actual value
with the given scale on construction. The scale is set to 4 by default, but can be changed globally in the `pimcore_ecommerce_framework.decimal_scale`
config entry.

An example: Given a scale of 4, a `Decimal` will internally represent a number of `123.45` as `1234500` by calculating
`123.45 * 10^4 = 1234500`. 
 
To calculate with these values, the `Decimal` class exposes methods like `add()`, `sub()`, `mul()`, `div()` and others
to run calculations without having to deal with the internal scale representation. For details see the [Decimal class definition](https://github.com/pimcore/pimcore/blob/master/pimcore/lib/Pimcore/Bundle/EcommerceFrameworkBundle/Type/Decimal.php)
and the corresponding [test](https://github.com/pimcore/pimcore/blob/master/pimcore/tests/ecommerce/Type/DecimalTest.php)
which contains a lot of usage examples and describes the `Decimal` behaviour quite well.

> **Important**: The `Decimal` is designed as *immutable* value object. Every operation yields a *new* instance of a `Decimal`
  representing the new value. Please be aware that this needs to be considered when calculating with `Decimal`s and/or 
  when extending the `Decimal` class with custom functionality.
  
On the DB side, all E-Commerce Framework class definitions supporting price values were updated to store prices as [`DECIMAL`](https://dev.mysql.com/doc/refman/5.7/en/precision-math-decimal-characteristics.html)
data type. This means, when fetching a price value from an ecommerce-object (e.g.) an order, it will be a string instead
of a `float`. This string can directly be passed to the `Decimal` value object's `create()` method. 

A usage example:
  
```php
<?php

use Pimcore\Bundle\EcommerceFrameworkBundle\Type\Decimal;

$a = Decimal::create(10);
$b = Decimal::create(20);
$c = $a->add($b);

var_dump($c->asString()); // 10.0000 as the default string representation contains all digits depending on the scale

// regarding immutability:
$a->add($b);

// $a is still 10 and will never be changed - the add() method returns a new object ($c in the example above)
$a->equals(Decimal::create(30)); // false
$a->equals(Decimal::create(10)); // true
```

In most cases, you should be able to build a `Decimal` object by using the static `create()` method which is able to build
a `Decimal` from strings, integers and floating point numbers. If needed, you can also make use of a number of `from*` methods
directly exposing costruction logic for a specific type (e.g. `fromDecimal(Decimal $decimal)`).


## Rounding logic

The `Decimal` object will try to avoid rounding and calculating with floats if possible. If you pass a string to the `create()`
method, it will try to convert the numeric string to an integer with string operations before falling back to converting
it into a float which is rounded.

Examples (given a scale of 4):

* `Decimal::create('123')` will just add 4 chars of `0` to the string and cast it to an integer (no floats involved) - results
  in `1230000`
* `Decimal::create('123.45')` will move the dot to the right by 2 places and add 2 chars of `0` - results in `1234500`

However, if the given value exceeds the maximum scale or is a float value, the `create()` method needs to fall back to 
float calculations and rounding to generate the integer representation:

* `Decimal:create('123.1234567')` with a string value will fall back to float calculations as the amount of digits after
  the comma exceed the scale of `4`. The value is first multiplied with `10^4`, resulting in PHP casting the string to float.
  The float `1231234.567` is then passed to PHP's `round()` function with a `precision` parameter of `0` and a default rounding
  mode of `PHP_ROUND_HALF_EVEN`, resulting in an integer representation of `1231235` (note the rounding on the last digit).
* `Decimal:create(123.1234567)` with a float value will have the same behaviour as above (skipping the string conversion). 

You can influence how rounding is applied by specifying the `$roundingMode` parameter on the `create()` method:

```
<?php

use Pimcore\Bundle\EcommerceFrameworkBundle\Type\Decimal;

var_dump(Decimal::create('123.55555', 4, PHP_ROUND_HALF_DOWN)->asString()); // 123.5555
var_dump(Decimal::create('123.55555', 4, PHP_ROUND_HALF_UP)->asString());   // 123.5556
```

Please be aware that as rounding is applied only when exceeding the scale, the following can happen: 

```
<?php

use Pimcore\Bundle\EcommerceFrameworkBundle\Type\Decimal;

// supported by scale -> 1.9999
var_dump(Decimal::create('1.9999')->asString());

// rounding is applied -> 2.0000
var_dump(Decimal::create('1.99999')->asString());
``` 

This is important to know as it could lead to unexpected calculation results. The `DECIMAL` mysql data type has the same
behaviour - if you calculate with `Decimal`s at a higher scale you still need to update your class definitions to match
that scale as otherwise the same rounding logic will be applied by the database. However, while doing calculations (e.g.
tax or discount calculations, reports), you're free to calculate at a higher scale by passing the `scale` parameter to the
`create()` method and just setting the resulting value to the scale represented on the DB. Example:


```php
<?php

use Pimcore\Bundle\EcommerceFrameworkBundle\Type\Decimal;

// operate at a high scale supporting 8 digits after the comma
$a = Decimal::create(10, 8);

// do calculations
$b = $a->toPercentage(15);

// create a representation with a lower scale - at this point rounding logic will be applied
$result = $b->withScale(4);

// an order object
$order->setTotalPrice($result->asString());
$order->save();
```


## Extending the Decimal class

You're free to add custom calculation logic to the Decimal class, but please make sure every operation returns a new instance
holding the calculated value. An object can never change its internal state (value and scale) after construction!
 

## Caveats/aspects good to know

* In order to have sufficient number of digits in integer datatype, your system should run on 64 bit infrastructure. 
  On 32 bit systems, you would be able to handle prices up to 214,748.3647 only (since max int value with 32 bit is 2,147,483,647).
  The `Decimal` implementation contains logic to throw an exception on an integer overflow in case the used numbers are
  too large/small.
