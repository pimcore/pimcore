<?php
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @category   Pimcore
 * @package    User
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\User;

use Pimcore\Model;

class Resource extends UserRole\Resource {

    /**
     * Deletes object from database
     *
     * @return void
     */
    public function delete() {

        parent::delete();

        $userId = $this->model->getId();

        // cleanup system

        // assets
        $this->db->update("assets", array("userOwner" => null), $this->db->quoteInto("userOwner = ?", $userId));
        $this->db->update("assets", array("userModification" => null), $this->db->quoteInto("userModification = ?", $userId));
        $this->db->delete("users_workspaces_asset", $this->db->quoteInto("userId = ?", $userId));

        // classes
        $this->db->update("classes", array("userOwner" => null), $this->db->quoteInto("userOwner = ?", $userId));
        $this->db->update("classes", array("userModification" => null), $this->db->quoteInto("userModification = ?", $userId));

        // documents
        $this->db->update("documents", array("userOwner" => null), $this->db->quoteInto("userOwner = ?", $userId));
        $this->db->update("documents", array("userModification" => null), $this->db->quoteInto("userModification = ?", $userId));
        $this->db->delete("users_workspaces_document", $this->db->quoteInto("userId = ?", $userId ));

        // objects
        $this->db->update("objects", array("o_userOwner" => null), $this->db->quoteInto("o_userOwner = ?", $userId ));
        $this->db->update("objects", array("o_userModification" => null), $this->db->quoteInto("o_userModification = ?", $userId));
        $this->db->delete("users_workspaces_object", $this->db->quoteInto("userId= ?", $userId ));

        // versions
        $this->db->update("versions", array("userId" => null), $this->db->quoteInto("userId = ?", $userId));
    }

}
