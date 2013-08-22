<?php

interface OnlineShop_Framework_ICommitOrderProcessor {

    /**
     * @return OnlineShop_Framework_AbstractOrder
     */
    public function getOrCreateOrder(OnlineShop_Framework_ICart $cart);

    /**
     * @return OnlineShop_Framework_AbstractPaymentInformation
     */
    public function getOrCreateActivePaymentInfo(OnlineShop_Framework_AbstractOrder $order);

    /**
     * @param OnlineShop_Framework_Impl_Checkout_Payment_Status $status
     * @return OnlineShop_Framework_AbstractOrder
     */
    public function updateOrderPayment(OnlineShop_Framework_Impl_Checkout_Payment_Status $status);

    /**
     * @abstract
     * @param OnlineShop_Framework_ICart $cart
     * @return OnlineShop_Framework_AbstractOrder
     */
    public function commitOrder(OnlineShop_Framework_ICart $cart);

    /**
     * @abstract
     * @param int $id
     */
    public function setParentOrderFolder($id);

    /**
     * @abstract
     * @param string $classname
     */
    public function setOrderClass($classname);

    /**
     * @abstract
     * @param string $classname
     */
    public function setOrderItemClass($classname);

    /**
     * @abstract
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
