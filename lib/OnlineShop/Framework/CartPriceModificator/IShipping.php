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


/**
 * Interface OnlineShop_Framework_CartPriceModificator_IShipping
 *
 * special interface for shipping price modifications - needed for pricing rule that remove shipping costs
 */
interface OnlineShop_Framework_CartPriceModificator_IShipping extends OnlineShop_Framework_ICartPriceModificator
{
    /**
     * @param float $charge
     *
     * @return OnlineShop_Framework_ICartPriceModificator
     */
    public function setCharge($charge);

    /**
     * @return float
     */
    public function getCharge();
}