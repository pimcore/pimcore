<?php

class OnlineShop_Framework_Impl_SessionCart_Resource extends Pimcore_Model_Resource_Abstract {

    protected $fieldsToSave = array("id", "name", "userid", "creationDateTimestamp");
    /**
     * @param int $id
     * @return void
     */
    public function getById($id) {
        $carts = new Zend_Session_Namespace('carts');
        $found = false;
        foreach ($carts as $cart) {
            if ($cart->id == $id) {
                $this->assignVariablesToModel($cart);
                $found = true;
            }
        }
        if(!$found) {
            throw new Exception("Cart " . $id . " not found.");
        }

    }

    /**
     * @param stdClass $data
     * @return void
     */
    protected function assignVariablesToModel($cart) {
        $this->model->setValues($cart);
    }

    public function create() {
        if(!$this->model->getId()) {
            $cartsSystem = new Zend_Session_Namespace('cartsSystem');
            if (!isset($cartsSystem->lastId)) {
                $cartsSystem->lastId = 1;
            } else {
                $cartsSystem->lastId++;
            }
            $this->model->setId($cartsSystem->lastId);
        }
        $this->save();
    }

    public function save() {
        if ($this->model->getId()) {
            return $this->update();
        }
        return $this->create();
    }

    /**
     * @return void
     */
    public function update() {
        $this->delete();
        $carts = new Zend_Session_Namespace('carts');
        $cartName = $this->model->getName();
        if($carts->$cartName) {
            $carts->$cartName = new stdClass();
        }
        foreach ($this->fieldsToSave as $field) {
            $getter = "get" . ucfirst($field);
            $value = $this->model->$getter();

            if (is_array($value) || is_object($value)) {
                $value = serialize($value);
            } else  if(is_bool($value)) {
                $value = (int)$value;
            }

            $carts->$cartName->$field = $value;
        }
    }

    /**
     * Deletes object from the session
     *
     * @return void
     */
    public function delete() {
        $carts = new Zend_Session_Namespace('carts');
        foreach ($carts as $index => $cart) {
            if ($cart->id == $this->model->getId()) {
                unset($carts->$index);
            }
        }
    }

}
