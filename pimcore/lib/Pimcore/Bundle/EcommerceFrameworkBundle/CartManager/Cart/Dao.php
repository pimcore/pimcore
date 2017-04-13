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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\Cart;

class Dao extends \Pimcore\Model\Dao\AbstractDao
{
    const TABLE_NAME = 'ecommerceframework_cart';

    /**
     * Contains all valid columns in the database table
     *
     * @var array
     */
    protected $validColumns = [];

    protected $fieldsToSave = ['name', 'userid', 'creationDateTimestamp'];

    /**
     * Get the valid columns from the database
     *
     * @return void
     */
    public function init()
    {
        $this->validColumns = $this->getValidTableColumns(self::TABLE_NAME);
    }

    /**
     * @param int $id
     *
     * @return void
     */
    public function getById($id)
    {
        $classRaw = $this->db->fetchRow('SELECT * FROM ' . self::TABLE_NAME . ' WHERE id=' . $this->db->quote($id));
        if (empty($classRaw)) {
            throw new \Exception('Cart ' . $id . ' not found.');
        }
        $this->assignVariablesToModel($classRaw);
    }

    /**
     * Create a new record for the object in database
     *
     * @return bool
     */
    public function create()
    {
        $this->db->insert(self::TABLE_NAME, []);
        $this->model->setId($this->db->lastInsertId());

        $this->save();
    }

    /**
     * Save object to database
     *
     * @return void
     */
    public function save()
    {
        if ($this->model->getId()) {
            return $this->update();
        }

        return $this->create();
    }

    /**
     * @return void
     */
    public function update()
    {
        foreach ($this->fieldsToSave as $field) {
            if (in_array($field, $this->validColumns)) {
                $getter = 'get' . ucfirst($field);
                $value = $this->model->$getter();

                if (is_array($value) || is_object($value)) {
                    $value = serialize($value);
                } elseif (is_bool($value)) {
                    $value = (int)$value;
                }
                $data[$field] = $value;
            }
        }

        $this->db->updateWhere(self::TABLE_NAME, $data, 'id=' . $this->db->quote($this->model->getId()));
    }

    /**
     * Deletes object from database
     *
     * @return void
     */
    public function delete()
    {
        $this->db->deleteWhere(self::TABLE_NAME, 'id=' . $this->db->quote($this->model->getId()));
    }

    /**
     * @param array $fields
     */
    public function setFieldsToSave(array $fields)
    {
        $this->fieldsToSave = $fields;
    }
}
