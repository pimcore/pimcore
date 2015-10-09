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


class OnlineShop_Framework_Impl_CartItem_List_Resource extends \Pimcore\Model\Listing\Resource\AbstractResource {

    /**
     * @var string
     */
    protected $className = 'OnlineShop_Framework_Impl_CartItem';

    /**
     * @return array
     */
    public function load() {
        $items = array();
        $cartItems = $this->db->fetchAll("SELECT cartid, itemKey, parentItemKey FROM " . OnlineShop_Framework_Impl_CartItem_Resource::TABLE_NAME .
                                                 $this->getCondition() . $this->getOrder() . $this->getOffsetLimit());

        foreach ($cartItems as $item) {
            $items[] = call_user_func(array($this->getClassName(), 'getByCartIdItemKey'), $item['cartid'], $item['itemKey'], $item['parentItemKey']);
        }
        $this->model->setCartItems($items);

        return $items;
    }

    public function getTotalCount() {
        $amount = $this->db->fetchRow("SELECT COUNT(*) as amount FROM `" . OnlineShop_Framework_Impl_CartItem_Resource::TABLE_NAME . "`" . $this->getCondition());
        return $amount["amount"];
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