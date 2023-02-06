<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartCheckoutData;

use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartCheckoutData;
use Pimcore\Db\Helper;
use Pimcore\Model\Exception\NotFoundException;

/**
 * @internal
 *
 * @property CartCheckoutData $model
 */
class Dao extends \Pimcore\Model\Dao\AbstractDao
{
    const TABLE_NAME = 'ecommerceframework_cartcheckoutdata';

    /**
     * Contains all valid columns in the database table
     *
     * @var array
     */
    protected array $validColumns = [];

    protected array $fieldsToSave = ['cartId', 'key', 'data'];

    /**
     * Get the valid columns from the database
     *
     * @return void
     */
    public function init(): void
    {
        $this->validColumns = $this->getValidTableColumns(self::TABLE_NAME);
    }

    /**
     * @param string $key
     * @param int|string $cartId
     *
     * @throws NotFoundException
     */
    public function getByKeyCartId(string $key, int|string $cartId): void
    {
        $classRaw = $this->db->fetchAssociative('SELECT * FROM ' . self::TABLE_NAME . ' WHERE `key`=' . $this->db->quote($key). ' AND cartId = ' . $this->db->quote($cartId));
        if (empty($classRaw)) {
            throw new NotFoundException('CartItem for cartid ' . $cartId . ' and key ' . $key . ' not found.');
        }
        $this->assignVariablesToModel($classRaw);
    }

    /**
     * Save object to database
     */
    public function save(): void
    {
        $this->update();
    }

    public function update(): void
    {
        $data = [];
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
            $this->db->insert(self::TABLE_NAME, Helper::quoteDataIdentifiers($this->db, $data));
        } catch (\Exception $e) {
            $this->db->update(self::TABLE_NAME, Helper::quoteDataIdentifiers($this->db, $data), ['key' => $this->db->quote($this->model->getKey()), 'cartId' => $this->db->quote($this->model->getCartId())]);
        }
    }

    /**
     * Deletes object from database
     *
     * @return void
     */
    public function delete(): void
    {
        $this->db->delete(self::TABLE_NAME, ['key' => $this->model->getKey(), 'cartId' => $this->model->getCartId()]);
    }

    public function removeAllFromCart(int|string $cartId): void
    {
        $this->db->delete(self::TABLE_NAME, ['cartId' => $cartId]);
    }
}
