<?php

namespace OnlineShop\Framework\Impl\OrderManager\Order\Listing\Filter;

use OnlineShop\Framework\OrderManager\IOrderList;
use OnlineShop\Framework\OrderManager\IOrderListFilter;

/**
 * Search filter with flexible variable definition
 */
class Search extends AbstractSearch
{
    /**
     * Search variable
     * @var string
     */
    protected $variable;

    /**
     * @param string $value
     * @param string $variable
     */
    public function __construct($value, $variable)
    {
        parent::__construct($value);
        $this->variable = $variable;
    }

    protected function getConditionVariable()
    {
        return $this->variable;
    }
}