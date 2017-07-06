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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartPriceModificator;

use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\Value\PriceAmount;

/**
 * Interface IDiscount
 *
 * Special interface for price modifications added by discount pricing rules for carts
 */
interface IDiscount extends ICartPriceModificator
{
    /**
     * @param PriceAmount $amount
     *
     * @return IDiscount
     */
    public function setAmount(PriceAmount $amount);

    /**
     * @return PriceAmount
     */
    public function getAmount(): PriceAmount;
}
