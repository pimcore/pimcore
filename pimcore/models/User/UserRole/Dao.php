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
 * @package    User
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\User\UserRole;

use Pimcore\Model;

/**
 * @property \Pimcore\Model\User\UserRole\Folder $model
 */
class Dao extends Model\User\AbstractUser\Dao
{

    /**
     * @param $id
     * @throws \Exception
     */
    public function getById($id)
    {
        parent::getById($id);

        if (in_array($this->model->getType(), ["user", "role"])) {
            $this->loadWorkspaces();
        }
    }

    /**
     * @param $name
     * @throws \Exception
     */
    public function getByName($name)
    {
        parent::getByName($name);

        if (in_array($this->model->getType(), ["user", "role"])) {
            $this->loadWorkspaces();
        }
    }

    /**
     *
     */
    public function loadWorkspaces()
    {
        $types = ["asset", "document", "object"];

        foreach ($types as $type) {
            $workspaces = [];
            $className = "\\Pimcore\\Model\\User\\Workspace\\" . ucfirst($type);
            $result = $this->db->fetchAll("SELECT * FROM users_workspaces_" . $type . " WHERE userId = ?", [$this->model->getId()]);
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
    public function emptyWorkspaces()
    {
        $this->db->delete("users_workspaces_asset", ["userId" => $this->model->getId()]);
        $this->db->delete("users_workspaces_document", ["userId" => $this->model->getId()]);
        $this->db->delete("users_workspaces_object", ["userId" => $this->model->getId()]);
    }
}
