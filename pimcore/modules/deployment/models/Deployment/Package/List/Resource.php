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
 * @package    Document
 * @copyright  Copyright (c) 2009-2013 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Deployment_Package_List_Resource extends Pimcore_Model_List_Resource_Abstract {

    public function load() {
        $data = array();
        $ids = $this->db->fetchCol("SELECT id FROM " . Deployment_Package_Resource::TABLE_NAME . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());

        foreach($ids as $id){
            $data[] = Deployment_Package::getById($id);
        }
        return $data;
    }

    /**
     * Returns the total amount of Email_Log entries
     *
     * @return integer
     */
    public function getTotalCount() {

        try {
            $amount = (int) $this->db->fetchOne("SELECT COUNT(*) as amount FROM " . Deployment_Package_Resource::TABLE_NAME ." " . $this->getCondition(), $this->model->getConditionVariables());
        } catch (Exception $e) {
            Logger::log($e->getMessage());
        }
        return $amount;
    }
}
