<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\OfferTool;

use Pimcore\Bundle\EcommerceFrameworkBundle\AvailabilitySystem\AvailabilityInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\AvailabilitySystem\AvailabilitySystemInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Exception\UnsupportedException;
use Pimcore\Bundle\EcommerceFrameworkBundle\Factory;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\CheckoutableInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\AbstractPriceInfo;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\PriceInfoInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\PriceInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\PriceSystemInterface;
use Pimcore\Model\DataObject;

/**
 * Abstract base class for pimcore objects who should be used as custom products in the offer tool
 */
abstract class AbstractOfferToolProduct extends \Pimcore\Model\DataObject\Concrete implements CheckoutableInterface
{
    // =============================================
    //     CheckoutableInterface Methods
    //  =============================================

    /**
     * should be overwritten in mapped sub classes of product classes
     *
     * @return string|null
     */
    abstract public function getOSName(): ?string;

    /**
     * should be overwritten in mapped sub classes of product classes
     *
     * @return string|null
     */
    abstract public function getOSProductNumber(): ?string;

    /**
     * defines the name of the availability system for this product.
     * for offline tool there are no availability systems implemented
     *
     * @return string
     */
    public function getAvailabilitySystemName(): string
    {
        return 'none';
    }

    /**
     * checks if product is bookable
     * returns always true in default implementation
     *
     * @return bool
     */
    public function getOSIsBookable($quantityScale = 1): bool
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
    public function getPriceSystemName(): ?string
    {
        return 'defaultOfferToolPriceSystem';
    }

    /**
     * returns instance of price system implementation based on result of getPriceSystemName()
     *
     * @return \Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\PriceSystemInterface
     */
    public function getPriceSystemImplementation(): ?PriceSystemInterface
    {
        return Factory::getInstance()->getPriceSystem($this->getPriceSystemName());
    }

    /**
     * returns instance of availability system implementation based on result of getAvailabilitySystemName()
     *
     * @return AvailabilitySystemInterface
     */
    public function getAvailabilitySystemImplementation(): ?AvailabilitySystemInterface
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
    public function getOSPrice($quantityScale = 1): ?PriceInterface
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
    public function getOSPriceInfo($quantityScale = 1): ?PriceInfoInterface
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
    public function getOSAvailabilityInfo($quantity = null): ?AvailabilityInterface
    {
        return $this->getAvailabilitySystemImplementation()->getAvailabilityInfo($this, $quantity);
    }

    /**
     * @static
     *
     * @param int $id
     * @param bool $force
     *
     * @return AbstractOfferToolProduct|null
     */
    public static function getById($id, $force = false)
    {
        $object = DataObject::getById($id, $force);

        if ($object instanceof AbstractOfferToolProduct) {
            return $object;
        }

        return null;
    }

    /**
     * @throws UnsupportedException
     *
     * @return string|null
     */
    public function getProductGroup(): ?string
    {
        throw new UnsupportedException('getProductGroup is not implemented for ' . get_class($this));
    }
}
