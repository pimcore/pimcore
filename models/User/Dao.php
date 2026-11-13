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

namespace Pimcore\Model\User;

/**
 * @internal
 *
 * @property \Pimcore\Model\User $model
 */
class Dao extends UserRole\Dao
{
    /**
     * Users and service users share one namespace of names, while the unique key only covers names
     * within one type. Tells whether the other of the two types already uses the model's name.
     */
    public function isNameTakenByOtherUserType(): bool
    {
        $otherType = match ($this->model->getType()) {
            'user' => 'serviceuser',
            'serviceuser' => 'user',
            default => null,
        };

        if ($otherType === null) {
            return false;
        }

        return $this->db->fetchOne(
            'SELECT `id` FROM `users` WHERE `type` = ? AND `name` = ? LIMIT 1',
            [$otherType, $this->model->getName()]
        ) !== false;
    }

    /**
     * The type the row of the model has in the database, null while the model is not saved.
     */
    public function getStoredType(): ?string
    {
        if ($this->model->getId() === null) {
            return null;
        }

        $type = $this->db->fetchOne('SELECT `type` FROM `users` WHERE `id` = ?', [$this->model->getId()]);

        return $type === false ? null : (string) $type;
    }

    /**
     * Deletes object from database
     */
    public function delete(): void
    {
        parent::delete();

        $userId = $this->model->getId();

        // cleanup system

        // assets
        $this->db->update('assets', ['userOwner' => null], ['userOwner' => $userId]);
        $this->db->update('assets', ['userModification' => null], ['userModification' => $userId]);

        // documents
        $this->db->update('documents', ['userOwner' => null], ['userOwner' => $userId]);
        $this->db->update('documents', ['userModification' => null], ['userModification' => $userId]);

        // objects
        $this->db->update('objects', ['userOwner' => null], ['userOwner' => $userId]);
        $this->db->update('objects', ['userModification' => null], ['userModification' => $userId]);

        // versions
        $this->db->update('versions', ['userId' => null], ['userId' => $userId]);

        // scheduled tasks
        $this->db->delete('schedule_tasks', ['userId' => $userId]);
    }
}
