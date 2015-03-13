<?php

/**
 * Interface for prices returned by price modifcators
 */
interface OnlineShop_Framework_IModificatedPrice extends OnlineShop_Framework_IPrice {

    /**
     * @return string
     */
    public function getDescription();

}
 
