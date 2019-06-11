# Working with Availabilities

For availabilities there is a similar concepts like the [PriceSystems](./09_Working_with_Prices/01_Price_Systems_and_getting_Prices.md) 
for prices - which is called *Availability Systems*.

The Availability Systems are responsible for retrieving or calculating availabilities of a product and returning a so called
`AvailabilityInfo` object which contains all the availability information.
Each product can have its own Availability System. 

## Configuration of Availability Systems

A availability system is a class implementing `Pimcore\Bundle\EcommerceFrameworkBundle\AvailabilitySystem\AvailabilitySystemInterface` which 
is defined as service and registered with a name in the `pimcore_ecommerce_framework.availability_systems` configuration tree. 

Currently the framework ships only with a [sample implementation](https://github.com/pimcore/pimcore/blob/master/bundles/EcommerceFrameworkBundle/AvailabilitySystem/AvailabilitySystem.php#L20)
which you can use as starting point.

There are 3 places where the configuration of Availability Systems takes place: 

- **Product class**: Each product has to implement the method `getAvailabilitySystemName()` which returns the name of its 
  Availability System. 
- **Service definition**: Each Availability System must be registered as service
- **Configuration**: The `availability_systems` section maps Availability System names to service IDs  


The product class returns the name of an Availability System:

```php
<?php

class MyProduct implements \Pimcore\Bundle\EcommerceFrameworkBundle\Model\CheckoutableInterface
{
    public function getAvailabilitySystemName()
    {
        return 'myAvailabilitySystem';
    }
}
```

Each Availability System must be defined as service (either a service defined by core configuration or your custom services):

```
# services.yml
    _defaults:
        public: false
        autowire: true
        autoconfigure: true

    #
    # AVAILABILITY SYSTEMS
    #
    Pimcore\Bundle\EcommerceFrameworkBundle\AvailabilitySystem\AvailabilitySystem: ~
```


The `availability_systems` configuration maps names to service IDs:

```
pimcore_ecommerce_framework:
    # defines 3 availability systems
    availability_systems:
        # the attribute price system is already defined in core price_systems.yml service definition
        default:
            id: Pimcore\Bundle\EcommerceFrameworkBundle\AvailabilitySystem\AvailabilitySystem
       
        foo:
            id: App\Ecommerce\AvailabilitySystem\CustomAvailabilitySystem
            
        bar:
            id: app.custom_attribute_availability_system

```


## Getting Availabilities
Each product (if it implements `CheckoutableInterface`) needs to implement the method `getOSAvailabilityInfo` which in the default
implementation gets the corresponding Availability System and calculates the availability. 

The return value is an `AvailabilityInterface` object, which at least has one `getAvailable` method and can contain additional 
availability information (e.g. availability for different storage locations etc.). 

This can be used to visualize availability on product detail pages or to check, if a product can actually be checked out.   

