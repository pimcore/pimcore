<?php

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Model\User\UserRole;

use Exception;
use Pimcore\Model;
use Pimcore\Model\Element;
use Pimcore\Model\User;

/**
 * @internal
 *
 * @property \Pimcore\Model\User\UserRole $model
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

        if (in_array($this->model->getType(), ['user', 'role', 'serviceuser'], true)) {
            $this->loadWorkspaces();
        }

        $this->loadResponsibles();
    }

    /**
     *
     * @throws Exception
     */
    public function getByName(string $name): void
    {
        parent::getByName($name);

        if (in_array($this->model->getType(), ['user', 'role', 'serviceuser'], true)) {
            $this->loadWorkspaces();
        }

        $this->loadResponsibles();
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

    /**
     * Loads the responsible users and roles of a service user.
     */
    protected function loadResponsibles(): void
    {
        if (!$this->model instanceof User || !$this->model->isServiceUser()) {
            return;
        }

        $users = [];
        $roles = [];
        $rows = $this->db->fetchAllAssociative(
            'SELECT `principalType`, `principalId` FROM `users_responsibles` WHERE `serviceUserId` = ?'
            . ' ORDER BY `principalId`',
            [$this->model->getId()]
        );
        foreach ($rows as $row) {
            if ($row['principalType'] === 'role') {
                $roles[] = (int) $row['principalId'];
            } else {
                $users[] = (int) $row['principalId'];
            }
        }

        $this->model->setResponsibleUsers($users)->setResponsibleRoles($roles);
    }

    /**
     * Replaces the stored responsible users and roles of a service user (runs in the transaction of save()).
     */
    protected function saveResponsibles(): void
    {
        if (!$this->model instanceof User || !$this->model->isServiceUser()) {
            return;
        }

        $serviceUserId = $this->model->getId();
        $this->db->delete('users_responsibles', ['serviceUserId' => $serviceUserId]);

        foreach (['user' => $this->model->getResponsibleUsers(), 'role' => $this->model->getResponsibleRoles()] as $type => $ids) {
            foreach ($ids as $id) {
                $this->db->insert('users_responsibles', [
                    'serviceUserId' => $serviceUserId,
                    'principalType' => $type,
                    'principalId' => $id,
                ]);
            }
        }
    }

    public function emptyWorkspaces(): void
    {
        $this->db->delete('users_workspaces_asset', ['userId' => $this->model->getId()]);
        $this->db->delete('users_workspaces_document', ['userId' => $this->model->getId()]);
        $this->db->delete('users_workspaces_object', ['userId' => $this->model->getId()]);
    }
}
