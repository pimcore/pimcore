<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */


/**
 * Interface OnlineShop_Framework_ICommitOrderProcessor
 */
interface OnlineShop_Framework_ICommitOrderProcessor {

    /**
     * Looks if order object for given cart already exists, otherwise creates it
     *
     * @return OnlineShop_Framework_AbstractOrder
     */
    public function getOrCreateOrder(OnlineShop_Framework_ICart $cart);

    /**
     * Gets or creates active payment info for the given order
     *
     * @deprecated use orderManager instead
     * @param OnlineShop_Framework_AbstractOrder $order
     * @return OnlineShop_Framework_AbstractPaymentInformation
     */
    public function getOrCreateActivePaymentInfo(OnlineShop_Framework_AbstractOrder $order);

    /**
     * Updates payment information with given status information
     * order id is retrieved from status object
     *
     * @deprecated use orderManager instead
     * @param OnlineShop_Framework_Payment_IStatus $status
     * @return OnlineShop_Framework_AbstractOrder
     */
    public function updateOrderPayment(OnlineShop_Framework_Payment_IStatus $status);

    /**
     * commits order
     *
     * @param OnlineShop_Framework_ICart $cart
     * @return OnlineShop_Framework_AbstractOrder
     */
    public function commitOrder(OnlineShop_Framework_ICart $cart);

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
     * @param string $confirmationMail
     */
    public function setConfirmationMail($confirmationMail);


    /**
     * cleans up orders with state pending payment after 1h
     *
     * @return void
     */
    public function cleanUpPendingOrders();
}
