<?php

class OnlineShop_Framework_IndexService_Interpreter_Numeric implements OnlineShop_Framework_IndexService_Interpreter {

    public static function interpret($value, $config = null) {
        return floatval($value);
    }
}
