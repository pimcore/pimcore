<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
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
            return $db->quoteIdentifier($name) . ' = ' . $db->quote((string) $value);
        }
        $values = explode(',', $value);
        $fieldConditions = array_map(function ($value) use ($name, $db) {
            $quotedValue = $db->quote('%,' . Helper::escapeLike((string) $value) . ',%');

            return $db->quoteIdentifier($name) . ' LIKE ' . $quotedValue . ' ';
        }, array_filter($values));
        if (!empty($fieldConditions)) {
            $result = '(' . implode(' AND ', $fieldConditions) . ')';
        }

        return $result;
    }
}
