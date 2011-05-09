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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class User_Resource extends Pimcore_Model_Resource_Abstract {

    /**
     * Contains all valid columns in the database table
     *
     * @var array
     */
    protected $validColumns = array();

    /**
     * Get the valid columns from the database
     *
     * @return void
     */
    public function init() {
        $this->validColumns = $this->getValidTableColumns("users");
    }

    /**
     * Get the data for the object from database for the given id
     *
     * @param integer $id
     * @return void
     */
    public function getById($id) {

        $data = $this->db->fetchRow("SELECT * FROM users WHERE id = ?", $id);

        if (is_numeric($data["id"])) {
            $this->assignVariablesToModel($data);
        } else {
            throw new Exception("user doesn't exist");
        }


    }

    /**
     * Get the data for the object from database for the given name
     *
     * @param string $name
     * @return void
     */
    public function getByName($username) {
        try {
            $data = $this->db->fetchRow("SELECT * FROM users WHERE username = ?", $username);

            if ($data["id"]) {
                $this->assignVariablesToModel($data);
            }
            else {
                throw new Exception("user doesn't exist");
            }
        }
        catch (Exception $e) {
            throw $e;
        }

    }

    /**
     * Get the data for the object from database for the given name
     *
     * @param string $name
     * @return void
     */
    public function save() {

        if ($this->model->getId()) {
            return $this->model->update();
        }
        return $this->create();
    }

    /**
     * Create a new record for the object in database
     *
     * @return boolean
     */
    public function create() {
        try {
            $this->db->insert("users", array(
                "username" => $this->model->getUsername(),
                "password" => $this->model->getPassword()
            ));

            $this->model->setId($this->db->lastInsertId());

            return $this->save();
        }
        catch (Exception $e) {
            throw $e;
        }

    }

     /**
     * Quick test if there are children
     *
     * @return boolean
     */
    public function hasChilds() {
        $c = $this->db->fetchRow("SELECT id FROM users WHERE parentId = ?",  $this->model->getId());

        $state = false;
        if ($c["id"]) {
            $state = true;
        }

        $this->model->setHasChilds($state);

        return $state;
    }


    /**
     * Save changes to database, it's an good idea to use save() instead
     *
     * @return void
     */
    public function update() {
        try {
            $data["id"] = $this->model->getId();
            $data["parentId"] = $this->model->getParentId();
            $data["username"] = $this->model->getUsername();
            $data["password"] = $this->model->getPassword();
            $data["language"] = $this->model->getLanguage();
            $data["firstname"] = $this->model->getFirstname();
            $data["lastname"] = $this->model->getLastname();
            $data["email"] = $this->model->getEmail();
            $data["admin"] = intval($this->model->getAdmin());
            $data["active"] = intval($this->model->getActive());
            $data["hasCredentials"] = intval($this->model->getHasCredentials());

            $this->db->update("users", $data, $this->db->quoteInto("id = ?", $this->model->getId() ));
            if ($this->model->getUserPermissionList() != null) {
                $this->model->getUserPermissionList()->update($this->model);
            }
        }
        catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Deletes object from database
     *
     * @return void
     */
    public function delete() {

        $userId = $this->model->getId();

        Logger::debug("delete user with ID: " . $userId);

        try {
            $this->db->delete("users", $this->db->quoteInto("id = ?", $userId ));
            $this->model->getUserPermissionList()->deleteForUser($this->model);
        }
        catch (Exception $e) {
            throw $e;
        }

        // cleanup system

        // assets
        $this->db->update("assets", array("userOwner" => null), $this->db->quoteInto("userOwner = ?", $userId));
        $this->db->update("assets", array("userModification" => null), $this->db->quoteInto("userModification = ?", $userId));
        $this->db->delete("assets_permissions", $this->db->quoteInto("userId = ?", $userId));

        // classes
        $this->db->update("classes", array("userOwner" => null), $this->db->quoteInto("userOwner = ?", $userId));
        $this->db->update("classes", array("userModification" => null), $this->db->quoteInto("userModification = ?", $userId));

        // documents
        $this->db->update("documents", array("userOwner" => null), $this->db->quoteInto("userOwner = ?", $userId));
        $this->db->update("documents", array("userModification" => null), $this->db->quoteInto("userModification = ?", $userId . "'"));
        $this->db->delete("documents_permissions", $this->db->quoteInto("userId = ?", $userId ));

        // objects
        $this->db->update("objects", array("o_userOwner" => null), $this->db->quoteInto("o_userOwner = ?", $userId ));
        $this->db->update("objects", array("o_userModification" => null), $this->db->quoteInto("o_userModification = ?", $userId));
        $this->db->delete("objects_permissions", $this->db->quoteInto("userId= ?", $userId ));

        // versions
        $this->db->update("versions", array("userId" => null), $this->db->quoteInto("userId = ?", $userId));
    }

}
