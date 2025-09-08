<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Model\Listing\Dao;

use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Query\QueryException;
use Pimcore\Model\DataObject;

trait QueryBuilderHelperTrait
{
    /**
     * @var callable|null
     *
     * @deprecated Since 12.2.0, use $queryBuilderProcessors instead, add processors via addQueryBuilderProcessor()
     *
     * @todo Remove in Pimcore 13
     */
    protected $onCreateQueryBuilderCallback;

    /**
     * @var callable[]
     */
    private array $queryBuilderProcessors = [];

    public function onCreateQueryBuilder(?callable $callback): void
    {
        $this->onCreateQueryBuilderCallback = $callback;
        $this->discardQueryBuilderProcessors();
        if (is_callable($callback)) {
            $this->addQueryBuilderProcessor($callback);
        }
    }

    public function addQueryBuilderProcessor(callable $callback): void
    {
        $this->queryBuilderProcessors[] = $callback;
    }

    public function discardQueryBuilderProcessors(): void
    {
        $this->queryBuilderProcessors = [];
    }

    protected function applyListingParametersToQueryBuilder(QueryBuilder $queryBuilder): void
    {
        $this->applyConditionsToQueryBuilder($queryBuilder);
        $this->applyGroupByToQueryBuilder($queryBuilder);
        $this->applyOrderByToQueryBuilder($queryBuilder);
        $this->applyLimitToQueryBuilder($queryBuilder);

        foreach ($this->queryBuilderProcessors as $processor) {
            $processor($queryBuilder);
        }
    }

    /**
     * @internal
     */
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
                $condition .= ' ' . $tableName . ".type IN ('" . implode("','", $objectTypes) . "')";
            }

            if ($condition) {
                if (DataObject\AbstractObject::doHideUnpublished() && !$this->model->getUnpublished()) {
                    $condition = '(' . $condition . ') AND ' . $tableName . '.published = 1';
                }
            } elseif (DataObject\AbstractObject::doHideUnpublished() && !$this->model->getUnpublished()) {
                $condition = $tableName . '.published = 1';
            }
        }

        if ($condition) {
            $queryBuilder->where($condition)
                ->setParameters($this->model->getConditionVariables(), $this->model->getConditionVariableTypes());
        }
    }

    /**
     * @internal
     */
    private function applyGroupByToQueryBuilder(QueryBuilder $queryBuilder): void
    {
        $groupBy = $this->model->getGroupBy();
        if ($groupBy) {
            $queryBuilder->addGroupBy($groupBy);
        }
    }

    /**
     * @internal
     */
    private function applyOrderByToQueryBuilder(QueryBuilder $queryBuilder): void
    {
        $orderKey = $this->model->getOrderKey();
        $order = $this->model->getOrder();

        if (!empty($order) || !empty($orderKey)) {
            $c = 0;
            $lastOrder = $order[0] ?? null;

            foreach ($orderKey as $key) {
                if (!empty($order[$c])) {
                    $lastOrder = $order[$c];
                }

                $parts[] = $key . ' ' . $lastOrder;

                $c++;
            }

            if (!empty($parts)) {
                $queryBuilder->orderBy(implode(', ', $parts), ' ');
            }
        }
    }

    /**
     * @internal
     */
    private function applyLimitToQueryBuilder(QueryBuilder $queryBuilder): void
    {
        $queryBuilder->setFirstResult($this->model->getOffset());
        $queryBuilder->setMaxResults($this->model->getLimit());
    }

    protected function prepareQueryBuilderForTotalCount(QueryBuilder $queryBuilder, string $identifierColumn): void
    {
        $queryBuilder->resetOrderBy();
        $queryBuilder->setMaxResults(null);
        $queryBuilder->setFirstResult(0);

        if (method_exists($this->model, 'addDistinct') && $this->model->addDistinct()) {
            $queryBuilder->distinct();
        }

        if ($this->isQueryBuilderPartInUse($queryBuilder, 'groupBy') || $this->isQueryBuilderPartInUse($queryBuilder, 'having')) {
            if (!$this->isQueryBuilderPartInUse($queryBuilder, 'select')) {
                $queryBuilder->select($identifierColumn);
            }
        } elseif ($this->isQueryBuilderPartInUse($queryBuilder, 'distinct')) {
            $countIdentifier = 'DISTINCT ' . $identifierColumn;
            $queryBuilder->select('COUNT(' . $countIdentifier . ') AS totalCount');
        } else {
            $queryBuilder->select('COUNT(*)');
        }
    }

    protected function isQueryBuilderPartInUse(QueryBuilder $query, string $part): bool
    {
        $mapping = [
            'groupBy' => 'GROUP BY ',
            'having' => 'HAVING ',
            'distinct'=> ' DISTINCT ',
            'select' => '^SELECT ',
        ];
        $pattern = '/' . $mapping[$part] . '/';

        try {
            $querySQL = $query->getSql();
        } catch (QueryException $e) {
            if (str_contains($e->getMessage(), 'No SELECT expressions given')) {
                if ($part === 'select') {
                    return false;
                }
                $newQueryBuilder = clone $query;
                $newQueryBuilder->select('*');
                $querySQL = $newQueryBuilder->getSQL();
            } else {
                $querySQL = $query->getSQL();
            }
        }

        if (preg_match($pattern, $querySQL, $matches)) {
            return true;
        }

        return false;
    }
}
