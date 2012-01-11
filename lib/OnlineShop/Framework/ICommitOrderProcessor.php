<?php

interface OnlineShop_Framework_ICommitOrderProcessor {
    /**
     * @abstract
     * @param OnlineShop_Framework_ICart $cart
     * @return mixed
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
}
