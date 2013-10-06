<?php

class OnlineShop_Framework_Impl_CartCheckoutData extends OnlineShop_Framework_AbstractCartCheckoutData {

    public function save() {
        $this->getResource()->save();
    }

    public static function getByKeyCartId($key, $cartId) {
        $cacheKey = OnlineShop_Framework_Impl_CartCheckoutData_Resource::TABLE_NAME . "_" . $key . "_" . $cartId;

        try {
            $checkoutDataItem = Zend_Registry::get($cacheKey);
        }
        catch (Exception $e) {

            try {
                $checkoutDataItem = new self();
                $checkoutDataItem->getResource()->getByKeyCartId($key, $cartId);
                Zend_Registry::set($cacheKey, $checkoutDataItem);
            } catch(Exception $ex) {
                Logger::debug($ex->getMessage());
                return null;
            }

        }

        return $checkoutDataItem;
    }

    public static function removeAllFromCart($cartId) {
        $checkoutDataItem = new self();
        $checkoutDataItem->getResource()->removeAllFromCart($cartId);
    }

}
