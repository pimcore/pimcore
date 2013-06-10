<?php
/**
 * Created by IntelliJ IDEA.
 * User: rtippler
 * Date: 30.05.11
 * Time: 15:37
 * To change this template use File | Settings | File Templates.
 */
 
interface OnlineShop_Framework_IPriceSystem {

     /**
     * @param OnlineShop_Framework_ProductInterfaces_ICheckoutable $abstractProduct
     * @param int | string $quantityScale
     *    quantityScale - numeric or string (allowed values: OnlineShop_Framework_IPriceInfo::MIN_PRICE
     * @param OnlineShop_Framework_ProductInterfaces_ICheckoutable[] $products
     * @return OnlineShop_Framework_Pricing_IPriceInfo
     */
    public function getPriceInfo(OnlineShop_Framework_ProductInterfaces_ICheckoutable $abstractProduct, $quantityScale = null, $products = null);

    /**
     * @abstract
     * @param  $productIds
     * @param  $fromPrice
     * @param  $toPrice
     * @param  $order
     * @param $offset
     * @param  $limit
     * @return array(pimcore productid => price value)
     */
    public function filterProductIds($productIds, $fromPrice, $toPrice, $order, $offset, $limit);



}
