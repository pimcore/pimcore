## 1 - Order Manager configuration

### Configuration

The configuration takes place in the OnlineShopConfig.php
```php
"ordermanager" => [
            "class" => "OnlineShop\\Framework\\OrderManager\\OrderManager",
            "config" => [
                "orderList" => [
                    "class" => "OnlineShop\\Framework\\OrderManager\\Order\\Listing",
                    "classItem" => "OnlineShop\\Framework\\OrderManager\\Order\\Listing\\Item"
                ],
                "orderAgent" => [
                    "class" => "OnlineShop\\Framework\\OrderManager\\Order\\Agent"
                ],
                /* settings for order storage - pimcore class names for oder and order items */
                "orderstorage" => [
                    "orderClass" => "\\Pimcore\\Model\\Object\\OnlineShopOrder",
                    "orderItemClass" => "\\Pimcore\\Model\\Object\\OnlineShopOrderItem"
                ],
                /* parent folder for order objects - either ID or path can be specified. path is parsed by strftime. */
                "parentorderfolder" => "/order/%Y/%m/%d",
                /* special configuration for specific checkout tenants */
                "tenants" => [
                    "otherFolder" => [
                        "parentorderfolder" => "/order_otherfolder/%Y/%m/%d"
                    ]
                ]
            ]
        ],
```

> For older Versions check [OnlineShopConfig_sample.xml](/config/OnlineShopConfig_sample.xml)

## 2 - Usage OrderList

### Basic - get the newest orders
```php
<?php

// create new order list
$orderManager = \OnlineShop\Framework\Factory::getInstance()->getOrderManager();
$orderList = $orderManager->createOrderList();

// get the newest 10 orders
$orderList->setOrder( 'order.orderDate desc' );
$orderList->setLimit( 10, 0 );

// iterate
foreach($orderList as $order)
{
    /* @var OnlineShop\Framework\OrderManager\Order\Listing\IOrderListItem $order */

    echo $order->getOrdernumber();
}


// ALTERNATE: use zend paginator
$paginator = Zend_Paginator::factory( $orderList);
$paginator->setItemCountPerPage( 10 );
$paginator->setCurrentPageNumber( $this->getParam('page', 1) );

foreach($paginator as $order)
...

```


### Basic - using filter
```php
<?php

// create new order list
$orderManager = \OnlineShop\Framework\Factory::getInstance()->getOrderManager();
$orderList = $orderManager->createOrderList();


// create date time filter
$filterDate = new \OnlineShop\Framework\OrderManager\Order\Listing\Filter\OrderDateTime();
$filterDate->setFrom( new DateTime('20.01.2015') );
$filterDate->setTill( new DateTime('31.01.2015') );


// add filter
$orderList->addFilter( $filterDate );

```


### Basic - list order items
```php
<?php

// create new order list
$orderManager = \OnlineShop\Framework\Factory::getInstance()->getOrderManager();
$orderList = $orderManager->createOrderList();


// get only orders that are commited
$orderList->setListType( $orderList::LIST_TYPE_ORDER_ITEM );
$orderList->setOrderState( \OnlineShop\Framework\Model\AbstractOrder::ORDER_STATE_COMMITTED );


```



### Advanced - custom condition
```php
<?php

// create new order list
$orderManager = \OnlineShop\Framework\Factory::getInstance()->getOrderManager();
$orderList = $orderManager->createOrderList();


// e.g. - search for a specify ordernumber
$query = $orderList->getQuery();
$query->where('order.ordernumber = ?', 'ord_554b425dcae53');


// e.g. - search for a user comment
$query = $orderList->getQuery();
$query->where('order.comment like ?', '%hallo world%');

```



### Expert - high performance

> avoid loading of pimcore object's for higher performance

```php
<?php

// create new order list
$orderManager = \OnlineShop\Framework\Factory::getInstance()->getOrderManager();
$orderList = $orderManager->createOrderList();


// add all required fields to the select
$orderList->addSelectField(['OrderNumber' => 'order.orderNumber']);
$orderList->addSelectField(['TotalPrice' => 'order.totalPrice']);
$orderList->addSelectField(['Items' => 'count(orderItem.o_id)']);

```


### Expert - extended conditions via joins

```php
<?php

// e.g. get all orderings for a customer
$orderList->joinCustomer( \Pimcore\Model\Object\Customer::classId() );
$orderList->getQuery()->where('customer.o_id = ?', 12345);


// e.g. filter product number
$orderList->joinProduct( \Pimcore\Model\Object\Product::classId() );
$orderList->getQuery()->where('product.productNumber = ?', 'CMD1191');

```


### Generic filter

> Namespace: \OnlineShop\Framework\OrderManager\Order\Listing\Filter

| Filter          | Description |
| --------------- | ----------- |
| OrderDateTime   | Date Range Filter   |
| OrderSearch     | Search order for a specified keyword   |
| Payment         | Filter by payment state (ok or fail) |
| Product         | Filter by product and its variants |
| ProductType     | Filter ordered products for the given class names   |
| Search          | Generic search filter for searches against a given DB column |
| Search\Customer | Search for customer name |
| Search\CustomerEmail | Search for customer email |
| Search\PaymentReference | Search for a payment reference |

## 3 - Usage OrderAgent

### Change item quantity
```php
<?php

// load order item
$orderItem = Object_OnlineShopOrderItem::getById( $this->getParam('id') );
$order = $orderItem->getOrder();

// create new order agent
$orderManager = \OnlineShop\Framework\Factory::getInstance()->getOrderManager();
$orderAgent = $orderManager->createOrderAgent( $order );

// change amount to 5
$log = $orderAgent->itemChangeAmount( $orderItem, 5 );
/* @var \Pimcore\Model\Element\Note $log */

// add user comment
$log->addData('message', 'text', 'customer has changed the order by phone');
$log->save();

```


### Changelog usage
```php
<?php

// load order
$order = Object_OnlineShopOrder::getById( $this->getParam('id') );

// create new order agent
$orderManager = \OnlineShop\Framework\Factory::getInstance()->getOrderManager();
$orderAgent = $orderManager->createOrderAgent( $order );

// get changelog
foreach($orderAgent->getFullChangeLog() as $log)
{
    /* @var \Pimcore\Model\Element\Note $log */
    ...
}

```
