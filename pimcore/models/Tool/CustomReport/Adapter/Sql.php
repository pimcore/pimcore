<?php
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @category   Pimcore
 * @package    Pimcore
 * @copyright  Copyright (c) 2009-2013 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Tool_CustomReport_Adapter_Sql extends Tool_CustomReport_Adapter_Abstract {


    public function getData($filters, $sort, $dir, $offset, $limit, $fields = null, $drillDownFilters = null) {
        $db = Pimcore_Resource::get();

        $baseQuery = $this->getBaseQuery($filters, $fields, false, $drillDownFilters);

        if($baseQuery) {
            $total = $db->fetchOne($baseQuery["count"]);

            $order = "";
            if($sort && $dir) {
                $order = " ORDER BY " . $db->quoteIdentifier($sort) . " " . $dir;
            }

            $sql = $baseQuery["data"] . $order;
            if($offset !== null && $limit) {
                $sql .= " LIMIT $offset,$limit";
            }

            $data = $db->fetchAll($sql);
        }

        return array("data" => $data, "total" => $total);
    }

    public function getColumns($configuration) {
        $sql = "";
        if($configuration) {
            $sql = $this->buildQueryString($configuration);
        }

        $res = null;
        $errorMessage = null;
        $columns = null;

        if(!preg_match("/(ALTER|CREATE|DROP|RENAME|TRUNCATE|UPDATE|DELETE) /i", $sql, $matches)) {
            $sql .= " LIMIT 0,1";
            $db = Pimcore_Resource::get();
            $res = $db->fetchRow($sql);
            $columns = array_keys($res);
        } else {
            throw new Exception("Only 'SELECT' statements are allowed! You've used '" . $matches[0] . "'");
        }

        return $columns;
    }

    protected function buildQueryString($config, $ignoreSelectAndGroupBy = false, $drillDownFilters = null, $selectField = null) {
        $sql = "";
        if($config->sql && !$ignoreSelectAndGroupBy) {
            if(strpos(strtoupper(trim($config->sql)), "SELECT") === false || strpos(strtoupper(trim($config->sql)), "SELECT") > 5) {
                $sql .= "SELECT ";
            }
            $sql .= str_replace("\n", " ", $config->sql);
        } else if($selectField) {
            $db = Pimcore_Resource::get();
            $sql .= "SELECT " . $db->quoteIdentifier($selectField);
        } else {
            $sql .= "SELECT *";
        }
        if($config->from) {
            if(strpos(strtoupper(trim($config->from)), "FROM") === false) {
                $sql .= " FROM ";
            }
            $sql .= " " . str_replace("\n", " ", $config->from);
        }
        if($config->where || $drillDownFilters) {
            if(strpos(strtoupper(trim($config->where)), "WHERE") === false) {
                $sql .= " WHERE ";
            }
            $whereParts = array();
            if($config->where) {
                $whereParts[] = "(" . str_replace("\n", " ", $config->where) . ")";
            }

            if($drillDownFilters) {
                $db = Pimcore_Resource::get();
                foreach($drillDownFilters as $field => $value) {
                    if($value !== "" && $value !== null) {
                        $whereParts[] = "`$field` = " . $db->quote($value);
                    }
                }
            }

            $sql .= " " . implode(" AND ", $whereParts);
        }
        if($config->groupby && !$ignoreSelectAndGroupBy) {
            if(strpos(strtoupper($config->groupby), "GROUP BY") === false) {
                $sql .= " GROUP BY ";
            }
            $sql .= " " . str_replace("\n", " ", $config->groupby);
        }

        return $sql;
    }

    protected function getBaseQuery($filters, $fields, $ignoreSelectAndGroupBy = false, $drillDownFilters = null, $selectField = null) {
        $db = Pimcore_Resource::get();
        $condition = array("1 = 1");

        $sql = $this->buildQueryString($this->config, $ignoreSelectAndGroupBy, $drillDownFilters, $selectField);

        $data = "";

        if($filters) {
            if(is_array($filters)) {
                foreach ($filters as $filter) {
                    if($filter["type"] == "string") {
                        $condition[] = $db->quoteIdentifier($filter["field"]) . " LIKE " . $db->quote("%" . $filter["value"] . "%");
                    } else if($filter["type"] == "numeric") {
                        $compMapping = array(
                            "lt" => "<",
                            "gt" => ">",
                            "eq" => "="
                        );
                        if($compMapping[$filter["comparison"]]) {
                            $condition[] = $db->quoteIdentifier($filter["field"]) . " " . $compMapping[$filter["comparison"]] . " " . $db->quote($filter["value"]);
                        }
                    } else if ($filter["type"] == "boolean") {
                        $condition[] = $db->quoteIdentifier($filter["field"]) . " = " . $db->quote((int)$filter["value"]);
                    } else if ($filter["type"] == "date") {

                    }
                }
            }
        }

        if(!preg_match("/(ALTER|CREATE|DROP|RENAME|TRUNCATE|UPDATE|DELETE) /i", $sql, $matches)) {

            $condition = implode(" AND ", $condition);

            $total = "SELECT COUNT(*) FROM (" . $sql . ") AS somerandxyz WHERE " . $condition;

            if($fields) {
                $data = "SELECT `" . implode("`, `", $fields) . "` FROM (" . $sql . ") AS somerandxyz WHERE " . $condition;
            } else {
                $data = "SELECT * FROM (" . $sql . ") AS somerandxyz WHERE " . $condition;
            }

        } else {
            return;
        }


        return array(
            "data" => $data,
            "count" => $total
        );
    }

    public function getAvailableOptions($filters, $field, $drillDownFilters) {
        $db = Pimcore_Resource::get();
        $baseQuery = $this->getBaseQuery($filters, array($field), true, $drillDownFilters, $field);
        $data = array();
        if($baseQuery) {
            $sql = $baseQuery["data"] . " GROUP BY " . $db->quoteIdentifier($field);
            $data = $db->fetchAll($sql);
        }

        $filteredData = array();
        foreach($data as $d) {
            if(!empty($d[$field]) || $d[$field] === 0) {
                $filteredData[] = array("value" => $d[$field]);
            }
        }

        return array("data" => array_merge(array(array("value" => null)), $filteredData));
    }


}