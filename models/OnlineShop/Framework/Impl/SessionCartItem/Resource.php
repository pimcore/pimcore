<?php

class OnlineShop_Framework_Impl_SessionCartItem_Resource extends Pimcore_Model_Resource_Abstract {

    protected $fieldsToSave = array("cartId", "productId", "count", "itemKey", "parentItemKey", "comment");

    /**
     * @param int $productId
     * @param int $cartId
     * @return void
     */
    public function getByCartIdItemKey($cartId, $itemKey, $parentKey = "") {
        $allCartItems = new Zend_Session_Namespace('cartItems');
        $conditions = array(
            'itemKey' => $itemKey,
            'cartId' => $cartId,
            'parentItemKey' => $parentKey
        );
        $foundItem = null;
        foreach ($allCartItems as $cartItem) {
            $hit = null;
            foreach ($conditions as $condition => $value){
                if ($cartItem->$condition == $value AND ($hit === null OR $hit === true)) {
                    $hit = true;
                } else {
                    break;
                }
            }
            if ($hit === true) {
                $foundItem = $cartItem;
            }
        }

        if ($foundItem == null) {
            throw new Exception("CartItem for cartId " . $cartId . " and itemKey " . $itemKey . " not found.");
        }
        $this->assignVariablesToModel($foundItem);
    }

    /**
     * Save object to database
     *
     * @return void
     */
    public function save() {
        return $this->update();
    }

    /**
     * @return void
     */
    //TODO why after updating existing item, some fields are converted to string?
    public function update() {
        $cartsSystem = new Zend_Session_Namespace('cartsSystem');
        if (!isset($cartsSystem->lastItemId)) {
            $cartsSystem->lastItemId = 1;
        } else {
            $cartsSystem->lastItemId++;
        }

        $lastItemId = 'item' .  $cartsSystem->lastItemId;

        $cartItems = new Zend_Session_Namespace('cartItems');
        if(empty($cartItems->$lastItemId)) {
            $cartItems->$lastItemId = new stdClass();
        }
        foreach ($this->fieldsToSave as $field) {
            $getter = "get" . ucfirst($field);
            $value = $this->model->$getter();

            if (is_array($value) || is_object($value)) {
                $value = serialize($value);
            } else  if(is_bool($value)) {
                $value = (int)$value;
            }
            $cartItems->$lastItemId->$field = $value;
        }
    }

    /**
     * Deletes object from database
     *
     * @return void
     */
    public function delete() {
        $cacheKey = "cartItem_" . $this->model->getCartId() . "_" . $this->model->getParentItemKey() . $this->model->getItemKey();
        Zend_Registry::set($cacheKey, null);
    }

    public function removeAllFromCart($cartId) {
        $items = new Zend_Session_Namespace('cartItems');
        foreach ($items as $index=> $item) {
            if ($item->cartId == $cartId) {
                unset($items->$index);
            }
        };
    }

}
