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
 * @package    Object
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Object\Classificationstore\GroupConfig;

use Pimcore\Model;

/**
 * @property \Pimcore\Model\Object\Classificationstore\GroupConfig $model
 */
class Dao extends Model\Dao\AbstractDao
{
    use Model\Element\ChildsCompatibilityTrait;

    const TABLE_NAME_GROUPS = "classificationstore_groups";

    /**
     * Get the data for the object from database for the given id, or from the ID which is set in the object
     *
     * @param integer $id
     * @return void
     */
    public function getById($id = null)
    {
        if ($id != null) {
            $this->model->setId($id);
        }

        $data = $this->db->fetchRow("SELECT * FROM " . self::TABLE_NAME_GROUPS . " WHERE id = ?", $this->model->getId());

        if ($data) {
            $this->assignVariablesToModel($data);
        } else {
            throw new \Exception("GroupConfig with id: " . $this->model->getId() . " does not exist");
        }
    }

    /**
     * @param null $name
     * @throws \Exception
     */
    public function getByName($name = null)
    {
        if ($name != null) {
            $this->model->setName($name);
        }

        $name = $this->model->getName();
        $storeId = $this->model->getStoreId();

        $data = $this->db->fetchRow("SELECT * FROM " . self::TABLE_NAME_GROUPS . " WHERE name = ? and storeId = ?", [$name, $storeId]);

        if ($data["id"]) {
            $this->assignVariablesToModel($data);
        } else {
            throw new \Exception("Config with name: " . $this->model->getName() . " does not exist");
        }
    }

    /**
     * @return int
     *
     * @todo: $amount could not be defined, so this could cause an issue
     */
    public function hasChildren()
    {
        try {
            $amount = (int) $this->db->fetchOne("SELECT COUNT(*) as amount FROM " . self::TABLE_NAME_GROUPS . " where parentId= " . $this->model->id);
        } catch (\Exception $e) {
        }

        return $amount;
    }

    /**
     * Save object to database
     *
     * @return bool
     */
    public function save()
    {
        if ($this->model->getId()) {
            return $this->model->update();
        }

        return $this->create();
    }

    /**
     * Deletes object from database
     *
     * @return void
     */
    public function delete()
    {
        $this->db->delete(self::TABLE_NAME_GROUPS, $this->db->quoteInto("id = ?", $this->model->getId()));
    }

    /**
     * @throws \Exception
     */
    public function update()
    {
        try {
            $ts = time();
            $this->model->setModificationDate($ts);

            $type = get_object_vars($this->model);

            foreach ($type as $key => $value) {
                if (in_array($key, $this->getValidTableColumns(self::TABLE_NAME_GROUPS))) {
                    if (is_bool($value)) {
                        $value = (int) $value;
                    }
                    if (is_array($value) || is_object($value)) {
                        $value = \Pimcore\Tool\Serialize::serialize($value);
                    }

                    $data[$key] = $value;
                }
            }

            $this->db->update(self::TABLE_NAME_GROUPS, $data, $this->db->quoteInto("id = ?", $this->model->getId()));

            return $this->model;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Create a new record for the object in database
     *
     * @return boolean
     */
    public function create()
    {
        $ts = time();
        $this->model->setModificationDate($ts);
        $this->model->setCreationDate($ts);

        $this->db->insert(self::TABLE_NAME_GROUPS, []);

        $this->model->setId($this->db->lastInsertId());

        return $this->save();
    }
}
