<?php

namespace OnlineShop\Framework\Impl\OrderManager\Order\Listing\Filter;

use OnlineShop\Framework\OrderManager\IOrderList;
use OnlineShop\Framework\OrderManager\IOrderListFilter;

class Origin implements IOrderListFilter
{
    const ORIGIN_ONLINE = 'online';
    const ORIGIN_MANUAL = 'manual';

    /**
     * @var string
     */
    protected $value;

    /**
     * Allowed origin values
     * @var array
     */
    protected $allowedValues = [
        self::ORIGIN_ONLINE,
        self::ORIGIN_MANUAL
    ];

    /**
     * @param string $origin
     */
    public function __construct($origin)
    {
        if (!in_array($origin, $this->allowedValues)) {
            throw new \InvalidArgumentException('Invalid filter value');
        }

        $this->value = $origin;
    }

    /**
     * @param IOrderList $orderList
     * @return IOrderListFilter
     */
    public function apply(IOrderList $orderList)
    {
        switch($this->value) {
            case self::ORIGIN_ONLINE:
                $orderList->addCondition('order.agentId IS NULL');
                break;

            case self::ORIGIN_MANUAL:
                $orderList->addCondition('order.agentId IS NOT NULL');
                break;
        }

        return $this;
    }
}