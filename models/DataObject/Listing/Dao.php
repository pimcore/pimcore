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

namespace Pimcore\Model\DataObject\Listing;

use Doctrine\DBAL\Query\QueryBuilder as DoctrineQueryBuilder;
use Pimcore\Db\ZendCompatibility\Expression;
use Pimcore\Db\ZendCompatibility\QueryBuilder as ZendCompatibilityQueryBuilder;
use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Model\Listing\Dao\QueryBuilderHelperTrait;

/**
 * @property \Pimcore\Model\DataObject\Listing $model
 */
class Dao extends Model\Listing\Dao\AbstractDao
{
    use QueryBuilderHelperTrait;

    /**
     * @deprecated
     *
     * @var \Closure
     */
    protected $onCreateQueryCallback;

    /**
     * @return string
     */
    public function getTableName()
    {
        return 'objects';
    }

    /**
     * @param array|string|Expression $columns
     *
     * @return ZendCompatibilityQueryBuilder
     *
     * @throws \Exception
     *
     * @deprecated use getQueryBuilder() instead.
     */
    public function getQuery($columns = '*')
    {
        @trigger_error(sprintf('Using %s is deprecated and will be removed in Pimcore 10, please use getQueryBuilder() instead', __METHOD__), E_USER_DEPRECATED);

        // init
        $select = $this->db->select();

        // create base
        $select->from([$this->getTableName()], $columns);

        // add joins
        $this->addJoins($select);

        // add condition
        $this->addConditions($select);

        // group by
        $this->addGroupBy($select);

        // order
        $this->addOrder($select);

        // limit
        $this->addLimit($select);

        if ($this->onCreateQueryCallback) {
            $closure = $this->onCreateQueryCallback;
            $closure($select);
        }

        return $select;
    }

    /**
     * @param string|string[]|null $columns
     *
     * @return DoctrineQueryBuilder
     *
     * @throws \Exception
     */
    public function getQueryBuilder(...$columns): DoctrineQueryBuilder
    {
        $queryBuilder = $this->db->createQueryBuilder();
        $queryBuilder->select(...$columns)->from($this->getTableName());

        // apply joins
        $this->applyJoins($queryBuilder);

        $this->applyListingParametersToQueryBuilder($queryBuilder);

        return $queryBuilder;
    }

    /**
     * Loads a list of objects for the specicifies parameters, returns an array of DataObject\AbstractObject elements
     *
     * @return array
     */
    public function load()
    {

        // load id's
        $list = $this->loadIdList();

        $objects = [];
        foreach ($list as $o_id) {
            if ($object = DataObject::getById($o_id)) {
                $objects[] = $object;
            }
        }

        $this->model->setObjects($objects);

        return $objects;
    }

    /**
     * @return int
     */
    public function getTotalCount()
    {
        $queryBuilder = $this->getQueryBuilderCompatibility();
        $this->prepareQueryBuilderForTotalCount($queryBuilder);

        $totalCount = $this->db->fetchOne((string)$queryBuilder, $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());

        return (int) $totalCount;
    }

    /**
     * @deprecated
     *
     * @param ZendCompatibilityQueryBuilder $query
     * @param string $part
     *
     * @return bool
     */
    private function isQueryPartinUse($query, $part)
    {
        try {
            if ($query->getPart($part)) {
                return true;
            }
        } catch (\Exception $e) {
            // do nothing
        }

        return false;
    }

    /**
     * @return int
     */
    public function getCount()
    {
        if ($this->model->isLoaded()) {
            return count($this->model->getObjects());
        } else {
            $idList = $this->loadIdList();

            return count($idList);
        }
    }

    /**
     * Loads a list of document ids for the specicifies parameters, returns an array of ids
     *
     * @return int[]
     */
    public function loadIdList()
    {
        $queryBuilder = $this->getQueryBuilderCompatibility([sprintf('%s as o_id', $this->getTableName() . '.o_id'), sprintf('%s as o_type', $this->getTableName() . '.o_type')]);
        $objectIds = $this->db->fetchCol((string) $queryBuilder, $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());

        return array_map('intval', $objectIds);
    }

    /**
     * @deprecated
     *
     * @param ZendCompatibilityQueryBuilder $select
     *
     * @return $this
     *
     */
    protected function addJoins(ZendCompatibilityQueryBuilder $select)
    {
        return $this;
    }

    /**
     * @param DoctrineQueryBuilder $queryBuilder
     *
     * @return $this
     */
    protected function applyJoins(DoctrineQueryBuilder $queryBuilder)
    {
        return $this;
    }

    /**
     * @deprecated
     *
     * @param ZendCompatibilityQueryBuilder $select
     *
     * @return $this
     */
    protected function addConditions(ZendCompatibilityQueryBuilder $select)
    {
        $condition = $this->model->getCondition();
        $objectTypes = $this->model->getObjectTypes();

        $tableName = $this->getTableName();

        if (!empty($objectTypes)) {
            if (!empty($condition)) {
                $condition .= ' AND ';
            }
            $condition .= ' ' . $tableName . ".o_type IN ('" . implode("','", $objectTypes) . "')";
        }

        if ($condition) {
            if (DataObject::doHideUnpublished() && !$this->model->getUnpublished()) {
                $condition = '(' . $condition . ') AND ' . $tableName . '.o_published = 1';
            }
        } elseif (DataObject::doHideUnpublished() && !$this->model->getUnpublished()) {
            $condition = $tableName . '.o_published = 1';
        }

        if ($condition) {
            $select->where($condition);
        }

        return $this;
    }

    /**
     * @param callable $callback
     */
    public function onCreateQuery(callable $callback)
    {
        @trigger_error(sprintf('Using %s is deprecated and will be removed in Pimcore 10, please use onCreateQueryBuilder() instead', __METHOD__), E_USER_DEPRECATED);
        $this->onCreateQueryCallback = $callback;
    }
}
