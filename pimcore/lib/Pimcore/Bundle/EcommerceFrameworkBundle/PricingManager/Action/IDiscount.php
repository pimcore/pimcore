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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Action;

use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\IAction;

interface IDiscount extends IAction
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
