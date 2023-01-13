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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartItem\Listing;

/**
 * @internal
 *
 * @property \Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartItem\Listing $model
 */
class Dao extends \Pimcore\Model\Listing\Dao\AbstractDao
{
    protected string $className = '\Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartItem';

    public function load(): array
    {
        $items = [];
        $cartItems = $this->db->fetchAllAssociative('SELECT cartid, itemKey, parentItemKey FROM ' . \Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartItem\Dao::TABLE_NAME .
                                                 $this->getCondition() . $this->getOrder() . $this->getOffsetLimit());

        foreach ($cartItems as $item) {
            $items[] = call_user_func([$this->getClassName(), 'getByCartIdItemKey'], $item['cartid'], $item['itemKey'], $item['parentItemKey']);
        }
        $this->model->setCartItems($items);

        return $items;
    }

    public function getTotalCount(): int
    {
        try {
            return (int)$this->db->fetchOne('SELECT COUNT(*) FROM `' . \Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartItem\Dao::TABLE_NAME . '`' . $this->getCondition());
        } catch (\Exception $e) {
            return 0;
        }
    }

    public function getTotalAmount(): int
    {
        return (int)$this->db->fetchOne('SELECT SUM(count) FROM `' . \Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartItem\Dao::TABLE_NAME . '`' . $this->getCondition());
    }

    public function setClassName(string $className): void
    {
        $this->className = $className;
    }

    public function getClassName(): string
    {
        return $this->className;
    }
}
