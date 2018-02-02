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
 * @package    Object
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\Listing;

use Pimcore\Db\ZendCompatibility\Expression;
use Pimcore\Db\ZendCompatibility\QueryBuilder;
use Pimcore\Model;
use Pimcore\Model\DataObject;

/**
 * @property \Pimcore\Model\DataObject\Listing $model
 */
class Dao extends Model\Listing\Dao\AbstractDao
{
    /** @var Callback function */
    protected $onCreateQueryCallback;

    /**
     * @return string
     */
    public function getTableName()
    {
        return 'objects';
    }

    /**
     * get select query
     *
     * @return QueryBuilder
     *
     * @throws \Exception
     */
    public function getQuery()
    {
        // init
        $select = $this->db->select();

        // create base
        $select->from([ $this->getTableName() ]);

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
        $query = $this->getQuery();

        if ($this->model->addDistinct()) {
            $query->distinct(true);
        }

        $query->reset(QueryBuilder::LIMIT_COUNT);
        $query->reset(QueryBuilder::LIMIT_OFFSET);
        $query->reset(QueryBuilder::ORDER);

        if ($this->isQueryPartinUse($query, QueryBuilder::GROUP) || $this->isQueryPartinUse($query, QueryBuilder::HAVING)) {
            $query = 'SELECT COUNT(*) FROM (' . $query . ') as XYZ';
        } else {
            $query->reset(QueryBuilder::COLUMNS);

            $countIdentifier = '*';
            if ($this->isQueryPartinUse($query, QueryBuilder::DISTINCT)) {
                $countIdentifier = 'DISTINCT ' . $this->getTableName() . '.o_id';
            }

            $query->columns(['totalCount' => new Expression('COUNT(' . $countIdentifier . ')')]);
        }

        $totalCount = $this->db->fetchOne($query, $this->model->getConditionVariables());

        return (int) $totalCount;
    }

    /**
     * @param QueryBuilder $query
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
        if (count($this->model->getObjects()) == 0) {
            $this->load();
        }

        return count($this->model->getObjects());
    }

    /**
     * Loads a list of document ids for the specicifies parameters, returns an array of ids
     *
     * @return array
     */
    public function loadIdList()
    {
        $query = $this->getQuery();
        $objectIds = $this->db->fetchCol($query, $this->model->getConditionVariables());

        return $objectIds;
    }

    /**
     * @param QueryBuilder $select
     *
     * @return $this
     */
    protected function addJoins(QueryBuilder $select)
    {
        return $this;
    }

    /**
     * @param QueryBuilder $select
     *
     * @return $this
     */
    protected function addConditions(QueryBuilder $select)
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
            if (DataObject\AbstractObject::doHideUnpublished() && !$this->model->getUnpublished()) {
                $condition = '(' . $condition . ') AND ' . $tableName . '.o_published = 1';
            }
        } elseif (DataObject\AbstractObject::doHideUnpublished() && !$this->model->getUnpublished()) {
            $condition = $tableName . '.o_published = 1';
        }

        if ($condition) {
            $select->where($condition);
        }

        return $this;
    }

    /**
     * @param $callback Callable
     */
    public function onCreateQuery(callable $callback)
    {
        $this->onCreateQueryCallback = $callback;
    }
}
