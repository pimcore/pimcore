<?php

class OnlineShop_Framework_FilterService_FilterGroupHelper
{

    /**
     * might be overwritten, if new column groups are necessary
     *
     * @param $columnGroup
     * @return string
     */
    protected static function getColumnTypeForColumnGroup($columnGroup) {
        return $columnGroup;
    }

    public static function getGroupByValuesForFilterGroup($columnGroup, $productList, $field) {
        $columnType = self::getColumnTypeForColumnGroup($columnGroup);

        $data = array();

        if($columnType == "relation") {
            $values = $productList->getGroupByRelationValues($field);

            foreach($values as $v) {
                $obj = Object_Abstract::getById($v);
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
                $helper = explode(OnlineShop_Framework_IndexService_Tenant_Worker::MULTISELECT_DELIMITER, $v);
                foreach($helper as $h) {
                    $data[$h] = array("key" => $h, "value" => $h);
                }
            }
        } else if($columnType == "category") {
            $values = $productList->getGroupByValues($field);

            foreach($values as $v) {
                $helper = explode(",", $v);
                foreach($helper as $h) {
                    $obj = Object_Abstract::getById($h);
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
            $values = $productList->getGroupByValues($field);

            sort($values);

            foreach($values as $v) {
                $data[] = array("key" => $v, "value" => $v);
            }
        }

        return $data;
    }

}
