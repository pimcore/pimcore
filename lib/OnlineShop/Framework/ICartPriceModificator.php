<?php

interface OnlineShop_Framework_ICartPriceModificator {

    public function getName();

    /**
     * @abstract
     * @return OnlineShop_Framework_IPrice
     */
    public function modify(OnlineShop_Framework_IPrice $currentSubTotal, OnlineShop_Framework_ICart $cart);

}
