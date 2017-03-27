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


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CartManager\CartItem\Listing;

class Dao extends \Pimcore\Model\Listing\Dao\AbstractDao {

    /**
     * @var string
     */
    protected $className = '\Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CartManager\CartItem';

    /**
     * @return array
     */
    public function load() {
        $items = array();
        $cartItems = $this->db->fetchAll("SELECT cartid, itemKey, parentItemKey FROM " . \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CartManager\CartItem\Dao::TABLE_NAME .
                                                 $this->getCondition() . $this->getOrder() . $this->getOffsetLimit());

        foreach ($cartItems as $item) {
            $items[] = call_user_func(array($this->getClassName(), 'getByCartIdItemKey'), $item['cartid'], $item['itemKey'], $item['parentItemKey']);
        }
        $this->model->setCartItems($items);

        return $items;
    }

    public function getTotalCount() {
        $amount = $this->db->fetchRow("SELECT COUNT(*) as amount FROM `" . \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CartManager\CartItem\Dao::TABLE_NAME . "`" . $this->getCondition());
        return $amount["amount"];
    }

    public function getTotalAmount()
    {
        return (int)$this->db->fetchOne("SELECT SUM(count) FROM `" . \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CartManager\CartItem\Dao::TABLE_NAME . "`" . $this->getCondition());
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
