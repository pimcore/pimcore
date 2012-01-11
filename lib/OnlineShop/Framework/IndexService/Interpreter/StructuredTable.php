<?php

class OnlineShop_Framework_IndexService_Interpreter_StructuredTable implements OnlineShop_Framework_IndexService_Interpreter {

    public static function interpret($value, $config = null) {

        if(empty($config->tablerow)) {
            throw new Exception("Table row config missing.");
        }
        if(empty($config->tablecolumn)) {
            throw new Exception("Table column config missing.");
        }

        $getter = "get" . ucfirst($config->tablerow) . "__" . ucfirst($config->tablecolumn);

        if($value && $value instanceof Object_Data_StructuredTable) {
            if(!empty($config->defaultUnit)) {
                return $value->$getter() . " " . $config->defaultUnit;
            } else {
                return $value->$getter();
            }
        }

        return null;
    }
}
