<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace OnlineShop\Framework\CartManager\Cart;

class Dao extends \Pimcore\Model\Dao\AbstractDao {

    const TABLE_NAME = "plugin_onlineshop_cart";

    /**
     * Contains all valid columns in the database table
     *
     * @var array
     */
    protected $validColumns = array();

    protected $fieldsToSave = array("name", "userid", "creationDateTimestamp");

    /**
     * Get the valid columns from the database
     *
     * @return void
     */
    public function init() {
        $this->validColumns = $this->getValidTableColumns(self::TABLE_NAME);
    }

    /**
     * @param int $id
     * @return void
     */
    public function getById($id) {

        $classRaw = $this->db->fetchRow("SELECT * FROM " . self::TABLE_NAME . " WHERE id=" . $this->db->quote($id));
        if(empty($classRaw)) {
            throw new \Exception("Cart " . $id . " not found.");
        }
        $this->assignVariablesToModel($classRaw);
    }


    /**
     * Create a new record for the object in database
     *
     * @return boolean
     */
    public function create() {
        $this->db->insert(self::TABLE_NAME, array());
        $this->model->setId($this->db->lastInsertId());

        $this->save();
    }

    /**
     * Save object to database
     *
     * @return void
     */
    public function save() {
        if ($this->model->getId()) {
            return $this->update();
        }
        return $this->create();
    }

    /**
     * @return void
     */
    public function update() {

        foreach ($this->fieldsToSave as $field) {
            if (in_array($field, $this->validColumns)) {
                $getter = "get" . ucfirst($field);
                $value = $this->model->$getter();

                if (is_array($value) || is_object($value)) {
                    $value = serialize($value);
                } else  if(is_bool($value)) {
                    $value = (int)$value;
                }
                $data[$field] = $value;
            }
        }

        $this->db->update(self::TABLE_NAME, $data, "id=" . $this->db->quote($this->model->getId()));
    }

    /**
     * Deletes object from database
     *
     * @return void
     */
    public function delete() {
        $this->db->delete(self::TABLE_NAME, "id=" . $this->db->quote($this->model->getId()));
    }

    /**
     * @param array $fields
     */
    public function setFieldsToSave(array $fields) {
        $this->fieldsToSave = $fields;
    }

}
