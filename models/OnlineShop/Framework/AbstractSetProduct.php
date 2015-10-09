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
 * Abstract base class for pimcore objects who should be used as set products in the online shop framework
 */
class OnlineShop_Framework_AbstractSetProduct extends OnlineShop_Framework_AbstractProduct {

    /**
     * returns mandatory products for a set product
     *
     * @throws OnlineShop_Framework_Exception_UnsupportedException
     * @return OnlineShop_Framework_AbstractSetProductEntry[]
     */
    public function getMandatoryProductEntries() {
        throw new OnlineShop_Framework_Exception_UnsupportedException("getMandatoryProductEntries is not supported for " . get_class($this));
    }

    /**
     * returns optional products for a set product
     *
     * @throws OnlineShop_Framework_Exception_UnsupportedException
     * @return OnlineShop_Framework_AbstractSetProductEntry[]
     */
    public function getOptionalProductEntries() {
        throw new OnlineShop_Framework_Exception_UnsupportedException("getOptionalProductEntries is not supported for " . get_class($this));
    }


    /**
     * checks if product is bookable
     * default implementation checks if set product is active, given products are bookable and set product has a valid price
     * if no products given, mandatory products are used
     *
     * @param int $quantityScale
     * @param OnlineShop_Framework_AbstractSetProductEntry[] $products
     * @return bool
     */
    public function getOSIsBookable($quantityScale = 1, $products = null) {
        if($this->isActive()) {
            if(empty($products)) {
                $products = $this->getMandatoryProductEntries();
            }
            if(!empty($products)) {
                foreach($products as $productEntry) {
                    if($productEntry->getQuantity() > 0) {
                        if(!$productEntry->getProduct()->getOSIsBookable($productEntry->getQuantity())) {
                            return false;
                        }
                    }
                }
            }
            //set is only bookable when price is valid!!! //
            $priceInfo =$this->getOSPriceInfo($quantityScale,$products);
            return $priceInfo!=null&&$priceInfo->isPriceValid();
        } else {
            return false;
        }
    }

    /**
     * Delivers price of set product with given products
     *
     * @throws OnlineShop_Framework_Exception_UnsupportedException
     * @param OnlineShop_Framework_AbstractSetProductEntry[] $products
     * @param int $quantityScale
     * @return OnlineShop_Framework_IPrice
     * @deprecated - use getOSPriceInfo($quantityScale,$products) instead
     */
    public function getCalculatedPrice($products, $quantityScale = 1) {
        return $this->getOSPrice($quantityScale, $products);
    }

    /**
     * Delivers priceInfo of setproduct with given products
     *
     * @throws OnlineShop_Framework_Exception_UnsupportedException
     * @param OnlineShop_Framework_AbstractSetProductEntry[] $products
     * @param int $quantityScale
     * @return stdClass
     * @deprecated - use getOSPriceInfo($quantityScale,$products) instead
     */
    public function getCalculatedPriceInfo($products, $quantityScale = 1) {
        return $this->getOSPriceInfo($quantityScale, $products);
        //return $this->getPriceSystemImplementation()->getPriceInfo($this, $products);
    }


    /**
    * Delivers min price for given products or with default mandatory products of set product
     *
     * @throws OnlineShop_Framework_Exception_UnsupportedException
     * @param null $quantityScale
     * @param null $products
     * @return OnlineShop_Framework_IPrice
     */
    public function getOSPrice($quantityScale = null, $products = null) {
        if( $this->getOSPriceInfo($quantityScale,$products)) {
            return $this->getOSPriceInfo($quantityScale,$products)->getPrice();
        }
        return null;
    }

    /**
     * Delivers priceinfo with min price for given products or with  default mandatory products of set product
     *
     * @throws OnlineShop_Framework_Exception_UnsupportedException
     * @param int $quantityScale
     * @param null $products
     * @return OnlineShop_Framework_IPriceInfo
     */
    public function getOSPriceInfo($quantityScale = null, $products = null) {
        if (!is_array($products)) {
            $products = $this->getMandatoryProductEntries();
        }
        return $this->getPriceSystemImplementation()->getPriceInfo($this, $quantityScale, $products);
    }

    /**
     * @param int $quantity
     * @param $products OnlineShop_Framework_AbstractSetProductEntry[]
     * @return OnlineShop_Framework_IAvailability
     */
    public function getOSAvailabilityInfo($quantity = null, $products = null) {
        if (!is_array($products)) {
            $products = $this->getMandatoryProductEntries();
        }
        return $this->getAvailabilitySystemImplementation()->getAvailabilityInfo($this, $quantity, $products);
    }


    /**
     * checks if all mandatory of set products are set in given product list
     *
     * @throws OnlineShop_Framework_Exception_UnsupportedException
     * @param  OnlineShop_Framework_AbstractSetProductEntry[] $products
     * @return void
     */
    protected function checkMandatoryProducts($products) {
        $mandatoryProducts = $this->getMandatoryProductEntries();
        $mandatoryProductIds = array();
        foreach ($mandatoryProducts as $p) {
            $mandatoryProductIds[$p->getId()] = $p->getQuantity();
        }

        foreach ($products as $product) {
            if ($mandatoryProductIds[$product->getId()]) {
                $mandatoryProductIds[$product->getId()] -= $product->getQuantity();
                if ($mandatoryProductIds[$product->getId()] == 0) {
                    unset($mandatoryProductIds[$product->getId()]);
                }
            }
        }

        if (count($mandatoryProductIds) > 0) {
            throw new OnlineShop_Framework_Exception_UnsupportedException("Not all mandatory Products in product list.");
        }
    }
}
