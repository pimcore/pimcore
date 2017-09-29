# Price Systems

In terms of pricing, the E-Commerce Framework has the concept of Price Systems. These Price Systems are responsible for 
retrieving or calculating prices and returning so called `PriceInfo` objects which contain the calculated prices. 
Each product can have its own Price System. 

So very complex pricing structures and different price sources can be integrated into the system quite easily.

In terms of product availabilities and stocks, the very similar concept of Availability Systems is used.


## Configuration of Price Systems

A price system is a class implementing `Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\IPriceSystem` which is defined
as service and registered with a name in the `pimcore_ecommerce_framework.price_systems` configuration tree. The framework
already ships with a number of [concrete implementations](https://github.com/pimcore/pimcore/tree/master/pimcore/lib/Pimcore/Bundle/EcommerceFrameworkBundle/PriceSystem)
which you can use as starting point.

There are 3 places where the configuration of Price Systems takes place: 

- **Product class**: Each product has to implement the method `getPriceSystemName()` which returns the name of its 
  Price System. 
- **Service definition**: Each price system must be registered as service
- **Configuration**: The `price_systems` section maps price system names to service IDs  


The product class returns the name of a price system:

```php
<?php

class MyProduct implements \Pimcore\Bundle\EcommerceFrameworkBundle\Model\ICheckoutable
{
    public function getPriceSystemName()
    {
        return 'foo';
    }
}
```

Each price system must be defined as service (either a service defined by core configuration or your custom services):

```
# services.yml
services:
    # defines a completely custom price system as service
    # arguments depend on your implementation
    App\Ecommerce\PriceSystem\CustomPriceSystem:
        arguments:
            - 'bar'
            
    # this reuses a core price system, but defines a new service which sets custom options
    # on the price system (a custom price attribute). available options vary by implementation
    app.custom_attribute_price_system:
        class: Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\AttributePriceSystem
        arguments:
            - '@pimcore_ecommerce.pricing_manager'
            - '@pimcore_ecommerce.environment'
            - { attribute_name: 'customPriceField' }
```


The `price_systems` configuration maps names to service IDs:

```
pimcore_ecommerce_framework:
    # defines 3 price systems
    price_systems:
        # the attribute price system is already defined in core price_systems.yml service definition
        default:
            id: Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\AttributePriceSystem
       
        foo:
            id: App\Ecommerce\PriceSystem\CustomPriceSystem
            
        bar:
            id: app.custom_attribute_price_system

```

> The simplest price system is [`Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\AttributePriceSystem`](https://github.com/pimcore/pimcore/blob/master/pimcore/lib/Pimcore/Bundle/EcommerceFrameworkBundle/PriceSystem/AttributePriceSystem.php) 
> which reads the price from an attribute of the product object. For implementing custom price systems have a look at method comments 
> of [`\Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\IPriceSystem`](https://github.com/pimcore/pimcore/blob/master/pimcore/lib/Pimcore/Bundle/EcommerceFrameworkBundle/PriceSystem/IPriceSystem.php) 
> and the implementations of the existing price systems. 


## Getting and Printing Prices
Once the Price Systems are set up correctly, working with prices should be quite easy. Each product has the method 
`getOSPrice()` which returns a `\Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\IPrice` object with the price of 
the product. 

Internally the product gets its Price System and starts the price calculation to get the price. 

When the price system returns a custom `PriceInfo` object (e.g. with additional stock prices, customer specific prices etc.), 
this `PriceInfo` can be retrieved by calling `getOSPriceInfo()` method of the product object. 

A sample for printing the price on a product detail page is: 

```php
<?php ?>
<p class="price">
   <span><?= $this->product->getOSPrice() ?></span>
</p>
```
