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

namespace OnlineShop\Framework\PricingManager\Condition;

interface IBracket extends \OnlineShop\Framework\PricingManager\ICondition
{
    const OPERATOR_AND = 'and';
    const OPERATOR_OR = 'or';
    const OPERATOR_AND_NOT = 'and_not';

    /**
     * @param \OnlineShop\Framework\PricingManager\ICondition $condition
     * @param string $operator \OnlineShop\Framework\PricingManager\Condition\IBracket::OPERATOR_*
     *
     * @return IBracket
     */
    public function addCondition(\OnlineShop\Framework\PricingManager\ICondition $condition, $operator);
}