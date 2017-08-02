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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\OfferTool;

use Pimcore\Bundle\EcommerceFrameworkBundle\Exception\UnsupportedException;
use Pimcore\Bundle\EcommerceFrameworkBundle\Factory;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\ICheckoutable;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\AbstractPriceInfo;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\IPriceInfo;
use Pimcore\Model\Object\AbstractObject;

/**
 * Abstract base class for pimcore objects who should be used as custom products in the offer tool
 */
class AbstractOfferToolProduct extends \Pimcore\Model\Object\Concrete implements ICheckoutable
{
    // =============================================
    //     ICheckoutable Methods
    //  =============================================

    /**
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
     * for offline tool there are no availability systems implemented
     *
     * @throws UnsupportedException
     *
     * @return string
     */
    public function getAvailabilitySystemName()
    {
        return 'none';
    }

    /**
     * checks if product is bookable
     * returns always true in default implementation
     *
     * @return bool
     */
    public function getOSIsBookable($quantityScale = 1)
    {
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
        return 'defaultOfferToolPriceSystem';
    }

    /**
     * returns instance of price system implementation based on result of getPriceSystemName()
     *
     * @return \Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\IPriceSystem
     */
    public function getPriceSystemImplementation()
    {
        return Factory::getInstance()->getPriceSystem($this->getPriceSystemName());
    }

    /**
     * returns instance of availability system implementation based on result of getAvailabilitySystemName()
     *
     * @return \Pimcore\Bundle\EcommerceFrameworkBundle\AvailabilitySystem\IAvailabilitySystem
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
     * @return \Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\IPrice
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
     * @return IPriceInfo|AbstractPriceInfo
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
     * @return \Pimcore\Bundle\EcommerceFrameworkBundle\AvailabilitySystem\IAvailability
     */
    public function getOSAvailabilityInfo($quantity = null)
    {
        return $this->getAvailabilitySystemImplementation()->getAvailabilityInfo($this, $quantity);
    }

    /**
     * @static
     *
     * @param int $id
     * @param bool $force
     *
     * @return null|AbstractObject
     */
    public static function getById($id, $force = false)
    {
        $object = AbstractObject::getById($id, $force);

        if ($object instanceof AbstractOfferToolProduct) {
            return $object;
        }

        return null;
    }

    /**
     * @throws UnsupportedException
     *
     * @return string
     */
    public function getProductGroup()
    {
        throw new UnsupportedException('getProductGroup is not implemented for ' . get_class($this));
    }
}
