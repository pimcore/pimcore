<?php

class OnlineShop_Framework_IndexService_Interpreter_DefaultObjects implements OnlineShop_Framework_IndexService_RelationInterpreter {

    public static function interpret($value, $config = null) {
        $result = array();

        if(is_array($value)) {
            foreach($value as $v) {
                $result[] = array("dest" => $v->getId(), "type" => "object");
            }
        } else if($value instanceof \Pimcore\Model\Object\AbstractObject) {
            $result[] = array("dest" => $value->getId(), "type" => "object");
        }
        return $result;
    }
}
