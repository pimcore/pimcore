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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartItem;

use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartItem;
use Pimcore\Model\Exception\NotFoundException;

/**
 * @internal
 *
 * @property CartItem $model
 */
class Dao extends \Pimcore\Model\Dao\AbstractDao
{
    const TABLE_NAME = 'ecommerceframework_cartitem';

    /**
     * Contains all valid columns in the database table
     *
     * @var array
     */
    protected array $validColumns = [];

    protected array $fieldsToSave = ['cartId', 'productId', 'count', 'itemKey', 'parentItemKey', 'comment', 'addedDateTimestamp', 'sortIndex'];

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
     * @param int|string $cartId
     * @param string $itemKey
     * @param string $parentKey
     *
     * @throws NotFoundException
     */
    public function getByCartIdItemKey(int|string $cartId, string $itemKey, string $parentKey = ''): void
    {
        $classRaw = $this->db->fetchAssociative('SELECT * FROM ' . self::TABLE_NAME . ' WHERE itemKey=' . $this->db->quote($itemKey). ' AND cartId = ' . $this->db->quote($cartId) . ' AND parentItemKey = ' . $this->db->quote($parentKey));
        if (empty($classRaw)) {
            throw new NotFoundException('CartItem for cartId ' . $cartId . ' and itemKey ' . $itemKey . ' not found.');
        }

        $this->model->setIsLoading(true);

        $this->assignVariablesToModel($classRaw);

        $this->model->setIsLoading(false);
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
            $this->db->insert(self::TABLE_NAME, $data);
        } catch (\Exception $e) {
            $this->db->update(self::TABLE_NAME, $data, ['itemKey' => $this->model->getItemKey(), 'cartId' => $this->model->getCartId(),  'parentItemKey' => $this->model->getParentItemKey()]);
        }
    }

    /**
     * Deletes object from database
     *
     * @return void
     */
    public function delete(): void
    {
        $this->db->delete(self::TABLE_NAME, ['itemKey' => $this->model->getItemKey(), 'cartId' => $this->model->getCartId(), 'parentItemKey' => $this->model->getParentItemKey()]);
    }

    public function removeAllFromCart(int|string $cartId): void
    {
        $this->db->delete(self::TABLE_NAME, ['cartId' => $cartId]);
    }
}
