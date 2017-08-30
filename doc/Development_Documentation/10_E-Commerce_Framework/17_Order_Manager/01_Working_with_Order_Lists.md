# Working with Order Lists

The Order List are a one stop API for filtering and listing order objects. Of course default Pimcore object lists also 
can be used for listing order objects. But Order Lists provide additional functionality in terms of predefined filters etc. 

## Basic - Get Newest Orders
```php
<?php

// create new order list
$orderManager = Factory::getInstance()->getOrderManager();
$orderList = $orderManager->createOrderList();

// get the newest 10 orders
$orderList->setOrder( 'order.orderDate desc' );
$orderList->setLimit( 10, 0 );

// iterate
foreach($orderList as $order)
{
    /* @var \Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\IOrderListItem $order */
    echo $order->getOrdernumber();
}


// ALTERNATE: use zend paginator
$paginator = new Paginator( $orderList);
$paginator->setItemCountPerPage( 10 );
$paginator->setCurrentPageNumber( $request->get('page', 1) );

foreach($paginator as $order) {
   ...
}
```


### Basic - Using Filter
```php
<?php

// create new order list
$orderManager = Factory::getInstance()->getOrderManager();
$orderList = $orderManager->createOrderList();


// create date time filter
$filterDate = new \Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\Order\Listing\Filter\OrderDateTime();
$filterDate->setFrom( new DateTime('20.01.2015') );
$filterDate->setTill( new DateTime('31.01.2015') );


// add filter
$orderList->addFilter( $filterDate );

```


### Basic - list Order Items
```php
<?php

// create new order list
$orderManager = Factory::getInstance()->getOrderManager();
$orderList = $orderManager->createOrderList();


// get only orders that are committed
$orderList->setListType( $orderList::LIST_TYPE_ORDER_ITEM );
$orderList->setOrderState( AbstractOrder::ORDER_STATE_COMMITTED );

```



### Advanced - Custom Condition
```php
<?php

// create new order list
$orderManager = Factory::getInstance()->getOrderManager();
$orderList = $orderManager->createOrderList();


// e.g. - search for a specify order number
$query = $orderList->getQuery();
$query->where('order.ordernumber = ?', 'ord_554b425dcae53');


// e.g. - search for a user comment
$query = $orderList->getQuery();
$query->where('order.comment like ?', '%hallo world%');

```



### Expert - High Performance

> avoid loading of Pimcore object's for higher performance

```php
<?php

// create new order list
$orderManager = Factory::getInstance()->getOrderManager();
$orderList = $orderManager->createOrderList();


// add all required fields to the select
$orderList->addSelectField(['OrderNumber' => 'order.orderNumber']);
$orderList->addSelectField(['TotalPrice' => 'order.totalPrice']);
$orderList->addSelectField(['Items' => 'count(orderItem.o_id)']);

```


### Expert - Extended Conditions via Joins

```php
<?php

// e.g. get all orderings for a customer
$orderList->joinCustomer( \Pimcore\Model\DataObject\Customer::classId() );
$orderList->getQuery()->where('customer.o_id = ?', 12345);


// e.g. filter product number
$orderList->joinProduct( \Pimcore\Model\DataObject\Product::classId() );
$orderList->getQuery()->where('product.productNumber = ?', 'CMD1191');

```


### Generic Filter

> Namespace: \Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\Order\Listing\Filter

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
