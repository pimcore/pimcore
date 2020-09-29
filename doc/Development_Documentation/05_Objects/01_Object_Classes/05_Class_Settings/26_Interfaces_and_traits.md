# Using interfaces and traits
In some cases you need to implement interfaces and overwrite some provided functions.

##### Example
I used the demo project to show the usage of this feature.
Extending cars to have a gearbox function to indidcate which type of shifting is used. This will be done with an interface and a trait to implement the functions.

##### Create the interface
```php
<?php
// src/AppBundle/Model/Product/TransmissionInterface.php

namespace AppBundle\Model\Product;

interface TransmissionInterface
{
    /**
     * @return string|null
     */
    public function getGearboxType(): ?string;

    /**
     * @return int|null
     */
    public function getNumberOfGears(): ?int;
}
```

##### Create the trait
Returns a GearboxType and a number of gears.
```php
<?php
// src/AppBundle/Traits/TransmissionTrait.php

namespace AppBundle\Traits;

trait TransmissionTrait
{
    public function getGearboxType(): ?string
    {
        return "manual";
    }

    public function getNumberOfGears(): ?int
    {
        return 5;
    }
}

```

## Use it with Cars product data
Navigate to the Settings *Settings* -> *Data Objects* -> *Classes* -> *Product Data* -> *Car*

Click on *General Settings* and paste your interface and trait path into `Implements interface(s)` and `Use (traits)`

![Example Screenshot](https://user-images.githubusercontent.com/15780280/94515658-5995cd80-0224-11eb-9992-243036ab3158.png)

Save your changes

It will generate the `implements \AppBundle\Model\Product\TransmissionInterface` and the
`use \AppBundle\Traits\TransmissionTrait;` lines within the DataObject Class.

```php
// var/classes/DataObject/Car.php
...
class Car extends \AppBundle\Model\Product\AbstractProduct implements \AppBundle\Model\Product\TransmissionInterface {

use \AppBundle\Traits\TransmissionTrait;

protected $o_classId = "CAR";
protected $o_className = "Car";
...
```
