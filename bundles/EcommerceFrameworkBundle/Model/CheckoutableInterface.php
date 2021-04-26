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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\Model;

use Pimcore\Bundle\EcommerceFrameworkBundle\AvailabilitySystem\AvailabilityInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\AvailabilitySystem\AvailabilitySystemInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\AbstractPriceInfo;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\PriceInfoInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\PriceInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\PriceSystemInterface;

/**
 * Interface CheckoutableInterface
 */
interface CheckoutableInterface extends ProductInterface
{
    /**
     * defines the name of the price system for this product.
     * there should either be a attribute in pro product object or
     * it should be overwritten in mapped sub classes of product classes
     *
     * @return string|null
     */
    public function getPriceSystemName(): ?string;

    /**
     * defines the name of the availability system for this product.
     * there should either be a attribute in pro product object or
     * it should be overwritten in mapped sub classes of product classes
     *
     * @return string|null
     */
    public function getAvailabilitySystemName(): ?string;

    /**
     * checks if product is bookable
     *
     * @return bool
     */
    public function getOSIsBookable($quantityScale = 1): bool;

    /**
     * returns instance of price system implementation based on result of getPriceSystemName()
     *
     * @return PriceSystemInterface|null
     */
    public function getPriceSystemImplementation(): ?PriceSystemInterface;

    /**
     * returns instance of availability system implementation based on result of getAvailabilitySystemName()
     *
     * @return AvailabilitySystemInterface|null
     */
    public function getAvailabilitySystemImplementation(): ?AvailabilitySystemInterface;

    /**
     * returns price for given quantity scale
     *
     * @param int $quantityScale
     *
     * @return PriceInterface|null
     */
    public function getOSPrice($quantityScale = 1): ?PriceInterface;

    /**
     * returns price info for given quantity scale.
     * price info might contain price and additional information for prices like discounts, ...
     *
     * @param int $quantityScale
     *
     * @return PriceInfoInterface|AbstractPriceInfo
     */
    public function getOSPriceInfo($quantityScale = 1): ?PriceInfoInterface;

    /**
     * returns availability info based on given quantity
     *
     * @param int $quantity
     *
     * @return AvailabilityInterface|null
     */
    public function getOSAvailabilityInfo($quantity = null): ?AvailabilityInterface;
}
