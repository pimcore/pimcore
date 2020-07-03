<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\Payment;

use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder;
use Pimcore\Model\DataObject\Listing\Concrete;
use Symfony\Component\Intl\Exception\NotImplementedException;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractPayment implements PaymentInterface
{
    /**
     * @var bool
     */
    protected $recurringPaymentEnabled;

    /**
     * @var string
     */
    protected $configurationKey;

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
     *
     * @return OptionsResolver
     */
    protected function configureOptions(OptionsResolver $resolver): OptionsResolver
    {
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
        throw new NotImplementedException('getRecurringPaymentDataProperties not implemented for ' . get_class($this));
    }

    public function applyRecurringPaymentCondition(Concrete $orderListing, $additionalParameters = [])
    {
        throw new NotImplementedException('getRecurringPaymentDataProperties not implemented for ' . get_class($this));
    }

    /**
     * @inheritdoc
     */
    public function getConfigurationKey()
    {
        return $this->configurationKey;
    }

    /**
     * @param string $configurationKey
     */
    public function setConfigurationKey(string $configurationKey)
    {
        $this->configurationKey = $configurationKey;
    }
}
