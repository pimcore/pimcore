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
            // return all documents as Type Document => fot trees an so on there isn't the whole data required
            $objects[] = Object_Abstract::getById($objectData["o_id"]);
        }

        $this->model->setObjects($objects);
        return $objects;
    }
    
    public function getCount() {
        if (count($this->model->getObjects()) > 0) {
            return count($this->model->getObjects());
        }

        $amount = $this->db->fetchAll("SELECT COUNT(*) as amount FROM objects" . $this->getCondition() . $this->getGroupBy() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());
        return $amount["amount"];
    }
    
    public function getTotalCount() {
        $amount = $this->db->fetchRow("SELECT COUNT(*) as amount FROM objects" . $this->getCondition() . $this->getGroupBy(), $this->model->getConditionVariables());

        return $amount["amount"];
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

    protected function getCondition() {
        if ($cond = $this->model->getCondition()) {
            if (Object_Abstract::doHideUnpublished() && !$this->model->getUnpublished()) {
                return " WHERE (" . $cond . ") AND o_published = 1";
            }
            return " WHERE " . $cond . " ";
        }
        else if (Object_Abstract::doHideUnpublished() && !$this->model->getUnpublished()) {
            return " WHERE o_published = 1";
        }
        return "";
    }
}
