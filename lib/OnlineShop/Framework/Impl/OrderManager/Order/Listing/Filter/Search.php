<?php

namespace OnlineShop\Framework\Impl\OrderManager\Order\Listing\Filter;

use OnlineShop\Framework\OrderManager\IOrderList;
use OnlineShop\Framework\OrderManager\IOrderListFilter;

/**
 * Search filter with flexible column definition
 */
class Search extends AbstractSearch
{
    /**
     * Search column
     * @var string
     */
    protected $column;

    /**
     * @param string $value
     * @param string $column
     */
    public function __construct($value, $column)
    {
        parent::__construct($value);
        $this->column = $column;
    }

    protected function getConditionColumn()
    {
        return $this->column;
    }
}