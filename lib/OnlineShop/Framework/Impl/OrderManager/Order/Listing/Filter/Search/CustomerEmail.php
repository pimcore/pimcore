<?php

namespace OnlineShop\Framework\Impl\OrderManager\Order\Listing\Filter\Search;

use OnlineShop\Framework\Impl\OrderManager\Order\Listing\Filter\AbstractSearch;

class CustomerEmail extends AbstractSearch
{
    /**
     * @return string
     */
    protected function getConditionColumn()
    {
        return 'order.invoiceEmail';
    }
}