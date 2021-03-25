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
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Tool\CustomReport\Adapter;

use Pimcore\Db;

class Sql extends AbstractAdapter
{
    /**
     * @param array|null $filters
     * @param string|null $sort
     * @param string|null $dir
     * @param int|null $offset
     * @param int|null $limit
     * @param array|null $fields
     * @param array|null $drillDownFilters
     *
     * @return array
     */
    public function getData($filters, $sort, $dir, $offset, $limit, $fields = null, $drillDownFilters = null)
    {
        $db = Db::get();

        if ($fields === null) {
            $columns = $this->fullConfig->getColumnConfiguration();
            $fields = [];
            foreach ($columns as $column) {
                if ($column['export']) {
                    $fields[] = $column['name'];
                }
            }
        }

        $baseQuery = $this->getBaseQuery($filters, $fields, false, $drillDownFilters);
        $data = [];
        $total = 0;

        if ($baseQuery) {
            $total = $db->fetchOne($baseQuery['count']);

            $order = '';
            if ($sort && $dir) {
                $order = ' ORDER BY ' . $db->quoteIdentifier($sort) . ' ' . $dir;
            }

            $sql = $baseQuery['data'] . $order;
            if ($offset !== null && $limit) {
                $sql .= " LIMIT $offset,$limit";
            }

            $data = $db->fetchAll($sql);
        }

        return ['data' => $data, 'total' => $total];
    }

    /**
     * @param \stdClass $configuration
     *
     * @return array
     *
     * @throws \Exception
     */
    public function getColumns($configuration)
    {
        $sql = '';
        if ($configuration) {
            $sql = $this->buildQueryString($configuration);
        }

        $res = null;
        $errorMessage = null;
        $columns = null;

        if (!preg_match('/(ALTER|CREATE|DROP|RENAME|TRUNCATE|UPDATE|DELETE) /i', $sql, $matches)) {
            $sql .= ' LIMIT 0,1';
            $db = Db::get();
            $res = $db->fetchRow($sql);
            $columns = array_keys($res);
        } else {
            throw new \Exception("Only 'SELECT' statements are allowed! You've used '" . $matches[0] . "'");
        }

        return $columns;
    }

    /**
     * @param \stdClass $config
     * @param bool $ignoreSelectAndGroupBy
     * @param array|null $drillDownFilters
     * @param string|null $selectField
     *
     * @return string
     */
    protected function buildQueryString($config, $ignoreSelectAndGroupBy = false, $drillDownFilters = null, $selectField = null)
    {
        $config = (array)$config;
        $sql = '';
        if (!empty($config['sql']) && !$ignoreSelectAndGroupBy) {
            if (strpos(strtoupper(trim($config['sql'])), 'SELECT') !== 0) {
                $sql .= 'SELECT ';
            }
            $sql .= str_replace("\n", ' ', $config['sql']);
        } elseif ($selectField) {
            $db = Db::get();
            $sql .= 'SELECT ' . $db->quoteIdentifier($selectField);
        } else {
            $sql .= 'SELECT *';
        }
        if (!empty($config['from'])) {
            if (strpos(strtoupper(trim($config['from'])), 'FROM') !== 0) {
                $sql .= ' FROM ';
            }
            $sql .= ' ' . str_replace("\n", ' ', $config['from']);
        }
        if (!empty($config['where']) || $drillDownFilters) {
            $whereParts = [];
            if (!empty($config['where'])) {
                if (strpos(strtoupper(trim($config['where'])), 'WHERE') === 0) {
                    $config['where'] = preg_replace('/^\s*WHERE\s*/', '', $config['where']);
                }
                $whereParts[] = '(' . str_replace("\n", ' ', $config['where']) . ')';
            }

            if ($drillDownFilters) {
                $db = Db::get();
                foreach ($drillDownFilters as $field => $value) {
                    if ($value !== '' && $value !== null) {
                        $whereParts[] = "`$field` = " . $db->quote($value);
                    }
                }
            }

            if ($whereParts) {
                $sql .= ' WHERE ' . implode(' AND ', $whereParts);
            }
        }
        if (!empty($config['groupby']) && !$ignoreSelectAndGroupBy) {
            if (strpos(strtoupper(trim($config['groupby'])), 'GROUP BY') !== 0) {
                $sql .= ' GROUP BY ';
            }
            $sql .= ' ' . str_replace("\n", ' ', $config['groupby']);
        }

        return $sql;
    }

    /**
     * @param array $filters
     * @param array $fields
     * @param bool $ignoreSelectAndGroupBy
     * @param array|null $drillDownFilters
     * @param string|null $selectField
     *
     * @return array|null
     */
    protected function getBaseQuery($filters, $fields, $ignoreSelectAndGroupBy = false, $drillDownFilters = null, $selectField = null)
    {
        $db = Db::get();
        $condition = ['1 = 1'];

        $sql = $this->buildQueryString($this->config, $ignoreSelectAndGroupBy, $drillDownFilters, $selectField);

        $data = '';

        if ($filters) {
            if (is_array($filters)) {
                foreach ($filters as $filter) {
                    $value = $filter['value'] ;
                    $type = $filter['type'];
                    $operator = $filter['operator'];
                    $maxValue = null;
                    if ($type == 'date') {
                        if ($operator == 'eq') {
                            $maxValue = strtotime($value . '+23 hours 59 minutes');
                        }
                        $value = strtotime($value);
                    }

                    switch ($operator) {
                        case 'like':
                            $fields[] = $filter['property'];
                            $condition[] = $db->quoteIdentifier($filter['property']) . ' LIKE ' . $db->quote('%' . $value. '%');
                            break;
                        case 'lt':
                        case 'gt':
                        case 'eq':
                            $compMapping = [
                                'lt' => '<',
                                'gt' => '>',
                                'eq' => '=',
                            ];

                            if ($type == 'date') {
                                if ($operator == 'eq') {
                                    $condition[] = $db->quoteIdentifier($filter['property']) . ' BETWEEN ' . $db->quote($value) . ' AND ' . $db->quote($maxValue);
                                    break;
                                }
                            }
                            $fields[] = $filter['property'];
                            $condition[] = $db->quoteIdentifier($filter['property']) . ' ' . $compMapping[$operator] . ' ' . $db->quote($value);
                            break;
                        case '=':
                            $fields[] = $filter['property'];
                            $condition[] = $db->quoteIdentifier($filter['property']) . ' = ' . $db->quote($value);
                            break;
                    }
                }
            }
        }

        if (!preg_match('/(ALTER|CREATE|DROP|RENAME|TRUNCATE|UPDATE|DELETE) /i', $sql, $matches)) {
            $condition = implode(' AND ', $condition);

            $total = 'SELECT COUNT(*) FROM (' . $sql . ') AS somerandxyz WHERE ' . $condition;

            if ($fields) {
                $data = 'SELECT `' . implode('`, `', $fields) . '` FROM (' . $sql . ') AS somerandxyz WHERE ' . $condition;
            } else {
                $data = 'SELECT * FROM (' . $sql . ') AS somerandxyz WHERE ' . $condition;
            }
        } else {
            return null;
        }

        return [
            'data' => $data,
            'count' => $total,
        ];
    }

    /**
     * @param array $filters
     * @param string $field
     * @param array $drillDownFilters
     *
     * @return array
     */
    public function getAvailableOptions($filters, $field, $drillDownFilters)
    {
        $db = Db::get();
        $baseQuery = $this->getBaseQuery($filters, [$field], true, $drillDownFilters, (empty($filters) ? $field : null));
        $data = [];
        if ($baseQuery) {
            $sql = $baseQuery['data'] . ' GROUP BY ' . $db->quoteIdentifier($field);
            $data = $db->fetchAll($sql);
        }

        $filteredData = [];
        foreach ($data as $d) {
            if (!empty($d[$field]) || $d[$field] === 0) {
                $filteredData[] = ['value' => $d[$field]];
            }
        }

        return [
            'data' => array_merge(
                [
                    ['value' => null],
                ],
                $filteredData
            ),
        ];
    }
}
