<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */


/**
 * Class OnlineShop_Framework_Impl_AbstractPriceSystem
 *
 * abstract implementation for price systems
 */
abstract class OnlineShop_Framework_Impl_AbstractPriceSystem implements OnlineShop_Framework_IPriceSystem {

    protected $config;

    public function __construct($config) {
        $this->config = $config;
    }


     /**
     * @param OnlineShop_Framework_ProductInterfaces_ICheckoutable $abstractProduct
     * @param int | string $quantityScale
     *    quantityScale - numeric or string (allowed values: OnlineShop_Framework_IPriceInfo::MIN_PRICE
     * @param OnlineShop_Framework_ProductInterfaces_ICheckoutable[] $products
     * @return OnlineShop_Framework_Pricing_IPriceInfo
     */
    public function getPriceInfo(OnlineShop_Framework_ProductInterfaces_ICheckoutable $abstractProduct, $quantityScale = null, $products = null) {
        return $this->initPriceInfoInstance($quantityScale,$abstractProduct,$products);
    }


    /**
     * returns shop-instance specific implementation of priceInfo, override this method in your own price system to
     * set any price values
     * @param $quantityScale
     * @param $product
     * @param $products
     * @return OnlineShop_Framework_Pricing_IPriceInfo
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

