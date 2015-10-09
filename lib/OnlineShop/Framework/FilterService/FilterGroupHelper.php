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


/**
 * Class OnlineShop_Framework_FilterService_FilterGroupHelper
 *
 * Helper for getting possible group by values based on different column groups
 *
 * one or more column groups can be mapped to one column type - which defines the logic for retrieving data
 *
 * available column types are
 *  - relation
 *  - multiselect
 *  - category
 *  - other
 *
 */
class OnlineShop_Framework_FilterService_FilterGroupHelper
{

    /**
     * its possible to combine more different column groups to one column type (which has one logic for retrieving data)
     *
     * might be overwritten, if new column groups are necessary
     *
     * @param $columnGroup
     * @return string
     */
    protected static function getColumnTypeForColumnGroup($columnGroup) {
        return $columnGroup;
    }

    /**
     * returns all possible group by values for given column group, product list and field combination
     *
     * @param $columnGroup
     * @param OnlineShop_Framework_IProductList $productList
     * @param string $field
     * @return array
     */
    public static function getGroupByValuesForFilterGroup($columnGroup, OnlineShop_Framework_IProductList $productList, $field) {
        $columnType = self::getColumnTypeForColumnGroup($columnGroup);

        $data = array();

        if($columnType == "relation") {
            $productList->prepareGroupByRelationValues($field);
            $values = $productList->getGroupByRelationValues($field);

            foreach($values as $v) {
                $obj = \Pimcore\Model\Object\AbstractObject::getById($v);
                if($obj) {
                    $name = $obj->getKey();
                    if(method_exists($obj, "getName")) {
                        $name = $obj->getName();
                    }
                    $data[$v] = array("key" => $v, "value" => $name . " (" . $obj->getId() . ")");
                }
            }

        } else if($columnType == "multiselect") {
            $values = $productList->getGroupByValues($field);

            sort($values);

            foreach($values as $v) {
                $helper = explode(OnlineShop_Framework_IndexService_Tenant_IWorker::MULTISELECT_DELIMITER, $v);
                foreach($helper as $h) {
                    $data[$h] = array("key" => $h, "value" => $h);
                }
            }
        } else if($columnType == "category") {
            $values = $productList->getGroupByValues($field);

            foreach($values as $v) {
                $helper = explode(",", $v);
                foreach($helper as $h) {
                    $obj = \Pimcore\Model\Object\AbstractObject::getById($h);
                    if($obj) {
                        $name = $obj->getKey();
                        if(method_exists($obj, "getName")) {
                            $name = $obj->getName();
                        }
                        $data[$h] = array("key" => $h, "value" => $name . " (" . $obj->getId() . ")");
                    }
                }
            }
        } else {
            $productList->prepareGroupByValues($field);
            $values = $productList->getGroupByValues($field);

            sort($values);

            foreach($values as $v) {
                $data[] = array("key" => $v, "value" => $v);
            }
        }

        return $data;
    }

}