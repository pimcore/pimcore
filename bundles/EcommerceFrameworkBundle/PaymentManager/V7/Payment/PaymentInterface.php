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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\V7\Payment;

use Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\OrderAgentInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\V7\Payment\StartPaymentRequest\AbstractRequest;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\V7\Payment\StartPaymentResponse\StartPaymentResponseInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\PriceInterface;

interface PaymentInterface extends \Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\Payment\PaymentInterface
{
    /**
     * Start payment
     *
     * @param PriceInterface $price
     * @param array $config
     *
     * @deprecated use startPayment instead.
     *
     * @return mixed - either an url for a link the user has to follow to (e.g. paypal) or
     *                 an symfony form builder which needs to submitted (e.g. datatrans and wirecard)
     */
    public function initPayment(PriceInterface $price, array $config);

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
}
