<?php

/**
 * Interface OnlineShop_Framework_IAvailabilitySystem
 */
interface OnlineShop_Framework_IAvailabilitySystem {


    /**
     * @abstract
     * @param OnlineShop_Framework_ProductInterfaces_ICheckoutable $abstractProduct
     * @param int $quantityScale
     * @param null $products
     * @return OnlineShop_Framework_IAvailability
     */
    public function getAvailabilityInfo(OnlineShop_Framework_ProductInterfaces_ICheckoutable $abstractProduct, $quantityScale = 1, $products = null);




}
