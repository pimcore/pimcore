<?php
/**
 * Created by PhpStorm.
 * User: Julian Raab
 * Date: 31.01.2018
 * Time: 00:31
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\Payment;


abstract class AbstractPayment implements IPayment
{

    public function isRecurringPaymentEnabled()
    {
        return false;
    }

}
