<?php

declare(strict_types=1);

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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem;

use Pimcore\Bundle\EcommerceFrameworkBundle\Model\CheckoutableInterface;

/**
 * Interface for PriceInfo implementations of online shop framework
 */
interface PriceInfoInterface
{
    const MIN_PRICE = 'min';

    /**
     * Returns single price
     *
     * @return PriceInterface
     */
    public function getPrice(): PriceInterface;

    /**
     * Returns total price (single price * quantity)
     *
     * @return PriceInterface
     */
    public function getTotalPrice(): PriceInterface;

    /**
     * Returns if price is a minimal price (e.g. when having many product variants they might have a from price)
     *
     * @return bool
     */
    public function isMinPrice(): bool;

    /**
     * Returns quantity
     *
     * @return int|string
     */
    public function getQuantity();

    /**
     * Numeric quantity or constant PriceInterfaceInfo::MIN_PRICE
     *
     * @param int|string $quantity
     */
    public function setQuantity($quantity);

    /**
     * Relation to price system
     *
     * @param PriceSystemInterface $priceSystem
     *
     * @return PriceInfoInterface
     */
    public function setPriceSystem(PriceSystemInterface $priceSystem);

    /**
     * Relation to product
     *
     * @param CheckoutableInterface $product
     *
     * @return PriceInfoInterface
     */
    public function setProduct(CheckoutableInterface $product);

    /**
     * Returns product
     *
     * @return CheckoutableInterface
     */
    public function getProduct();
}

class_alias(PriceInfoInterface::class, 'Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\IPriceInfo');
