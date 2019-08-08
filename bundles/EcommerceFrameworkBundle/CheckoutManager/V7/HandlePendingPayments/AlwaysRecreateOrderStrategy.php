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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\V7\HandlePendingPayments;

use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder;
use Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\V7\OrderManagerInterface;

class AlwaysRecreateOrderStrategy implements HandlePendingPaymentsStrategyInterface
{
    /**
     * @param AbstractOrder $order
     * @param CartInterface $cart
     * @param OrderManagerInterface $orderManager
     *
     * @return AbstractOrder
     */
    public function handlePaymentNotAllowed(AbstractOrder $order, CartInterface $cart, OrderManagerInterface $orderManager): AbstractOrder
    {
        return $orderManager->recreateOrder($cart);
    }
}
