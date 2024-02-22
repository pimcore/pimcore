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

use Pimcore\Db;
use Pimcore\Model;

/**
 * @internal
 */
class Sql extends AbstractAdapter
{
    /**
     * Relation loaders dictionary
     *
     * @var array
     */
    private array $relationLoaderDictionary = [
        'object' => Model\DataObject::class,
        'asset' => Model\Asset::class,
        'document' => Model\Document::class,
    ];

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

            $this->loadRelationData($data);
        }

        return ['data' => $data, 'total' => $total];
    }

    protected function loadRelationData(array &$data): void
    {
        $columnsDictionary = $this->getRelationColumns();
        $columnNames = array_keys($columnsDictionary);

        $relationDataDictionary = [];
        foreach ($data as $index =>$row) {
            foreach ($columnNames as $columnName) {
                if (empty($row[$columnName])) {
                    continue;
                }

                $type = $columnsDictionary[$columnName];
                $relationDataDictionary[$type] = $relationDataDictionary[$type] ?? [];
                $relationDataDictionary[$type][] = $row[$columnName];

                $element = $this->loadElementById($row[$columnName], $type);
                $data[$index][$columnName] = $element ? $element->getFullPath() : $row[$columnName];
            }
        }
    }

    /**
     * Load many elements by ids for specific model type and return assoc array
     *
     * @param number[]|string[] $ids
     * @param string $type
     *
     * @return Model\Element\AbstractElement[]
     */
    protected function loadElementsByIds(array $ids, string $type): array
    {
        $ids = array_unique(array_filter($ids));

        return array_reduce(
            $ids,
            function ($carrier, $id) use ($type) {
                $carrier[strval($id)] = $this->loadElementById($id, $type);

                return $carrier;
            },
            []
        );
    }

    protected function loadElementById(int $id, string $type): ?Model\Element\AbstractElement
    {
        $class = $this->relationLoaderDictionary[$type] ?? null;

        $element = call_user_func([$class, 'getById'], $id);

        return $element;
    }

    /**
     * Return columns that are marked as relations
     *
     * @return array
     */
    protected function getRelationColumns(): array
    {
        return array_reduce(
            $this->fullConfig->getColumnConfiguration(),
            function ($carrier, $item) {
                if (!$item["display"] && !$item["export"]) {
                    return $carrier;
                }
                if (preg_match("/^\@\w+\:(object|asset|document)$/", $item['filter'] ?? '', $match)) {
                    $carrier[$item['name']] = $match[1];
                }

                return $carrier;
            },
            []
        );
    }

    public function getColumns(?\stdClass $configuration): array
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

    protected function buildQueryString(\stdClass $config, bool $ignoreSelectAndGroupBy = false, array $drillDownFilters = null, string $selectField = null): string
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
                case 'in':
                    if (!empty($value)) {
                        $values = implode(', ', array_map(fn ($id) => intval($id), $value));
                        $condition[] = sprintf("%s IN (%s)", $db->quoteIdentifier($filter['property']), $values);
                    }
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
