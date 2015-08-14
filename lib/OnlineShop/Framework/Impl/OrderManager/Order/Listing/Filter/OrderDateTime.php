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
use Zend_Date;

class OrderDateTime implements IOrderListFilter
{
    /**
     * @var Zend_Date
     */
    protected $from;

    /**
     * @var Zend_Date
     */
    protected $till;

    /**
     * @var string
     */
    protected $variable = 'order.orderDate';

    /**
     * @param IOrderList $orderList
     *
     * @return IOrderListFilter
     */
    public function apply(IOrderList $orderList)
    {
        // init
        $query = $orderList->getQuery();

        if($this->getFrom())
        {
            $query->where($this->getVariable() . ' >= ?', $this->getFrom()->getTimestamp());
        }

        if($this->getTill())
        {
            $query->where($this->getVariable() . ' <= ?', $this->getTill()->getTimestamp());
        }
    }

    /**
     * @return Zend_Date
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * @param Zend_Date $from
     *
     * @return $this
     */
    public function setFrom(Zend_Date $from)
    {
        $this->from = $from;
        return $this;
    }

    /**
     * @return Zend_Date
     */
    public function getTill()
    {
        return $this->till;
    }

    /**
     * @param Zend_Date $till
     *
     * @return $this
     */
    public function setTill(Zend_Date $till)
    {
        $this->till = $till;
        return $this;
    }

    /**
     * @return string
     */
    public function getVariable()
    {
        return $this->variable;
    }

    /**
     * @param string $variable
     */
    public function setVariable($variable)
    {
        $this->variable = $variable;
    }
}