<?php

class OnlineShop_Framework_Impl_CartItem extends OnlineShop_Framework_AbstractCartItem implements OnlineShop_Framework_ICartItem {



    public function getCart() {
        if (empty($this->cart)) {
            $this->cart = OnlineShop_Framework_Impl_Cart::getById($this->cartId);
        }
        return $this->cart;
    }




    public function save() {
        $items = $this->getSubItems();
        if (!empty($this->subItems)) {
            foreach ($this->subItems as $item) {
                $item->save();
            }
        }
        $this->getResource()->save();
    }

    public static function getByCartIdItemKey($cartId, $itemKey, $parentKey = "") {
        $cacheKey = OnlineShop_Framework_Impl_CartItem_Resource::TABLE_NAME . "_" . $cartId . "_" . $parentKey . $itemKey;

        try {
            $cartItem = Zend_Registry::get($cacheKey);
        }
        catch (Exception $e) {
            try {
                $cartItem = new static();
                $cartItem->getResource()->getByCartIdItemKey($cartId, $itemKey, $parentKey);
                $cartItem->getSubItems();
                Zend_Registry::set($cacheKey, $cartItem);
            } catch (Exception $ex) {
                Logger::debug($ex->getMessage());
                return null;
            }

        }

        return $cartItem;
    }

    public static function removeAllFromCart($cartId) {
        $cartItem = new self();
        $cartItem->getResource()->removeAllFromCart($cartId);
    }

    /**
     * @return OnlineShop_Framework_ICartItem[]
     */
    public function getSubItems() {

        if ($this->subItems == null) {
            $this->subItems = array();

            $itemList = new OnlineShop_Framework_Impl_CartItem_List();
            $db = Pimcore_Resource::get();
            $itemList->setCondition("cartId = " . $db->quote($this->getCartId()) . " AND parentItemKey = " . $db->quote($this->getItemKey()));

            foreach ($itemList->getCartItems() as $item) {
                if ($item->getProduct() != null) {
                    $this->subItems[] = $item;
                } else {
                    Logger::warn("product " . $item->getProductId() . " not found");
                }
            }
        }
        return $this->subItems;
    }



}
