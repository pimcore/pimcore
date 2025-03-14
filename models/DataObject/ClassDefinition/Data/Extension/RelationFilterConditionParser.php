<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Model\DataObject\ClassDefinition\Data\Extension;

use Pimcore\Db\Helper;

/**
 * Trait RelationFilterConditionParser
 *
 * @package Pimcore\Model\DataObject\ClassDefinition\Data\Extension
 */
trait RelationFilterConditionParser
{
    /**
     * Parses filter value of a relation field and creates the filter condition
     */
    public function getRelationFilterCondition(?string $value, string $operator, string $name): string
    {
        $db = \Pimcore\Db::get();
        $result = $db->quoteIdentifier($name) . ' IS NULL';
        if ($value === null || $value === 'null') {
            return $result;
        }
        if ($operator === '=') {
            return $db->quoteIdentifier($name) . ' = ' . $db->quote($value);
        }
        $values = explode(',', $value);
        $fieldConditions = array_map(function ($value) use ($name, $db) {
            $quotedValue = $db->quote('%,' . Helper::escapeLike($value) . ',%');

            return $db->quoteIdentifier($name) . ' LIKE ' . $quotedValue . ' ';
        }, array_filter($values));
        if (!empty($fieldConditions)) {
            $result = '(' . implode(' AND ', $fieldConditions) . ')';
        }

        return $result;
    }
}
