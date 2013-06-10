<?php
/**
 * Created by IntelliJ IDEA.
 * User: rtippler
 * Date: 07.06.11
 * Time: 10:56
 * To change this template use File | Settings | File Templates.
 */

class OnlineShop_Framework_Impl_AttributePriceSystem extends OnlineShop_Framework_Impl_AbstractPriceSystem implements OnlineShop_Framework_IPriceSystem {

    public function filterProductIds($productIds, $fromPrice, $toPrice, $order, $offset, $limit) {
        throw new Exception("not supported yet");
    }

    /**
     * @param $quantityScale
     * @param $product
     * @param $products
     *
     * @internal param $infoConstructorParams
     * @return OnlineShop_Framework_AbstractPriceInfo
     */
    function createPriceInfoInstance($quantityScale, $product, $products) {
        return OnlineShop_Framework_Impl_AttributePriceInfo::getInstance(
            array(
                "quantityScale" => $quantityScale,
                "product" => $product,
                "products" => $products,
                "config" => $this->config
            )
        );
    }



}
