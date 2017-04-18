# Checkout Steps

For each checkout step (e.g. delivery address, delivery date, ...) there has to be a concrete checkout step implementation.
This implementation is responsible for storage and loading of necessary checkout data for each step. It needs to extend 
`\Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\AbstractStep` and implement 
`\Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\ICheckoutStep`. 

Following methods have to be implemented: 
* `commit($data)`: Is called when step is finished and data needs to be saved. 
* `getData()`: Returns saved data for this step.
* `getName()`: Returns name of the step. 


## Configuration of Checkout Steps: 
See [EcommerceFrameworkConfig.php](https://github.com/pimcore/pimcore/blob/master/pimcore/lib/Pimcore/Bundle/EcommerceFrameworkBundle/install/EcommerceFrameworkConfig_sample.php#L86-L86)
for checkout step configuration.  


## Sample Implementation of a Checkout Step:
```php
<?php

namespace Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager;

/**
 * Class \Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\DeliveryAddress
 *
 * sample implementation for delivery address
 */
class DeliveryAddress extends AbstractStep implements ICheckoutStep
{
    /**
     * namespace key
     */
    const PRIVATE_NAMESPACE = 'delivery_address';

    /**
     * @return string
     */
    public function getName()
    {
        return 'deliveryaddress';
    }

    /**
     * sets delivered data and commits step
     *
     * @param  $data
     *
     * @return bool
     */
    public function commit($data)
    {
        $this->cart->setCheckoutData(self::PRIVATE_NAMESPACE, json_encode($data));

        return true;
    }

    /**
     * returns saved data of step
     *
     * @return mixed
     */
    public function getData()
    {
        $data = json_decode($this->cart->getCheckoutData(self::PRIVATE_NAMESPACE));

        return $data;
    }
}

```

## Working with Steps: 
```php
<?php

$manager = Factory::getInstance()->getCheckoutManager($cart);
$step = $manager->getCheckoutStep("deliveryaddress");
$address = new stdClass();
//fill address
$manager->commitStep($step, $address);
 
$step = $manager->getCheckoutStep("deliverydate");
$manager->commitStep($step, $data);
$cart->save();
```
