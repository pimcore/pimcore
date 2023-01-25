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
