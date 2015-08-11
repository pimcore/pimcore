<?php

namespace OnlineShop\Framework\Impl\OrderManager\Order\Listing\Filter\Search;

use OnlineShop\Framework\Impl\OrderManager\Order\Listing\Filter\AbstractSearch;
use OnlineShop\Framework\OrderManager\IOrderList;

class PaymentReference extends AbstractSearch
{
    /**
     * @return string
     */
    protected function getConditionVariable()
    {
        return 'paymentInfo.paymentReference';
    }

    /**
     * @return string
     */
    protected function getConditionValue()
    {
        $value = parent::getConditionValue();
        $value = ',' . $value . ',';

        return $value;
    }

    /**
     * Join paymentInfo
     *
     * @param IOrderList $orderList
     */
    protected function prepareApply(IOrderList $orderList)
    {
        $orderList->joinPaymentInfo();
    }
}