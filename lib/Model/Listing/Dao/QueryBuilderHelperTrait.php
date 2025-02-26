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

namespace Pimcore\Model\Listing\Dao;

use Doctrine\DBAL\Query\QueryBuilder;
use Exception;
use Pimcore\Model\DataObject;

trait QueryBuilderHelperTrait
{
    /**
     * @var callable|null
     */
    protected $onCreateQueryBuilderCallback;

    public function onCreateQueryBuilder(?callable $callback): void
    {
        $this->onCreateQueryBuilderCallback = $callback;
    }

    protected function applyListingParametersToQueryBuilder(QueryBuilder $queryBuilder): void
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
        $originalSelect = $queryBuilder->getQueryPart('select');
        $queryBuilder->select('COUNT(*)');
        $queryBuilder->resetOrderBy();
        $queryBuilder->setMaxResults(null);
        $queryBuilder->setFirstResult(0);

        if (method_exists($this->model, 'addDistinct') && $this->model->addDistinct()) {
            $queryBuilder->distinct();
        }

        if ($this->isQueryBuilderPartInUse($queryBuilder, 'groupBy') || $this->isQueryBuilderPartInUse($queryBuilder, 'having')) {
            $queryBuilder->select(!empty($originalSelect) ? $originalSelect : $identifierColumn);

            // Rewrite to 'SELECT COUNT(*) FROM (' . $queryBuilder . ') XYZ'
            $innerQuery = (string)$queryBuilder;
            $queryBuilder
                ->resetQueryParts()
                ->select('COUNT(*)')
                ->from('(' . $innerQuery . ')', 'XYZ')
            ;
        } elseif ($this->isQueryBuilderPartInUse($queryBuilder, 'distinct')) {
            $countIdentifier = 'DISTINCT ' . $identifierColumn;
            $queryBuilder->select('COUNT(' . $countIdentifier . ') AS totalCount');
        }
    }

    protected function isQueryBuilderPartInUse(QueryBuilder $query, string $part): bool
    {
        try {
            if ($query->getQueryPart($part)) {
                return true;
            }
        } catch (Exception $e) {
            // do nothing
        }

        return false;
    }
}
