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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager;

use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICart;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\IStatus;

interface IOrderManager
{
    /**
     * @return IOrderList
     */
    public function createOrderList();

    /**
     * @param AbstractOrder $order
     *
     * @return IOrderAgent
     */
    public function createOrderAgent(AbstractOrder $order);

    /**
     * @param int $id
     */
    public function setParentOrderFolder($id);

    /**
     * @param string $classname
     */
    public function setOrderClass($classname);

    /**
     * @param string $classname
     */
    public function setOrderItemClass($classname);

    /**
     * Looks if order object for given cart already exists, otherwise creates it
     *
     * move to ordermanagers
     *
     * @return AbstractOrder
     */
    public function getOrCreateOrderFromCart(ICart $cart);

    /**
     * Looks if order object for given cart exists and returns it - it does not create it!
     *
     * @param ICart $cart
     *
     * @return AbstractOrder
     */
    public function getOrderFromCart(ICart $cart);

    /**
     * gets order based on given payment status
     *
     * @param IStatus $paymentStatus
     *
     * @return AbstractOrder
     */
    public function getOrderByPaymentStatus(IStatus $paymentStatus);

    /**
     * Build order listing
     *
     * @return \Pimcore\Model\Object\Listing\Concrete
     *
     * @throws \Exception
     */
    public function buildOrderList();

    /**
     * Build order item listing
     *
     * @return \Pimcore\Model\Object\Listing\Concrete
     *
     * @throws \Exception
     */
    public function buildOrderItemList();
}
