<?php

/**
 * Interface OnlineShop_Framework_IPriceSystem
 */
interface OnlineShop_Framework_IPriceSystem {

    /**
     * creates price info object for given product and quantity scale
     *
     * @param OnlineShop_Framework_ProductInterfaces_ICheckoutable $abstractProduct
     * @param null|int|string $quantityScale - numeric or string (allowed values: OnlineShop_Framework_IPriceInfo::MIN_PRICE)
     * @param OnlineShop_Framework_ProductInterfaces_ICheckoutable[] $products
     * @return OnlineShop_Framework_IPriceInfo
     */
    public function getPriceInfo(OnlineShop_Framework_ProductInterfaces_ICheckoutable $abstractProduct, $quantityScale = null, $products = null);

    /**
     * filters and orders given product ids based on price information
     *
     * @param $productIds
     * @param $fromPrice
     * @param $toPrice
     * @param $order
     * @param $offset
     * @param $limit
     * @return mixed
     */
    public function filterProductIds($productIds, $fromPrice, $toPrice, $order, $offset, $limit);



}
