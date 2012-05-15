<?php

class OnlineShop_Framework_IndexService_Interpreter_ObjectIdSum implements OnlineShop_Framework_IndexService_Interpreter {

    public static function interpret($value, $config = null) {

        $sum = 0;
        if(is_array($value)) {
            foreach($value as $object) {
                if($object instanceof Element_Interface) {
                    $sum += $object->getId();
                }
            }
        }
        return $sum;
    }
}
