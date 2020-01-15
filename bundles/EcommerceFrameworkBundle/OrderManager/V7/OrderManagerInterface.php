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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\V7;

use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Exception\UnsupportedException;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder;

interface OrderManagerInterface extends \Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\OrderManagerInterface
{
    /**
     * @param CartInterface $cart
     *
     * @return AbstractOrder
     */
    public function recreateOrder(CartInterface $cart): AbstractOrder;

    /**
     * @param AbstractOrder $sourceOrder
     *
     * @return AbstractOrder
     */
    public function recreateOrderBasedOnSourceOrder(AbstractOrder $sourceOrder): AbstractOrder;

    /**
     * @param CartInterface $cart
     *
     * @return bool
     */
    public function cartHasPendingPayments(CartInterface $cart): bool;

    /**
     * @param CartInterface $cart
     * @param AbstractOrder $order
     *
     * @return bool
     *
     * @throws UnsupportedException
     */
    public function orderNeedsUpdate(CartInterface $cart, AbstractOrder $order): bool;
}
