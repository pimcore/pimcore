<?php
/**
 * Created by PhpStorm.
 * User: tballmann
 * Date: 09.04.2015
 * Time: 16:23
 */

namespace OnlineShop\Framework\Impl\OrderManager\Order\Listing\Filter;

use OnlineShop\Framework\OrderManager\IOrderList;
use OnlineShop\Framework\OrderManager\IOrderListFilter;

class ProductType implements IOrderListFilter
{
    /**
     * @var array
     */
    protected $types = [];

    /**
     * @param IOrderList $orderList
     *
     * @return IOrderListFilter
     */
    public function apply(IOrderList $orderList)
    {
        // init
        $orderList->joinOrderItemObjects();

        // quote
        $types = [];
        foreach($this->getTypes() as $t)
        {
            $types[] = $orderList->getQuery()->getAdapter()->quote( $t );
        }

        $orderList->getQuery()->where(
            sprintf('orderItemObjects.o_className IN (%s)'
                , implode(',', $types)
            )
        );
    }

    /**
     * @return array
     */
    public function getTypes()
    {
        return $this->types;
    }

    /**
     * @param array $types
     *
     * @return $this
     */
    public function setTypes(array $types)
    {
        $this->types = $types;
        return $this;
    }
}