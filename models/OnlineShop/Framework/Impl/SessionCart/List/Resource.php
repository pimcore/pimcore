<?php

class OnlineShop_Framework_Impl_SessionCart_List_Resource extends Pimcore_Model_List_Resource_Abstract {

    /**
     * @return array
     */
    public function load() {
        $carts = new Zend_Session_Namespace('carts');
        foreach ($carts as $cart){
            $cartList[] = OnlineShop_Framework_Impl_SessionCart::getById($cart->id);
        }
        $this->model->setCarts($cartList);
        return $cartList;
    }

    public function getTotalCount() {
        $carts = new Zend_Session_Namespace('carts');
        foreach ($carts as $cart){
            $cartList[] = $cart;
        }
        return count($cartList);
    }

}