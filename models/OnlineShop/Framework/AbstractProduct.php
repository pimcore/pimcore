<?php

class OnlineShop_Framework_AbstractProduct extends Object_Concrete {


    public function getName() {
        throw new OnlineShop_Framework_Exception_UnsupportedException("getName is not supported for " . get_class($this));
    }

    public function getPriceSystemName() {
        throw new OnlineShop_Framework_Exception_UnsupportedException("getPriceSystemName is not supported for " . get_class($this));
    }

    public function  getAvailabilitySystemName() {
        //default
        return "default";
    }

    public function isActive($inProductList = false) {
        throw new OnlineShop_Framework_Exception_UnsupportedException("getActive is not supported for " . get_class($this));
    }

    /**
     * checks if product is bookable
     * @return bool
     */
    public function getOSIsBookable($quantityScale = 1) {
        $price = $this->getOSPrice($quantityScale);
        return !empty($price) && $this->isActive();
    }

    public function getOSIndexType() {
        return $this->getO_type();
    }

    public function getOSParentId() {
        return $this->getO_parentId();
    }


    public function getCategories() {
        throw new OnlineShop_Framework_Exception_UnsupportedException("getCategories is not supported for " . get_class($this));
    }

    /** @return OnlineShop_Framework_IPriceSystem */
    public function getPriceSystemImplementation() {
        return OnlineShop_Framework_Factory::getInstance()->getPriceSystem($this->getPriceSystemName());
    }

    /**
     * @return OnlineShop_Framework_IAvailabilitySystem
     */
    public function getAvailabilitySystemImplementation() {
        return OnlineShop_Framework_Factory::getInstance()->getAvailabilitySystem($this->getAvailabilitySystemName());
    }

    /**
     * @param int $quantityScale
     * @return OnlineShop_Framework_Price
     */
    public function getOSPrice($quantityScale = 1) {
        return $this->getOSPriceInfo($quantityScale)->getPrice();

    }

    /**
     * @param int $quantityScale
     * @return OnlineShop_Framework_IPriceInfo
     */
    public function getOSPriceInfo($quantityScale = 1) {
        return $this->getPriceSystemImplementation()->getPriceInfo($this,$quantityScale);
    }

    /**
     * @param null $quantity
     * @return OnlineShop_Framework_IAvailability
     */
    public function getOSAvailabilityInfo($quantity = null) {
        return $this->getAvailabilitySystemImplementation()->getAvailabilityInfo($this, $quantity);

    }

    public static function getById($id) {
        $object = Object_Abstract::getById($id);

        if ($object instanceof OnlineShop_Framework_AbstractProduct) {
            return $object;
        }
        return null;
    }

}
