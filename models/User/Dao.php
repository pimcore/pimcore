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
    }
}
