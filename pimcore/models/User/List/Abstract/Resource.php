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
 * @package    User
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class User_List_Abstract_Resource extends Pimcore_Model_List_Resource_Abstract {

    /**
     * Loads a list of users for the specifies parameters, returns an array of User elements
     * @return array
     */
    public function load() {

        $items = array();
        $usersData = $this->db->fetchAll("SELECT id,type FROM users" . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());

        foreach ($usersData as $userData) {
            $className = User_Service::getClassNameForType($userData["type"]);
            $item = $className::getById($userData["id"]);
            if($item) {
                $items[] = $item;
            }
        }

        $this->model->setItems($items);
        return $items;
    }

    protected function getCondition() {
        $condition = parent::getCondition();
        if(!empty($condition)){
            $condition.=" AND ";
        } else {
            $condition = " WHERE ";
        }

        $types = array($this->model->getType(), $this->model->getType() . "folder");
        $condition .= "id > 0 AND `type` IN ('" . implode("','",$types) . "')";

        return $condition;
    }

}
