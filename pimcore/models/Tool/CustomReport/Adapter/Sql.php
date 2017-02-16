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
 * @category   Pimcore
 * @package    Pimcore
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Tool\CustomReport\Adapter;

use Pimcore\Model;
use Pimcore\Db;

class Sql extends AbstractAdapter
{

    /**
     * @param $filters
     * @param $sort
     * @param $dir
     * @param $offset
     * @param $limit
     * @param null $fields
     * @param null $drillDownFilters
     * @return array
     */
    public function getData($filters, $sort, $dir, $offset, $limit, $fields = null, $drillDownFilters = null)
    {
        $db = Db::get();

        $baseQuery = $this->getBaseQuery($filters, $fields, false, $drillDownFilters);

        if ($baseQuery) {
            $total = $db->fetchOne($baseQuery["count"]);

            $order = "";
            if ($sort && $dir) {
                $order = " ORDER BY " . $db->quoteIdentifier($sort) . " " . $dir;
            }

            $sql = $baseQuery["data"] . $order;
            if ($offset !== null && $limit) {
                $sql .= " LIMIT $offset,$limit";
            }

            $data = $db->fetchAll($sql);
        }

        return ["data" => $data, "total" => $total];
    }

    /**
     * @param $configuration
     * @return array|mixed|null
     * @throws \Exception
     */
    public function getColumns($configuration)
    {
        $sql = "";
        if ($configuration) {
            $sql = $this->buildQueryString($configuration);
        }

        $res = null;
        $errorMessage = null;
        $columns = null;

        if (!preg_match("/(ALTER|CREATE|DROP|RENAME|TRUNCATE|UPDATE|DELETE) /i", $sql, $matches)) {
            $sql .= " LIMIT 0,1";
            $db = Db::get();
            $res = $db->fetchRow($sql);
            $columns = array_keys($res);
        } else {
            throw new \Exception("Only 'SELECT' statements are allowed! You've used '" . $matches[0] . "'");
        }

        return $columns;
    }

    /**
     * @param $config
     * @param bool $ignoreSelectAndGroupBy
     * @param null $drillDownFilters
     * @param null $selectField
     * @return string
     */
    protected function buildQueryString($config, $ignoreSelectAndGroupBy = false, $drillDownFilters = null, $selectField = null)
    {
        $config = (array)$config;
        $sql = "";
        if ($config["sql"] && !$ignoreSelectAndGroupBy) {
            if (strpos(strtoupper(trim($config["sql"])), "SELECT") === false || strpos(strtoupper(trim($config["sql"])), "SELECT") > 5) {
                $sql .= "SELECT ";
            }
            $sql .= str_replace("\n", " ", $config["sql"]);
        } elseif ($selectField) {
            $db = Db::get();
            $sql .= "SELECT " . $db->quoteIdentifier($selectField);
        } else {
            $sql .= "SELECT *";
        }
        if ($config["from"]) {
            if (strpos(strtoupper(trim($config["from"])), "FROM") === false) {
                $sql .= " FROM ";
            }
            $sql .= " " . str_replace("\n", " ", $config["from"]);
        }
        if ($config["where"] || $drillDownFilters) {
            $whereParts = [];
            if ($config["where"]) {
                $whereParts[] = "(" . str_replace("\n", " ", $config["where"]) . ")";
            }

            if ($drillDownFilters) {
                $db = Db::get();
                foreach ($drillDownFilters as $field => $value) {
                    if ($value !== "" && $value !== null) {
                        $whereParts[] = "`$field` = " . $db->quote($value);
                    }
                }
            }

            if ($whereParts) {
                if ($config["where"]) {
                    $sql .= " WHERE ";
                } else {
                    if (strpos(strtoupper(trim($config["where"])), "WHERE") === false) {
                        $sql .= " WHERE ";
                    }
                }

                $sql .= " " . implode(" AND ", $whereParts);
            }
        }
        if ($config["groupby"] && !$ignoreSelectAndGroupBy) {
            if (strpos(strtoupper($config["groupby"]), "GROUP BY") === false) {
                $sql .= " GROUP BY ";
            }
            $sql .= " " . str_replace("\n", " ", $config["groupby"]);
        }

        return $sql;
    }

    /**
     * @param $filters
     * @param $fields
     * @param bool $ignoreSelectAndGroupBy
     * @param null $drillDownFilters
     * @param null $selectField
     * @return array
     */
    protected function getBaseQuery($filters, $fields, $ignoreSelectAndGroupBy = false, $drillDownFilters = null, $selectField = null)
    {
        $db = Db::get();
        $condition = ["1 = 1"];

        $sql = $this->buildQueryString($this->config, $ignoreSelectAndGroupBy, $drillDownFilters, $selectField);

        $data = "";

        if ($filters) {
            if (is_array($filters)) {
                foreach ($filters as $filter) {
                    $value = $filter["value"] ;
                    $type = $filter["type"];
                    if ($type == "date") {
                        $value = strtotime($value);
                    }
                    $operator = $filter['operator'];
                    switch ($operator) {
                        case 'like':
                            $condition[] = $db->quoteIdentifier($filter["property"]) . " LIKE " . $db->quote("%" . $value. "%");
                            break;
                        case "lt":
                        case "gt":
                        case "eq":

                            $compMapping = [
                                "lt" => "<",
                                "gt" => ">",
                                "eq" => "="
                            ];

                            $condition[] = $db->quoteIdentifier($filter["property"]) . " " . $compMapping[$operator] . " " . $db->quote($value);
                            break;
                        case "=":
                            $condition[] = $db->quoteIdentifier($filter["property"]) . " = " . $db->quote($value);
                            break;
                    }
                }
            }
        }

        if (!preg_match("/(ALTER|CREATE|DROP|RENAME|TRUNCATE|UPDATE|DELETE) /i", $sql, $matches)) {
            $condition = implode(" AND ", $condition);

            $total = "SELECT COUNT(*) FROM (" . $sql . ") AS somerandxyz WHERE " . $condition;

            if ($fields) {
                $data = "SELECT `" . implode("`, `", $fields) . "` FROM (" . $sql . ") AS somerandxyz WHERE " . $condition;
            } else {
                $data = "SELECT * FROM (" . $sql . ") AS somerandxyz WHERE " . $condition;
            }
        } else {
            return;
        }


        return [
            "data" => $data,
            "count" => $total
        ];
    }

    /**
     * @param $filters
     * @param $field
     * @param $drillDownFilters
     * @return array|mixed
     */
    public function getAvailableOptions($filters, $field, $drillDownFilters)
    {
        $db = Db::get();
        $baseQuery = $this->getBaseQuery($filters, [$field], true, $drillDownFilters, $field);
        $data = [];
        if ($baseQuery) {
            $sql = $baseQuery["data"] . " GROUP BY " . $db->quoteIdentifier($field);
            $data = $db->fetchAll($sql);
        }

        $filteredData = [];
        foreach ($data as $d) {
            if (!empty($d[$field]) || $d[$field] === 0) {
                $filteredData[] = ["value" => $d[$field]];
            }
        }

        return [
            "data" => array_merge([
                        ["value" => null]
                      ], $filteredData
            )
        ];
    }
}
