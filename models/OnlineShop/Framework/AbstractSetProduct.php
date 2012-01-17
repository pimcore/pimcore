<?php

class OnlineShop_Framework_AbstractSetProduct extends OnlineShop_Framework_AbstractProduct {

    /**
     * @throws OnlineShop_Framework_Exception_UnsupportedException
     * @return OnlineShop_Framework_AbstractSetProductEntry[]
     */
    public function getMandatoryProductEntries() {
        throw new OnlineShop_Framework_Exception_UnsupportedException("getMandatoryProductEntries is not supported for " . get_class($this));
    }

    /**
     * @throws OnlineShop_Framework_Exception_UnsupportedException
     * @return OnlineShop_Framework_AbstractSetProductEntry[]
     */
    public function getOptionalProductEntries() {
        throw new OnlineShop_Framework_Exception_UnsupportedException("getOptionalProductEntries is not supported for " . get_class($this));
    }


    /**
     * @param OnlineShop_Framework_AbstractSetProductEntry[] $products
     * @return bool
     */
    public function getOSIsBookable($quantityScale = 1, $products = null) {
        $bookable = parent::getOSIsBookable($quantityScale);
        if($bookable) {
            if(empty($products)) {
                $products = $this->getMandatoryProductEntries();
            }
            foreach($products as $productEntry) {
                if(!$productEntry->getProduct()->getOSIsBookable($productEntry->getQuantity())) {
                    return false;
                }
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * Delivers price of setproduct with given products
     *
     * @throws OnlineShop_Framework_Exception_UnsupportedException
     * @param OnlineShop_Framework_AbstractSetProductEntry[] $products
     * @param int $quantityScale
     * @return OnlineShop_Framework_IPrice
     * @deprecated - use getOSPriceInfo($quantityScale,$products) instead
     */
    public function getCalculatedPrice($products, $quantityScale = 1) {
        return $this->getOSPrice($quantityScale, $products);
        //return $this->getPriceSystemImplementation()->getPrice($this, $quantityScale, $products);
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
    * Delivers min price for given products or with default mandatory products of setproduct
     *
     * @throws OnlineShop_Framework_Exception_UnsupportedException
     * @param null $quantityScale
     * @param null $products
     * @return OnlineShop_Framework_IPrice
     */
    public function getOSPrice($quantityScale = null, $products = null) {
        return $this->getOSPriceInfo($quantityScale,$products)->getPrice();
    }

    /**
     * Delivers priceinfo with min price for given products or with  default mandatory products of setproduct
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
