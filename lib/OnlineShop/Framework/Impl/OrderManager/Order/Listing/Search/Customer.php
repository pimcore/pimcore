<?php

namespace OnlineShop\Framework\Impl\OrderManager\Order\Listing\Filter\Search;

use OnlineShop\Framework\Impl\OrderManager\Order\Listing\Filter\AbstractSearch;

class Customer extends AbstractSearch
{
    /**
     * @return string
     */
    protected function getConditionVariable()
    {
        return 'CONCAT(order.invoiceFirstName, " ", order.invoiceLastName)';
    }
}