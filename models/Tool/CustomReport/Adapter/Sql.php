<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Model\Tool\CustomReport\Adapter;

use Pimcore\Db;

/**
 * @internal
 */
class Sql extends AbstractAdapter
{
    /**
     * {@inheritdoc}
     */
    public function getData($filters, $sort, $dir, $offset, $limit, $fields = null, $drillDownFilters = null)
    {
        $db = Db::get();

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

            $data = $db->fetchAllAssociative($sql);
        }

        return ['data' => $data, 'total' => $total];
    }

    /**
     * {@inheritdoc}
     */
    public function getColumns($configuration)
    {
        $sql = '';
        if ($configuration) {
            $sql = $this->buildQueryString($configuration);
        }

        if (!preg_match('/(ALTER|CREATE|DROP|RENAME|TRUNCATE|UPDATE|DELETE) /i', $sql, $matches)) {
            $sql .= ' LIMIT 0,1';
            $db = Db::get();
            $res = $db->fetchAssociative($sql);
            if ($res) {
                return array_keys($res);
            }

            return [];
        }

        throw new \Exception("Only 'SELECT' statements are allowed! You've used '" . $matches[0] . "'");
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

        if (!empty($config['where'])) {
            if (str_starts_with(strtoupper(trim($config['where'])), 'WHERE')) {
                $config['where'] = preg_replace('/^\s*WHERE\s*/', '', $config['where']);
            }
            $sql .= ' WHERE (' . str_replace("\n", ' ', $config['where']) . ')';
        }

        if (!empty($config['groupby']) && !$ignoreSelectAndGroupBy) {
            if (strpos(strtoupper(trim($config['groupby'])), 'GROUP BY') !== 0) {
                $sql .= ' GROUP BY ';
            }
            $sql .= ' ' . str_replace("\n", ' ', $config['groupby']);
        }

        if ($drillDownFilters) {
            $havingParts = [];
            $db = Db::get();
            foreach ($drillDownFilters as $field => $value) {
                if ($value !== '' && $value !== null) {
                    $havingParts[] = "$field = " . $db->quote($value);
                }
            }

            if ($havingParts) {
                $sql .= ' HAVING ' . implode(' AND ', $havingParts);
            }
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
        $extractAllFields = empty($fields);
        if ($filters) {
            if (is_array($filters)) {
                foreach ($filters as $filter) {
                    $value = $filter['value'] ?? null;
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

            if ($fields && !$extractAllFields) {
                $data = 'SELECT `' . implode('`,`', $fields) . '` FROM (' . $sql . ') AS somerandxyz WHERE ' . $condition;
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
     * {@inheritdoc}
     */
    public function getAvailableOptions($filters, $field, $drillDownFilters)
    {
        $db = Db::get();
        $baseQuery = $this->getBaseQuery($filters, [$field], false, $drillDownFilters);
        $data = [];
        if ($baseQuery) {
            $sql = $baseQuery['data'] . ' GROUP BY ' . $db->quoteIdentifier($field);
            $data = $db->fetchAllAssociative($sql);
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
