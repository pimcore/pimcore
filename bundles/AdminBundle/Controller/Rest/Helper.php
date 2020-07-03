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

namespace Pimcore\Bundle\AdminBundle\Controller\Rest;

use Pimcore\Db;

/**
 * @deprecated
 */
class Helper
{
    public static function buildSqlCondition($q, $op = null, $subject = null)
    {

        // Examples:
        //
        //q={"o_modificationDate" : {"$gt" : "1000"}}
        //where ((`o_modificationDate` > '1000') )
        //
        //
        //
        //
        //q=[{"o_modificationDate" : {"$gt" : "1000"}}, {"o_modificationDate" : {"$lt" : "9999"}}]
        //where ( ((`o_modificationDate` > '1000') )  AND  ((`o_modificationDate` < '9999') )  )
        //
        //
        //
        //
        //q={"o_modificationDate" : {"$gt" : "1000"}, "$or": [{"o_id": "3", "o_key": {"$like" :"%lorem-ipsum%"}}]}
        //where ((`o_modificationDate` > '1000') AND  ((`o_id` = '3') OR  ((`o_key` LIKE '%lorem-ipsum%') )  )  )
        //
        // q={"$and" : [{"o_published": "0"}, {"o_modificationDate" : {"$gt" : "1000"}, "$or": [{"o_id": "3", "o_key": {"$like" :"%lorem-ipsum%"}}]}]}
        //
        // where ( ((`o_published` = '0') )  AND  ((`o_modificationDate` > '1000') AND  ((`o_id` = '3') OR (`o_key` LIKE '%lorem-ipsum%') )  )  )

        if (!$op) {
            $op = 'AND';
        }
        $mappingTable = ['$gt' => '>', '$gte' => '>=', '$lt' => '<', '$lte' => '<=', '$like' => 'LIKE', '$notlike' => 'NOT LIKE', '$notnull' => 'IS NOT NULL',
                '$not' => 'NOT', ];
        $ops = array_keys($mappingTable);

        $db = Db::get();

        $parts = [];
        if (is_string($q)) {
            return $q;
        }

        foreach ($q as $key => $value) {
            if (array_search(strtolower($key), ['$and', '$or']) !== false) {
                $childOp = strtolower($key) == '$and' ? 'AND' : 'OR';

                if (is_array($value)) {
                    $childParts = [];
                    foreach ($value as $arrItem) {
                        $childParts[] = self::buildSqlCondition($arrItem, $childOp);
                    }
                    $parts[] = implode(' ' . $childOp . ' ', $childParts);
                } else {
                    $parts[] = self::buildSqlCondition($value, $childOp);
                }
            } else {
                if (is_array($value)) {
                    foreach ($value as $subValue) {
                        $parts[] = self::buildSqlCondition($subValue);
                    }
                } elseif ($value instanceof \stdClass) {
                    $objectVars = get_object_vars($value);
                    foreach ($objectVars as $objectVar => $objectValue) {
                        if (array_search(strtolower($objectVar), $ops) !== false) {
                            $innerOp = $mappingTable[strtolower($objectVar)];
                            if ($innerOp == 'NOT') {
                                $parts[] = '( NOT ' . $db->quoteIdentifier($key) . ' =' . $db->quote($objectValue) . ')';
                            } else {
                                $parts[] = '(' . $db->quoteIdentifier($key) . ' ' . $innerOp . ' ' . $db->quote($objectValue) . ')';
                            }
                        } else {
                            if ($objectValue instanceof \stdClass) {
                                $parts[] = self::buildSqlCondition($objectValue, null, $objectVar);
                            } else {
                                $parts[] = '(' . $db->quoteIdentifier($objectVar) . ' = ' . $db->quote($objectValue) . ')';
                            }
                        }
                    }
                    $combinedParts = implode(' ' . $op . ' ', $parts);
                    $parts = [$combinedParts];
                } else {
                    if (array_search(strtolower($key), $ops) !== false) {
                        $innerOp = $mappingTable[strtolower($key)];
                        if ($innerOp == 'NOT') {
                            $parts[] = '(NOT' . $db->quoteIdentifier($subject) . ' = ' . $db->quote($value) . ')';
                        } else {
                            $parts[] = '(' . $db->quoteIdentifier($subject) . ' ' . $innerOp . ' ' . $db->quote($value) . ')';
                        }
                    } else {
                        $parts[] = '(' . $db->quoteIdentifier($key) . ' = ' . $db->quote($value) . ')';
                    }
                }
            }
        }

        $subCondition = ' (' . implode(' ' . $op . ' ', $parts) . ' ) ';

        return $subCondition;
    }
}
