<?php
/**
 * Created by PhpStorm.
 * User: Julian Raab
 * Date: 31.01.2018
 * Time: 00:31
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\Payment;

use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder;
use Pimcore\Model\DataObject\Listing\Concrete;
use Symfony\Component\Intl\Exception\NotImplementedException;

abstract class AbstractPayment implements IPayment
{

    public function isRecurringPaymentEnabled()
    {
        return false;
    }

    public function setRecurringPaymentSourceOrderData(AbstractOrder $sourceOrder, $paymentBrick)
    {
        throw new NotImplementedException("getRecurringPaymentDataProperties not implemented for " . get_class($this));
    }

    public function applyRecurringPaymentCondition(Concrete $orderListing, $additionalParameters = [])
    {
        throw new NotImplementedException("getRecurringPaymentDataProperties not implemented for " . get_class($this));
    }

}
