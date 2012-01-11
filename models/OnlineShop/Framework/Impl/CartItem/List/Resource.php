<?php

class OnlineShop_Framework_Impl_CartItem_List_Resource extends Pimcore_Model_List_Resource_Abstract {

    /**
     * @return array
     */
    public function load() {
        $items = array();
        $cartItems = $this->db->fetchAll("SELECT cartid, itemKey, parentItemKey FROM " . OnlineShop_Framework_Impl_CartItem_Resource::TABLE_NAME .
                                                 $this->getCondition() . $this->getOrder() . $this->getOffsetLimit());

        foreach ($cartItems as $item) {
            $items[] = OnlineShop_Framework_Impl_CartItem::getByCartIdItemKey($item['cartid'], $item['itemKey'], $item['parentItemKey']);
        }
        $this->model->setCartItems($items);

        return $items;
    }

    public function getTotalCount() {
        $amount = $this->db->fetchRow("SELECT COUNT(*) as amount FROM `" . OnlineShop_Framework_Impl_CartItem_Resource::TABLE_NAME . "`" . $this->getCondition());
        return $amount["amount"];
    }

}