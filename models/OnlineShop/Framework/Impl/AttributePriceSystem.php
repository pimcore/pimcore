<?php
/**
 * Created by IntelliJ IDEA.
 * User: rtippler
 * Date: 07.06.11
 * Time: 10:56
 * To change this template use File | Settings | File Templates.
 */

class OnlineShop_Framework_Impl_AttributePriceSystem implements OnlineShop_Framework_IPriceSystem {

    /**
     * @param OnlineShop_Framework_AbstractProduct $product
     * @param int $quantityScale
     * @param null $products
     * @return OnlineShop_Framework_AbstractProduct
     */
    public function getPriceInfo(OnlineShop_Framework_AbstractProduct $product, $quantityScale = 1, $products = null) {
        return $product;
    }

    /**
     * @abstract
     * @param \OnlineShop_Framework_AbstractProduct | Website_OnlineShop_Product $product
     * @param int|null $quantityScale
     * @param null $products
     * @return OnlineShop_Framework_Price
     */
    public function getPrice(OnlineShop_Framework_AbstractProduct $product, $quantityScale = 1, $products = null) {
        return new OnlineShop_Framework_Impl_Price($product->getOfferprice(),new Zend_Currency(new Zend_Locale('de_AT')), false);
    }

    public function filterProductIds($productIds, $fromPrice, $toPrice, $order, $offset, $limit) {
        throw new Exception("not supported yet");
    }
}
