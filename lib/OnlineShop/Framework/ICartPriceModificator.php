<?php

/**
 * Interface OnlineShop_Framework_ICartPriceModificator
 */
interface OnlineShop_Framework_ICartPriceModificator {

    /**
     * @return string
     */
    public function getName();

    /**
     * function which modifies the current sub total price
     *
     * @param OnlineShop_Framework_IPrice  $currentSubTotal  - current sub total which is modified and returned
     * @param OnlineShop_Framework_ICart   $cart             - cart
     * @return OnlineShop_Framework_IModificatedPrice
     */
    public function modify(OnlineShop_Framework_IPrice $currentSubTotal, OnlineShop_Framework_ICart $cart);

}
