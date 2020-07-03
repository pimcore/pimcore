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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\FilterService;

use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\ProductList\ProductListInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Worker\WorkerInterface;
use Pimcore\Model\DataObject\AbstractObject;

/**
 * Helper for getting possible group by values based on different column groups
 *
 * one or more column groups can be mapped to one column type - which defines the logic for retrieving data
 *
 * available column types are
 *  - relation
 *  - multiselect
 *  - category
 *  - other
 */
class FilterGroupHelper
{
    /**
     * its possible to combine more different column groups to one column type (which has one logic for retrieving data)
     *
     * might be overwritten, if new column groups are necessary
     *
     * @param string $columnGroup
     *
     * @return string
     */
    protected function getColumnTypeForColumnGroup($columnGroup)
    {
        return $columnGroup;
    }

    /**
     * returns all possible group by values for given column group, product list and field combination
     *
     * @param string $columnGroup
     * @param ProductListInterface $productList
     * @param string $field
     *
     * @return array
     */
    public function getGroupByValuesForFilterGroup($columnGroup, ProductListInterface $productList, $field)
    {
        $columnType = $this->getColumnTypeForColumnGroup($columnGroup);

        $data = [];

        if ($columnType == 'relation') {
            $productList->prepareGroupByRelationValues($field);
            $values = $productList->getGroupByRelationValues($field);

            foreach ($values as $v) {
                $obj = AbstractObject::getById($v);
                if ($obj) {
                    $name = $obj->getKey();
                    //give the possibility to add a nice name with HTML-icons etc. to the filter definition output fields
                    if (method_exists($obj, 'getNameForFilterDefinition')) {
                        $name = $obj->getNameForFilterDefinition();
                    } elseif (method_exists($obj, 'getName')) {
                        $name = $obj->getName();
                    }
                    $data[$v] = ['key' => $v, 'value' => $name . ' (' . $obj->getId() . ')'];
                }
            }
        } elseif ($columnType == 'multiselect') {
            $values = $productList->getGroupByValues($field);

            sort($values);

            foreach ($values as $v) {
                $helper = explode(WorkerInterface::MULTISELECT_DELIMITER, $v);
                foreach ($helper as $h) {
                    $data[$h] = ['key' => $h, 'value' => $h];
                }
            }
        } elseif ($columnType == 'category') {
            $values = $productList->getGroupByValues($field);

            foreach ($values as $v) {
                $helper = explode(',', $v);
                foreach ($helper as $h) {
                    $obj = AbstractObject::getById($h);
                    if ($obj) {
                        $name = $obj->getKey();
                        if (method_exists($obj, 'getName')) {
                            $name = $obj->getName();
                        }
                        $data[$h] = ['key' => $h, 'value' => $name . ' (' . $obj->getId() . ')'];
                    }
                }
            }
        } else {
            $productList->prepareGroupByValues($field);
            $values = $productList->getGroupByValues($field);

            sort($values);

            foreach ($values as $v) {
                $data[] = ['key' => $v, 'value' => $v];
            }
        }

        return $data;
    }
}
