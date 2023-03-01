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

use Doctrine\DBAL\Query\QueryBuilder;
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
        $baseQuery = $this->getBaseQuery($filters, $fields, false, $drillDownFilters);
        $data = [];
        $total = 0;

        if ($baseQuery) {
            $total = $baseQuery['count']->execute()->fetchOne();

            $sql = $baseQuery['data'];
            if ($sort && $dir) {
                $sql->orderBy($sort, $dir);
            }

            if ($offset !== null && $limit) {
                $sql->setFirstResult($offset);
                $sql->setMaxResults($limit);
            }

            $data = $sql->execute()->fetchAllAssociative();
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

        if ($sql instanceof QueryBuilder &&
            !preg_match('/(ALTER|CREATE|DROP|RENAME|TRUNCATE|UPDATE|DELETE) /i', $sql->getSQL(), $matches)) {
            $sql->setMaxResults(1);
            $res = $sql->execute()->fetchAllAssociative();
            if (array_key_exists(0, $res)) {
                return array_keys($res[0]);
            }

            return [];
        }

        throw new \Exception("Only 'SELECT' statements are allowed!");
    }

    /**
     * @param \stdClass $config
     * @param bool $ignoreSelectAndGroupBy
     * @param array|null $drillDownFilters
     * @param string|null $selectField
     *
     * @return QueryBuilder
     */
    protected function buildQueryString($config, $ignoreSelectAndGroupBy = false, $drillDownFilters = null, $selectField = null)
    {
        $config = (array)$config;
        $db = DB::get();
        $queryBuilder = $db->createQueryBuilder();

        if (!empty($config['sql']) && !$ignoreSelectAndGroupBy) {
            $queryBuilder
                ->select(str_replace("\n", ' ', $config['sql']));
        } elseif ($selectField) {
            $queryBuilder
                ->select($db->quoteIdentifier($selectField));
        } else {
            $queryBuilder
                ->select('*');
        }
        if (!empty($config['from'])) {
            $queryBuilder
                ->from(str_replace("\n", ' ', $config['from']));
        }

        if (!empty($config['where'])) {
            if (str_starts_with(strtoupper(trim($config['where'])), 'WHERE')) {
                $config['where'] = preg_replace('/^\s*WHERE\s*/', '', $config['where']);
            }
            $queryBuilder
                ->where(str_replace("\n", ' ', $config['where']));
        }

        if (!empty($config['groupby']) && !$ignoreSelectAndGroupBy) {
            $queryBuilder
                ->groupBy(str_replace("\n", ' ', $config['groupby']));
        }

        if ($drillDownFilters) {
            foreach ($drillDownFilters as $field => $value) {
                if ($value !== '' && $value !== null) {
                    $queryBuilder
                        ->having(
                            $queryBuilder->expr()->eq($db->quoteIdentifier($field), $db->quote($value))
                        );
                }
            }
        }

        return $queryBuilder;
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
        $queryBuilder = $db->createQueryBuilder();
        $conditions = [];
        $conditions[] = $queryBuilder->expr()->eq(1, 1);
        $sql = $this->buildQueryString($this->config, $ignoreSelectAndGroupBy, $drillDownFilters, $selectField);

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
                            $conditions[] = $queryBuilder->expr()->like(
                                $db->quoteIdentifier($filter['property']),
                                $db->quote($value)
                            );

                            break;
                        case 'lt':
                        case 'gt':
                        case 'eq':
                            if ($type == 'date') {
                                if ($operator == 'eq') {
                                    $conditions[] = $queryBuilder->expr()->gte(
                                        $db->quoteIdentifier($filter['property']),
                                        $db->quote($value)
                                    );
                                    $conditions[] = $queryBuilder->expr()->lte(
                                        $db->quoteIdentifier($filter['property']),
                                        $db->quote($maxValue)
                                    );

                                    break;
                                }
                            }
                            $fields[] = $filter['property'];
                            $conditions[] = $queryBuilder->expr()->{$operator}(
                                $db->quoteIdentifier($filter['property']),
                                $db->quote($value)
                            );

                            break;
                        case '=':
                            $fields[] = $filter['property'];
                            $conditions[] = $queryBuilder->expr()->eq(
                                $db->quoteIdentifier($filter['property']),
                                $db->quote($value)
                            );

                            break;
                    }
                }
            }
        }

        if (!preg_match('/(ALTER|CREATE|DROP|RENAME|TRUNCATE|UPDATE|DELETE) /i', $sql->getSQL(), $matches)) {
            $totalQueryBuilder = $db->createQueryBuilder();
            $total = $totalQueryBuilder
                ->select('count(*)')
                ->from(('(' .$sql .') AS somerandxyz'));

            foreach ($conditions as $condition) {
                $total->where($condition);
            }

            if ($fields && !$extractAllFields) {
                $data = $queryBuilder
                    ->select('`' .implode('`, `', $fields) . '`')
                    ->from(('(' .$sql .') AS somerandxyz'));

                foreach ($conditions as $condition) {
                    $data->where($condition);
                }
            } else {
                $data = $queryBuilder
                    ->select('*')
                    ->from(('(' .$sql .') AS somerandxyz'));

                foreach ($conditions as $condition) {
                    $data->where($condition);
                }
            }
        } else {
            return null;
        }

        $total->setParameters($sql->getParameters());
        $data->setParameters($sql->getParameters());

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
        $baseQuery = $this->getBaseQuery($filters, [$field], false, $drillDownFilters);
        $data = [];
        if ($baseQuery) {
            $baseQuery['data']->groupBy($field);
            $data = $baseQuery['data']->execute()->fetchAllAssociative();
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
