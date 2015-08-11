<?php

namespace OnlineShop\Framework\Impl\OrderManager\Order\Listing\Filter;

use OnlineShop\Framework\OrderManager\IOrderList;
use OnlineShop\Framework\OrderManager\IOrderListFilter;

/**
 * Filter which can only be applied to item lists
 */
abstract class AbstractItem implements IOrderListFilter
{
    /**
     * @param IOrderList $orderList
     * @return IOrderListFilter
     */
    public function apply(IOrderList $orderList)
    {
        if ($orderList->getListType() !== IOrderList::LIST_TYPE_ORDER_ITEM) {
            throw new \RuntimeException('Filter can only be applied on OrderLists with list type set to item');
        }
    }
}