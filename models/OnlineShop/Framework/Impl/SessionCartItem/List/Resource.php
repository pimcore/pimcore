<?php

class OnlineShop_Framework_Impl_SessionCartItem_List_Resource extends Pimcore_Model_List_Resource_Abstract {

    /**
     * @return array
     */
    public function load() {
        $allCartItems = new Zend_Session_Namespace('cartItems');
        $conditions = explode('||', str_replace(' WHERE ', '', $this->getCondition()));
        $cartItems = array();
        $items = array();
        foreach ($allCartItems as $cartItem) {
            $hit = null;
            foreach ($conditions as $condition){
                $expandedCondition = explode('=', $condition);
                $value = $expandedCondition[1];
                $key = (string) $expandedCondition[0];
                if ($cartItem->$key == $value AND ($hit === null OR $hit === true)) {
                    $hit = true;
                } else {
                    break;
                }
            }
            if ($hit === true) {
                $cartItems[] = $cartItem;
            }
        }

        foreach ($cartItems as $item) {
            $items[] = OnlineShop_Framework_Impl_SessionCartItem::getByCartIdItemKey($item->cartid, $item->itemKey, $item->parentItemKey);
        }
        $this->model->setCartItems($items);
        return $items;
    }

    public function getTotalCount() {
        $cartItems = new Zend_Session_Namespace('cartItems');
        foreach ($cartItems as $item){
            $cartItemList[] = $item;
        }
        return count($cartItemList);
    }

}