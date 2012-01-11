<?php

class OnlineShop_Framework_AbstractCategory extends Object_Concrete {

    public static function getById($id) {
        $object = Object_Abstract::getById($id);

        if($object instanceof OnlineShop_Framework_AbstractCategory) {
            return $object;
        }
        return null;
    }

    public function getOSProductsInParentCategoryVisible() {
        return true;
    }

    public function getFilterFields() {
        throw new OnlineShop_Framework_Exception_UnsupportedException("getFilterfields is not implemented for " . get_class($this));
    }

}
