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
        $this->db->update("assets", ["userOwner" => null], $this->db->quoteInto("userOwner = ?", $userId));
        $this->db->update("assets", ["userModification" => null], $this->db->quoteInto("userModification = ?", $userId));
        $this->db->delete("users_workspaces_asset", $this->db->quoteInto("userId = ?", $userId));

        // documents
        $this->db->update("documents", ["userOwner" => null], $this->db->quoteInto("userOwner = ?", $userId));
        $this->db->update("documents", ["userModification" => null], $this->db->quoteInto("userModification = ?", $userId));
        $this->db->delete("users_workspaces_document", $this->db->quoteInto("userId = ?", $userId));

        // objects
        $this->db->update("objects", ["o_userOwner" => null], $this->db->quoteInto("o_userOwner = ?", $userId));
        $this->db->update("objects", ["o_userModification" => null], $this->db->quoteInto("o_userModification = ?", $userId));
        $this->db->delete("users_workspaces_object", $this->db->quoteInto("userId= ?", $userId));

        // versions
        $this->db->update("versions", ["userId" => null], $this->db->quoteInto("userId = ?", $userId));
    }
}
