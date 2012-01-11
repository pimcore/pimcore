<?php

class OnlineShop_Framework_IndexService_Interpreter_AssetId implements OnlineShop_Framework_IndexService_Interpreter {

    public static function interpret($value, $config = null) {
        if(!empty($value) && $value instanceof Asset) {
            return $value->getId();
        }
        return null;
    }
}
