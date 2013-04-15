<?php
/**
 * Created by IntelliJ IDEA.
 * User: rtippler
 * Date: 12.01.12
 * Time: 11:27
 * To change this template use File | Settings | File Templates.
 */

abstract class OnlineShop_Framework_Impl_AbstractPriceSystem implements OnlineShop_Framework_IPriceSystem {
     /**
     * @param OnlineShop_Framework_AbstractProduct $abstractProduct
     * @param int | string $quantityScale
     *    quantityScale - numeric or string (allowed values: OnlineShop_Framework_IPriceInfo::MIN_PRICE
     * @param OnlineShop_Framework_AbstractProduct[] $products
     * @return OnlineShop_Framework_AbstractPriceInfo
     */
    public function getPriceInfo(OnlineShop_Framework_AbstractProduct $abstractProduct, $quantityScale = null, $products = null) {
        return $this->initPriceInfoInstance($quantityScale,$abstractProduct,$products);
    }


    /**
     * returns shop-instance specific implementation of priceInfo, override this method in your own pricesystem to
     * set any price values
     * @param $quantityScale
     * @param $product
     * @param $products
     * @return OnlineShop_Framework_IPriceInfo
     */
    protected function initPriceInfoInstance($quantityScale,$product,$products) {
        $priceInfo = $this->createPriceInfoInstance($quantityScale,$product,$products);
        $priceInfo->setQuantity($quantityScale);
        $priceInfo->setProduct($product);
        $priceInfo->setProducts($products);
        $priceInfo->setPriceSystem($this);

        // apply pricing rules
        $priceInfoWithRules = OnlineShop_Framework_Factory::getInstance()->getPricingManager()->applyProductRules( $priceInfo );

        return $priceInfoWithRules;


    }


    /**
     * @param $quantityScale
     * @param $product
     * @param $products
     *
     * @internal param $infoConstructorParams
     * @return OnlineShop_Framework_AbstractPriceInfo
     */
    abstract function createPriceInfoInstance($quantityScale,$product,$products);
}

