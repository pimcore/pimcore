<?php

class OnlineShop_Framework_Impl_Cart extends OnlineShop_Framework_AbstractCart implements OnlineShop_Framework_ICart {

    /**
     * @return string
     */
    protected function getCartItemClassName() {
        return "OnlineShop_Framework_Impl_CartItem";
    }

    /**
     * @return string
     */
    protected function getCartCheckoutDataClassName() {
        return "OnlineShop_Framework_Impl_CartCheckoutData";
    }

    public function save() {
        $this->getResource()->save();
        OnlineShop_Framework_Impl_CartItem::removeAllFromCart($this->getId());
        foreach ($this->items as $item) {
            $item->save();
        }

        OnlineShop_Framework_Impl_CartCheckoutData::removeAllFromCart($this->getId());
        foreach ($this->checkoutData as $data) {
            $data->save();
        }
    }

    /**
     * @return void
     */
    public function delete() {
        $cacheKey = OnlineShop_Framework_Impl_Cart_Resource::TABLE_NAME . "_" . $this->getId();
        Zend_Registry::set($cacheKey, null);

        OnlineShop_Framework_Impl_CartItem::removeAllFromCart($this->getId());
        OnlineShop_Framework_Impl_CartCheckoutData::removeAllFromCart($this->getId());

        $this->clear();
        $this->getResource()->delete();
    }



    /**
     * @param int $id
     * @return OnlineShop_Framework_Impl_Cart
     */
    public static function getById($id) {
        $cacheKey = OnlineShop_Framework_Impl_Cart_Resource::TABLE_NAME . "_" . $id;
        try {
            $cart = Zend_Registry::get($cacheKey);
        }
        catch (Exception $e) {

            try {
                $cartClass = get_called_class();
                $cart = new $cartClass;
                $cart->setIgnoreReadonly();
                $cart->getResource()->getById($id);

                $itemList = new OnlineShop_Framework_Impl_CartItem_List();
                $itemList->setCartItemClassName( $cart->getCartItemClassName() );
                $db = Pimcore_Resource::get();
                $itemList->setCondition("cartId = " . $db->quote($cart->getId()) . " AND parentItemKey = ''");
                $items = array();
                foreach ($itemList->getCartItems() as $item) {
                    if ($item->getProduct() != null) {
                        $items[$item->getItemKey()] = $item;
                    }else {
                        Logger::warn("product " . $item->getProductId() . " not found");
                    }
                }
                $cart->setItems($items);

                $dataList = new OnlineShop_Framework_Impl_CartCheckoutData_List();
                $dataList->setCondition("cartId = " . $db->quote($cart->getId()));


                foreach ($dataList->getCartCheckoutDataItems() as $data) {
                    $cart->setCheckoutData($data->getKey(), $data->getData());
                }

                $cart->unsetIgnoreReadonly();

                Zend_Registry::set($cacheKey, $cart);
            } catch (Exception $ex) {
                Logger::debug($ex->getMessage());
                return null;
            }

        }

        return $cart;
    }

    /**
     * @static
     * @param int $userId
     * @return array
     */
    public static function getAllCartsForUser($userId) {
        $list = new OnlineShop_Framework_Impl_Cart_List();
        $db = Pimcore_Resource::get();
        $list->setCondition("userid = " . $db->quote($userId));
        $list->setCartClass( get_called_class() );
        return $list->getCarts();
    }





}
