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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Condition;

use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\ConditionInterface;

interface BracketInterface extends ConditionInterface
{
    const OPERATOR_AND = 'and';
    const OPERATOR_OR = 'or';
    const OPERATOR_AND_NOT = 'and_not';

    /**
     * @param ConditionInterface $condition
     * @param string $operator IBracket::OPERATOR_*
     *
     * @return self
     */
    public function addCondition(ConditionInterface $condition, $operator);

    /**
     * Returns all defined conditions with given type
     *
     * @param string $typeClass
     *
     * @return ConditionInterface[]
     */
    public function getConditionsByType(string $typeClass): array;
}

class_alias(BracketInterface::class, 'Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Condition\IBracket');
