<?php
/**
 * @category    pimcore
 * @date        25/06/2021
 * @author      Filip Szenborn <fszenborn@divante.co>
 */
declare(strict_types=1);

namespace Pimcore\Model\DataObject\ClassDefinition\Data\Extension;

/**
 * Trait RelationFilterConditionParser
 * @package Pimcore\Model\DataObject\ClassDefinition\Data\Extension
 */
trait RelationFilterConditionParser
{
    /**
     * Parses filter value of a relation field and creates the filter condition
     * @param string $value
     * @param string $operator
     * @param string $name
     * @return string
     */
    public function getRelationFilterCondition($value, $operator, $name)
    {
        if ($operator == '=') {
            return '`' . $name . '` = ' . "'" . $value . "'";
        }
        $result = $name . " IS NULL";
        $values = explode(',', (string)$value ?? '');
        if (is_array($values) && !empty($values)) {
            $fieldConditions = array_map(function ($value) use ($name) {
                return '`' . $name . "` LIKE '%," . $value . ",%' ";
            }, array_filter($values));
            if (!empty($fieldConditions)) {
                $result = '(' . implode(' AND ', $fieldConditions) . ')';
            }
        }

        return $result;
    }
}
