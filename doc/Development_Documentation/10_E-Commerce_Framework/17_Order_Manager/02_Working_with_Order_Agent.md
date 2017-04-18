# Working with Order Agent

The Order Agent is a one stop API for working with orders, e.g. changing state of orders, modifying quantity of items, etc.
and keeps track of these changes in a change log. 

See following examples for how Order Agent can be used: 

## Change item quantity
```php
<?php

// load order item
$orderItem = Object_OnlineShopOrderItem::getById( $this->getParam('id') );
$order = $orderItem->getOrder();

// create new order agent
$orderManager = Factory::getInstance()->getOrderManager();
$orderAgent = $orderManager->createOrderAgent( $order );

// change amount to 5
$log = $orderAgent->itemChangeAmount( $orderItem, 5 );
/* @var \Pimcore\Model\Element\Note $log */

// add user comment
$log->addData('message', 'text', 'customer has changed the order by phone');
$log->save();

```

## Changelog Usage
```php
<?php

// load order
$order = Object_OnlineShopOrder::getById( $this->getParam('id') );

// create new order agent
$orderManager = Factory::getInstance()->getOrderManager();
$orderAgent = $orderManager->createOrderAgent( $order );

// get changelog
foreach($orderAgent->getFullChangeLog() as $log)
{
    /* @var \Pimcore\Model\Element\Note $log */
    ...
}
```
