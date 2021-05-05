<?php

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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\V7\Payment;

use Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\OrderAgentInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\StatusInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\V7\Payment\StartPaymentRequest\AbstractRequest;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\V7\Payment\StartPaymentResponse\StartPaymentResponseInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\PriceInterface;

interface PaymentInterface
{
    /**
     * @return string
     */
    public function getName();

    /**
     * Starts payment
     *
     * @param OrderAgentInterface $orderAgent
     * @param PriceInterface $price
     * @param AbstractRequest $config
     *
     * @return StartPaymentResponseInterface
     */
    public function startPayment(OrderAgentInterface $orderAgent, PriceInterface $price, AbstractRequest $config): StartPaymentResponseInterface;

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
     * returns configuration key in yml configuration file
     *
     * @return string
     */
    public function getConfigurationKey();
}
