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
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Object\Listing;

use Pimcore\Model;
use Pimcore\Model\Object;
use Prophecy\Comparator\ClosureComparator;

/**
 * @property \Pimcore\Model\Object\Listing $model
 */
class Dao extends Model\Listing\Dao\AbstractDao
{
    /** @var  Callback function */
    protected $onCreateQueryCallback;

    /**
     * get select query
     *
     * @return \Zend_Db_Select
     * @throws \Exception
     */
    public function getQuery()
    {
        // init
        $select = $this->db->select();

        // create base
        $select->from(
            [ 'objects' ], [
                new \Zend_Db_Expr('SQL_CALC_FOUND_ROWS objects.o_id'), 'objects.o_type'
            ]
        );


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
     * Loads a list of objects for the specicifies parameters, returns an array of Object\AbstractObject elements
     *
     * @return array
     */
    public function load()
    {

        // load id's
        $list = $this->loadIdList();


        $objects = [];
        foreach ($list as $o_id) {
            if ($object = Object::getById($o_id)) {
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
        $limit = $this->model->getLimit();
        $hasLimit = !empty($limit);
        $query = $this->getQuery();

        if (!$hasLimit) {
            $query->limit(1);
        }

        $this->loadIdList();

        if (!$hasLimit) {
            $query->reset(\Zend_Db_Select::LIMIT_COUNT);
        }

        return (int)$this->totalCount;
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
        $query = $this->getQuery(true);
        $objectIds = $this->db->fetchCol($query, $this->model->getConditionVariables());
        $this->totalCount = (int)$this->db->fetchOne('SELECT FOUND_ROWS()');

        return $objectIds;
    }


    /**
     * @param \Zend_DB_Select $select
     *
     * @return $this
     */
    protected function addJoins(\Zend_DB_Select $select)
    {
        return $this;
    }


    /**
     * @param \Zend_DB_Select $select
     *
     * @return $this
     */
    protected function addConditions(\Zend_DB_Select $select)
    {
        $condition = $this->model->getCondition();
        $objectTypes = $this->model->getObjectTypes();

        $tableName = method_exists($this, "getTableName") ? $this->getTableName() : "objects";

        if (!empty($objectTypes)) {
            if (!empty($condition)) {
                $condition .= " AND ";
            }
            $condition .= " " . $tableName . ".o_type IN ('" . implode("','", $objectTypes) . "')";
        }

        if ($condition) {
            if (Object\AbstractObject::doHideUnpublished() && !$this->model->getUnpublished()) {
                $condition = "(" . $condition . ") AND " . $tableName . ".o_published = 1";
            }
        } elseif (Object\AbstractObject::doHideUnpublished() && !$this->model->getUnpublished()) {
            $condition = $tableName . ".o_published = 1";
        }


        if ($condition) {
            $select->where($condition);
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function reset()
    {
        $this->totalCount = 0;

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
