<?php

class OnlineShop_Framework_IndexService_Interpreter_Round implements OnlineShop_Framework_IndexService_Interpreter {

    public static function interpret($value, $config = null) {
        if(is_numeric($value)) {
            return round($value, 0);
        }
        return $value;
    }
}
