<?php

class OnlineShop_Framework_Impl_CartPriceModificatorShipping implements OnlineShop_Framework_ICartPriceModificator {

    public function getName() {
        return "shipping";
    }

    /**
     * @return OnlineShop_Framework_IPrice
     */
    public function modify(OnlineShop_Framework_IPrice $currentSubTotal, OnlineShop_Framework_ICart $cart) {
        return new OnlineShop_Framework_Impl_Price("55.99", new Zend_Currency(new Zend_Locale("de_AT")));
    }
}