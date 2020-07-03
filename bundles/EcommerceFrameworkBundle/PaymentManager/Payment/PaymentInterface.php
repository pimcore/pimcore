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
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\StatusInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\PriceInterface;
use Pimcore\Model\DataObject\Listing\Concrete;

/**
 * Interface for checkout payment provider
 */
interface PaymentInterface
{
    /**
     * @return string
     */
    public function getName();

    /**
     * Start payment
     *
     * @param PriceInterface $price
     * @param array $config
     *
     * @return mixed - either an url for a link the user has to follow to (e.g. paypal) or
     *                 an symfony form builder which needs to submitted (e.g. datatrans and wirecard)
     */
    public function initPayment(PriceInterface $price, array $config);

    /**
     * Handles response of payment provider and creates payment status object
     *
     * @param StatusInterface $response
     *
     * @return StatusInterface
     */
    public function handleResponse($response);

    /**
     * Returns the authorized data from payment provider
     *
     * @return array
     */
    public function getAuthorizedData();

    /**
     * Set authorized data from payment provider
     *
     * @param array $authorizedData
     */
    public function setAuthorizedData(array $authorizedData);

    /**
     * Executes payment
     *
     * @param PriceInterface|null $price
     * @param string|null $reference
     *
     * @return StatusInterface
     */
    public function executeDebit(PriceInterface $price = null, $reference = null);

    /**
     * Executes credit
     *
     * @param PriceInterface $price
     * @param string $reference
     * @param string $transactionId
     *
     * @return StatusInterface
     */
    public function executeCredit(PriceInterface $price, $reference, $transactionId);

    /**
     * Payment supports recurring payment
     *
     * @todo Pimcore 7 remove this method as it moved to RecurringPaymentInterface
     *
     * @return bool
     */
    public function isRecurringPaymentEnabled();

    /**
     * @param AbstractOrder $sourceOrder
     * @param object $paymentBrick
     *
     * @todo Pimcore 7 remove this method as it moved to RecurringPaymentInterface
     *
     * @return mixed
     */
    public function setRecurringPaymentSourceOrderData(AbstractOrder $sourceOrder, $paymentBrick);

    /**
     * @param Concrete $orderListing
     *
     * @todo Pimcore 7 remove this method as it moved to RecurringPaymentInterface
     *
     * @return Concrete
     */
    public function applyRecurringPaymentCondition(Concrete $orderListing, $additionalParameters = []);

    /**
     * returns configuration key in yml configuration file
     *
     * @return string
     */
    public function getConfigurationKey();
}

class_alias(PaymentInterface::class, 'Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\Payment\IPayment');
