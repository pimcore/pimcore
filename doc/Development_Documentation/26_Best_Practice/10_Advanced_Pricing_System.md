# Advanced Pricing System

In Pimcore E-Commerce Framework, price systems are responsible for price calculations. The easiest use case is getting
the price from an attribute in the product object. The shipped 
[AttributePriceSystem](https://github.com/pimcore/pimcore/blob/master/bundles/EcommerceFrameworkBundle/PriceSystem/AttributePriceSystem.php) 
implementation does exactly that. 

But there might be more complex use cases where price calculation is some custom logic that needs to be implemented. 

**Solution**

To accomplish that, you need to create your own price system implementation. 
 
##### Implementation of Custom Price Systems

To implement custom price systems, you need to implement the interface 
[`PriceSystemInterface`](https://github.com/pimcore/pimcore/blob/master/bundles/EcommerceFrameworkBundle/PriceSystem/PriceSystemInterface.php) 
or extend any shipped implementation from the framework like for example the 
[`AttributePriceSystem`](https://github.com/pimcore/pimcore/blob/master/bundles/EcommerceFrameworkBundle/PriceSystem/AttributePriceSystem.php). 


Following example implements a price system that retrieves prices from an extra price table. 

```php
<?php

namespace AppBundle\Ecommerce\Pricing;

use Pimcore\Bundle\EcommerceFrameworkBundle\Model\CheckoutableInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\AttributePriceSystem;
use Pimcore\Bundle\EcommerceFrameworkBundle\Type\Decimal;
use Pimcore\Db;

class MyPriceSystem extends AttributePriceSystem {

    /**
     * Calculates prices from product
     *
     * @param CheckoutableInterface $product
     * @param CheckoutableInterface[] $products
     * @return Decimal
     */
    protected function calculateAmount(CheckoutableInterface $product, $products): Decimal
    {
        // Get Product ID
        $productId = $product->getId();

        // Do some magic stuff to calculate price, e.g. get it from an extra price table, or price service, etc.
        $price = Db::get()->fetchOne("SELECT price FROM app_prices WHERE productId = ?", [$productId]);

        return Decimal::create($price);
    }

}

```


##### Using Custom Price Systems

To use your custom price system, you need to configure the framework as follows. 

1) Configure your price system as container service
```yml
services:

    # define own price system service and configure options for attribute name and price object class
    app.default_price_system:
        #class: Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\AttributePriceSystem
        class: AppBundle\Ecommerce\Pricing\MyPriceSystem
        arguments:
            - '@pimcore_ecommerce.locator.pricing_manager'
            - '@pimcore_ecommerce.environment'
            - { price_class: Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\Price }
            
```


2) Configure the price system in e-commerce framework config
```yml
pimcore_ecommerce_framework:
    # Configuration of price systems - key is name of price system
    price_systems:
        default:
            # Price system defined and configured as container service
            id: app.default_price_system
```


That should be enough that the system uses your custom price system when you call `$product->getOsPrice()`. 


##### Filtering for Prices

When using custom price systems, filtering for and sorting based on prices in product lists also needs to be addressed 
by the price system. To do so, overwrite the method `filterProductIds` in your price system. This method gets all 
productIds that apply the other filter criteria (without offset and limit) needs to filter and sort them. 
 
Following sample implementation does that by using a sql query:
 
```php
<?php
    /**
     * Filters and orders given product IDs based on price information
     *
     * @param array $productIds  - contains all productId that apply filter criteria without limit & offset
     * @param float $fromPrice   - optional filter for 'fromPrice'
     * @param float $toPrice     - optional filter for 'toPrice'
     * @param string $order       - order for sorting (asc|desc)
     * @param int $offset
     * @param int $limit
     * @return array
     */
    public function filterProductIds($productIds, $fromPrice, $toPrice, $order, $offset, $limit)
    {
        $db = Db::get();

        if($limit) {
            if($offset) {
                $limit = "LIMIT " . $offset . ", " . $limit;
            } else {
                $limit = "LIMIT " . $limit;
            }
        }

        // Create a query for sorting prices and applying offset and limit
        $idString = implode(",", $productIds);
        $sql = "SELECT o_id FROM object_12 a LEFT JOIN app_prices b" .
            " ON a.o_id = b.productId WHERE o_id IN ($idString) ORDER BY ISNULL(b.price), b.price $order, o_id $limit;";


        $sortedIds = $db->fetchAll($sql);


        return $sortedIds;
    }

```

To tell the product list that sorting should be done based on prices, use `ProductListInterface::ORDERKEY_PRICE` as order key 
as follows: 
```php
$products = $factory->getIndexService()->getProductListForCurrentTenant();
$products->setOrderKey(ProductListInterface::ORDERKEY_PRICE);
```

To filter for Prices use the method `addPriceCondition()` as follows: 
```php
$products = $factory->getIndexService()->getProductListForCurrentTenant();
$products->addPriceCondition(10, 300);
```

