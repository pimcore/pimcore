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

use Doctrine\DBAL\ArrayParameterType;
use Exception;
use Pimcore\Model;
use Pimcore\Model\Element;
use Pimcore\Model\User;
use Pimcore\Model\User\Exception\InvalidServiceUserException;

use function count;
use function sprintf;

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
     *
     * Only principals that still exist with the stored type are loaded: a row left behind by a principal deleted
     * while the service user was being saved is ignored here and dropped by the next save().
     */
    protected function loadResponsibles(): void
    {
        if (!$this->model instanceof User || !$this->model->isServiceUser()) {
            return;
        }

        $users = [];
        $roles = [];
        $rows = $this->db->fetchAllAssociative(
            'SELECT r.`principalType`, r.`principalId` FROM `users_responsibles` r'
            . ' INNER JOIN `users` p ON p.`id` = r.`principalId` AND p.`type` = r.`principalType`'
            . ' WHERE r.`serviceUserId` = ? ORDER BY r.`principalId`',
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
     *
     * The values are checked here, right before they are written and after the pre-save events, so a value a
     * listener set is checked as well; the exception rolls the whole save back.
     *
     * @throws InvalidServiceUserException if a person has responsible users or roles, or a responsible id is not
     *     an existing user or role
     * @throws Exception
     */
    protected function saveResponsibles(): void
    {
        if (!$this->model instanceof User) {
            return;
        }

        $principals = [
            'user' => $this->model->getResponsibleUsers(),
            'role' => $this->model->getResponsibleRoles(),
        ];

        if (!$this->model->isServiceUser()) {
            if ($principals['user'] !== [] || $principals['role'] !== []) {
                throw new InvalidServiceUserException('Only a service user can have responsible users or roles.');
            }

            return;
        }

        $this->assertResponsiblesExist($principals);

        $serviceUserId = $this->model->getId();
        $this->db->delete('users_responsibles', ['serviceUserId' => $serviceUserId]);

        foreach ($principals as $type => $ids) {
            foreach ($ids as $id) {
                $this->db->insert('users_responsibles', [
                    'serviceUserId' => $serviceUserId,
                    'principalType' => $type,
                    'principalId' => $id,
                ]);
            }
        }
    }

    /**
     * Every responsible user id must be a person (type 'user'), every responsible role id a role.
     *
     * @param array{user: int[], role: int[]} $principals
     *
     * @throws InvalidServiceUserException
     * @throws Exception
     */
    private function assertResponsiblesExist(array $principals): void
    {
        foreach ($principals as $type => $ids) {
            if ($ids === []) {
                continue;
            }

            $found = $this->db->fetchFirstColumn(
                'SELECT `id` FROM `users` WHERE `type` = :type AND `id` IN (:ids)',
                ['type' => $type, 'ids' => $ids],
                ['ids' => ArrayParameterType::INTEGER]
            );

            if (count($found) !== count($ids)) {
                throw new InvalidServiceUserException(sprintf(
                    'Every responsible %s id must belong to an existing %s.',
                    $type,
                    $type === 'user' ? 'user (not a service user or folder)' : 'role'
                ));
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
