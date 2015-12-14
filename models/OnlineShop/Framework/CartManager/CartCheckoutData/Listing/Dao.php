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

namespace OnlineShop\Framework\CartManager\CartCheckoutData\Listing;

class Dao extends \Pimcore\Model\Listing\Dao\AbstractDao {

    /**
     * @return array
     */
    public function load() {
        $items = array();

        $cartCheckoutDataItems = $this->db->fetchAll("SELECT cartid, `key` FROM " . \OnlineShop\Framework\CartManager\CartCheckoutData\Dao::TABLE_NAME .
                                                 $this->getCondition() . $this->getOrder() . $this->getOffsetLimit());

        foreach ($cartCheckoutDataItems as $item) {
            $items[] = \OnlineShop\Framework\CartManager\CartCheckoutData::getByKeyCartId($item['key'], $item['cartid']);
        }
        $this->model->setCartCheckoutDataItems($items);

        return $items;
    }

    public function getTotalCount() {
        $amount = $this->db->fetchRow("SELECT COUNT(*) as amount FROM `" . \OnlineShop\Framework\CartManager\CartCheckoutData\Dao::TABLE_NAME . "`" . $this->getCondition());
        return $amount["amount"];
    }

}