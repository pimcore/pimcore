<?php
/**
 * Created by IntelliJ IDEA.
 * User: rtippler
 * Date: 28.10.11
 * Time: 14:58
 * To change this template use File | Settings | File Templates.
 */
class OnlineShop_Framework_Impl_AttributeAvailabilitySystem implements OnlineShop_Framework_IAvailabilitySystem{
    /**
     * @param OnlineShop_Framework_AbstractProduct $abstractProduct
     * @param int $quantityScale
     * @param null $products
     * @return OnlineShop_Framework_IAvailability
     */
    public function getAvailabilityInfo(OnlineShop_Framework_AbstractProduct $abstractProduct, $quantityScale = 1, $products = null) {
        return $abstractProduct;
    }



}

