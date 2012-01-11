<?php

class OnlineShop_Framework_IndexService_Getter_DefaultBrickGetter implements OnlineShop_Framework_IndexService_Getter {

    public static function get($object, $config = null) {
        $brickContainerGetter = "get" . ucfirst($config->brickfield);
        $brickContainer = $object->$brickContainerGetter();

        $brickGetter = "get" . ucfirst($config->bricktype);
        $brick = $brickContainer->$brickGetter();
        if($brick) {
            $fieldGetter = "get" . ucfirst($config->fieldname);
            return $brick->$fieldGetter();
        }
    }

}
