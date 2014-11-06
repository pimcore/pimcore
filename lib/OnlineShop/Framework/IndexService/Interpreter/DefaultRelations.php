<?php

class OnlineShop_Framework_IndexService_Interpreter_DefaultRelations implements OnlineShop_Framework_IndexService_RelationInterpreter {

    public static function interpret($value, $config = null) {
        $result = array();

        if(is_array($value)) {
            foreach($value as $v) {
                $result[] = array("dest" => $v->getId(), "type" => Element_Service::getElementType($v));
            }
        } else if($value instanceof Element_Abstract) {
            $result[] = array("dest" => $value->getId(), "type" => Element_Service::getElementType($value));
        }
        return $result;
    }
}
