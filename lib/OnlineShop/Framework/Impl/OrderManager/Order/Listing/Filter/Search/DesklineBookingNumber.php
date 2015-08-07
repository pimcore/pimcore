<?php

namespace OnlineShop\Framework\Impl\OrderManager\Order\Listing\Filter\Search;

use OnlineShop\Framework\Impl\OrderManager\Order\Listing\Filter\AbstractSearch;

class DesklineBookingNumber extends AbstractSearch
{
    /**
     * @return string
     */
    protected function getConditionVariable()
    {
        return 'order.desklineBookingNumber';
    }
}