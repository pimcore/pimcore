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

namespace OnlineShop\Framework\PriceSystem;

/**
 * Class AbstractPriceSystem
 *
 * abstract implementation for price systems
 */
abstract class AbstractPriceSystem implements IPriceSystem {

    protected $config;

    public function __construct($config) {
        $this->config = $config;
    }


     /**
     * @param \OnlineShop\Framework\Model\ICheckoutable $abstractProduct
     * @param int | string $quantityScale
     *    quantityScale - numeric or string (allowed values: \OnlineShop\Framework\PriceSystem\IPriceInfo::MIN_PRICE
     * @param \OnlineShop\Framework\Model\ICheckoutable[] $products
     * @return IPriceInfo
     */
    public function getPriceInfo(\OnlineShop\Framework\Model\ICheckoutable $abstractProduct, $quantityScale = null, $products = null) {
        return $this->initPriceInfoInstance($quantityScale,$abstractProduct,$products);
    }


    /**
     * returns shop-instance specific implementation of priceInfo, override this method in your own price system to
     * set any price values
     * @param $quantityScale
     * @param $product
     * @param $products
     * @return IPriceInfo
     */
    protected function initPriceInfoInstance($quantityScale,$product,$products) {
        $priceInfo = $this->createPriceInfoInstance($quantityScale,$product,$products);

        if($quantityScale !== IPriceInfo::MIN_PRICE)
        {
            $priceInfo->setQuantity($quantityScale);
        }

        $priceInfo->setProduct($product);
        $priceInfo->setProducts($products);
        $priceInfo->setPriceSystem($this);

        // apply pricing rules
        $priceInfoWithRules = \OnlineShop\Framework\Factory::getInstance()->getPricingManager()->applyProductRules( $priceInfo );

        return $priceInfoWithRules;


    }


    /**
     * @param $quantityScale
     * @param $product
     * @param $products
     *
     * @internal param $infoConstructorParams
     * @return AbstractPriceInfo
     */
    abstract function createPriceInfoInstance($quantityScale,$product,$products);
}

