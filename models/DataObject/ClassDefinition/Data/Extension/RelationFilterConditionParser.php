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
     * @param $value
     * @param $operator
     * @param $name
     * @return string
     */
    public function getRelationFilterCondition($value, $operator, $name)
    {
        if ($operator == '=') {
            $value = "'%," . $value . ",%'";
            return '`' . $name . '` LIKE ' . $value . ' ';
        } elseif ($operator == 'LIKE') {
            $result = $name . " IS NULL";
            $values = explode(',', (string)$value ?? '');
            if (is_array($values) && !empty($values)) {
                $fieldConditions = [];
                foreach ($values as $value) {
                    if (empty($value)) {
                        continue;
                    }
                    $fieldConditions[] = '`' . $name . "` LIKE '%" . $value . "%' ";
                }
                if (!empty($fieldConditions)) {
                    $result = '(' . implode(' AND ', $fieldConditions) . ')';
                }
            }
            return $result;
        }
    }
}
