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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\User;

/**
 * @property \Pimcore\Model\User $model
 */
class Dao extends UserRole\Dao
{

    /**
     * Deletes object from database
     */
    public function delete()
    {
        parent::delete();

        $userId = $this->model->getId();

        // cleanup system

        // assets
        $this->db->update("assets", ["userOwner" => null], ["userOwner" => $userId]);
        $this->db->update("assets", ["userModification" => null], ["userModification" => $userId]);
        $this->db->delete("users_workspaces_asset", ["userId" => $userId]);

        // documents
        $this->db->update("documents", ["userOwner" => null], ["userOwner" => $userId]);
        $this->db->update("documents", ["userModification" => null], ["userModification" => $userId]);
        $this->db->delete("users_workspaces_document", ["userId" => $userId]);

        // objects
        $this->db->update("objects", ["o_userOwner" => null], ["o_userOwner" => $userId]);
        $this->db->update("objects", ["o_userModification" => null], ["o_userModification" => $userId]);
        $this->db->delete("users_workspaces_object", ["userId" => $userId]);

        // versions
        $this->db->update("versions", ["userId" => null], ["userId" => $userId]);
    }
}
