<?php

abstract class OnlineShop_Framework_Impl_Checkout_AbstractStep implements OnlineShop_Framework_ICheckoutStep {

    /**
     * @var OnlineShop_Framework_ICart
     */
    protected $cart;

    public function __construct(OnlineShop_Framework_ICart $cart) {
        $this->cart = $cart;
    }

}
