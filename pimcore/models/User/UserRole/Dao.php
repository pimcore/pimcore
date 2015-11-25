<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    User
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\User\UserRole;

use Pimcore\Model;

class Dao extends Model\User\AbstractUser\Dao {

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
