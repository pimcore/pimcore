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

use Pimcore\Model\Object\OnlineShopOrder;
use Pimcore\Model\Object\OnlineShopOrderItem;


class Order implements IOrderListFilter
{
    /**
     * @var string
     */
    protected $query;
    

    /**
     * @param IOrderList $orderList
     *
     * @return IOrderListFilter
     */
    public function apply(IOrderList $orderList)
    {
        // init
        $query = $orderList->getQuery();


        if($this->getQuery())
        {
            $condition = <<<'SQL'
0
OR `order`.ordernumber like ?
OR `order`.comment like ?

OR `order`.customerName like ?
OR `order`.customerCompany like ?
OR `order`.customerStreet like ?
OR `order`.customerZip like ?
OR `order`.customerCity like ?
OR `order`.customerCountry like ?

OR `order`.deliveryName like ?
OR `order`.deliveryCompany like ?
OR `order`.deliveryStreet like ?
OR `order`.deliveryZip like ?
OR `order`.deliveryCity like ?
OR `order`.deliveryCountry like ?
SQL;

            $query->where($condition, '%' . $this->getQuery() . '%');
        }

    }

    /**
     * @return string
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @param string $query
     *
     * @return $this
     */
    public function setQuery($query)
    {
        $this->query = $query;
        return $this;
    }
}