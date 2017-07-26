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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager;

use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICart;
use Pimcore\Bundle\EcommerceFrameworkBundle\Exception\UnsupportedException;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractPaymentInformation;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\IStatus;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\Payment\IPayment;

interface ICheckoutManager
{
    /**
     * Returns all checkout steps defined for this checkout
     *
     * @return ICheckoutStep[]
     */
    public function getCheckoutSteps();

    /**
     * Returns checkout step with given name
     *
     * @param string $stepName
     *
     * @return ICheckoutStep
     */
    public function getCheckoutStep($stepName);

    /**
     * Returns current checkout step
     *
     * @return ICheckoutStep
     */
    public function getCurrentStep();

    /**
     * Returns the cart the checkout is started with
     *
     * @return ICart
     */
    public function getCart();

    /**
     * Commits checkout step
     *
     * All previous steps must be committed, otherwise committing step is not allowed
     *
     * @param ICheckoutStep $step
     * @param  mixed $data
     *
     * @return bool
     */
    public function commitStep(ICheckoutStep $step, $data);

    /**
     * Checks if checkout is finished (= all checkout steps are committed)
     * only a finished checkout can be committed
     *
     * @return bool
     */
    public function isFinished();

    /**
     * Returns if there currently is a active payment
     *
     * @return bool
     */
    public function hasActivePayment();

    /**
     * Starts payment for checkout - only possible if payment provider is configured
     * sets cart to read only mode since it must not changed during ongoing payment process
     *
     * @return AbstractPaymentInformation
     */
    public function startOrderPayment();

    /**
     * Cancels payment for current payment info
     *
     *  - payment will be cancelled, order state will be resetted and cart will we writable again.
     *
     * -> this should be used, when user cancels payment
     *
     * Only possible when payment state is PENDING, otherwise exception is thrown
     *
     * @return null|AbstractOrder
     *
     * @throws UnsupportedException
     */
    public function cancelStartedOrderPayment();

    /**
     * Returns order (creates it if not available yet)
     *
     * @return AbstractOrder
     */
    public function getOrder();

    /**
     * Facade method for
     *
     *  - handling payment response and
     *  - commit order payment
     *
     * Use this for committing order when payment is activated
     *
     * Delegates to commit order processor
     *
     * @param $paymentResponseParams
     *
     * @return AbstractOrder
     */
    public function handlePaymentResponseAndCommitOrderPayment($paymentResponseParams);

    /**
     * Commits order payment
     *
     *  - updates order payment information in order object
     *  - only when payment status == [ORDER_STATE_COMMITTED, ORDER_STATE_PAYMENT_AUTHORIZED] -> order is committed
     *
     * Delegates to commit order processor
     *
     * @deprecated use handlePaymentResponseAndCommitOrderPayment instead
     *
     * @param IStatus $status
     *
     * @return AbstractOrder
     */
    public function commitOrderPayment(IStatus $status);

    /**
     * Commits order - does not consider any payment
     *
     * Use this for committing order when no payment is activated
     *
     * Delegates to commit order processor
     *
     * @return AbstractOrder
     */
    public function commitOrder();

    /**
     * Returns if checkout process and subsequently order is committed
     * basically checks, if order is available and if this order is committed
     *
     * @return bool
     */
    public function isCommitted();

    /**
     * Returns payment adapter
     *
     * @return IPayment|null
     */
    public function getPayment();

    /**
     * Cleans up orders with state pending payment after 1h -> delegates this to commit order processor
     *
     * @return void
     */
    public function cleanUpPendingOrders();
}
