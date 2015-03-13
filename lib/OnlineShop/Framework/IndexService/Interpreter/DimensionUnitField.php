<?php

class OnlineShop_Framework_IndexService_Interpreter_DimensionUnitField implements OnlineShop_Framework_IndexService_Interpreter {

    public static function interpret($value, $config = null) {

        if(!empty($value) && $value instanceof Object_Data_DimensionUnitField) {

            if($config->onlyDimensionValue == "true") {
                $unit = $value->getUnit();
                $value = $value->getValue();

                if($unit->getFactor()) {
                    $value *= $unit->getFactor();
                }

                return $value;

            } else {
                return $value->__toString();
            }
        }

        return null;
    }
}
