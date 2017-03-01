<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2017 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Admin\Helper;

class QueryParams
{

    /**
     * @param $params
     * @return array  [orderKey => null|string, order => null|string]
     */
    public static function extractSortingSettings($params)
    {
        $orderKey = null;
        $order = null;
        $orderByFeature = null;

        $sortParam = isset($params["sort"]) ? $params["sort"] : false;
        if ($sortParam) {
            $sortParam = json_decode($sortParam, true);
            $sortParam = $sortParam[0];

            if (substr($sortParam["property"], 0, 1) != "~") {
                $orderKey = $sortParam["property"];
                $order = $sortParam["direction"];
            } else {
                $orderKey = $sortParam["property"];
                $order = $sortParam["direction"];

                $parts = explode("~", $orderKey);

                $fieldname = $parts[2];
                $groupKeyId = $parts[3];
                $groupKeyId = explode("-", $groupKeyId);
                $groupId = $groupKeyId[0];
                $keyid = $groupKeyId[1];

                return ['fieldname' => $fieldname, 'groupId' => $groupId, "keyId"=> $keyid, "order" => $order, "isFeature" => 1];
            }
        }

        return ['orderKey' => $orderKey, "order" => $order];
    }

    /**
     * @param $param
     * @return int
     */
    public static function getRecordIdForGridRequest($param)
    {
        $param = json_decode($param, true);
        return $param['id'];
    }

    /**
     * Creates a condition string from the passed ExtJs filter definitions
     *
     * @param $filterString
     * @param array $matchExact
     * @param bool $returnString
     * @param array $callbacks
     * @return array|string
     * @throws \Exception
     */
    public static function getFilterCondition($filterString, $matchExact = ['id', 'o_id'], $returnString = true, $callbacks = [])
    {
        if (!$filterString) {
            return '';
        }
        $conditions = [];

        $filters = json_decode($filterString);
        $db = \Pimcore\Db::get();
        foreach ($filters as $f) {
            if ($f->type == 'string') {
                if (in_array($f->property, $matchExact)) {
                    $conditions[$f->property][] = ' ' . $db->getQuoteIdentifierSymbol() . $f->property . $db->getQuoteIdentifierSymbol() . " = " . $db->quote($f->value) . ' ';
                } else {
                    $conditions[$f->property][] = ' ' . $db->getQuoteIdentifierSymbol() . $f->property . $db->getQuoteIdentifierSymbol() . " LIKE " . $db->quote("%" . $f->value . "%") . ' ';
                }
            } elseif ($f->type == 'numeric') {
                if ($f->operator == 'eq') {
                    $symbol = ' = ';
                } elseif ($f->operator == 'lt') {
                    $symbol = ' < ';
                } elseif ($f->operator == 'gt') {
                    $symbol = ' > ';
                }
                $conditions[$f->property][] = ' ' . $db->getQuoteIdentifierSymbol() . $f->property . $db->getQuoteIdentifierSymbol() . $symbol . $db->quote($f->value) . ' ';
            } elseif ($f->type == 'date') {
                /**
                 * make sure you pass the date as timestamp
                 *
                 * filter: {type : 'date',dateFormat: 'timestamp'}
                 */
                $date = Carbon::createFromTimestamp($f->value)->setTime(0, 0, 0);

                if ($f->operator == 'eq') {
                    $conditions[$f->property][] = ' ' . $f->property . ' >= ' . $db->quote($date->getTimestamp());
                    $conditions[$f->property][] = ' ' . $f->property . ' <= ' . $db->quote($date->addDay(1)->subSecond(1)->getTimestamp());
                } elseif ($f->operator == 'lt') {
                    $conditions[$f->property][] = ' ' . $f->property . ' < ' . $db->quote($date->getTimestamp());
                } elseif ($f->operator == 'gt') {
                    $conditions[$f->property][] = ' ' . $f->property . ' > ' . $db->quote($date->addDay(1)->subSecond(1)->getTimestamp());
                }
            } else {
                throw new \Exception("Filer of type " . $f->type . " not jet supported.");
            }
        }

        $conditionsGrouped = [];
        foreach ($conditions as $fieldName => $fieldConditions) {
            if (count($fieldConditions) > 1) {
                $conditionsGrouped[$fieldName] = ' (' . implode(' AND ', $fieldConditions) . ') ';
            } else {
                $conditionsGrouped[$fieldName] = $fieldConditions[0];
            }
        }
        if ($returnString) {
            return implode(' OR ', $conditionsGrouped);
        } else {
            return $conditionsGrouped;
        }
    }
}
