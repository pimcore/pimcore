<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\Payment;

use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\V7\Payment\PaymentInterface;
use Pimcore\Model\DataObject\Listing\Concrete;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractPayment implements PaymentInterface
{
    protected bool $recurringPaymentEnabled = false;

    protected string $configurationKey;

    protected function processOptions(array $options)
    {
        if (isset($options['recurring_payment_enabled'])) {
            $this->recurringPaymentEnabled = (bool) $options['recurring_payment_enabled'];
        }
    }

    protected function configureOptions(OptionsResolver $resolver): OptionsResolver
    {
        $resolver
            ->setDefined('recurring_payment_enabled')
            ->setAllowedTypes('recurring_payment_enabled', ['bool']);

        return $resolver;
    }

    public function isRecurringPaymentEnabled(): bool
    {
        return $this->recurringPaymentEnabled;
    }

    /**
     * @param AbstractOrder $sourceOrder
     * @param object $paymentBrick
     *
     * @return void
     */
    public function setRecurringPaymentSourceOrderData(AbstractOrder $sourceOrder, $paymentBrick)
    {
        throw new \RuntimeException('getRecurringPaymentDataProperties not implemented for ' . get_class($this));
    }

    /**
     * @param Concrete $orderListing
     * @param array $additionalParameters
     *
     * @return void
     */
    public function applyRecurringPaymentCondition(Concrete $orderListing, $additionalParameters = [])
    {
        throw new \RuntimeException('getRecurringPaymentDataProperties not implemented for ' . get_class($this));
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigurationKey(): string
    {
        return $this->configurationKey;
    }

    public function setConfigurationKey(string $configurationKey)
    {
        $this->configurationKey = $configurationKey;
    }
}
