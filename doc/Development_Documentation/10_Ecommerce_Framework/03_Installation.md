# Installation and First Configuration

This section describes the installation of the E-Commerce Framework and the first steps for configuration. 

## Installation

The E-Commerce Framework is shipped with Pimcore core. To install it, navigate to `Tools` > `Extensions` in Pimcore 
Admin UI and activate and install the `PimcoreEcommerceFrameworkBundle`. 

The installer does following tasks: 
- Create configuration file `app/config/pimcore/EcommerceFrameworkConfig.php` and fills it with default values. 
- Install several field collections.
- Install several object classes. 
- Install several object bricks. 
- Create additional tables for carts, pricing rules, vouchers, etc. 
- Import translations for Pimcore Admin UI and Order Backend. 
- Add additional permissions. 

If either classes, field collections, object bricks or tables already exists, the installation cannot be started. 

After this installation routine, additional configurations have to be made - most important Product, ProductCategory and
eventually `EcommerceFrameworkConfig.php`. 


## Configure Product and Product Category Class
The E-Commerce Framework installation does not create classes for products and product categories. That is because the 
framework does limit you on specific classes or class structures. Literally every class can act as a product or product 
category class and it is also possible to have several product classes (if necessary). 

The only requirement is, that the classes have to be 'prepared' for being products or product categories. 

### Product
There are two ways of preparing a Pimcore class for product-usage in the E-Commerce Framework

1. The Pimcore class needs to extend the abstract class `\Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractProduct`
   * This is useful, when both product index and checkout functionality is based on the E-Commerce Framework. 
2. Make sure that the Pimcore class implements either `\Pimcore\Bundle\EcommerceFrameworkBundle\Model\IIndexable` or 
`\Pimcore\Bundle\EcommerceFrameworkBundle\Model\ICheckoutable` - or both, depending on the use case it is used for.
   * This is useful, when e.g. only checkout functionality is based on the E-Commerce Framework, but not the product 
   presentation. 
   * The interfaces define methods that are needed for the two use cases and need to be implemented. 

> For the abstract class use the parent class functionality of Pimcore. For implementing the interfaces use either 
the parent class functionality or the dependency injection functionality of pimcore 
(see also [Overriding Models](../20_Extending_Pimcore/03_Overriding_Models.md)).

### Product Category
When a product category class is used, this class needs to extend the abstract class 
`\Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractCategory`. 

> For product categories only one Pimcore class should be used. For products, several Pimcore classes can be used. 
Possibly the index update scripts need to be adapted.


## Configuring EcommerceFrameworkConfig.php

[Sample EcommerceFrameworkConfig.php](../../../pimcore/lib/Pimcore/Bundle/EcommerceFrameworkBundle/install/EcommerceFrameworkConfig_sample.php)

Open `app/config/pimcore/EcommerceFrameworkConfig.php` and adjust the settings. This configuration file is the central 
configuration for the E-Commerce Framework and defines the implementations and configurations for all modules.

So this configuration file specifies things like
- cart manager
- price systems
- availability systems
- checkout manager and checkout steps
- payment providers
- index service and which attributes should be in the product index
- pricing manager
- ...
For detailed information see comments within the configuration file. Depending on your use case, you might not need 
all components configured in the configuration file. 

Things you need to adjust when using the product index: 
* productindex - columns (adjust the sample attributes to attributes that are available in your product class). 

> During development you will return to this file and adjust the settings multiple times. 
