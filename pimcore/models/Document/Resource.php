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
 * @package    Document
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Document;

use Pimcore\Model;

class Resource extends Model\Element\Resource
{

    /**
     * Contains the valid database colums
     *
     * @var array
     */
    protected $validColumnsDocument = array();

    /**
     * Get the valid database columns from database
     *
     * @return void
     */
    public function init()
    {
        $this->validColumnsDocument = $this->getValidTableColumns("documents");
    }

    /**
     * @param $id
     * @throws \Exception
     */
    public function getById($id)
    {
        try {
            $data = $this->db->fetchRow("SELECT documents.*, tree_locks.locked FROM documents
                LEFT JOIN tree_locks ON documents.id = tree_locks.id AND tree_locks.type = 'document'
                    WHERE documents.id = ?", $id);

        } catch (\Exception $e) {
        }

        if ($data["id"] > 0) {
            $this->assignVariablesToModel($data);
        } else {
            throw new \Exception("Document with the ID " . $id . " doesn't exists");
        }
    }

    /**
     * Get the data for the document from database for the given path
     *
     * @param string $path
     * @return void
     */
    public function getByPath($path)
    {

        // check for root node
        $_path = $path != "/" ? dirname($path) : $path;
        $_path = str_replace("\\", "/", $_path); // windows patch
        $_key = basename($path);
        $_path .= $_path != "/" ? "/" : "";

        $data = $this->db->fetchRow("SELECT id FROM documents WHERE path = " . $this->db->quote($_path) . " and `key` = " . $this->db->quote($_key));

        if ($data["id"]) {
            $this->assignVariablesToModel($data);
        } else {
            // try to find a page with a pretty URL (use the original $path)
            $data = $this->db->fetchRow("SELECT id FROM documents_page WHERE prettyUrl = " . $this->db->quote($path));
            if ($data["id"]) {
                $this->assignVariablesToModel($data);
            } else {
                throw new \Exception("document with path $path doesn't exist");
            }
        }
    }


    /**
     * @throws \Exception
     */
    public function create()
    {
        try {
            $this->db->insert("documents", array(
                "key" => $this->model->getKey(),
                "path" => $this->model->getRealPath(),
                "parentId" => $this->model->getParentId(),
                "index" => 0
            ));

            $date = time();
            $this->model->setId($this->db->lastInsertId());

            if (!$this->model->getKey()) {
                $this->model->setKey($this->model->getId());
            }
        } catch (\Exception $e) {
            throw $e;
        }

    }

    /**
     * @throws \Exception
     */
    public function update()
    {
        try {
            $this->model->setModificationDate(time());

            $document = get_object_vars($this->model);

            foreach ($document as $key => $value) {
                if (in_array($key, $this->validColumnsDocument)) {

                    // check if the getter exists
                    $getter = "get" . ucfirst($key);
                    if (!method_exists($this->model, $getter)) {
                        continue;
                    }

                    // get the value from the getter
                    $value = $this->model->$getter();

                    if (is_bool($value)) {
                        $value = (int)$value;
                    }
                    $data[$key] = $value;
                }
            }

            $this->db->insertOrUpdate("documents", $data);

            $this->updateLocks();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @throws \Exception
     */
    public function delete()
    {
        try {
            $this->db->delete("documents", $this->db->quoteInto("id = ?", $this->model->getId()));
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function updateWorkspaces() {
        $this->db->update("users_workspaces_document", array(
            "cpath" => $this->model->getRealFullPath()
        ), "cid = " . $this->model->getId());
    }

    public function updateChildsPaths($oldPath)
    {
        //get documents to empty their cache
        $documents = $this->db->fetchCol("SELECT id FROM documents WHERE path LIKE ?", $oldPath . "%");

        $userId = "0";
        if ($user = \Pimcore\Tool\Admin::getCurrentUser()) {
            $userId = $user->getId();
        }

        //update documents child paths
        $this->db->query("update documents set path = replace(path," . $this->db->quote($oldPath . "/") . "," . $this->db->quote($this->model->getRealFullPath() . "/") . "), modificationDate = '" . time() . "', userModification = '" . $userId . "' where path like " . $this->db->quote($oldPath . "/%") . ";");

        //update documents child permission paths
        $this->db->query("update users_workspaces_document set cpath = replace(cpath," . $this->db->quote($oldPath . "/") . "," . $this->db->quote($this->model->getRealFullPath() . "/") . ") where cpath like " . $this->db->quote($oldPath . "/%") . ";");

        //update documents child properties paths
        $this->db->query("update properties set cpath = replace(cpath," . $this->db->quote($oldPath . "/") . "," . $this->db->quote($this->model->getRealFullPath() . "/") . ") where cpath like " . $this->db->quote($oldPath . "/%") . ";");


        return $documents;
    }

    /**
     * @return string retrieves the current full document path from DB
     */
    public function getCurrentFullPath()
    {

        $path = null;
        try {
            $path = $this->db->fetchOne("SELECT CONCAT(path,`key`) as path FROM documents WHERE id = ?", $this->model->getId());
        } catch (\Exception $e) {
            \Logger::error("could not  get current document path from DB");

        }

        return $path;

    }

    /**
     * Get the properties for the object from database and assign it
     *
     * @return void
     */
    public function getProperties($onlyInherited = false, $onlyDirect = false)
    {

        $properties = array();

        if ($onlyDirect) {
            $propertiesRaw = $this->db->fetchAll("SELECT * FROM properties WHERE cid = ? AND ctype='document'", $this->model->getId());
        } else {
            $parentIds = $this->getParentIds();
            $propertiesRaw = $this->db->fetchAll("SELECT * FROM properties WHERE ((cid IN (" . implode(",", $parentIds) . ") AND inheritable = 1) OR cid = ? )  AND ctype='document'", $this->model->getId());
        }

        // because this should be faster than mysql
        usort($propertiesRaw, function ($left, $right) {
            return strcmp($left["cpath"], $right["cpath"]);
        });

        foreach ($propertiesRaw as $propertyRaw) {

            try {
                $property = new Model\Property();
                $property->setType($propertyRaw["type"]);
                $property->setCid($this->model->getId());
                $property->setName($propertyRaw["name"]);
                $property->setCtype("document");
                $property->setDataFromResource($propertyRaw["data"]);
                $property->setInherited(true);
                if ($propertyRaw["cid"] == $this->model->getId()) {
                    $property->setInherited(false);
                }
                $property->setInheritable(false);
                if ($propertyRaw["inheritable"]) {
                    $property->setInheritable(true);
                }

                if ($onlyInherited && !$property->getInherited()) {
                    continue;
                }

                $properties[$propertyRaw["name"]] = $property;
            } catch (\Exception $e) {
                \Logger::error("can't add property " . $propertyRaw["name"] . " to document " . $this->model->getRealFullPath());
            }
        }

        // if only inherited then only return it and dont call the setter in the model
        if ($onlyInherited || $onlyDirect) {
            return $properties;
        }

        $this->model->setProperties($properties);

        return $properties;
    }

    /**
     * deletes all properties for the object from database
     *
     * @return void
     */
    public function deleteAllProperties()
    {
        $this->db->delete("properties", $this->db->quoteInto("cid = ? AND ctype = 'document'", $this->model->getId()));
    }

    /**
     * @return void
     */
    public function deleteAllPermissions()
    {
        $this->db->delete("users_workspaces_document", $this->db->quoteInto("cid = ?", $this->model->getId()));
    }

    /**
     * @return void
     */
    public function deleteAllTasks()
    {
        $this->db->delete("schedule_tasks", $this->db->quoteInto("cid = ? AND ctype='document'", $this->model->getId()));
    }

    /**
     * Quick test if there are childs
     *
     * @return boolean
     */
    public function hasChilds()
    {
        $c = $this->db->fetchOne("SELECT id FROM documents WHERE parentId = ? LIMIT 1", $this->model->getId());
        return (bool)$c;
    }

    /**
     * returns the amount of directly childs (not recursivly)
     *
     * @param User $user
     * @return integer
     */
    public function getChildAmount($user = null)
    {
        if ($user and !$user->isAdmin()) {
            $userIds = $user->getRoles();
            $userIds[] = $user->getId();

            $query = "select count(*) from documents d where parentId = ?
                    and (select list as locate from users_workspaces_document where userId in (" . implode(',', $userIds) . ") and LOCATE(cpath,CONCAT(d.path,d.`key`))=1  ORDER BY LENGTH(cpath) DESC LIMIT 1)=1;";


        } else {
            $query = "SELECT COUNT(*) AS count FROM documents WHERE parentId = ?";
        }
        $c = $this->db->fetchOne($query, $this->model->getId());
        return $c;
    }

	/**
	 * Quick test if there are siblings
	 *
	 * @return boolean
	 */
	public function hasSiblings() {
		$c = $this->db->fetchOne("SELECT id FROM documents WHERE parentId = ? and id != ? LIMIT 1", [$this->model->getParentId(), $this->model->getId()]);
		return (bool)$c;
	}

    public function isLocked()
    {

        // check for an locked element below this element
        $belowLocks = $this->db->fetchOne("SELECT tree_locks.id FROM tree_locks
            INNER JOIN documents ON tree_locks.id = documents.id
                WHERE documents.path LIKE ? AND tree_locks.type = 'document' AND tree_locks.locked IS NOT NULL AND tree_locks.locked != '' LIMIT 1", $this->model->getFullpath() . "/%");

        if ($belowLocks > 0) {
            return true;
        }

        $parentIds = $this->getParentIds();
        $inhertitedLocks = $this->db->fetchOne("SELECT id FROM tree_locks WHERE id IN (" . implode(",", $parentIds) . ") AND type='document' AND locked = 'propagate' LIMIT 1");

        if ($inhertitedLocks > 0) {
            return true;
        }


        return false;
    }

    public function updateLocks()
    {
        // tree_locks
        $this->db->delete("tree_locks", "id = " . $this->model->getId() . " AND type = 'document'");
        if ($this->model->getLocked()) {
            $this->db->insert("tree_locks", array(
                "id" => $this->model->getId(),
                "type" => "document",
                "locked" => $this->model->getLocked()
            ));
        }
    }

    /**
     *
     */
    public function unlockPropagate() {
        $lockIds = $this->db->fetchCol("SELECT id from documents WHERE path LIKE " . $this->db->quote($this->model->getFullPath() . "/%") . " OR id = " . $this->model->getId());
        $this->db->delete("tree_locks", "type = 'document' AND id IN (" . implode(",", $lockIds) . ")");
        return $lockIds;
    }

    public function isAllowed($type, $user)
    {

        // collect properties via parent - ids
        $parentIds = array(1);

        $obj = $this->model->getParent();
        if ($obj) {
            while ($obj) {
                $parentIds[] = $obj->getId();
                $obj = $obj->getParent();
            }
        }
        $parentIds[] = $this->model->getId();

        $userIds = $user->getRoles();
        $userIds[] = $user->getId();

        try {
            $permissionsParent = $this->db->fetchOne("SELECT `" . $type . "` FROM users_workspaces_document WHERE cid IN (" . implode(",", $parentIds) . ") AND userId IN (" . implode(",", $userIds) . ") ORDER BY LENGTH(cpath) DESC, ABS(userId-" . $user->getId() . ") ASC LIMIT 1");

            if ($permissionsParent) {
                return true;
            }

            // exception for list permission
            if (empty($permissionsParent) && $type == "list") {
                // check for childs with permissions
                $path = $this->model->getFullPath() . "/";
                if ($this->model->getId() == 1) {
                    $path = "/";
                }

                $permissionsChilds = $this->db->fetchOne("SELECT list FROM users_workspaces_document WHERE cpath LIKE ? AND userId IN (" . implode(",", $userIds) . ") LIMIT 1", $path . "%");
                if ($permissionsChilds) {
                    return true;
                }
            }
        } catch (\Exception $e) {
            \Logger::warn("Unable to get permission " . $type . " for document " . $this->model->getId());
        }

        return false;
    }

    public function saveIndex($index)
    {
        $this->db->update("documents", array("index" => $index), $this->db->quoteInto("id = ?", $this->model->getId()));
    }

    public function getNextIndex()
    {
        $index = $this->db->fetchOne("SELECT MAX(`index`) FROM documents WHERE parentId = ?", array($this->model->getParentId()));
        $index++;
        return $index;
    }

}
