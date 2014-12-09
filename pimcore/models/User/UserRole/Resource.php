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
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\User\UserRole;

use Pimcore\Model;

class Resource extends Model\User\AbstractUser\Resource {

    /**
     * @param $id
     * @throws \Exception
     */
    public function getById($id) {
        parent::getById($id);

        if(in_array($this->model->getType(), array("user","role"))) {
            $this->loadWorkspaces();
        }
    }

    /**
     * @param $name
     * @throws \Exception
     */
    public function getByName($name) {
        parent::getByName($name);

        if(in_array($this->model->getType(), array("user","role"))) {
            $this->loadWorkspaces();
        }
    }

    /**
     *
     */
    public function loadWorkspaces () {

        $types = array("asset","document","object");

        foreach ($types as $type) {
            $workspaces = array();
            $className = "\\Pimcore\\Model\\User\\Workspace\\" . ucfirst($type);
            $result = $this->db->fetchAll("SELECT * FROM users_workspaces_" . $type . " WHERE userId = ?", $this->model->getId());
            foreach ($result as $row) {
                $workspace = new $className();
                $workspace->setValues($row);
                $workspaces[] = $workspace;
            }

            $this->model->{"setWorkspaces" . ucfirst($type)}($workspaces);
        }
    }

    /**
     *
     */
    public function emptyWorkspaces () {
        $this->db->delete("users_workspaces_asset", $this->db->quoteInto("userId = ?", $this->model->getId() ));
        $this->db->delete("users_workspaces_document", $this->db->quoteInto("userId = ?", $this->model->getId() ));
        $this->db->delete("users_workspaces_object", $this->db->quoteInto("userId = ?", $this->model->getId() ));
    }
}
