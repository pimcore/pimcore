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

class User_UserRole_Resource extends User_Abstract_Resource {

    public function getById($id) {
        parent::getById($id);
        $this->loadPermissions();
        $this->loadWorkspaces();
    }

    public function getByName($name) {
        parent::getByName($name);
        $this->loadPermissions();
        $this->loadWorkspaces();
    }

    public function loadPermissions () {
        $permissions = $this->db->fetchCol("SELECT name FROM users_permissions WHERE userId = ?", $this->model->getId());

        $list = new User_Permission_Definition_List();
        $definitions = $list->load();

        foreach ($definitions as $definition) {
            if(in_array($definition->getKey(), $permissions)) {
                $this->model->setPermission($definition->getKey(), true);
            } else {
                $this->model->setPermission($definition->getKey(), false);
            }
        }
    }

    public function loadWorkspaces () {

    }

    public function delete() {
        $this->db->delete("users_permissions", $this->db->quoteInto("userId = ?", $this->model->getId()));
    }

    public function update() {

        parent::update();

        $list = new User_Permission_Definition_List();
        $definitions = $list->load();

        $this->db->delete("users_permissions", $this->db->quoteInto("userId = ?", $this->model->getId()));

        foreach ($definitions as $definition) {
            if($this->model->getPermission($definition->getKey())) {
                $this->db->insert("users_permissions", array(
                    "userId" => $this->model->getId(),
                    "name" => $definition->getKey()
                ));
            }
        }
    }
}
