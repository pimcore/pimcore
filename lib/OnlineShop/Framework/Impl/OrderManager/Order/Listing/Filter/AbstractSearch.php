<?php

namespace OnlineShop\Framework\Impl\OrderManager\Order\Listing\Filter;

use OnlineShop\Framework\OrderManager\IOrderList;
use OnlineShop\Framework\OrderManager\IOrderListFilter;

/**
 * Base filter for LIKE queries. For simple queries you'll just
 * need to override the getConditionVariable() method and return
 * the query part coming before LIKE.
 */
abstract class AbstractSearch implements IOrderListFilter
{
    /**
     * Search value
     * @var string
     */
    protected $value;

    /**
     * @param string $value
     */
    public function __construct($value)
    {
        $this->value = trim($value);
    }

    /**
     * Return the string coming before LIKE, e.g. 'order.invoiceEmail'
     *
     * @return string
     */
    abstract protected function getConditionVariable();

    /**
     * Pad the value with wildcards
     *
     * @return string
     */
    protected function getConditionValue()
    {
        return '%' . $this->value . '%';
    }

    /**
     * @param IOrderList $orderList
     * @return IOrderListFilter
     */
    public function apply(IOrderList $orderList)
    {
        if (empty($this->value)) {
            return $orderList;
        }

        $this->prepareApply($orderList);

        $query = sprintf('%s LIKE ?', $this->getConditionVariable());
        $value = $this->getConditionValue();

        $orderList->addCondition($query, $value);
        return $this;
    }

    /**
     * Override if necessary (e.g. join a table)
     *
     * @param IOrderList $orderList
     */
    protected function prepareApply(IOrderList $orderList)
    {
    }
}