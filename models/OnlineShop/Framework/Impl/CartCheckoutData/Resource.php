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


class OnlineShop_Framework_Impl_CartCheckoutData_Resource extends \Pimcore\Model\Resource\AbstractResource {

    const TABLE_NAME = "plugin_onlineshop_cartcheckoutdata";

    /**
     * Contains all valid columns in the database table
     *
     * @var array
     */
    protected $validColumns = array();

    protected $fieldsToSave = array("cartId", "key", "data");

    /**
     * Get the valid columns from the database
     *
     * @return void
     */
    public function init() {
        $this->validColumns = $this->getValidTableColumns(self::TABLE_NAME);
    }

    /**
     * @throws Exception
     * @param  string $key
     * @param  int $cartId
     * @return void
     */
    public function getByKeyCartId($key, $cartId) {
        $classRaw = $this->db->fetchRow("SELECT * FROM " . self::TABLE_NAME . " WHERE `key`=" . $this->db->quote($key). " AND cartId = " . $this->db->quote($cartId));
        if(empty($classRaw)) {
            throw new Exception("CartItem for cartid " . $cartId . " and key " . $key . " not found.");
        }
        $this->assignVariablesToModel($classRaw);
    }

    /**
     * Save object to database
     *
     * @return void
     */
    public function save() {
        return $this->update();
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

        try {
            $this->db->insert(self::TABLE_NAME, $data);
        } catch(Exception $e) {
            $this->db->update(self::TABLE_NAME, $data,  "`key`=" . $this->db->quote($this->model->getKey()). " AND cartId = " . $this->db->quote($this->model->getCartId()));
        }
    }

    /**
     * Deletes object from database
     *
     * @return void
     */
    public function delete() {
        $this->db->delete(self::TABLE_NAME, "productId=" . $this->db->quote($this->model->productId). " AND cartId = " . $this->db->quote($this->model->cartId));
    }

    public function removeAllFromCart($cartId) {
        $this->db->delete(self::TABLE_NAME, "cartId = " . $this->db->quote($cartId));
    }

}
