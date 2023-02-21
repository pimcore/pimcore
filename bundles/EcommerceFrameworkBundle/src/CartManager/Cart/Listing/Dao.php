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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\Cart\Listing;

/**
 * @internal
 *
 * @property \Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\Cart\Listing $model
 */
class Dao extends \Pimcore\Model\Listing\Dao\AbstractDao
{
    protected string $cartClass = '\Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\Cart';

    public function load(): array
    {
        $carts = [];
        $cartIds = $this->db->fetchFirstColumn('SELECT id FROM ' . \Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\Cart\Dao::TABLE_NAME .
                                                 $this->getCondition() . $this->getOrder() . $this->getOffsetLimit());

        foreach ($cartIds as $id) {
            $carts[] = call_user_func([$this->getCartClass(), 'getById'], $id);
        }

        $this->model->setCarts($carts);

        return $carts;
    }

    public function getTotalCount(): int
    {
        try {
            return (int) $this->db->fetchOne('SELECT COUNT(*) FROM `' . \Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\Cart\Dao::TABLE_NAME . '`' . $this->getCondition());
        } catch (\Exception $e) {
            return 0;
        }
    }

    public function setCartClass(string $cartClass): void
    {
        $this->cartClass = $cartClass;
    }

    public function getCartClass(): string
    {
        return $this->cartClass;
    }
}
