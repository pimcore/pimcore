<?php
/**
 * Created by elements.at New Media Solutions GmbH
 * Wenzel Wondra
 * User: wwondra
 * Date: 23.07.13
 * Time: 14:08
 */

class OnlineShop_Framework_IndexService_Interpreter_ObjectValue implements OnlineShop_Framework_IndexService_Interpreter
{

    public static function interpret($value, $config = null)
    {
        $targetList = $config->target;

        if (empty($targetList->fieldname)) {
            throw new Exception("target fieldname missing.");
        }

        if ($value instanceof Object_Abstract) {

            $fieldGetter = "get" . ucfirst($targetList->fieldname);

            if (method_exists($value, $fieldGetter)) {
                return $value->$fieldGetter($targetList->locale);
            }
        }
        return null;
    }
}