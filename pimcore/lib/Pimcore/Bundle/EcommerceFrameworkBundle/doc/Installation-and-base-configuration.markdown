## 1 - Installation
Get the latest version of the e-commerce framework, copy it to your project and install the framework. 


## 2 - Configure product and product category classes
As products and product categories simple pimcore objects can be used. In order add e-commerce specific functionality to these pimcore objects, certain base classes or interfaces need to implemented. 
For detailed information of the needed functionality see source code documentation of the abstract classes and interfaces. 
TODO: ADD LINKS HERE

### Product
There are two ways of preparing a pimcore class for product-usage in the ecommerce-framework

1. The pimcore class needs to extend the abstract class `\Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractProduct`
   * this is useful, when product index and checkout functionality is based on the e-commerce framework. 
2. Make sure that the pimcore class implements either `\Pimcore\Bundle\EcommerceFrameworkBundle\Model\IIndexable` or `\Pimcore\Bundle\EcommerceFrameworkBundle\Model\ICheckoutable` - or both, depending on the use case it is used for.
   * this is useful, when e.g. only checkout functionality is based on the e-commerce framework, but not the product presentation. 
   * the interfaces define methods that are needed for the two use cases and need to be implemented. 


> For the abstract class use the parent class functionality of pimcore. For implementing the interfaces use either the parent class functionality or the dependency injection functionality of pimcore (see also <https://www.pimcore.org/docs/latest/Extending_Pimcore/Dependency_Injection.html>).

### Product Category
When a product category class is used, this class needs to extend the abstract class `\Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractCategory`


> For product categories only one pimcore class should be used. For products, several pimcore classes can be used. Possibly the index update scripts need to be adapted.


## 3 - Configuring EcommerceFrameworkConfig.php

[Sample EcommerceFrameworkConfig.php](/config/EcommerceFrameworkConfig_sample.php)

Open /website/var/plugins/EcommerceFramework/EcommerceFrameworkConfig.php and adjust the settings. This configuration file is the central configuration for the e-commerce framework and defines the concrete implementations and configurations for all modules.

So this configuration file specifies things like
- cart manager
- price systems
- availability systems
- checkout manager and checkout steps
- payment providers
- index service and which attributes should be in the product index
- pricing manager
- ...
For detailed information see comments within the configuration file. Depending on your use case, you might not need all components configured in the configuration file. 

Things you need to adjust when using the product index: 
* productindex - columns (adust the sample attributes to attributes that are available in your product class). 

> During development you will return to this file and adjust the settings multiple times. 

> In older versions this settings file is cached due to performance issues. You need to clean up the configuration cache in the pimcore backend so that the changes take affect. Since Version 0.10.0 the settings file is not cached any more. 
