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

use Pimcore\Bundle\EcommerceFrameworkBundle\Model\ICheckoutable;

/**
 * Interface for PriceInfo implementations of online shop framework
 */
interface IPriceInfo
{
    const MIN_PRICE = 'min';

    /**
     * Returns single price
     *
     * @return IPrice
     */
    public function getPrice(): IPrice;

    /**
     * Returns total price (single price * quantity)
     *
     * @return IPrice
     */
    public function getTotalPrice(): IPrice;

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
     * Numeric quantity or constant IPriceInfo::MIN_PRICE
     *
     * @param int|string $quantity
     */
    public function setQuantity($quantity);

    /**
     * Relation to price system
     *
     * @param IPriceSystem $priceSystem
     *
     * @return IPriceInfo
     */
    public function setPriceSystem(IPriceSystem $priceSystem);

    /**
     * Relation to product
     *
     * @param ICheckoutable $product
     *
     * @return IPriceInfo
     */
    public function setProduct(ICheckoutable $product);

    /**
     * Returns product
     *
     * @return ICheckoutable
     */
    public function getProduct();
}
