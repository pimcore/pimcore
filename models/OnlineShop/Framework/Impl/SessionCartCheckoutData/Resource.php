<?php

class OnlineShop_Framework_Impl_SessionCartCheckoutData_Resource extends Pimcore_Model_Resource_Abstract {

    protected $fieldsToSave = array("cartId", "key", "data");

    /**
     * @throws Exception
     * @param  string $key
     * @param  int $cartId
     * @return void
     */
    public function getByKeyCartId($key, $cartId) {
        $allCheckoutData = new Zend_Session_Namespace('checkoutData');
        $conditions = array(
            'key' => $key,
            'cartId' => $cartId,
        );
        $foundItem = null;
        foreach ($allCheckoutData as $checkoutData) {
            $hit = null;
            foreach ($conditions as $condition => $value){
                if ($checkoutData->$condition == $value AND ($hit === null OR $hit === true)) {
                    $hit = true;
                } else {
                    break;
                }
            }
            if ($hit === true) {
                $foundItem = $checkoutData;
            }
        }

        if ($foundItem == null) {
            throw new Exception("CartItem for cartId " . $cartId . " and itemKey " . $key . " not found.");
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
    public function update() {
        $cartsSystem = new Zend_Session_Namespace('cartsSystem');
        if (!isset($cartsSystem->lastCheckoutId)) {
            $cartsSystem->lastCheckoutId = 1;
        } else {
            $cartsSystem->lastCheckoutId++;
        }

        $lastCheckoutId = 'checkout' .  $cartsSystem->lastCheckoutId;

        $checkoutData = new Zend_Session_Namespace('checkoutData');
        if(empty($checkoutData->$lastCheckoutId)) {
            $checkoutData->$lastCheckoutId = new stdClass();
        }
        foreach ($this->fieldsToSave as $field) {
            $getter = "get" . ucfirst($field);
            $value = $this->model->$getter();

            if (is_array($value) || is_object($value)) {
                $value = serialize($value);
            } else  if(is_bool($value)) {
                $value = (int)$value;
            }
            $checkoutData->$lastCheckoutId->$field = $value;
        }
    }

    /**
     * Deletes object from database
     *
     * @return void
     */
    public function delete() {
        $cacheKey = "SessionCheckOutData_" . $this->model->key . "_" . $this->model->cartId;
        Zend_Registry::set($cacheKey, null);
    }

    public function removeAllFromCart($cartId) {
        $items = new Zend_Session_Namespace('checkoutData');
        foreach ($items as $index=> $item) {
            if ($item->cartId == $cartId) {
                unset($items->$index);
            }
        };
    }

}
