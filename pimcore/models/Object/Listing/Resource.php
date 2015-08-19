<?php
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @category   Pimcore
 * @package    Object
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Object\Listing;

use Pimcore\Model;
use Pimcore\Model\Object;

class Resource extends Model\Listing\Resource\AbstractResource {


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
            [ 'objects' ]
            , [
                new \Zend_Db_Expr('SQL_CALC_FOUND_ROWS o_id')
                , 'o_type'
            ]
        );


        // add joins
        $this->addJoins( $select );

        // add condition
        $this->addConditions( $select );

        // group by
        $this->addGroupBy( $select );

        // order
        $this->addOrder( $select );

        // limit
        $this->addLimit( $select );

        return $select;
    }


    /**
     * Loads a list of objects for the specicifies parameters, returns an array of Object\AbstractObject elements
     *
     * @return array
     */
    public function load() {

        // load id's
        $list = $this->loadIdList();


        $objects = array();
        foreach ($list as $o_id) {
            if($object = Object::getById($o_id)) {
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

        if(!$hasLimit)
        {
            $query->limit(1);
        }

        $this->loadIdList();

        if(!$hasLimit)
        {
            $query->reset( \Zend_Db_Select::LIMIT_COUNT );
        }

        return (int)$this->totalCount;
    }


    /**
     * @return int
     */
    public function getCount()
    {
        if (count($this->model->getObjects()) == 0)
        {
            $this->load();
        }

        return count($this->model->getObjects());
    }

    /**
     * Loads a list of document ids for the specicifies parameters, returns an array of ids
     *
     * @return array
     */
    public function loadIdList() {

        $query = $this->getQuery(true);
        $objectIds = $this->db->fetchCol( $query, $this->model->getConditionVariables() );
        $this->totalCount = (int)$this->db->fetchOne( 'SELECT FOUND_ROWS()' );

        return $objectIds;
    }


    /**
     * @return string
     */
    protected function getCondition() {

        $condition = $this->model->getCondition();
        $objectTypes = $this->model->getObjectTypes();
        if(!empty($objectTypes)) {
            if(!empty($condition)) {
                $condition .= " AND ";
            }
            $condition .= " o_type IN ('" . implode("','", $objectTypes) . "')";
        }

        if ($condition) {
            if (Object\AbstractObject::doHideUnpublished() && !$this->model->getUnpublished()) {
                return " WHERE (" . $condition . ") AND o_published = 1";
            }
            return " WHERE " . $condition . " ";
        }
        else if (Object\AbstractObject::doHideUnpublished() && !$this->model->getUnpublished()) {
            return " WHERE o_published = 1";
        }
        return "";
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

        if(!empty($objectTypes)) {
            if(!empty($condition)) {
                $condition .= " AND ";
            }
            $condition .= " o_type IN ('" . implode("','", $objectTypes) . "')";
        }

        if ($condition) {
            if (Object\AbstractObject::doHideUnpublished() && !$this->model->getUnpublished()) {
                $condition = "(" . $condition . ") AND o_published = 1";
            }
        }
        else if (Object\AbstractObject::doHideUnpublished() && !$this->model->getUnpublished()) {
            $condition = "o_published = 1";
        }


        if($condition)
        {
            $select->where( $condition );
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function reset() {
        $this->totalCount = 0;

        return $this;
    }
}
