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
     * @Deprecated
     * @abstract
     * @param OnlineShop_Framework_AbstractProduct $abstractProduct
     * @param int|null $quantityScale
     * @param OnlineShop_Framework_AbstractProduct[] $products
     * @return OnlineShop_Framework_IPrice
     */
    public function getPrice(OnlineShop_Framework_AbstractProduct $abstractProduct, $quantityScale = 1, $products = null);

    /**
     * @abstract
     * @param OnlineShop_Framework_AbstractProduct $abstractProduct
     * @param int $quantityScale
     * @param OnlineShop_Framework_AbstractProduct[] $products
     * @return OnlineShop_Framework_PriceWrapper
     */
    public function getPriceInfo(OnlineShop_Framework_AbstractProduct $abstractProduct, $quantityScale = 1, $products = null);

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
