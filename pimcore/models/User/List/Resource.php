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

class User_List_Resource extends Pimcore_Model_List_Resource_Abstract {

    /**
     * Loads a list of users for the specicifies parameters, returns an array of User elements
     *
     * @return array
     */
    public function load() {

        $usersData = $this->db->fetchAll("SELECT id FROM users" . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());

        foreach ($usersData as $userData) {
            $users[] = User::getById($userData["id"]);
        }

        $this->model->setUsers($users);
        return $users;
    }

    protected function getCondition() {
        $cond = parent::getCondition();
        if(!empty($cond)){
            $cond.=" AND ";
        } else {
            $cond = " WHERE ";
        }
        $cond.="id > 0";
        return $cond;
    }

}
