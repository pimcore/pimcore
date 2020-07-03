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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\Model;

use Pimcore\Bundle\EcommerceFrameworkBundle\AvailabilitySystem\AvailabilityInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\AvailabilitySystem\AvailabilitySystemInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Exception\UnsupportedException;
use Pimcore\Bundle\EcommerceFrameworkBundle\Factory;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\AbstractPriceInfo;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\PriceInfoInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\PriceInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\PriceSystemInterface;

/**
 * Abstract base class for pimcore objects who should be used as products in the online shop framework
 */
class AbstractProduct extends \Pimcore\Model\DataObject\Concrete implements ProductInterface, IndexableInterface, CheckoutableInterface
{
    // =============================================
    //     IndexableInterface Methods
    //  =============================================

    /**
     * defines if product is included into the product index. If false, product doesn't appear in product index.
     *
     * @return bool
     */
    public function getOSDoIndexProduct()
    {
        return true;
    }

    /**
     * returns if product is active.
     * there should either be a attribute in pro product object or
     * it should be overwritten in mapped sub classes of product classes in case of multiple criteria for product active state
     *
     * @param bool $inProductList
     *
     * @throws UnsupportedException
     *
     * @return bool
     */
    public function isActive($inProductList = false)
    {
        throw new UnsupportedException('isActive is not supported for ' . get_class($this));
    }

    /**
     * defines the name of the price system for this product.
     * there should either be a attribute in pro product object or
     * it should be overwritten in mapped sub classes of product classes
     *
     * @throws UnsupportedException
     *
     * @return string
     */
    public function getPriceSystemName()
    {
        throw new UnsupportedException('getPriceSystemName is not supported for ' . get_class($this));
    }

    /**
     * returns product type for product index (either object or variant).
     * by default it returns type of object, but it may be overwritten if necessary.
     *
     * @return string
     */
    public function getOSIndexType()
    {
        return $this->getType();
    }

    /**
     * returns parent id for product index.
     * by default it returns id of parent object, but it may be overwritten if necessary.
     *
     * @return int
     */
    public function getOSParentId()
    {
        return $this->getParentId();
    }

    /**
     * returns array of categories.
     * has to be overwritten either in pimcore object or mapped sub class.
     *
     * @throws UnsupportedException
     *
     * @return array
     */
    public function getCategories()
    {
        throw new UnsupportedException('getCategories is not supported for ' . get_class($this));
    }

    // =============================================
    //     CheckoutableInterface Methods
    //  =============================================

    /**
     * called by default CommitOrderProcessor to get the product name to store it in the order item
     * should be overwritten in mapped sub classes of product classes
     *
     * @throws UnsupportedException
     *
     * @return string
     */
    public function getOSName()
    {
        throw new UnsupportedException('getOSName is not supported for ' . get_class($this));
    }

    /**
     * called by default CommitOrderProcessor to get the product number to store it in the order item
     * should be overwritten in mapped sub classes of product classes
     *
     * @throws UnsupportedException
     *
     * @return string
     */
    public function getOSProductNumber()
    {
        throw new UnsupportedException('getOSProductNumber is not supported for ' . get_class($this));
    }

    /**
     * defines the name of the availability system for this product.
     * there should either be a attribute in pro product object or
     * it should be overwritten in mapped sub classes of product classes
     *
     * @throws UnsupportedException
     *
     * @return string
     */
    public function getAvailabilitySystemName()
    {
        return 'default';
    }

    /**
     * checks if product is bookable
     * default implementation checks if there is a price available and of the product is active.
     * may be overwritten in subclasses for additional logic
     *
     * @return bool
     */
    public function getOSIsBookable($quantityScale = 1)
    {
        $price = $this->getOSPrice($quantityScale);

        return !empty($price) && $this->isActive();
    }

    /**
     * returns instance of price system implementation based on result of getPriceSystemName()
     *
     * @return PriceSystemInterface
     */
    public function getPriceSystemImplementation()
    {
        return Factory::getInstance()->getPriceSystem($this->getPriceSystemName());
    }

    /**
     * returns instance of availability system implementation based on result of getAvailabilitySystemName()
     *
     * @return AvailabilitySystemInterface
     */
    public function getAvailabilitySystemImplementation()
    {
        return Factory::getInstance()->getAvailabilitySystem($this->getAvailabilitySystemName());
    }

    /**
     * returns price for given quantity scale
     *
     * @param int $quantityScale
     *
     * @return PriceInterface
     */
    public function getOSPrice($quantityScale = 1)
    {
        return $this->getOSPriceInfo($quantityScale)->getPrice();
    }

    /**
     * returns price info for given quantity scale.
     * price info might contain price and additional information for prices like discounts, ...
     *
     * @param int $quantityScale
     *
     * @return PriceInfoInterface|AbstractPriceInfo
     */
    public function getOSPriceInfo($quantityScale = 1)
    {
        return $this->getPriceSystemImplementation()->getPriceInfo($this, $quantityScale);
    }

    /**
     * returns availability info based on given quantity
     *
     * @param int $quantity
     *
     * @return AvailabilityInterface
     */
    public function getOSAvailabilityInfo($quantity = null)
    {
        return $this->getAvailabilitySystemImplementation()->getAvailabilityInfo($this, $quantity);
    }
}
