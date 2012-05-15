<?php

class OnlineShop_Framework_IndexService_Interpreter_Soundex implements OnlineShop_Framework_IndexService_Interpreter {

    public static function interpret($value, $config = null) {

        if(is_array($value)) {
            sort($value);
            $string = implode(" ", $value);
        } else {
            $string = (string)$value;
        }
        $soundex = soundex($string);
        return intval(ord(substr($soundex, 0, 1)) . substr($soundex, 1));
    }
}
