<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Model\User\UserRole;

use Exception;
use Pimcore\Model;
use Pimcore\Model\Element;

/**
 * @internal
 *
 * @property \Pimcore\Model\User\UserRole\Folder $model
 */
class Dao extends Model\User\AbstractUser\Dao
{
    /**
     *
     * @throws Exception
     */
    public function getById(int $id): void
    {
        parent::getById($id);

        if (in_array($this->model->getType(), ['user', 'role'])) {
            $this->loadWorkspaces();
        }
    }

    /**
     *
     * @throws Exception
     */
    public function getByName(string $name): void
    {
        parent::getByName($name);

        if (in_array($this->model->getType(), ['user', 'role'])) {
            $this->loadWorkspaces();
        }
    }

    public function loadWorkspaces(): void
    {
        $types = ['asset', 'document', 'object'];

        foreach ($types as $type) {
            $workspaces = [];
            $baseClassName = Element\Service::getBaseClassNameForElement($type);
            $className = '\\Pimcore\\Model\\User\\Workspace\\' . $baseClassName;
            $result = $this->db->fetchAllAssociative('SELECT * FROM users_workspaces_' . $type . ' WHERE userId = ?', [$this->model->getId()]);
            foreach ($result as $row) {
                $workspace = new $className();
                $row['list'] = (bool)$row['list'];
                $row['view'] = (bool)$row['view'];
                $row['publish'] = (bool)$row['publish'];
                $row['delete'] = (bool)$row['delete'];
                $row['rename'] = (bool)$row['rename'];
                $row['create'] = (bool)$row['create'];
                $row['settings'] = (bool)$row['settings'];
                $row['versions'] = (bool)$row['versions'];
                $row['properties'] = (bool)$row['properties'];
                if ($type === 'document' || $type === 'object') {
                    $row['save'] = (bool)$row['save'];
                    $row['unpublish'] = (bool)$row['unpublish'];
                }
                $workspace->setValues($row, true);
                $workspaces[] = $workspace;
            }

            $this->model->{'setWorkspaces' . ucfirst($type)}($workspaces);
        }
    }

    public function emptyWorkspaces(): void
    {
        $this->db->delete('users_workspaces_asset', ['userId' => $this->model->getId()]);
        $this->db->delete('users_workspaces_document', ['userId' => $this->model->getId()]);
        $this->db->delete('users_workspaces_object', ['userId' => $this->model->getId()]);
    }
}
