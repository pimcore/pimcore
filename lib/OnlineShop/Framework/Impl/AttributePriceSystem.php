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
 * Class OnlineShop_Framework_Impl_AttributePriceSystem
 *
 * price system implementation for attribute price system
 */
class OnlineShop_Framework_Impl_AttributePriceSystem extends OnlineShop_Framework_Impl_CachingPriceSystem implements OnlineShop_Framework_IPriceSystem {

    /**
     * @param $productIds
     * @param $fromPrice
     * @param $toPrice
     * @param $order
     * @param $offset
     * @param $limit
     * @throws Exception
     */
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

        $amount = $this->calculateAmount($product, $products);
        $price = $this->getPriceClassInstance($amount);
        $totalPrice = $this->getPriceClassInstance($amount * $quantityScale);

        return new OnlineShop_Framework_Impl_AttributePriceInfo($price, $quantityScale, $totalPrice);
    }

    /**
     * calculates prices from product
     *
     * @param $product
     * @param $products
     * @return float
     * @throws Exception
     */
    protected function calculateAmount($product, $products) {
        $getter = "get" . ucfirst($this->config->attributename);
        if(method_exists($product, $getter)) {

            if(!empty($products)) {
                $sum = 0;
                foreach($products as $p) {

                    if($p instanceof OnlineShop_Framework_AbstractSetProductEntry) {
                        $sum += $p->getProduct()->$getter() * $p->getQuantity();
                    } else {
                        $sum += $p->$getter();
                    }
                }
                return $sum;

            } else {
                return $product->$getter();
            }
        }
    }

    /**
     * returns default currency based on environment settings
     *
     * @return Zend_Currency
     */
    protected function getDefaultCurrency() {
        return new Zend_Currency(OnlineShop_Framework_Factory::getInstance()->getEnvironment()->getCurrencyLocale());
    }

    /**
     * creates instance of OnlineShop_Framework_IPrice
     *
     * @param $amount
     * @return OnlineShop_Framework_IPrice
     * @throws Exception
     */
    protected function getPriceClassInstance($amount) {
        if($this->config->priceclass) {
            $price = new $this->config->priceClass($amount, $this->getDefaultCurrency(), false);
            if(!$price instanceof OnlineShop_Framework_IPrice) {
                throw new Exception("Price Class does not implement OnlineShop_Framework_IPrice");
            }
        } else {
            $price = new OnlineShop_Framework_Impl_Price($amount, $this->getDefaultCurrency(), false);
        }
        return $price;
    }

}
