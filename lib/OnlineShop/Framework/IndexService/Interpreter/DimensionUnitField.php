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

//                    if($unit->getBaseunit()) {
//
//                        $baseUnit = DimensionUnitField_Unit::getByAbbreviation($unit->getBaseunit());
////                        p_r($baseUnit);
//
//                        echo $value->getValue() * $unit->getFactor();
//
//                        die("sfdsdsdf");
//
//                    }
//
//
//
//                    return $value->getValue();

            } else {
                return $value->__toString();
            }
        }

        return null;
    }
}
