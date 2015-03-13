<?php

class OnlineShop_Framework_IndexService_Interpreter_ObjectId implements OnlineShop_Framework_IndexService_Interpreter {

    public static function interpret($value, $config = null) {
        if(!empty($value) && $value instanceof \Pimcore\Model\Object\AbstractObject) {
            return $value->getId();
        }
        return null;
    }
}
