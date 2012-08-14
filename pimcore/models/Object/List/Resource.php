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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Object_List_Resource extends Pimcore_Model_List_Resource_Abstract {

    /**
     * Loads a list of objects for the specicifies parameters, returns an array of Object_Abstract elements
     *
     * @return array
     */
    public function load() {

        $objects = array();
        $objectsData = $this->db->fetchAll("SELECT o_id,o_type FROM objects" . $this->getCondition() . $this->getGroupBy() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());

        foreach ($objectsData as $objectData) {
            if($object = Object_Abstract::getById($objectData["o_id"])) {
                $objects[] = $object;
            }
        }

        $this->model->setObjects($objects);
        return $objects;
    }

    /**
     * @return int
     */
    public function getCount() {
        if (count($this->model->getObjects()) > 0) {
            return count($this->model->getObjects());
        }

        $amount = (int) $this->db->fetchOne("SELECT COUNT(*) as amount FROM objects" . $this->getCondition() . $this->getGroupBy() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());
        return $amount;
    }

    /**
     * @return string
     */
    public function getTotalCount() {
        $amount = (int) $this->db->fetchOne("SELECT COUNT(*) as amount FROM objects" . $this->getCondition() . $this->getGroupBy(), $this->model->getConditionVariables());
        return $amount;
    }

    /**
     * Loads a list of document ids for the specicifies parameters, returns an array of ids
     *
     * @return array
     */
    public function loadIdList() {
        $objectIds = $this->db->fetchCol("SELECT o_id FROM objects" . $this->getCondition() . $this->getGroupBy() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());
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
            if (Object_Abstract::doHideUnpublished() && !$this->model->getUnpublished()) {
                return " WHERE (" . $condition . ") AND o_published = 1";
            }
            return " WHERE " . $condition . " ";
        }
        else if (Object_Abstract::doHideUnpublished() && !$this->model->getUnpublished()) {
            return " WHERE o_published = 1";
        }
        return "";
    }
}
