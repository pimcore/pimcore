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


interface OnlineShop_Framework_Pricing_Condition_ICartAmount extends OnlineShop_Framework_Pricing_ICondition
{
    /**
     * @param float $limit
     *
     * @return OnlineShop_Framework_Pricing_Condition_ICartAmount
     */
    public function setLimit($limit);

    /**
     * @return float
     */
    public function getLimit();
}