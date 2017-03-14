<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 * @package    EcommerceFramework
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model;

/**
 * Abstract base class for pimcore objects who should be used as products in the online shop framework
 */
class AbstractProduct extends \Pimcore\Model\Object\Concrete implements IIndexable, ICheckoutable, IProduct {

// =============================================
//     IIndexable Methods
//  =============================================

    /**
     * defines if product is included into the product index. If false, product doesn't appear in product index.
     *
     * @return bool
     */
    public function getOSDoIndexProduct() {
        return true;
    }



    /**
     * returns if product is active.
     * there should either be a attribute in pro product object or
     * it should be overwritten in mapped sub classes of product classes in case of multiple criteria for product active state
     *
     * @param bool $inProductList
     * @throws \OnlineShop\Framework\Exception\UnsupportedException
     * @return bool
     */
    public function isActive($inProductList = false) {
        throw new \OnlineShop\Framework\Exception\UnsupportedException("isActive is not supported for " . get_class($this));
    }


    /**
     * defines the name of the price system for this product.
     * there should either be a attribute in pro product object or
     * it should be overwritten in mapped sub classes of product classes
     *
     * @throws \OnlineShop\Framework\Exception\UnsupportedException
     * @return string
     */
    public function getPriceSystemName() {
        throw new \OnlineShop\Framework\Exception\UnsupportedException("getPriceSystemName is not supported for " . get_class($this));
    }


    /**
     * returns product type for product index (either object or variant).
     * by default it returns type of object, but it may be overwritten if necessary.
     *
     * @return string
     */
    public function getOSIndexType() {
        return $this->getType();
    }


    /**
     * returns parent id for product index.
     * by default it returns id of parent object, but it may be overwritten if necessary.
     *
     * @return int
     */
    public function getOSParentId() {
        return $this->getParentId();
    }

    /**
     * returns array of categories.
     * has to be overwritten either in pimcore object or mapped sub class.
     *
     * @throws \OnlineShop\Framework\Exception\UnsupportedException
     * @return array
     */
    public function getCategories() {
        throw new \OnlineShop\Framework\Exception\UnsupportedException("getCategories is not supported for " . get_class($this));
    }


// =============================================
//     ICheckoutable Methods
//  =============================================

    /**
     * called by default CommitOrderProcessor to get the product name to store it in the order item
     * should be overwritten in mapped sub classes of product classes
     *
     * @throws \OnlineShop\Framework\Exception\UnsupportedException
     * @return string
     */
    public function getOSName() {
        throw new \OnlineShop\Framework\Exception\UnsupportedException("getOSName is not supported for " . get_class($this));
    }

    /**
     * called by default CommitOrderProcessor to get the product number to store it in the order item
     * should be overwritten in mapped sub classes of product classes
     *
     * @throws \OnlineShop\Framework\Exception\UnsupportedException
     * @return string
     */
    public function getOSProductNumber() {
        throw new \OnlineShop\Framework\Exception\UnsupportedException("getOSProductNumber is not supported for " . get_class($this));
    }


    /**
     * defines the name of the availability system for this product.
     * there should either be a attribute in pro product object or
     * it should be overwritten in mapped sub classes of product classes
     *
     * @throws \OnlineShop\Framework\Exception\UnsupportedException
     * @return string
     */
    public function getAvailabilitySystemName() {
        return "default";
    }


    /**
     * checks if product is bookable
     * default implementation checks if there is a price available and of the product is active.
     * may be overwritten in subclasses for additional logic
     *
     * @return bool
     */
    public function getOSIsBookable($quantityScale = 1) {
        $price = $this->getOSPrice($quantityScale);
        return !empty($price) && $this->isActive();
    }


    /**
     * returns instance of price system implementation based on result of getPriceSystemName()
     *
     * @return \OnlineShop\Framework\PriceSystem\IPriceSystem
     */
    public function getPriceSystemImplementation() {
        return \OnlineShop\Framework\Factory::getInstance()->getPriceSystem($this->getPriceSystemName());
    }

    /**
     * returns instance of availability system implementation based on result of getAvailabilitySystemName()
     *
     * @return \OnlineShop\Framework\AvailabilitySystem\IAvailabilitySystem
     */
    public function getAvailabilitySystemImplementation() {
        return \OnlineShop\Framework\Factory::getInstance()->getAvailabilitySystem($this->getAvailabilitySystemName());
    }

    /**
     * returns price for given quantity scale
     *
     * @param int $quantityScale
     * @return \OnlineShop\Framework\PriceSystem\IPrice
     */
    public function getOSPrice($quantityScale = 1) {
        return $this->getOSPriceInfo($quantityScale)->getPrice();

    }

    /**
     * returns price info for given quantity scale.
     * price info might contain price and additional information for prices like discounts, ...
     *
     * @param int $quantityScale
     * @return \OnlineShop\Framework\PriceSystem\IPriceInfo
     */
    public function getOSPriceInfo($quantityScale = 1) {
        return $this->getPriceSystemImplementation()->getPriceInfo($this,$quantityScale);
    }

    /**
     * returns availability info based on given quantity
     *
     * @param int $quantity
     * @return \OnlineShop\Framework\AvailabilitySystem\IAvailability
     */
    public function getOSAvailabilityInfo($quantity = null) {
        return $this->getAvailabilitySystemImplementation()->getAvailabilityInfo($this, $quantity);

    }








    /**
     * @static
     * @param int $id
     * @return null|\Pimcore\Model\Object\AbstractObject
     */
    public static function getById($id) {
        $object = \Pimcore\Model\Object\AbstractObject::getById($id);

        if ($object instanceof AbstractProduct) {
            return $object;
        }
        return null;
    }

}
