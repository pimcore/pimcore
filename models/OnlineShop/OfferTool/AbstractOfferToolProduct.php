<?php

/**
 * Abstract base class for pimcore objects who should be used as custom products in the offer tool
 */
class OnlineShop_OfferTool_AbstractOfferToolProduct extends Object_Concrete implements OnlineShop_Framework_ProductInterfaces_ICheckoutable {

// =============================================
//     ICheckoutable Methods
//  =============================================

    /**
     * should be overwritten in mapped sub classes of product classes
     *
     * @throws OnlineShop_Framework_Exception_UnsupportedException
     * @return string
     */
    public function getOSName() {
        throw new OnlineShop_Framework_Exception_UnsupportedException("getOSName is not supported for " . get_class($this));
    }

    /**
     * should be overwritten in mapped sub classes of product classes
     *
     * @throws OnlineShop_Framework_Exception_UnsupportedException
     * @return string
     */
    public function getOSProductNumber() {
        throw new OnlineShop_Framework_Exception_UnsupportedException("getOSProductNumber is not supported for " . get_class($this));
    }


    /**
     * defines the name of the availability system for this product.
     * for offline tool there are no availability systems implemented
     *
     * @throws OnlineShop_Framework_Exception_UnsupportedException
     * @return string
     */
    public function getAvailabilitySystemName() {
        return "none";
    }


    /**
     * checks if product is bookable
     * returns always true in default implementation
     *
     * @return bool
     */
    public function getOSIsBookable($quantityScale = 1) {
        return true;
    }


    /**
     * defines the name of the price system for this product.
     * there should either be a attribute in pro product object or
     * it should be overwritten in mapped sub classes of product classes
     *
     * @return string
     */
    public function getPriceSystemName()
    {
        return "defaultOfferToolPriceSystem";
    }

    /**
     * returns instance of price system implementation based on result of getPriceSystemName()
     *
     * @return OnlineShop_Framework_IPriceSystem
     */
    public function getPriceSystemImplementation() {
        return OnlineShop_Framework_Factory::getInstance()->getPriceSystem($this->getPriceSystemName());
    }

    /**
     * returns instance of availability system implementation based on result of getAvailabilitySystemName()
     *
     * @return OnlineShop_Framework_IAvailabilitySystem
     */
    public function getAvailabilitySystemImplementation() {
        return OnlineShop_Framework_Factory::getInstance()->getAvailabilitySystem($this->getAvailabilitySystemName());
    }

    /**
     * returns price for given quantity scale
     *
     * @param int $quantityScale
     * @return OnlineShop_Framework_IPrice
     */
    public function getOSPrice($quantityScale = 1) {
        return $this->getOSPriceInfo($quantityScale)->getPrice();

    }

    /**
     * returns price info for given quantity scale.
     * price info might contain price and additional information for prices like discounts, ...
     *
     * @param int $quantityScale
     * @return OnlineShop_Framework_AbstractPriceInfo
     */
    public function getOSPriceInfo($quantityScale = 1) {
        return $this->getPriceSystemImplementation()->getPriceInfo($this,$quantityScale);
    }

    /**
     * returns availability info based on given quantity
     *
     * @param int $quantity
     * @return OnlineShop_Framework_IAvailability
     */
    public function getOSAvailabilityInfo($quantity = null) {
        return $this->getAvailabilitySystemImplementation()->getAvailabilityInfo($this, $quantity);

    }

    /**
     * @static
     * @param int $id
     * @return null|Object_Abstract
     */
    public static function getById($id) {
        $object = Object_Abstract::getById($id);

        if ($object instanceof OnlineShop_OfferTool_AbstractOfferToolProduct) {
            return $object;
        }
        return null;
    }


    /**
     * @throws OnlineShop_Framework_Exception_UnsupportedException
     * @return string
     */
    public function getProductGroup() {
        throw new OnlineShop_Framework_Exception_UnsupportedException("getProductGroup is not implemented for " . get_class($this));
    }



}
