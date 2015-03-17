<?php

class OnlineShop_Framework_IndexService_Interpreter_DefaultRelations implements OnlineShop_Framework_IndexService_RelationInterpreter {

    public static function interpret($value, $config = null) {
        $result = array();

        if(is_array($value)) {
            foreach($value as $v) {
                $result[] = array("dest" => $v->getId(), "type" => \Pimcore\Model\Element\Service::getElementType($v));
            }
        } else if($value instanceof \Pimcore\Model\Element\AbstractElement) {
            $result[] = array("dest" => $value->getId(), "type" => \Pimcore\Model\Element\Service::getElementType($value));
        }
        return $result;
    }
}
