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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartPriceModificator;

use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\Value\PriceAmount;

/**
 * Interface IShipping
 *
 * special interface for shipping price modifications - needed for pricing rule that remove shipping costs
 */
interface IShipping extends ICartPriceModificator
{
    /**
     * @param PriceAmount $charge
     *
     * @return ICartPriceModificator
     */
    public function setCharge(PriceAmount $charge);

    /**
     * @return PriceAmount
     */
    public function getCharge(): PriceAmount;
}
