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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartCheckoutData;

class Dao extends \Pimcore\Model\Dao\AbstractDao
{
    const TABLE_NAME = 'ecommerceframework_cartcheckoutdata';

    /**
     * Contains all valid columns in the database table
     *
     * @var array
     */
    protected $validColumns = [];

    protected $fieldsToSave = ['cartId', 'key', 'data'];

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
     * @throws \Exception
     *
     * @param  string $key
     * @param  int $cartId
     *
     * @return void
     */
    public function getByKeyCartId($key, $cartId)
    {
        $classRaw = $this->db->fetchRow('SELECT * FROM ' . self::TABLE_NAME . ' WHERE `key`=' . $this->db->quote($key). ' AND cartId = ' . $this->db->quote($cartId));
        if (empty($classRaw)) {
            throw new \Exception('CartItem for cartid ' . $cartId . ' and key ' . $key . ' not found.');
        }
        $this->assignVariablesToModel($classRaw);
    }

    /**
     * Save object to database
     */
    public function save()
    {
        return $this->update();
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

        try {
            $this->db->insert(self::TABLE_NAME, $data);
        } catch (\Exception $e) {
            $this->db->updateWhere(self::TABLE_NAME, $data, '`key`=' . $this->db->quote($this->model->getKey()). ' AND cartId = ' . $this->db->quote($this->model->getCartId()));
        }
    }

    /**
     * Deletes object from database
     *
     * @return void
     */
    public function delete()
    {
        $this->db->deleteWhere(self::TABLE_NAME, 'productId=' . $this->db->quote($this->model->productId). ' AND cartId = ' . $this->db->quote($this->model->cartId));
    }

    public function removeAllFromCart($cartId)
    {
        $this->db->deleteWhere(self::TABLE_NAME, 'cartId = ' . $this->db->quote($cartId));
    }
}
