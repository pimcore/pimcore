<?php
declare(strict_types=1);

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

namespace Pimcore\Bundle\CustomReportsBundle\Tool\Adapter;

use Exception;
use Pimcore\Db;
use stdClass;

/**
 * @internal
 */
class Sql extends AbstractAdapter
{
    public function getData(?array $filters, ?string $sort, ?string $dir, ?int $offset, ?int $limit, array $fields = null, array $drillDownFilters = null): array
    {
        $db = Db::get();

        $baseQuery = $this->getBaseQuery($filters ?? [], $fields ?? [], false, $drillDownFilters ?? []);
        $data = [];
        $total = 0;

        if ($baseQuery) {
            $total = $db->fetchOne($baseQuery['count']);

            $order = '';
            if ($sort && $dir) {
                $dir = ((strtoupper($dir) === 'ASC') ? 'ASC' : 'DESC');
                $order = ' ORDER BY ' . $db->quoteIdentifier($sort) . ' ' .$dir;
            }

            $sql = $baseQuery['data'] . $order;
            if ($offset !== null && $limit) {
                $sql .= " LIMIT $offset,$limit";
            }

            $data = $db->fetchAllAssociative($sql);
        }

        return ['data' => $data, 'total' => $total];
    }

    public function getColumns(?stdClass $configuration): array
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

        throw new Exception("Only 'SELECT' statements are allowed! You've used '" . $matches[0] . "'");
    }

    protected function buildQueryString(stdClass $config, bool $ignoreSelectAndGroupBy = false, array $drillDownFilters = null, string $selectField = null): string
    {
        $config = (array)$config;
        $sql = '';
        if (!empty($config['sql']) && !$ignoreSelectAndGroupBy) {
            if (!str_starts_with(strtoupper(trim($config['sql'])), 'SELECT')) {
                $sql .= 'SELECT';
            }
            $sql .= "\n" . $config['sql'];
        } elseif ($selectField) {
            $db = Db::get();
            $sql .= 'SELECT ' . $db->quoteIdentifier($selectField);
        } else {
            $sql .= 'SELECT *';
        }
        if (!empty($config['from'])) {
            if (!str_starts_with(strtoupper(trim($config['from'])), 'FROM')) {
                $sql .= "\n" . 'FROM ';
            }
            $sql .= "\n" . $config['from'];
        }

        if (!empty($config['where'])) {
            if (str_starts_with(strtoupper(trim($config['where'])), 'WHERE')) {
                $config['where'] = preg_replace('/^\s*WHERE\s*/', '', $config['where']);
            }
            $sql .= "\n" . 'WHERE (' . $config['where'] . ')';
        }

        if (!empty($config['groupby']) && !$ignoreSelectAndGroupBy) {
            if (!str_starts_with(strtoupper(trim($config['groupby'])), 'GROUP BY')) {
                $sql .= ' GROUP BY ';
            }
            $sql .= "\n" . $config['groupby'];
        }

        if ($drillDownFilters) {
            $havingParts = [];
            $db = Db::get();
            foreach ($drillDownFilters as $field => $value) {
                if ($value !== '' && $value !== null) {
                    $havingParts[] = ($db->quoteIdentifier($field) .' = ' . $db->quote($value));
                }
            }

            if ($havingParts) {
                $sql .= ' HAVING ' . implode(' AND ', $havingParts);
            }
        }

        return $sql;
    }

    protected function getBaseQuery(array $filters, array $fields, bool $ignoreSelectAndGroupBy = false, array $drillDownFilters = null, string $selectField = null): ?array
    {
        $db = Db::get();
        $condition = ['1 = 1'];

        $sql = $this->buildQueryString($this->config, $ignoreSelectAndGroupBy, $drillDownFilters, $selectField);

        $extractAllFields = empty($fields);
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

    public function getAvailableOptions(array $filters, string $field, array $drillDownFilters): array
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
                $filteredData[] = ['name' => $d[$field], 'value' => $d[$field]];
            }
        }

        return [
            'data' => array_merge(
                [
                    ['name' => 'empty', 'value' => null],
                ],
                $filteredData
            ),
        ];
    }
}
