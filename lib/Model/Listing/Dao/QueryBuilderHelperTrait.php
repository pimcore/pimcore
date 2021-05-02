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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Listing\Dao;

use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Query\QueryBuilder as DoctrineQueryBuilder;
use Pimcore\Model\DataObject;

/**
 * @internal
 */
trait QueryBuilderHelperTrait
{
    /**
     * @var callable|null
     */
    protected $onCreateQueryBuilderCallback;

    /**
     * @param callable|null $callback
     */
    public function onCreateQueryBuilder(?callable $callback): void
    {
        $this->onCreateQueryBuilderCallback = $callback;
    }

    /**
     * @param DoctrineQueryBuilder $queryBuilder
     * @param bool $join
     */
    protected function applyListingParametersToQueryBuilder(QueryBuilder $queryBuilder, bool $join = false): void
    {
        $this->applyConditionsToQueryBuilder($queryBuilder);
        $this->applyGroupByToQueryBuilder($queryBuilder);
        $this->applyOrderByToQueryBuilder($queryBuilder);
        $this->applyLimitToQueryBuilder($queryBuilder);

        $callback = $this->onCreateQueryBuilderCallback;
        if (is_callable($callback)) {
            $callback($queryBuilder);
        }
    }

    private function applyConditionsToQueryBuilder(QueryBuilder $queryBuilder): void
    {
        $condition = $this->model->getCondition();

        if ($this instanceof DataObject\Listing\Dao) {
            $objectTypes = $this->model->getObjectTypes();

            $tableName = $this->getTableName();

            if (!empty($objectTypes)) {
                if (!empty($condition)) {
                    $condition .= ' AND ';
                }
                $condition .= ' ' . $tableName . ".o_type IN ('" . implode("','", $objectTypes) . "')";
            }

            if ($condition) {
                if (DataObject\AbstractObject::doHideUnpublished() && !$this->model->getUnpublished()) {
                    $condition = '(' . $condition . ') AND ' . $tableName . '.o_published = 1';
                }
            } elseif (DataObject\AbstractObject::doHideUnpublished() && !$this->model->getUnpublished()) {
                $condition = $tableName . '.o_published = 1';
            }
        }

        if ($condition) {
            $queryBuilder->where($condition)
                ->setParameters($this->model->getConditionVariables(), $this->model->getConditionVariableTypes());
        }
    }

    private function applyGroupByToQueryBuilder(QueryBuilder $queryBuilder): void
    {
        $groupBy = $this->model->getGroupBy();
        if ($groupBy) {
            $queryBuilder->addGroupBy($groupBy);
        }
    }

    private function applyOrderByToQueryBuilder(QueryBuilder $queryBuilder): void
    {
        $orderKey = $this->model->getOrderKey();
        $order = $this->model->getOrder();

        if (!empty($order) || !empty($orderKey)) {
            $c = 0;
            $lastOrder = $order[0] ?? null;

            if (is_array($orderKey)) {
                foreach ($orderKey as $key) {
                    if (!empty($order[$c])) {
                        $lastOrder = $order[$c];
                    }

                    $parts[] = $key . ' ' . $lastOrder;

                    $c++;
                }
            }

            if (!empty($parts)) {
                $queryBuilder->orderBy((string) implode(', ', $parts), ' ');
            }
        }
    }

    /**
     * @param DoctrineQueryBuilder $queryBuilder
     */
    private function applyLimitToQueryBuilder(QueryBuilder $queryBuilder): void
    {
        $queryBuilder->setFirstResult($this->model->getOffset());
        $queryBuilder->setMaxResults($this->model->getLimit());
    }

    protected function prepareQueryBuilderForTotalCount(DoctrineQueryBuilder &$queryBuilder): void
    {
        $queryBuilder->select('COUNT(*)');
        $queryBuilder->resetQueryPart('orderBy');
        $queryBuilder->setMaxResults(null);
        $queryBuilder->setFirstResult(0);

        if ($this instanceof DataObject\Listing\Dao) {
            if (method_exists($this->model, 'addDistinct') && $this->model->addDistinct()) {
                $queryBuilder->distinct();
            }

            if ($this->isQueryBuilderPartinUse($queryBuilder, 'groupBy') || $this->isQueryBuilderPartinUse($queryBuilder, 'having')) {
                $queryBuilder = 'SELECT COUNT(*) FROM (' . $queryBuilder . ') as XYZ';
            } elseif ($this->isQueryBuilderPartinUse($queryBuilder, 'distinct')) {
                $countIdentifier = 'DISTINCT ' . $this->getTableName() . '.o_id';
                $queryBuilder->select('COUNT(' . $countIdentifier . ') AS totalCount');
            }
        }
    }

    /**
     * @param DoctrineQueryBuilder $query
     * @param string $part
     *
     * @return bool
     */
    protected function isQueryBuilderPartinUse($query, $part)
    {
        try {
            if ($query->getQueryPart($part)) {
                return true;
            }
        } catch (\Exception $e) {
            // do nothing
        }

        return false;
    }
}
