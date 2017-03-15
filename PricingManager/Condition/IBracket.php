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
 * @category   Pimcore
 * @package    EcommerceFramework
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PricingManager\Condition;

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