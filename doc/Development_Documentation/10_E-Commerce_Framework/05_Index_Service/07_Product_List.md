## Working with Product Lists
The API for getting (and filtering, ...) products out of the *Product Index* are so called Product Lists. They all 
implement the interface `\Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\ProductList\ProductListInterface` and need to be 
Product Index implementation specific. Detailed method documentation is available in 
[in-source documentation](https://github.com/pimcore/pimcore/blob/master/bundles/EcommerceFrameworkBundle/IndexService/ProductList/ProductListInterface.php). 

For how to get a Product List instance suitable for the current Product Index implementation and filter for products see 
following code sample: 

```php
<?php 
$list = \Pimcore\Bundle\EcommerceFrameworkBundle\Factory::getInstance()->getIndexService()->getProductListForCurrentTenant();
$list->addCondition("name = 'testproduct'", 'name');
$list->addRelationCondition('category', "dest IN (1024,1025,1026)");
$list->setOrder("ASC");
$list->setOrderKey('name');
$list->load();
```

All filtered attributes need to be in the *Product Index*. The Product List implements all iterator and paginator 
interfaces and can be used in foreach loops and in combination with `Zend\Paginator`. 

> For logging standard Symfony logging is used. E-Commerce Framework Product Indices log into special channels like
> `pimcore_ecommerce_indexupdater`, `pimcore_ecommerce_sql`, `pimcore_ecommerce_factfinder ` (Deprecated since 6.7.0), `pimcore_ecommerce_es`
> and `pimcore_ecommerce_findologic`. 
