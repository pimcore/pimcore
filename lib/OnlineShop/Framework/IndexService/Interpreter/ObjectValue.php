<?php

class OnlineShop_Framework_IndexService_Interpreter_ObjectValue implements OnlineShop_Framework_IndexService_Interpreter
{

    public static function interpret($value, $config = null)
    {
        $targetList = $config->target;

        if (empty($targetList->fieldname)) {
            throw new Exception("target fieldname missing.");
        }

        if ($value instanceof \Pimcore\Model\Object\AbstractObject) {

            $fieldGetter = "get" . ucfirst($targetList->fieldname);

            if (method_exists($value, $fieldGetter)) {
                return $value->$fieldGetter($targetList->locale);
            }
        }
        return null;
    }
}