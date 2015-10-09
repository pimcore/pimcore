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


interface OnlineShop_Framework_Pricing_Action_IDiscount extends OnlineShop_Framework_Pricing_IAction
{
    /**
     * @param float $amount
     *
     * @return void
     */
    public function setAmount($amount);

    /**
     * @param float $percent
     *
     * @return void
     */
    public function setPercent($percent);

    /**
     * @return float
     */
    public function getAmount();

    /**
     * @return float
     */
    public function getPercent();
}