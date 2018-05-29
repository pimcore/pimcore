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
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractPayment implements IPayment
{

    /**
     * @var bool
     */
    protected $recurringPaymentEnabled;

    /**
     * @param array $options
     */
    protected function processOptions(array $options)
    {
        if (isset($options['recurring_payment_enabled'])) {
            $this->recurringPaymentEnabled = $options['recurring_payment_enabled'];
        }
    }

    /**
     * @param OptionsResolver $resolver
     * @return OptionsResolver
     */
    protected function configureOptions(OptionsResolver $resolver): OptionsResolver{
        $resolver
            ->setDefined('recurring_payment_enabled')
            ->setAllowedTypes('recurring_payment_enabled', ['bool']);

        return $resolver;
    }

    /**
     * @return bool
     */
    public function isRecurringPaymentEnabled()
    {
        return $this->recurringPaymentEnabled;
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
