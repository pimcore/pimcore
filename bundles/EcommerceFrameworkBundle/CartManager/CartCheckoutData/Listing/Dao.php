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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartCheckoutData\Listing;

class Dao extends \Pimcore\Model\Listing\Dao\AbstractDao
{
    /**
     * @return array
     */
    public function load()
    {
        $items = [];

        $cartCheckoutDataItems = $this->db->fetchAll('SELECT cartid, `key` FROM ' . \Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartCheckoutData\Dao::TABLE_NAME .
                                                 $this->getCondition() . $this->getOrder() . $this->getOffsetLimit());

        foreach ($cartCheckoutDataItems as $item) {
            $items[] = \Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartCheckoutData::getByKeyCartId($item['key'], $item['cartid']);
        }
        $this->model->setCartCheckoutDataItems($items);

        return $items;
    }

    public function getTotalCount()
    {
        try {
            return (int)$this->db->fetchOne('SELECT COUNT(*) FROM `' . \Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartCheckoutData\Dao::TABLE_NAME . '`' . $this->getCondition());
        } catch (\Exception $e) {
            return 0;
        }
    }
}
