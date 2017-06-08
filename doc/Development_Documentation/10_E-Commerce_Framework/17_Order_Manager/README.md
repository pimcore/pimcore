# Order Manager
The Order Manager is responsible for all aspects of working with orders except committing them (which is the 
responsibility of the Commit Order Processor). These aspects contain among other things:
* Creating orders based on carts
* Order Storage (by default as Pimcore objects)
* Loading orders 
* Loading order lists and filter them ([Order List](./01_Working_with_Order_Lists.md))
* Working with orders after order commit ([Order Agent](./02_Working_with_Order_Agent.md)) 


## Configuration
The configuration takes place in the [EcommerceFrameworkConfig.php](https://github.com/pimcore/pimcore/blob/master/pimcore/lib/Pimcore/Bundle/EcommerceFrameworkBundle/Resources/install/EcommerceFrameworkConfig_sample.php#L649)
```php
/* order manager */
'ordermanager' => [
    'class' => '\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\OrderManager\\OrderManager',
    'config' => [
        'orderList' => [
            'class' => '\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\OrderManager\\Order\\Listing',
            'classItem' => '\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\OrderManager\\Order\\Listing\\Item'
        ],
        'orderAgent' => [
            'class' => '\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\OrderManager\\Order\\Agent'
        ],
        /* settings for order storage - pimcore class names for oder and order items */
        'orderstorage' => [
            'orderClass' => '\\Pimcore\\Model\\Object\\OnlineShopOrder',
            'orderItemClass' => '\\Pimcore\\Model\\Object\\OnlineShopOrderItem'
        ],
        /* parent folder for order objects - either ID or path can be specified. path is parsed by strftime. */
        'parentorderfolder' => '/order/%Y/%m/%d',
        /* special configuration for specific checkout tenants */
        'tenants' => [
            'otherFolder' => [
                'parentorderfolder' => '/order_otherfolder/%Y/%m/%d'
            ]
        ]
    ]
],
```
