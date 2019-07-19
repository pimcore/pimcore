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


use Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\V7\HandlePendingPayments\HandlePendingPaymentsStrategy;
use Pimcore\Bundle\EcommerceFrameworkBundle\Exception\UnsupportedException;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\V7\PaymentResponse\StartPaymentResponseInterface;

interface CheckoutManagerInterface extends \Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\CheckoutManagerInterface
{

    /**
     * Starts payment for checkout and also starts payment provider
     * - only possible if payment provider is configured
     *
     *
     * @return StartPaymentResponseInterface
     *
     * @throws UnsupportedException
     */
    public function startOrderPaymentWithPaymentProvider(array $paymentConfig): StartPaymentResponseInterface;

    /**
     * @param HandlePendingPaymentsStrategy $handlePendingPaymentsStrategy
     */
    public function setHandlePendingPaymentsStrategy(HandlePendingPaymentsStrategy $handlePendingPaymentsStrategy): void;

}