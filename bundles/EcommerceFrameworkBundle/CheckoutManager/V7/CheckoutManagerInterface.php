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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\V7;

use Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\V7\HandlePendingPayments\HandlePendingPaymentsStrategyInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Exception\UnsupportedException;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\V7\Payment\StartPaymentRequest\AbstractRequest;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\V7\Payment\StartPaymentResponse\StartPaymentResponseInterface;

interface CheckoutManagerInterface extends \Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\CheckoutManagerInterface
{
    /**
     * Starts payment for checkout and also starts payment provider
     * - only possible if payment provider is configured
     *
     * @param AbstractRequest $paymentConfig
     *
     * @return StartPaymentResponseInterface
     *
     * @throws UnsupportedException
     */
    public function startOrderPaymentWithPaymentProvider(AbstractRequest $paymentConfig): StartPaymentResponseInterface;

    /**
     * @param HandlePendingPaymentsStrategyInterface $handlePendingPaymentsStrategy
     */
    public function setHandlePendingPaymentsStrategy(HandlePendingPaymentsStrategyInterface $handlePendingPaymentsStrategy): void;
}
