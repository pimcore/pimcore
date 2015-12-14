<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace OnlineShop\Framework\CartManager\CartPriceModificator;

/**
 * Interface IDiscount
 *
 * special interface for price modifications added by discount pricing rules for carts
 */
interface IDiscount extends ICartPriceModificator
{
    /**
     * @param float $amount
     *
     * @return IDiscount
     */
    public function setAmount($amount);

    /**
     * @return float
     */
    public function getAmount();
}