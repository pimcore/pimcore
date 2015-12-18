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

namespace OnlineShop\Framework\CartManager\CartItem\Listing;

class Dao extends \Pimcore\Model\Listing\Dao\AbstractDao {

    /**
     * @var string
     */
    protected $className = '\OnlineShop\Framework\CartManager\CartItem';

    /**
     * @return array
     */
    public function load() {
        $items = array();
        $cartItems = $this->db->fetchAll("SELECT cartid, itemKey, parentItemKey FROM " . \OnlineShop\Framework\CartManager\CartItem\Dao::TABLE_NAME .
                                                 $this->getCondition() . $this->getOrder() . $this->getOffsetLimit());

        foreach ($cartItems as $item) {
            $items[] = call_user_func(array($this->getClassName(), 'getByCartIdItemKey'), $item['cartid'], $item['itemKey'], $item['parentItemKey']);
        }
        $this->model->setCartItems($items);

        return $items;
    }

    public function getTotalCount() {
        $amount = $this->db->fetchRow("SELECT COUNT(*) as amount FROM `" . \OnlineShop\Framework\CartManager\CartItem\Dao::TABLE_NAME . "`" . $this->getCondition());
        return $amount["amount"];
    }

    public function getAmountSum() {
        $amount = $this->db->fetchRow("SELECT SUM(`count`) as amountSum FROM `" . \OnlineShop\Framework\CartManager\CartItem\Dao::TABLE_NAME . "`" . $this->getCondition());
        return $amount["amountSum"];
    }


    /**
     * @param string $className
     */
    public function setClassName($className)
    {
        $this->className = $className;
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
    }
}